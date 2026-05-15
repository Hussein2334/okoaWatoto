<?php
// admin/dashboard.php
$page_title = 'Dashboard';
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

// Get cases by region
$stmt = $pdo->query("SELECT last_seen_location, COUNT(*) as count FROM children_reports GROUP BY last_seen_location ORDER BY count DESC LIMIT 10");
$regional_data = $stmt->fetchAll();

// Get monthly statistics
$stmt = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Missing' THEN 1 ELSE 0 END) as missing,
    SUM(CASE WHEN status = 'Reunited' THEN 1 ELSE 0 END) as reunited
    FROM children_reports 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC");
$monthly_stats = $stmt->fetchAll();

// Prepare data for chart
$months = [];
$missing_counts = [];
$reunited_counts = [];
foreach ($monthly_stats as $stat) {
    $months[] = date('M Y', strtotime($stat['month'] . '-01'));
    $missing_counts[] = $stat['missing'];
    $reunited_counts[] = $stat['reunited'];
}

// Get recent activity logs
$stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
$activities = $stmt->fetchAll();

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header - Reduced margin -->
<div class="mb-4">
    <h1 class="text-xl md:text-2xl font-bold text-primary">Dashboard</h1>
    <p class="text-gray-500 text-sm">Karibu tena, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
</div>

<!-- Quick Stats Cards - Reduced gap -->
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

<!-- Charts and Map Section - Reduced gap -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
    <!-- Monthly Statistics Chart -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
        <h3 class="font-semibold text-md text-primary mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">bar_chart</span>
            Monthly Statistics
        </h3>
        <canvas id="monthlyChart" height="220"></canvas>
    </div>
    
    <!-- Real Map of Tanzania -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
        <h3 class="font-semibold text-md text-primary mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">map</span>
            Map ya Tanzania - Missing Cases Distribution
        </h3>
        <div id="tanzaniaMap" style="height: 260px; border-radius: 0.5rem; overflow: hidden;"></div>
        <div class="mt-2 text-xs text-gray-500 text-center">
            <span class="inline-flex items-center gap-1"><div class="w-2 h-2 rounded-full bg-red-500"></div> High</span>
            <span class="inline-flex items-center gap-1 ml-2"><div class="w-2 h-2 rounded-full bg-orange-400"></div> Medium</span>
            <span class="inline-flex items-center gap-1 ml-2"><div class="w-2 h-2 rounded-full bg-blue-400"></div> Low</span>
        </div>
    </div>
</div>

<!-- Recent Cases Table - Reduced margin -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-5">
    <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center bg-gray-50">
        <h3 class="font-semibold text-md text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">schedule</span>
            Recent Missing Cases
        </h3>
        <a href="cases.php" class="text-primary text-xs hover:underline">View All →</a>
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
                            <?php if(!empty($case['photo']) && file_exists('../assets/uploads/' . $case['photo'])): ?>
                            <img src="../assets/uploads/<?php echo $case['photo']; ?>" class="w-6 h-6 rounded-full object-cover">
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
                

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity Logs - No extra margin -->
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
        <h3 class="font-semibold text-md text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">history</span>
            Recent Activity Logs
        </h3>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Chart
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Missing Cases',
                    data: <?php echo json_encode($missing_counts); ?>,
                    borderColor: '#ba1a1a',
                    backgroundColor: 'rgba(186, 26, 26, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                },
                {
                    label: 'Reunited Cases',
                    data: <?php echo json_encode($reunited_counts); ?>,
                    borderColor: '#0a6c44',
                    backgroundColor: 'rgba(10, 108, 68, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, font: { size: 11 } }
                }
            }
        }
    });
    
    // Tanzania Map - Leaflet (only if map element exists)
    const mapElement = document.getElementById('tanzaniaMap');
    if (mapElement && typeof L !== 'undefined') {
        const map = L.map('tanzaniaMap').setView([-6.3690, 34.8888], 6);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
        
        // Sample markers for regions
        const locations = [
            { name: 'Dar es Salaam', lat: -6.7924, lng: 39.2083, cases: 42 },
            { name: 'Arusha', lat: -3.3869, lng: 36.6820, cases: 28 },
            { name: 'Mwanza', lat: -2.5164, lng: 32.9175, cases: 35 },
            { name: 'Dodoma', lat: -6.1629, lng: 35.7516, cases: 15 },
            { name: 'Mbeya', lat: -8.9051, lng: 33.4826, cases: 12 },
            { name: 'Tanga', lat: -5.0689, lng: 39.0987, cases: 18 }
        ];
        
        locations.forEach(location => {
            const markerColor = location.cases > 30 ? '#ef4444' : (location.cases > 20 ? '#f97316' : '#3b82f6');
            const markerHtml = `<div style="background-color: ${markerColor}; width: 10px; height: 10px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 2px rgba(0,0,0,0.3);"></div>`;
            
            const customIcon = L.divIcon({ html: markerHtml, iconSize: [10, 10], className: 'custom-marker' });
            
            L.marker([location.lat, location.lng], { icon: customIcon })
                .bindPopup(`<b>${location.name}</b><br>Missing cases: <b>${location.cases}</b>`)
                .addTo(map);
        });
    }
</script>

<?php require_once 'includes/admin-footer.php'; ?>