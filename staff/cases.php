<?php
// staff/cases.php
$page_title = 'Manage Cases';

// Start output buffering to prevent header errors
ob_start();

require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

// Handle status update for missing cases
if (isset($_POST['update_missing_status'])) {
    $case_id = intval($_POST['case_id']);
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("SELECT * FROM children_reports WHERE id = ?");
    $stmt->execute([$case_id]);
    $old_data = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE children_reports SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $case_id])) {
        if (function_exists('logActivity')) {
            logActivity(
                "Missing Case Status Updated",
                "update",
                "Staff {$_SESSION['user_name']} changed case {$old_data['case_number']} status from {$old_data['status']} to {$new_status}",
                ['old_status' => $old_data['status'], 'case_number' => $old_data['case_number']],
                ['new_status' => $new_status, 'updated_by' => $_SESSION['user_name']]
            );
        }
        $_SESSION['success_message'] = "Missing case status updated successfully";
    }
    header("Location: cases.php?tab=missing");
    exit();
}

// Handle status update for found cases
if (isset($_POST['update_found_status'])) {
    $case_id = intval($_POST['case_id']);
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("SELECT * FROM found_reports WHERE id = ?");
    $stmt->execute([$case_id]);
    $old_data = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE found_reports SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $case_id])) {
        if (function_exists('logActivity')) {
            logActivity(
                "Found Case Status Updated",
                "update",
                "Staff {$_SESSION['user_name']} changed found case {$old_data['case_number']} status from {$old_data['status']} to {$new_status}",
                ['old_status' => $old_data['status'], 'case_number' => $old_data['case_number']],
                ['new_status' => $new_status, 'updated_by' => $_SESSION['user_name']]
            );
        }
        $_SESSION['success_message'] = "Found case status updated successfully";
    }
    header("Location: cases.php?tab=found");
    exit();
}

// Handle delete missing case
if (isset($_GET['delete_missing'])) {
    $case_id = intval($_GET['delete_missing']);
    
    $stmt = $pdo->prepare("SELECT * FROM children_reports WHERE id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM children_reports WHERE id = ?");
    if ($stmt->execute([$case_id])) {
        if (function_exists('logActivity')) {
            logActivity(
                "Missing Case Deleted",
                "delete",
                "Staff {$_SESSION['user_name']} deleted missing case {$case['case_number']}",
                $case,
                null
            );
        }
        $_SESSION['success_message'] = "Missing case deleted successfully";
    }
    header("Location: cases.php?tab=missing");
    exit();
}

// Handle delete found case
if (isset($_GET['delete_found'])) {
    $case_id = intval($_GET['delete_found']);
    
    $stmt = $pdo->prepare("SELECT * FROM found_reports WHERE id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM found_reports WHERE id = ?");
    if ($stmt->execute([$case_id])) {
        if (function_exists('logActivity')) {
            logActivity(
                "Found Case Deleted",
                "delete",
                "Staff {$_SESSION['user_name']} deleted found case {$case['case_number']}",
                $case,
                null
            );
        }
        $_SESSION['success_message'] = "Found case deleted successfully";
    }
    header("Location: cases.php?tab=found");
    exit();
}

// Get active tab
$active_tab = $_GET['tab'] ?? 'missing';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// =============================================
// MISSING CASES DATA
// =============================================
$search_missing = $_GET['search_missing'] ?? '';
$status_missing_filter = $_GET['status_missing'] ?? 'all';

$missing_count_sql = "SELECT COUNT(*) as total FROM children_reports WHERE 1=1";
$missing_sql = "SELECT cr.*, 
                    r.region_name,
                    d.district_name,
                    ps.station_name as police_station
                FROM children_reports cr
                LEFT JOIN regions r ON cr.region_id = r.id
                LEFT JOIN districts d ON cr.district_id = d.id
                LEFT JOIN police_stations ps ON cr.police_station_id = ps.id
                WHERE 1=1";
