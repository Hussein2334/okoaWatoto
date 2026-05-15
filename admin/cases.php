<?php
// admin/cases.php
$page_title = 'Manage Cases';
require_once '../config/database.php';
require_once 'includes/admin-header.php';

// Check if user is logged in and is admin/staff
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $case_id = intval($_POST['case_id']);
    $new_status = $_POST['status'];
    $case_type = $_POST['case_type'] ?? 'missing';
    
    if ($case_type == 'missing') {
        $stmt = $pdo->prepare("SELECT * FROM children_reports WHERE id = ?");
        $stmt->execute([$case_id]);
        $old_data = $stmt->fetch();
        
        $stmt = $pdo->prepare("UPDATE children_reports SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $case_id])) {
            if (function_exists('logActivity')) {
                logActivity(
                    "Case Status Updated",
                    "update",
                    "User {$_SESSION['user_name']} changed case {$old_data['case_number']} status from {$old_data['status']} to {$new_status}",
                    ['old_status' => $old_data['status'], 'case_number' => $old_data['case_number']],
                    ['new_status' => $new_status, 'updated_by' => $_SESSION['user_name']]
                );
            }
            $_SESSION['success_message'] = "Case status updated successfully";
        }
    } else {
        $stmt = $pdo->prepare("UPDATE found_reports SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $case_id])) {
            $_SESSION['success_message'] = "Found case status updated successfully";
        }
    }
    header("Location: cases.php");
    exit();
}

