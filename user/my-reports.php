<?php
// user/my-reports.php
$page_title = 'My Reports';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/user-header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user email and phone for matching
$user_email = $_SESSION['user_email'];
$user_phone = $user['phone'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// =============================================
// COUNT TOTAL REPORTS (MISSING + FOUND)
// =============================================

// Count missing reports
$count_missing_sql = "SELECT COUNT(*) as total FROM children_reports 
                      WHERE reporter_email = ? OR reporter_phone = ?";
$stmt = $pdo->prepare($count_missing_sql);
$stmt->execute([$user_email, $user_phone]);
$total_missing = $stmt->fetch()['total'];

// Count found reports
$count_found_sql = "SELECT COUNT(*) as total FROM found_reports 
                    WHERE finder_email = ? OR finder_phone = ?";
$stmt = $pdo->prepare($count_found_sql);
$stmt->execute([$user_email, $user_phone]);
$total_found = $stmt->fetch()['total'];

$total_records = $total_missing + $total_found;
$total_pages = ceil($total_records / $per_page);

// =============================================
// GET MISSING REPORTS
// =============================================
$missing_sql = "SELECT 
                    id, 
                    child_name as name, 
                    age, 
                    gender, 
                    description,
                    last_seen_location as location,
                    last_seen_date as report_date,
                    photo,
                    case_number, 
                    status,
                    'missing' as report_type,
                    created_at
                FROM children_reports 
                WHERE reporter_email = ? OR reporter_phone = ?
                ORDER BY created_at DESC";
$stmt = $pdo->prepare($missing_sql);
$stmt->execute([$user_email, $user_phone]);
$missing_reports = $stmt->fetchAll();

// =============================================
// GET FOUND REPORTS
// =============================================
$found_sql = "SELECT 
                    id, 
                    found_child_name as name, 
                    approximate_age as age, 
                    gender, 
                    description,
                    found_location as location,
                    found_date as report_date,
                    photo,
                    case_number, 
                    status,
                    'found' as report_type,
                    created_at,
                    finder_name,
                    current_location
                FROM found_reports 
                WHERE finder_email = ? OR finder_phone = ?
                ORDER BY created_at DESC";
$stmt = $pdo->prepare($found_sql);
$stmt->execute([$user_email, $user_phone]);
$found_reports = $stmt->fetchAll();

// =============================================
// MERGE AND SORT REPORTS
// =============================================
$all_reports = array_merge($missing_reports, $found_reports);

// Sort by created_at (newest first)
usort($all_reports, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Apply pagination
$paginated_reports = array_slice($all_reports, $offset, $per_page);
?>

<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-primary">My Reports</h1>
    <p class="text-gray-500 mt-1">All reports submitted by you (Missing and Found)</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Missing Reports</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $total_missing; ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600 text-2xl">warning</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Found Reports</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $total_found; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 text-2xl">volunteer_activism</span>
            </div>
        </div>
    </div>
</div>

<!-- Reports Tabs -->
<div class="mb-4">
    <div class="border-b border-gray-200">
        <nav class="flex gap-1">
            <button onclick="showTab('all')" id="tabAll" class="px-4 py-2 text-sm font-medium rounded-t-lg bg-primary text-white transition-colors">
                All Reports
            </button>
            <button onclick="showTab('missing')" id="tabMissing" class="px-4 py-2 text-sm font-medium rounded-t-lg text-gray-600 hover:bg-gray-100 transition-colors">
                Missing Reports
            </button>
            <button onclick="showTab('found')" id="tabFound" class="px-4 py-2 text-sm font-medium rounded-t-lg text-gray-600 hover:bg-gray-100 transition-colors">
                Found Reports
            </button>
        </nav>
    </div>
</div>

<!-- All Reports Table -->
<div id="allReports" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Age</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Location</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Report Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($paginated_reports)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">No reports found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($paginated_reports as $report): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <?php if($report['report_type'] == 'missing'): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    Missing
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    Found
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm font-mono"><?php echo htmlspecialchars($report['case_number']); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if(!empty($report['photo']) && file_exists('../assets/uploads/' . $report['photo'])): ?>
                                        <img src="../assets/uploads/<?php echo $report['photo']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-gray-400 text-sm">child_care</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium text-sm"><?php echo htmlspecialchars($report['name'] ?? 'Unknown'); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo $report['age'] ?? '?'; ?> yrs</td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($report['location'] ?? 'Unknown'); ?></td>
                        <td class="px-4 py-3">
                            <?php 
                            if($report['report_type'] == 'missing') {
                                $status_color = match($report['status']) {
                                    'Missing' => 'bg-red-100 text-red-800',
                                    'Found' => 'bg-yellow-100 text-yellow-800',
                                    'Reunited' => 'bg-green-100 text-green-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            } else {
                                $status_color = match($report['status']) {
                                    'Awaiting ID' => 'bg-yellow-100 text-yellow-800',
                                    'Reunited' => 'bg-green-100 text-green-800',
                                    'In Care' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            }
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                <?php echo $report['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <a href="../child-details.php?id=<?php echo $report['id']; ?>&type=<?php echo $report['report_type']; ?>" 
                               class="text-primary hover:underline text-sm flex items-center gap-1">
                                View Details
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="px-4 py-3 border-t border-gray-200 flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_records); ?> of <?php echo number_format($total_records); ?> reports
        </div>
        <div class="flex gap-1">
            <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-50">Prev</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= min(5, $total_pages); $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="px-3 py-1 border rounded-lg <?php echo $page == $i ? 'bg-primary text-white border-primary' : 'hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>" class="px-3 py-1 border rounded-lg hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Missing Reports Table (Hidden by default) -->
<div id="missingReports" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Age</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Location</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Report Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($missing_reports)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">No missing reports found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($missing_reports as $report): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono"><?php echo htmlspecialchars($report['case_number']); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if(!empty($report['photo']) && file_exists('../assets/uploads/' . $report['photo'])): ?>
                                        <img src="../assets/uploads/<?php echo $report['photo']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-gray-400 text-sm">child_care</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium text-sm"><?php echo htmlspecialchars($report['name'] ?? 'Unknown'); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo $report['age'] ?? '?'; ?> yrs</td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($report['location'] ?? 'Unknown'); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $report['status'] == 'Missing' ? 'bg-red-100 text-red-800' : ($report['status'] == 'Reunited' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo $report['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <a href="../child-details.php?id=<?php echo $report['id']; ?>&type=missing" class="text-primary hover:underline text-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Found Reports Table (Hidden by default) -->
<div id="foundReports" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Age</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Found Location</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Current Location</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Found Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($found_reports)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">No found reports found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($found_reports as $report): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono"><?php echo htmlspecialchars($report['case_number']); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden">
                                    <?php if(!empty($report['photo']) && file_exists('../assets/uploads/' . $report['photo'])): ?>
                                        <img src="../assets/uploads/<?php echo $report['photo']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="material-symbols-outlined text-gray-400 text-sm">child_care</span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium text-sm"><?php echo htmlspecialchars($report['name'] ?? 'Unknown'); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo $report['age'] ?? '?'; ?> yrs</td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($report['location'] ?? 'Unknown'); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($report['current_location'] ?? 'Not specified'); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $report['status'] == 'Awaiting ID' ? 'bg-yellow-100 text-yellow-800' : ($report['status'] == 'Reunited' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); ?>">
                                <?php echo $report['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <a href="../child-details.php?id=<?php echo $report['id']; ?>&type=found" class="text-primary hover:underline text-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function showTab(tabName) {
        // Hide all tables
        document.getElementById('allReports').classList.add('hidden');
        document.getElementById('missingReports').classList.add('hidden');
        document.getElementById('foundReports').classList.add('hidden');
        
        // Remove active class from all tabs
        document.getElementById('tabAll').classList.remove('bg-primary', 'text-white');
        document.getElementById('tabAll').classList.add('text-gray-600');
        document.getElementById('tabMissing').classList.remove('bg-primary', 'text-white');
        document.getElementById('tabMissing').classList.add('text-gray-600');
        document.getElementById('tabFound').classList.remove('bg-primary', 'text-white');
        document.getElementById('tabFound').classList.add('text-gray-600');
        
        // Show selected table and activate tab
        if (tabName === 'all') {
            document.getElementById('allReports').classList.remove('hidden');
            document.getElementById('tabAll').classList.add('bg-primary', 'text-white');
            document.getElementById('tabAll').classList.remove('text-gray-600');
        } else if (tabName === 'missing') {
            document.getElementById('missingReports').classList.remove('hidden');
            document.getElementById('tabMissing').classList.add('bg-primary', 'text-white');
            document.getElementById('tabMissing').classList.remove('text-gray-600');
        } else if (tabName === 'found') {
            document.getElementById('foundReports').classList.remove('hidden');
            document.getElementById('tabFound').classList.add('bg-primary', 'text-white');
            document.getElementById('tabFound').classList.remove('text-gray-600');
        }
    }
</script>

<?php require_once __DIR__ . '/includes/user-footer.php'; ?>