$missing_params = [];

if ($status_missing_filter !== 'all') {
    $missing_count_sql .= " AND status = ?";
    $missing_sql .= " AND cr.status = ?";
    $missing_params[] = $status_missing_filter;
}

if (!empty($search_missing)) {
    $missing_count_sql .= " AND (child_name LIKE ? OR case_number LIKE ? OR last_seen_location LIKE ?)";
    $missing_sql .= " AND (cr.child_name LIKE ? OR cr.case_number LIKE ? OR cr.last_seen_location LIKE ?)";
    $search_term = "%$search_missing%";
    $missing_params[] = $search_term;
    $missing_params[] = $search_term;
    $missing_params[] = $search_term;
}

$missing_stmt = $pdo->prepare($missing_count_sql);
$missing_stmt->execute($missing_params);
$total_missing_records = $missing_stmt->fetch()['total'];
$total_missing_pages = ceil($total_missing_records / $per_page);

$missing_sql .= " ORDER BY cr.created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$missing_stmt = $pdo->prepare($missing_sql);
$missing_stmt->execute($missing_params);
$missing_cases = $missing_stmt->fetchAll();

// Missing cases statistics
$total_missing_all = $pdo->query("SELECT COUNT(*) as total FROM children_reports")->fetch()['total'];
$total_missing_count = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Missing'")->fetch()['total'];
$total_found_count = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Found'")->fetch()['total'];
$total_reunited_count = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Reunited'")->fetch()['total'];

// =============================================
// FOUND CASES DATA
// =============================================
$search_found = $_GET['search_found'] ?? '';
$status_found_filter = $_GET['status_found'] ?? 'all';

$found_count_sql = "SELECT COUNT(*) as total FROM found_reports WHERE 1=1";
$found_sql = "SELECT fr.*, 
                    r.region_name,
                    d.district_name
                FROM found_reports fr
                LEFT JOIN regions r ON fr.region_id = r.id
                LEFT JOIN districts d ON fr.district_id = d.id
                WHERE 1=1";
$found_params = [];

if ($status_found_filter !== 'all') {
    $found_count_sql .= " AND status = ?";
    $found_sql .= " AND fr.status = ?";
    $found_params[] = $status_found_filter;
}

if (!empty($search_found)) {
    $found_count_sql .= " AND (found_child_name LIKE ? OR case_number LIKE ? OR found_location LIKE ?)";
    $found_sql .= " AND (fr.found_child_name LIKE ? OR fr.case_number LIKE ? OR fr.found_location LIKE ?)";
    $search_term = "%$search_found%";
    $found_params[] = $search_term;
    $found_params[] = $search_term;
    $found_params[] = $search_term;
}

$found_stmt = $pdo->prepare($found_count_sql);
$found_stmt->execute($found_params);
$total_found_records = $found_stmt->fetch()['total'];
$total_found_pages = ceil($total_found_records / $per_page);

$found_sql .= " ORDER BY fr.created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$found_stmt = $pdo->prepare($found_sql);
$found_stmt->execute($found_params);
$found_cases = $found_stmt->fetchAll();

// Found cases statistics
$total_found_all = $pdo->query("SELECT COUNT(*) as total FROM found_reports")->fetch()['total'];
$total_awaiting_count = $pdo->query("SELECT COUNT(*) as total FROM found_reports WHERE status = 'Awaiting ID'")->fetch()['total'];
$total_reunited_found_count = $pdo->query("SELECT COUNT(*) as total FROM found_reports WHERE status = 'Reunited'")->fetch()['total'];
$total_in_care_count = $pdo->query("SELECT COUNT(*) as total FROM found_reports WHERE status = 'In Care'")->fetch()['total'];

// Clear output buffer and include header
ob_end_clean();
require_once __DIR__ . '/includes/staff-header.php';
?>