// Handle delete case
if (isset($_GET['delete']) && isset($_GET['type'])) {
    $case_id = intval($_GET['delete']);
    $case_type = $_GET['type'];
    
    if ($case_type == 'missing') {
        $stmt = $pdo->prepare("SELECT * FROM children_reports WHERE id = ?");
        $stmt->execute([$case_id]);
        $case = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM children_reports WHERE id = ?");
        if ($stmt->execute([$case_id])) {
            if (function_exists('logActivity')) {
                logActivity(
                    "Case Deleted",
                    "delete",
                    "User {$_SESSION['user_name']} deleted case {$case['case_number']}",
                    $case,
                    null
                );
            }
            $_SESSION['success_message'] = "Case deleted successfully";
        }
    } else {
        $stmt = $pdo->prepare("DELETE FROM found_reports WHERE id = ?");
        if ($stmt->execute([$case_id])) {
            $_SESSION['success_message'] = "Found case deleted successfully";
        }
    }
    header("Location: cases.php");
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'missing';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query for missing cases
if ($type_filter == 'missing') {
    $count_sql = "SELECT COUNT(*) as total FROM children_reports WHERE 1=1";
    $data_sql = "SELECT cr.*, 
                        r.region_name,
                        d.district_name,
                        ps.station_name as police_station
                 FROM children_reports cr
                 LEFT JOIN regions r ON cr.region_id = r.id
                 LEFT JOIN districts d ON cr.district_id = d.id
                 LEFT JOIN police_stations ps ON cr.police_station_id = ps.id
                 WHERE 1=1";
    $params = [];
    
    if ($status_filter !== 'all') {
        $count_sql .= " AND status = ?";
        $data_sql .= " AND cr.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $count_sql .= " AND (child_name LIKE ? OR case_number LIKE ? OR last_seen_location LIKE ?)";
        $data_sql .= " AND (cr.child_name LIKE ? OR cr.case_number LIKE ? OR cr.last_seen_location LIKE ?)";
        $search_term = "%$search_query%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    $data_sql .= " ORDER BY cr.created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($data_sql);
    $stmt->execute($params);
    $cases = $stmt->fetchAll();
    
    $stmt_total = $pdo->query("SELECT COUNT(*) as total FROM children_reports");
    $total_all = $stmt_total->fetch()['total'];
    
    $stmt_missing = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Missing'");
    $total_missing = $stmt_missing->fetch()['total'];
    
    $stmt_found = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Found'");
    $total_found = $stmt_found->fetch()['total'];
    
    $stmt_reunited = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Reunited'");
    $total_reunited = $stmt_reunited->fetch()['total'];
    
} else {
    $count_sql = "SELECT COUNT(*) as total FROM found_reports WHERE 1=1";
    $data_sql = "SELECT fr.*, 
                        r.region_name,
                        d.district_name
                 FROM found_reports fr
                 LEFT JOIN regions r ON fr.region_id = r.id
                 LEFT JOIN districts d ON fr.district_id = d.id
                 WHERE 1=1";
    $params = [];
    
    if ($status_filter !== 'all') {
        $count_sql .= " AND status = ?";
        $data_sql .= " AND fr.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $count_sql .= " AND (found_child_name LIKE ? OR case_number LIKE ? OR found_location LIKE ?)";
        $data_sql .= " AND (fr.found_child_name LIKE ? OR fr.case_number LIKE ? OR fr.found_location LIKE ?)";
        $search_term = "%$search_query%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    $data_sql .= " ORDER BY fr.created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($data_sql);
    $stmt->execute($params);
    $cases = $stmt->fetchAll();
    
    $stmt_total = $pdo->query("SELECT COUNT(*) as total FROM found_reports");
    $total_all = $stmt_total->fetch()['total'];
    
    $stmt_awaiting = $pdo->query("SELECT COUNT(*) as total FROM found_reports WHERE status = 'Awaiting ID'");
    $total_awaiting = $stmt_awaiting->fetch()['total'];
    
    $stmt_reunited = $pdo->query("SELECT COUNT(*) as total FROM found_reports WHERE status = 'Reunited'");
    $total_reunited_found = $stmt_reunited->fetch()['total'];
}

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header - Compact -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Manage Cases</h1>
            <p class="text-gray-500 text-sm">Manage missing and found children cases</p>
        </div>
        <a href="../report-missing.php" class="bg-primary text-white px-3 py-1.5 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-1 text-sm">
            <span class="material-symbols-outlined text-base">add</span>
            Add New Case
        </a>
    </div>
</div>

<!-- Statistics Cards - Compact -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-primary"><?php echo number_format($total_all); ?></div>
        <div class="text-xs text-gray-500">Total Cases</div>
    </div>
    <?php if($type_filter == 'missing'): ?>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-red-600"><?php echo number_format($total_missing); ?></div>
        <div class="text-xs text-gray-500">Missing</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-yellow-600"><?php echo number_format($total_found); ?></div>
        <div class="text-xs text-gray-500">Found</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-green-600"><?php echo number_format($total_reunited); ?></div>
        <div class="text-xs text-gray-500">Reunited</div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-yellow-600"><?php echo number_format($total_awaiting); ?></div>
        <div class="text-xs text-gray-500">Awaiting ID</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-green-600"><?php echo number_format($total_reunited_found); ?></div>
        <div class="text-xs text-gray-500">Reunited</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-xl font-bold text-blue-600"><?php echo number_format($total_all - $total_awaiting - $total_reunited_found); ?></div>
        <div class="text-xs text-gray-500">In Care</div>
    </div>
    <?php endif; ?>
</div>

<!-- Filters - Compact -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <div class="flex flex-wrap gap-2">
        <a href="?type=missing&status=all" class="px-3 py-1 rounded-lg text-sm <?php echo $type_filter == 'missing' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition-colors">
            Missing Cases
        </a>
        <a href="?type=found&status=all" class="px-3 py-1 rounded-lg text-sm <?php echo $type_filter == 'found' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition-colors">
            Found Cases
        </a>
    </div>
    
    <div class="flex flex-wrap gap-2 mt-3">
        <a href="?type=<?php echo $type_filter; ?>&status=all" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All</a>
        <?php if($type_filter == 'missing'): ?>
        <a href="?type=missing&status=Missing" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'Missing' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Missing</a>
        <a href="?type=missing&status=Found" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'Found' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Found</a>
        <a href="?type=missing&status=Reunited" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'Reunited' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Reunited</a>
        <?php else: ?>
        <a href="?type=found&status=Awaiting ID" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'Awaiting ID' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Awaiting ID</a>
        <a href="?type=found&status=Reunited" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'Reunited' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Reunited</a>
        <a href="?type=found&status=In Care" class="px-2 py-0.5 rounded-full text-xs <?php echo $status_filter == 'In Care' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">In Care</a>
        <?php endif; ?>
    </div>
    
    <form method="GET" class="mt-3">
        <input type="hidden" name="type" value="<?php echo $type_filter; ?>">
        <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
        <div class="flex gap-2">
            <input type="text" name="search" placeholder="Search by name, case number, or location..." 
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm hover:bg-primary/90">Search</button>
            <?php if(!empty($search_query)): ?>
            <a href="?type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-300">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Cases Table - Compact -->
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
                <?php if(empty($cases)): ?>
                <tr>
                    <td colspan="7" class="px-3 py-6 text-center text-gray-500 text-sm">No cases found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($cases as $case): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-2 text-xs font-mono font-medium"><?php echo htmlspecialchars($case['case_number']); ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if(($type_filter == 'missing' || $type_filter == 'found') && !empty($case['photo']) && file_exists('../assets/uploads/' . $case['photo'])): ?>
                                        <img src="../assets/uploads/<?php echo $case['photo']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-gray-400 text-sm">child_care</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium text-sm">
                                    <?php echo htmlspecialchars($type_filter == 'missing' ? ($case['child_name'] ?? 'Unknown') : ($case['found_child_name'] ?? 'Unknown')); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            <?php 
                            if($type_filter == 'missing') {
                                echo $case['age'] . 'y / ' . $case['gender'];
                            } else {
                                echo ($case['approximate_age'] ?? '?') . 'y / ' . ($case['gender'] ?? '?');
                            }
                            ?>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            <?php echo htmlspecialchars($type_filter == 'missing' ? ($case['last_seen_location'] ?? 'Unknown') : ($case['found_location'] ?? 'Unknown')); ?>
                        </td>
                        <td class="px-3 py-2">
                            <?php 
                            $status = $type_filter == 'missing' ? $case['status'] : $case['status'];
                            $status_color = match($status) {
                                'Missing' => 'bg-red-100 text-red-800',
                                'Found' => 'bg-yellow-100 text-yellow-800',
                                'Reunited' => 'bg-green-100 text-green-800',
                                'Awaiting ID' => 'bg-yellow-100 text-yellow-800',
                                'In Care' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $status_color; ?>"><?php echo $status; ?></span>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500"><?php echo date('d M Y', strtotime($case['created_at'])); ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-1">
                                <form method="POST" class="inline status-form">
                                    <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                    <input type="hidden" name="case_type" value="<?php echo $type_filter; ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-xs border rounded px-1 py-0.5 focus:ring-2 focus:ring-primary">
                                        <?php if($type_filter == 'missing'): ?>
                                            <option value="Missing" <?php echo $case['status'] == 'Missing' ? 'selected' : ''; ?>>Missing</option>
                                            <option value="Found" <?php echo $case['status'] == 'Found' ? 'selected' : ''; ?>>Found</option>
                                            <option value="Reunited" <?php echo $case['status'] == 'Reunited' ? 'selected' : ''; ?>>Reunited</option>
                                        <?php else: ?>
                                            <option value="Awaiting ID" <?php echo $case['status'] == 'Awaiting ID' ? 'selected' : ''; ?>>Awaiting ID</option>
                                            <option value="Reunited" <?php echo $case['status'] == 'Reunited' ? 'selected' : ''; ?>>Reunited</option>
                                            <option value="In Care" <?php echo $case['status'] == 'In Care' ? 'selected' : ''; ?>>In Care</option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <a href="edit-case.php?id=<?php echo $case['id']; ?>&type=<?php echo $type_filter; ?>" class="text-blue-600 hover:text-blue-800">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                <a href="?delete=<?php echo $case['id']; ?>&type=<?php echo $type_filter; ?>" class="text-red-600 hover:text-red-800 delete-case" data-case-number="<?php echo $case['case_number']; ?>">
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
    
    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="px-3 py-2 border-t border-gray-200 flex justify-between items-center text-xs">
        <div class="text-gray-500"><?php echo (($page - 1) * $per_page) + 1; ?>-<?php echo min($page * $per_page, $total_records); ?> of <?php echo number_format($total_records); ?></div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="px-2 py-1 border rounded hover:bg-gray-50">Prev</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= min(3, $total_pages); $i++): ?>
            <a href="?page=<?php echo $i; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="px-2 py-1 border rounded <?php echo $page == $i ? 'bg-primary text-white border-primary' : 'hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="px-2 py-1 border rounded hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.status-form select').forEach(select => {
        select.addEventListener('change', function(e) {
            const form = this.closest('form');
            const newStatus = this.value;
            Swal.fire({
                title: 'Thibitisha',
                text: `Badilisha status kuwa "${newStatus}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#002045',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ndiyo',
                cancelButtonText: 'Hapana'
            }).then((result) => {
                if (!result.isConfirmed) location.reload();
            });
        });
    });
    
    document.querySelectorAll('.delete-case').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const caseNumber = this.dataset.caseNumber;
            Swal.fire({
                title: 'Thibitisha Kufuta',
                html: `Futa kesi <strong>${caseNumber}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Futa',
                cancelButtonText: 'Ghairi'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        });
    });
    
    <?php if(isset($_SESSION['success_message'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo addslashes($_SESSION['success_message']); ?>',
        confirmButtonColor: '#10b981',
        timer: 2000,
        showConfirmButton: false
    });
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>