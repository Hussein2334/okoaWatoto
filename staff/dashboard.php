<?php
// staff/dashboard.php
$page_title = 'Staff Dashboard';

// Path sahihi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/staff-header.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'staff') {
    header("Location: ../../index.php");
    exit();
}

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Missing'");
$total_missing = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Reunited'");
$total_reunited = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Missing' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$pending_verification = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE DATE(created_at) = CURDATE()");
$today_reports = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

// Get recent cases
$stmt = $pdo->query("SELECT * FROM children_reports WHERE status = 'Missing' ORDER BY created_at DESC LIMIT 5");
$recent_cases = $stmt->fetchAll();

// Get recent activity logs
$stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
$activities = $stmt->fetchAll();
?>

<!-- Dashboard Header -->
<div class="mb-4">
    <h1 class="text-xl md:text-2xl font-bold text-primary">Staff Dashboard</h1>
    <p class="text-gray-500 text-sm">Karibu tena, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
</div>

<!-- Quick Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">Missing Children</p>
                <p class="text-xl font-bold text-red-600"><?php echo number_format($total_missing); ?></p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600 text-xl">child_care</span>
            </div>
        </div>
        <div class="mt-1 text-xs text-gray-400">Active missing cases</div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">Reunited</p>
                <p class="text-xl font-bold text-green-600"><?php echo number_format($total_reunited); ?></p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-green-600 text-xl">family_restroom</span>
            </div>
        </div>
        <div class="mt-1 text-xs text-gray-400">Successfully reunited</div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">Pending Verification</p>
                <p class="text-xl font-bold text-yellow-600"><?php echo number_format($pending_verification); ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-yellow-600 text-xl">pending</span>
            </div>
        </div>
        <div class="mt-1 text-xs text-gray-400">Last 24 hours</div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-xs">Total Users</p>
                <p class="text-xl font-bold text-blue-600"><?php echo number_format($total_users); ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 text-xl">group</span>
            </div>
        </div>
        <div class="mt-1 text-xs text-gray-400">Registered users</div>
    </div>
</div>

<!-- Recent Cases Table -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-5">
    <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
        <h3 class="font-semibold text-md text-primary">Recent Missing Cases</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Case #</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Child Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Age/Gender</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Location</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Reported</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach($recent_cases as $case): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-3 py-2 text-xs font-mono"><?php echo htmlspecialchars($case['case_number']); ?></td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <?php if(!empty($case['photo']) && file_exists('../../assets/uploads/' . $case['photo'])): ?>
                            <img src="../../assets/uploads/<?php echo $case['photo']; ?>" class="w-6 h-6 rounded-full object-cover">
                            <?php else: ?>
                            <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="material-symbols-outlined text-gray-400 text-xs">child_care</span>
                            </div>
                            <?php endif; ?>
                            <span class="font-medium text-sm"><?php echo htmlspecialchars($case['child_name']); ?></span>
                        </div>
                    </td>
                    <td class="px-3 py-2 text-xs"><?php echo $case['age']; ?>y / <?php echo $case['gender']; ?></td>
                    <td class="px-3 py-2 text-xs"><?php echo htmlspecialchars($case['last_seen_location']); ?></td>
                    <td class="px-3 py-2 text-xs"><?php echo date('d M Y', strtotime($case['created_at'])); ?></td>
                    <td class="px-3 py-2">
                        <a href="edit-case.php?id=<?php echo $case['id']; ?>" class="text-primary hover:underline text-xs">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity Logs -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
        <h3 class="font-semibold text-md text-primary">Recent Activity Logs</h3>
    </div>
    <div class="divide-y divide-gray-200">
        <?php foreach($activities as $activity): ?>
        <div class="p-3 flex items-center gap-3 hover:bg-gray-50 transition-colors">
            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-500 text-sm">
                    <?php echo match($activity['action_type'] ?? 'view') {
                        'create' => 'add_circle',
                        'update' => 'edit',
                        'delete' => 'delete',
                        'login' => 'login',
                        'logout' => 'logout',
                        default => 'info'
                    }; ?>
                </span>
            </div>
            <div class="flex-1">
                <p class="text-sm"><?php echo htmlspecialchars($activity['description'] ?? $activity['action'] ?? 'Activity'); ?></p>
                <p class="text-xs text-gray-400"><?php echo date('d M Y, H:i:s', strtotime($activity['created_at'])); ?></p>
            </div>
            <div class="text-xs text-gray-400">
                <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>