<!-- Dashboard Header -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Manage Cases</h1>
            <p class="text-gray-500 text-sm">Manage missing and found children cases</p>
        </div>
        <div class="flex gap-2">
            <a href="../report-missing.php" class="bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-red-700 transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-base">warning</span>
                Report Missing
            </a>
            <a href="../report-found.php" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-green-700 transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-base">volunteer_activism</span>
                Report Found
            </a>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="mb-4">
    <div class="border-b border-gray-200">
        <nav class="flex gap-1">
            <a href="?tab=missing" 
               class="px-4 py-2 text-sm font-medium rounded-t-lg <?php echo $active_tab == 'missing' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100'; ?> transition-colors">
                <span class="material-symbols-outlined text-sm align-middle">search</span>
                Missing Cases
                <span class="ml-1 text-xs">(<?php echo $total_missing_all; ?>)</span>
            </a>
            <a href="?tab=found" 
               class="px-4 py-2 text-sm font-medium rounded-t-lg <?php echo $active_tab == 'found' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100'; ?> transition-colors">
                <span class="material-symbols-outlined text-sm align-middle">volunteer_activism</span>
                Found Cases
                <span class="ml-1 text-xs">(<?php echo $total_found_all; ?>)</span>
            </a>
        </nav>
    </div>
</div>

<?php if($active_tab == 'missing'): ?>
<!-- ============================================= -->
<!-- MISSING CASES SECTION -->
<!-- ============================================= -->

<!-- Missing Cases Statistics -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-primary"><?php echo number_format($total_missing_all); ?></div>
        <div class="text-xs text-gray-500">Total Missing Cases</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-red-600"><?php echo number_format($total_missing_count); ?></div>
        <div class="text-xs text-gray-500">Missing</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-yellow-600"><?php echo number_format($total_found_count); ?></div>
        <div class="text-xs text-gray-500">Found</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-green-600"><?php echo number_format($total_reunited_count); ?></div>
        <div class="text-xs text-gray-500">Reunited</div>
    </div>
</div>

