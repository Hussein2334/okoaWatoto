<?php
// admin/logs.php
$page_title = 'System Logs';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle export logs - MUST BE BEFORE ANY HTML OUTPUT
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Get filter parameters for export
    $filters = [];
    $export_action_type = $_GET['action_type'] ?? '';
    $export_user_filter = $_GET['user_id'] ?? '';
    $export_date_from = $_GET['date_from'] ?? '';
    $export_date_to = $_GET['date_to'] ?? '';
    
    $sql = "SELECT * FROM system_logs WHERE 1=1";
    $params = [];
    
    if (!empty($export_action_type)) {
        $sql .= " AND action_type = ?";
        $params[] = $export_action_type;
    }
    if (!empty($export_user_filter)) {
        $sql .= " AND user_id = ?";
        $params[] = $export_user_filter;
    }
    if (!empty($export_date_from)) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $export_date_from;
    }
    if (!empty($export_date_to)) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $export_date_to;
    }
    
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Clear any output buffers
    if (ob_get_level()) ob_end_clean();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="system_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, ['ID', 'User', 'Role', 'Action', 'Action Type', 'Description', 'IP Address', 'Page URL', 'Created At']);
    
    // Add data
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['user_name'] ?? 'Guest',
            $log['user_role'] ?? 'guest',
            $log['action'],
            $log['action_type'],
            $log['description'],
            $log['ip_address'],
            $log['page_url'],
            date('Y-m-d H:i:s', strtotime($log['created_at']))
        ]);
    }
    
    fclose($output);
    exit();
}

require_once 'includes/admin-header.php';

// Get filter parameters
$action_type_filter = $_GET['action_type'] ?? '';
$user_filter = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$count_sql = "SELECT COUNT(*) as total FROM system_logs WHERE 1=1";
$data_sql = "SELECT * FROM system_logs WHERE 1=1";
$params = [];

if (!empty($action_type_filter)) {
    $count_sql .= " AND action_type = ?";
    $data_sql .= " AND action_type = ?";
    $params[] = $action_type_filter;
}

if (!empty($user_filter)) {
    $count_sql .= " AND user_id = ?";
    $data_sql .= " AND user_id = ?";
    $params[] = $user_filter;
}

