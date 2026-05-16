<?php
// user/dashboard.php
$page_title = 'User Dashboard';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/user-header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user's own reports
$stmt = $pdo->prepare("SELECT * FROM children_reports WHERE reporter_phone = ? OR reporter_email = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_email'], $_SESSION['user_email']]);
$my_reports = $stmt->fetchAll();

// Get statistics
$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Missing'");
$total_missing = $stmt_total->fetch()['total'];

$stmt_reunited = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Reunited'");
$total_reunited = $stmt_reunited->fetch()['total'];
?>

<!-- Dashboard Header -->
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-primary">User Dashboard</h1>
    <p class="text-gray-500 mt-1">Karibu tena, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">My Reports</p>
                <p class="text-2xl font-bold text-primary"><?php echo count($my_reports); ?></p>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-2xl">description</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Missing Children</p>
                <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_missing); ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600 text-2xl">child_care</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Reunited Children</p>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($total_reunited); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 text-2xl">family_restroom</span>
            </div>
        </div>
    </div>
</div>

<!-- My Reports Table -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
        <h3 class="font-semibold text-lg text-primary">My Recent Reports</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Report Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if(empty($my_reports)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No reports found</td>
                </tr>
                <?php else: ?>
                    <?php foreach($my_reports as $report): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono"><?php echo htmlspecialchars($report['case_number']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($report['child_name']); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $report['status'] == 'Missing' ? 'bg-red-100 text-red-800' : ($report['status'] == 'Reunited' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo $report['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <a href="../child-details.php?id=<?php echo $report['id']; ?>&type=missing" class="text-primary hover:underline text-sm">View Details</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/user-footer.php'; ?>