<!-- Missing Cases Filters -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <div class="flex flex-wrap gap-2 mb-3">
        <a href="?tab=missing&status_missing=all" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_missing_filter == 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All</a>
        <a href="?tab=missing&status_missing=Missing" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_missing_filter == 'Missing' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Missing</a>
        <a href="?tab=missing&status_missing=Found" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_missing_filter == 'Found' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Found</a>
        <a href="?tab=missing&status_missing=Reunited" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_missing_filter == 'Reunited' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Reunited</a>
    </div>
    
    <form method="GET" class="flex gap-2">
        <input type="hidden" name="tab" value="missing">
        <input type="text" name="search_missing" placeholder="Search missing cases by name, case number, or location..." 
               value="<?php echo htmlspecialchars($search_missing); ?>"
               class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
        <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm">Search</button>
        <?php if(!empty($search_missing) || $status_missing_filter != 'all'): ?>
        <a href="?tab=missing" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Missing Cases Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Age/Gender</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Location</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Reported</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($missing_cases)): ?>
                <tr>
                    <td colspan="7" class="px-3 py-6 text-center text-gray-500 text-sm">No missing cases found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($missing_cases as $case): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-2 text-xs font-mono font-medium"><?php echo htmlspecialchars($case['case_number']); ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if(!empty($case['photo']) && file_exists('../assets/uploads/' . $case['photo'])): ?>
                                        <img src="../assets/uploads/<?php echo $case['photo']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-gray-400 text-sm">child_care</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium text-sm"><?php echo htmlspecialchars($case['child_name'] ?? 'Unknown'); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-xs"><?php echo $case['age']; ?>y / <?php echo $case['gender']; ?></td>
                        <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($case['last_seen_location'] ?? 'Unknown'); ?></td>
                        <td class="px-3 py-2">
                            <?php 
                            $status_color = match($case['status']) {
                                'Missing' => 'bg-red-100 text-red-800',
                                'Found' => 'bg-yellow-100 text-yellow-800',
                                'Reunited' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                <?php echo $case['status']; ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs"><?php echo date('d M Y', strtotime($case['created_at'])); ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-xs border rounded px-1 py-0.5">
                                        <option value="Missing" <?php echo $case['status'] == 'Missing' ? 'selected' : ''; ?>>Missing</option>
                                        <option value="Found" <?php echo $case['status'] == 'Found' ? 'selected' : ''; ?>>Found</option>
                                        <option value="Reunited" <?php echo $case['status'] == 'Reunited' ? 'selected' : ''; ?>>Reunited</option>
                                    </select>
                                    <input type="hidden" name="update_missing_status" value="1">
                                </form>
                                <a href="edit-case.php?id=<?php echo $case['id']; ?>&type=missing" class="text-blue-600 hover:text-blue-800">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                <a href="?delete_missing=<?php echo $case['id']; ?>&tab=missing" class="text-red-600 hover:text-red-800 delete-case" data-case-number="<?php echo $case['case_number']; ?>">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination for Missing Cases -->
    <?php if($total_missing_pages > 1): ?>
    <div class="px-3 py-2 border-t border-gray-200 flex justify-between items-center text-xs">
        <div class="text-gray-500">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_missing_records); ?> of <?php echo number_format($total_missing_records); ?> missing cases
        </div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?tab=missing&page=<?php echo $page-1; ?>&status_missing=<?php echo $status_missing_filter; ?>&search_missing=<?php echo urlencode($search_missing); ?>" class="px-2 py-1 border rounded">Prev</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= min(5, $total_missing_pages); $i++): ?>
            <a href="?tab=missing&page=<?php echo $i; ?>&status_missing=<?php echo $status_missing_filter; ?>&search_missing=<?php echo urlencode($search_missing); ?>" class="px-2 py-1 border rounded <?php echo $page == $i ? 'bg-primary text-white' : 'hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $total_missing_pages): ?>
            <a href="?tab=missing&page=<?php echo $page+1; ?>&status_missing=<?php echo $status_missing_filter; ?>&search_missing=<?php echo urlencode($search_missing); ?>" class="px-2 py-1 border rounded">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>

<!-- ============================================= -->
<!-- FOUND CASES SECTION -->
<!-- ============================================= -->

<!-- Found Cases Statistics -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-primary"><?php echo number_format($total_found_all); ?></div>
        <div class="text-xs text-gray-500">Total Found Cases</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-yellow-600"><?php echo number_format($total_awaiting_count); ?></div>
        <div class="text-xs text-gray-500">Awaiting ID</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-green-600"><?php echo number_format($total_reunited_found_count); ?></div>
        <div class="text-xs text-gray-500">Reunited</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-blue-600"><?php echo number_format($total_in_care_count); ?></div>
        <div class="text-xs text-gray-500">In Care</div>
    </div>
</div>

