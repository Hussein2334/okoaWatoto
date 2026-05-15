<?php
// includes/logger.php

/**
 * Log system activity
 * @param string $action - Name of the action performed
 * @param string $action_type - Type: login, logout, create, update, delete, view, export, error
 * @param string $description - Detailed description
 * @param array|null $old_data - Previous data (for updates/deletes)
 * @param array|null $new_data - New data (for creates/updates)
 */
function logActivity($action, $action_type = 'view', $description = '', $old_data = null, $new_data = null) {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['user_name'] ?? 'Guest';
        $user_role = $_SESSION['user_role'] ?? 'guest';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $page_url = $_SERVER['REQUEST_URI'] ?? '';
        
        $old_data_json = $old_data ? json_encode($old_data) : null;
        $new_data_json = $new_data ? json_encode($new_data) : null;
        
        $stmt = $pdo->prepare("INSERT INTO system_logs 
            (user_id, user_name, user_role, action, action_type, description, ip_address, user_agent, page_url, old_data, new_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$user_id, $user_name, $user_role, $action, $action_type, $description, $ip_address, $user_agent, $page_url, $old_data_json, $new_data_json]);
        
        // Update daily summary
        updateActivitySummary();
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        // Silent fail - don't break the main application
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Update daily activity summary
 */
function updateActivitySummary() {
    global $pdo;
    
    $today = date('Y-m-d');
    
    // Get today's stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT user_id) as unique_users, action_type 
                           FROM system_logs WHERE DATE(created_at) = ? GROUP BY action_type");
    $stmt->execute([$today]);
    $stats = $stmt->fetchAll();
    
    $total_actions = 0;
    $actions_by_type = [];
    
    foreach ($stats as $stat) {
        $total_actions += $stat['total'];
        $actions_by_type[$stat['action_type']] = $stat['total'];
    }
    
    $unique_users = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as unique_users FROM system_logs WHERE DATE(created_at) = ?");
    $unique_users->execute([$today]);
    $unique_count = $unique_users->fetch()['unique_users'];
    
    // Insert or update summary
    $stmt = $pdo->prepare("INSERT INTO activity_summary (date, total_actions, unique_users, actions_by_type) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           total_actions = ?, unique_users = ?, actions_by_type = ?");
    $stmt->execute([$today, $total_actions, $unique_count, json_encode($actions_by_type), 
                    $total_actions, $unique_count, json_encode($actions_by_type)]);
}

/**
 * Get system statistics
 */
function getSystemStats() {
    global $pdo;
    
    $stats = [];
    
    // Total logs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs");
    $stats['total_logs'] = $stmt->fetch()['total'];
    
    // Today's activities
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM system_logs WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $stats['today_activities'] = $stmt->fetch()['total'];
    
    // This week
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM system_logs WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
    $stmt->execute();
    $stats['week_activities'] = $stmt->fetch()['total'];
    
    // This month
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM system_logs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['month_activities'] = $stmt->fetch()['total'];
    
    // Actions by type
    $stmt = $pdo->query("SELECT action_type, COUNT(*) as count FROM system_logs GROUP BY action_type");
    $stats['actions_by_type'] = $stmt->fetchAll();
    
    // Top users
    $stmt = $pdo->query("SELECT user_name, COUNT(*) as actions FROM system_logs WHERE user_name != 'Guest' GROUP BY user_name ORDER BY actions DESC LIMIT 5");
    $stats['top_users'] = $stmt->fetchAll();
    
    return $stats;
}

/**
 * Get recent logs with pagination
 */
function getRecentLogs($limit = 50, $offset = 0, $filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM system_logs WHERE 1=1";
    $params = [];
    
    if (!empty($filters['action_type'])) {
        $sql .= " AND action_type = ?";
        $params[] = $filters['action_type'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (action LIKE ? OR description LIKE ? OR user_name LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Get total count for pagination
 */
function getLogsCount($filters = []) {
    global $pdo;
    
    $sql = "SELECT COUNT(*) as total FROM system_logs WHERE 1=1";
    $params = [];
    
    if (!empty($filters['action_type'])) {
        $sql .= " AND action_type = ?";
        $params[] = $filters['action_type'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (action LIKE ? OR description LIKE ? OR user_name LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch()['total'];
}

/**
 * Clean old logs (keep only last 3 months)
 */
function cleanOldLogs($days_to_keep = 90) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    return $stmt->execute([$days_to_keep]);
}
?>