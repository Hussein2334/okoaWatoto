<?php
// admin/reporters.php
$page_title = 'Reporters Management';

// Start output buffering
ob_start();

require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'staff') {
    header("Location: dashboard.php");
    exit();
}

// Get reporter details if viewing single reporter
$view_reporter_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
$reporter_details = null;
$reporter_reports = [];

if ($view_reporter_id > 0) {
    // Get reporter details from children_reports
    $stmt = $pdo->prepare("SELECT 
                            reporter_name, 
                            reporter_phone, 
                            reporter_email, 
                            reporter_type,
                            COUNT(*) as total_reports,
                            MAX(created_at) as last_report
                          FROM children_reports 
                          WHERE id = ? OR reporter_phone = (SELECT reporter_phone FROM children_reports WHERE id = ? LIMIT 1)
                          GROUP BY reporter_name, reporter_phone, reporter_email, reporter_type");
    $stmt->execute([$view_reporter_id, $view_reporter_id]);
    $reporter_details = $stmt->fetch();
    
    // Get all reports by this reporter
    if ($reporter_details) {
        $stmt = $pdo->prepare("SELECT * FROM children_reports 
                               WHERE reporter_phone = ? OR reporter_email = ? OR reporter_name = ?
                               ORDER BY created_at DESC");
        $stmt->execute([
            $reporter_details['reporter_phone'], 
            $reporter_details['reporter_email'],
            $reporter_details['reporter_name']
        ]);
        $reporter_reports = $stmt->fetchAll();
    }
}

// Get filter parameters
$search_query = $_GET['search'] ?? '';
$reporter_type_filter = $_GET['type'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query for reporters (group by reporter)
$count_sql = "SELECT COUNT(DISTINCT reporter_phone) as total FROM children_reports WHERE reporter_name IS NOT NULL AND reporter_name != ''";
$data_sql = "SELECT 
                reporter_name,
                reporter_phone,
                reporter_email,
                reporter_type,
                COUNT(*) as report_count,
                MAX(created_at) as last_report,
                MIN(created_at) as first_report
            FROM children_reports 
            WHERE reporter_name IS NOT NULL AND reporter_name != ''";
$params = [];

if (!empty($search_query)) {
    $count_sql .= " AND (reporter_name LIKE ? OR reporter_phone LIKE ? OR reporter_email LIKE ?)";
    $data_sql .= " AND (reporter_name LIKE ? OR reporter_phone LIKE ? OR reporter_email LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($reporter_type_filter !== 'all') {
    $count_sql .= " AND reporter_type = ?";
    $data_sql .= " AND reporter_type = ?";
    $params[] = $reporter_type_filter;
}

$data_sql .= " GROUP BY reporter_phone ORDER BY report_count DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

// Get total count
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

// Get reporters data
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$reporters = $stmt->fetchAll();

// Get reporter type statistics
$type_stats = $pdo->query("SELECT reporter_type, COUNT(*) as count FROM children_reports WHERE reporter_type IS NOT NULL GROUP BY reporter_type")->fetchAll();

// Total unique reporters
$total_reporters = $pdo->query("SELECT COUNT(DISTINCT reporter_phone) as total FROM children_reports WHERE reporter_name IS NOT NULL")->fetch()['total'];

// Clear buffer and include header
ob_end_clean();
require_once 'includes/staff-header.php';
require_once 'includes/staff-sidebar.php';
?>

<!-- Dashboard Header -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Reporters Management</h1>
            <p class="text-gray-500 text-sm">Manage people who reported missing children cases</p>
        </div>
        <div class="text-sm text-gray-500">
            Total Reporters: <span class="font-bold text-primary"><?php echo $total_reporters; ?></span>
        </div>
    </div>
</div>

<?php if($view_reporter_id > 0 && $reporter_details): ?>
<!-- Reporter Details View -->
<div class="mb-4">
    <a href="reporters.php" class="inline-flex items-center gap-1 text-primary hover:underline text-sm">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        Back to Reporters List
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <!-- Reporter Profile -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h2 class="text-md font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">person</span>
                    Reporter Profile
                </h2>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="text-xs text-gray-500 block">Full Name</label>
                    <p class="font-medium"><?php echo htmlspecialchars($reporter_details['reporter_name']); ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block">Phone Number</label>
                    <p class="font-medium"><?php echo htmlspecialchars($reporter_details['reporter_phone']); ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block">Email</label>
                    <p class="font-medium"><?php echo htmlspecialchars($reporter_details['reporter_email'] ?? 'Not provided'); ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block">Reporter Type</label>
                    <p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php 
                            echo match($reporter_details['reporter_type']) {
                                'Parent' => 'bg-blue-100 text-blue-800',
                                'Police' => 'bg-red-100 text-red-800',
                                'Witness' => 'bg-yellow-100 text-yellow-800',
                                'Relative' => 'bg-green-100 text-green-800',
                                'Teacher' => 'bg-purple-100 text-purple-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>">
                            <?php echo htmlspecialchars($reporter_details['reporter_type'] ?? 'Unknown'); ?>
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block">Total Reports</label>
                    <p class="text-2xl font-bold text-primary"><?php echo $reporter_details['total_reports']; ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block">First Report</label>
                    <p class="text-sm"><?php echo date('d M Y', strtotime($reporter_details['first_report'])); ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block">Last Report</label>
                    <p class="text-sm"><?php echo date('d M Y', strtotime($reporter_details['last_report'])); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reporter's Reports -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h2 class="text-md font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">description</span>
                    Reports by <?php echo htmlspecialchars($reporter_details['reporter_name']); ?>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Case #</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Child Name</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Report Date</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($reporter_reports as $report): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-xs font-mono"><?php echo htmlspecialchars($report['case_number']); ?></td>
                            <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($report['child_name']); ?></td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs <?php echo $report['status'] == 'Missing' ? 'bg-red-100 text-red-800' : ($report['status'] == 'Reunited' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                    <?php echo $report['status']; ?>
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                            <td class="px-3 py-2">
                                <a href="edit-case.php?id=<?php echo $report['id']; ?>&type=missing" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-primary"><?php echo $total_reporters; ?></div>
        <div class="text-xs text-gray-500">Total Reporters</div>
    </div>
    <?php foreach($type_stats as $stat): ?>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold <?php 
            echo match($stat['reporter_type']) {
                'Parent' => 'text-blue-600',
                'Police' => 'text-red-600',
                'Witness' => 'text-yellow-600',
                'Relative' => 'text-green-600',
                'Teacher' => 'text-purple-600',
                default => 'text-gray-600'
            };
        ?>">
            <?php echo $stat['count']; ?>
        </div>
        <div class="text-xs text-gray-500"><?php echo ucfirst($stat['reporter_type'] ?? 'Unknown'); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <div class="flex flex-wrap gap-2 mb-3">
        <a href="?type=all" class="px-2 py-0.5 rounded-full text-xs <?php echo $reporter_type_filter == 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">All Types</a>
        <a href="?type=Parent" class="px-2 py-0.5 rounded-full text-xs <?php echo $reporter_type_filter == 'Parent' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Parents</a>
        <a href="?type=Police" class="px-2 py-0.5 rounded-full text-xs <?php echo $reporter_type_filter == 'Police' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Police</a>
        <a href="?type=Witness" class="px-2 py-0.5 rounded-full text-xs <?php echo $reporter_type_filter == 'Witness' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Witnesses</a>
        <a href="?type=Relative" class="px-2 py-0.5 rounded-full text-xs <?php echo $reporter_type_filter == 'Relative' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Relatives</a>
        <a href="?type=Teacher" class="px-2 py-0.5 rounded-full text-xs <?php echo $reporter_type_filter == 'Teacher' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">Teachers</a>
    </div>
    
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" placeholder="Search by name, phone, or email..." 
               value="<?php echo htmlspecialchars($search_query); ?>"
               class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
        <input type="hidden" name="type" value="<?php echo $reporter_type_filter; ?>">
        <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm">Search</button>
        <?php if(!empty($search_query)): ?>
        <a href="?type=<?php echo $reporter_type_filter; ?>" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Reporters Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">#</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Reporter Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Phone</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Email</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Reports</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Last Report</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($reporters)): ?>
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500 text-sm">No reporters found</td>
                </tr>
                <?php else: ?>
                    <?php $rank = 1; foreach($reporters as $reporter): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-2 text-xs font-bold text-gray-500"><?php echo $rank; ?></td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-primary text-sm">person</span>
                                </div>
                                <span class="font-medium text-sm"><?php echo htmlspecialchars($reporter['reporter_name']); ?></span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($reporter['reporter_phone']); ?></td>
                        <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($reporter['reporter_email'] ?? '-'); ?></td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php 
                                echo match($reporter['reporter_type']) {
                                    'Parent' => 'bg-blue-100 text-blue-800',
                                    'Police' => 'bg-red-100 text-red-800',
                                    'Witness' => 'bg-yellow-100 text-yellow-800',
                                    'Relative' => 'bg-green-100 text-green-800',
                                    'Teacher' => 'bg-purple-100 text-purple-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php echo htmlspecialchars($reporter['reporter_type'] ?? 'Unknown'); ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs">
                                <?php echo $reporter['report_count']; ?>
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs"><?php echo date('d M Y', strtotime($reporter['last_report'])); ?></td>
                        <td class="px-3 py-2">
                            <a href="?view=<?php echo $reporter['reporter_phone']; ?>" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                View
                            </a>
                        </td>
                    </tr>
                    <?php $rank++; endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="px-3 py-2 border-t border-gray-200 flex justify-between items-center text-xs">
        <div class="text-gray-500">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_records); ?> of <?php echo number_format($total_records); ?> reporters
        </div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&type=<?php echo $reporter_type_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="px-2 py-1 border rounded hover:bg-gray-50">Prev</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= min(5, $total_pages); $i++): ?>
            <a href="?page=<?php echo $i; ?>&type=<?php echo $reporter_type_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="px-2 py-1 border rounded <?php echo $page == $i ? 'bg-primary text-white border-primary' : 'hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&type=<?php echo $reporter_type_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="px-2 py-1 border rounded hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php require_once 'includes/staff-footer.php'; ?>