<!-- Found Cases Filters -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <div class="flex flex-wrap gap-2 mb-3">
        <a href="?tab=found&status_found=all" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_found_filter == 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All</a>
        <a href="?tab=found&status_found=Awaiting ID" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_found_filter == 'Awaiting ID' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Awaiting ID</a>
        <a href="?tab=found&status_found=Reunited" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_found_filter == 'Reunited' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Reunited</a>
        <a href="?tab=found&status_found=In Care" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_found_filter == 'In Care' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">In Care</a>
    </div>
    
    <form method="GET" class="flex gap-2">
        <input type="hidden" name="tab" value="found">
        <input type="text" name="search_found" placeholder="Search found cases by name, case number, or location..." 
               value="<?php echo htmlspecialchars($search_found); ?>"
               class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
        <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm">Search</button>
        <?php if(!empty($search_found) || $status_found_filter != 'all'): ?>
        <a href="?tab=found" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Found Cases Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Age/Gender</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Found Location</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Current Location</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Found By</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($found_cases)): ?>
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 text-sm">No found cases found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($found_cases as $case): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-2 text-xs font-mono font-medium"><?php echo htmlspecialchars($case['case_number']); ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if(!empty($case['photo']) && file_exists('../assets/uploads/' . $case['photo'])): ?>
                                        <img src="../assets/uploads/<?php echo $case['photo']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-gray-400 text-sm">child_care</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium text-sm"><?php echo htmlspecialchars($case['found_child_name'] ?? 'Unknown Child'); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-xs"><?php echo ($case['approximate_age'] ?? '?'); ?>y / <?php echo $case['gender'] ?? '?'; ?></td>
                        <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($case['found_location'] ?? 'Unknown'); ?></td>
                        <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($case['current_location'] ?? 'Not specified'); ?></td>
                        <td class="px-3 py-2">
                            <?php 
                            $status_color = match($case['status']) {
                                'Awaiting ID' => 'bg-yellow-100 text-yellow-800',
                                'Reunited' => 'bg-green-100 text-green-800',
                                'In Care' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                <?php echo $case['status']; ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($case['finder_name'] ?? 'Unknown'); ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-xs border rounded px-1 py-0.5">
                                        <option value="Awaiting ID" <?php echo $case['status'] == 'Awaiting ID' ? 'selected' : ''; ?>>Awaiting ID</option>
                                        <option value="Reunited" <?php echo $case['status'] == 'Reunited' ? 'selected' : ''; ?>>Reunited</option>
                                        <option value="In Care" <?php echo $case['status'] == 'In Care' ? 'selected' : ''; ?>>In Care</option>
                                    </select>
                                    <input type="hidden" name="update_found_status" value="1">
                                </form>
                                <a href="edit-case.php?id=<?php echo $case['id']; ?>&type=found" class="text-blue-600 hover:text-blue-800">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                <a href="?delete_found=<?php echo $case['id']; ?>&tab=found" class="text-red-600 hover:text-red-800 delete-case" data-case-number="<?php echo $case['case_number']; ?>">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination for Found Cases -->
    <?php if($total_found_pages > 1): ?>
    <div class="px-3 py-2 border-t border-gray-200 flex justify-between items-center text-xs">
        <div class="text-gray-500">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_found_records); ?> of <?php echo number_format($total_found_records); ?> found cases
        </div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?tab=found&page=<?php echo $page-1; ?>&status_found=<?php echo $status_found_filter; ?>&search_found=<?php echo urlencode($search_found); ?>" class="px-2 py-1 border rounded">Prev</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= min(5, $total_found_pages); $i++): ?>
            <a href="?tab=found&page=<?php echo $i; ?>&status_found=<?php echo $status_found_filter; ?>&search_found=<?php echo urlencode($search_found); ?>" class="px-2 py-1 border rounded <?php echo $page == $i ? 'bg-primary text-white' : 'hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $total_found_pages): ?>
            <a href="?tab=found&page=<?php echo $page+1; ?>&status_found=<?php echo $status_found_filter; ?>&search_found=<?php echo urlencode($search_found); ?>" class="px-2 py-1 border rounded">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // SweetAlert for delete confirmation
    document.querySelectorAll('.delete-case').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const caseNumber = this.dataset.caseNumber;
            
            Swal.fire({
                title: 'Thibitisha Kufuta',
                html: `Je, una uhakika unataka kufuta kesi <strong>${caseNumber}</strong>?<br><br>Hatua hii haiwezi kubatilishwa!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ndiyo, Futa',
                cancelButtonText: 'Hapana'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
    
    // Success message
    <?php if(isset($_SESSION['success_message'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo addslashes($_SESSION['success_message']); ?>',
        confirmButtonColor: '#10b981',
        timer: 2000,
        showConfirmButton: false
    });
    <?php unset($_SESSION['success_message']); endif; ?>
</script>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>