if (!empty($date_from)) {
    $count_sql .= " AND DATE(created_at) >= ?";
    $data_sql .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $count_sql .= " AND DATE(created_at) <= ?";
    $data_sql .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

if (!empty($search_query)) {
    $count_sql .= " AND (action LIKE ? OR description LIKE ? OR user_name LIKE ? OR ip_address LIKE ?)";
    $data_sql .= " AND (action LIKE ? OR description LIKE ? OR user_name LIKE ? OR ip_address LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Get total count
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

// Get data with pagination
$data_sql .= " ORDER BY created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs");
$total_logs = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs WHERE DATE(created_at) = CURDATE()");
$today_logs = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM system_logs WHERE action_type = 'error'");
$error_logs = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT action_type, COUNT(*) as count FROM system_logs GROUP BY action_type");
$action_stats = $stmt->fetchAll();

// Get unique users for filter
$stmt = $pdo->query("SELECT DISTINCT user_id, user_name FROM system_logs WHERE user_id IS NOT NULL ORDER BY user_name");
$users = $stmt->fetchAll();

// Get action types for filter
$stmt = $pdo->query("SELECT DISTINCT action_type FROM system_logs ORDER BY action_type");
$action_types = $stmt->fetchAll();

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">System Logs</h1>
            <p class="text-gray-500 text-sm">View system activity logs</p>
        </div>
        <div>
            <a href="?export=csv&action_type=<?php echo urlencode($action_type_filter); ?>&user_id=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
               class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-green-700 transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-base">download</span>
                Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-primary"><?php echo number_format($total_logs); ?></div>
        <div class="text-xs text-gray-500">Total Logs</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-green-600"><?php echo number_format($today_logs); ?></div>
        <div class="text-xs text-gray-500">Today's Logs</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-red-600"><?php echo number_format($error_logs); ?></div>
        <div class="text-xs text-gray-500">Error Logs</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-purple-600"><?php echo number_format(count($users)); ?></div>
        <div class="text-xs text-gray-500">Active Users</div>
    </div>
</div>

<!-- Action Type Stats -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <h3 class="font-semibold text-sm text-primary mb-2">Logs by Action Type</h3>
    <div class="flex flex-wrap gap-2">
        <?php foreach($action_stats as $stat): ?>
        <div class="px-2 py-1 bg-gray-100 rounded-lg text-center min-w-[60px]">
            <div class="text-sm font-bold <?php 
                echo match($stat['action_type']) {
                    'error' => 'text-red-600',
                    'create' => 'text-green-600',
                    'update' => 'text-blue-600',
                    'delete' => 'text-red-600',
                    'login' => 'text-purple-600',
                    'logout' => 'text-gray-600',
                    default => 'text-gray-600'
                };
            ?>">
                <?php echo number_format($stat['count']); ?>
            </div>
            <div class="text-xs text-gray-500"><?php echo ucfirst($stat['action_type']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <form method="GET" id="filterForm" class="space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Action Type</label>
                <select name="action_type" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                    <option value="">All Actions</option>
                    <?php foreach($action_types as $type): ?>
                    <option value="<?php echo $type['action_type']; ?>" <?php echo $action_type_filter == $type['action_type'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst($type['action_type']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">User</label>
                <select name="user_id" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                    <option value="">All Users</option>
                    <?php foreach($users as $user): ?>
                    <option value="<?php echo $user['user_id']; ?>" <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['user_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Date From</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="w-full px-2 py-1.5 text-sm border rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Date To</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="w-full px-2 py-1.5 text-sm border rounded-lg">
            </div>
        </div>
        <div class="flex gap-2">
            <div class="flex-1">
                <input type="text" name="search" placeholder="Search by action, description, user, or IP..." 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       class="w-full px-2 py-1.5 text-sm border rounded-lg">
            </div>
            <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm hover:bg-primary/90">
                Search
            </button>
            <?php if(!empty($action_type_filter) || !empty($user_filter) || !empty($date_from) || !empty($date_to) || !empty($search_query)): ?>
            <a href="logs.php" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-300">
                Clear
            </a>
            <?php endif; ?>
        </div>
        <input type="hidden" name="page" id="pageInput" value="1">
    </form>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">ID</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Time</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">User</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Role</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Action Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Action</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Description</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($logs)): ?>
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 text-sm">No logs found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($logs as $log): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-2 text-xs"><?php echo $log['id']; ?></td>
                        <td class="px-3 py-2 text-xs whitespace-nowrap">
                            <?php echo date('d M H:i:s', strtotime($log['created_at'])); ?>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            <div class="flex items-center gap-1">
                                <div class="w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-xs">person</span>
                                </div>
                                <?php echo htmlspecialchars($log['user_name'] ?? 'Guest'); ?>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold <?php 
                                echo match($log['user_role']) {
                                    'admin' => 'bg-red-100 text-red-800',
                                    'staff' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php echo ucfirst($log['user_role'] ?? 'Guest'); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold <?php 
                                echo match($log['action_type']) {
                                    'create' => 'bg-green-100 text-green-800',
                                    'update' => 'bg-blue-100 text-blue-800',
                                    'delete' => 'bg-red-100 text-red-800',
                                    'login' => 'bg-purple-100 text-purple-800',
                                    'logout' => 'bg-gray-100 text-gray-800',
                                    'error' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php echo ucfirst($log['action_type'] ?? 'view'); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs font-medium"><?php echo htmlspecialchars(substr($log['action'], 0, 30)); ?></td>
                        <td class="px-3 py-2 text-xs max-w-xs truncate">
                            <?php echo htmlspecialchars(substr($log['description'] ?? '', 0, 50)); ?>
                        </td>
                        <td class="px-3 py-2 text-xs font-mono"><?php echo $log['ip_address'] ?? '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination with AJAX stay in place -->
    <?php if($total_pages > 1): ?>
    <div class="px-3 py-2 border-t border-gray-200 flex justify-between items-center text-xs">
        <div class="text-gray-500">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_records); ?> of <?php echo number_format($total_records); ?> logs
        </div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&action_type=<?php echo urlencode($action_type_filter); ?>&user_id=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_query); ?>" 
               class="pagination-link px-2 py-1 border rounded hover:bg-gray-50">
                Prev
            </a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= min(5, $total_pages); $i++): ?>
            <a href="?page=<?php echo $i; ?>&action_type=<?php echo urlencode($action_type_filter); ?>&user_id=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_query); ?>" 
               class="pagination-link px-2 py-1 border rounded <?php echo $page == $i ? 'bg-primary text-white border-primary' : 'hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&action_type=<?php echo urlencode($action_type_filter); ?>&user_id=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_query); ?>" 
               class="pagination-link px-2 py-1 border rounded hover:bg-gray-50">
                Next
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Smooth scroll handling for pagination - stay in place
    document.querySelectorAll('.pagination-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            
            // Store current scroll position
            const scrollPosition = window.scrollY;
            
            // Navigate to the URL
            window.location.href = url;
            
            // Store scroll position in session storage
            sessionStorage.setItem('scrollPosition', scrollPosition);
        });
    });
    
    // Restore scroll position after page load
    if (sessionStorage.getItem('scrollPosition')) {
        window.scrollTo(0, parseInt(sessionStorage.getItem('scrollPosition')));
        sessionStorage.removeItem('scrollPosition');
    }
    
    // Handle filter form submission - stay in place
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const scrollPosition = window.scrollY;
            sessionStorage.setItem('scrollPosition', scrollPosition);
        });
    }
    
    <?php if(isset($_SESSION['success_message'])): ?>
    Swal.fire({
        icon: 'success', 
        title: 'Success', 
        text: '<?php echo addslashes($_SESSION['success_message']); ?>', 
        confirmButtonColor: '#10b981', 
        timer: 2000, 
        showConfirmButton: false
    });
    <?php unset($_SESSION['success_message']); endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
    Swal.fire({ 
        icon: 'error', 
        title: 'Error', 
        text: '<?php echo addslashes($_SESSION['error_message']); ?>', 
        confirmButtonColor: '#dc2626' 
    });
    <?php unset($_SESSION['error_message']); endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>