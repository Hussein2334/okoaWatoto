<?php
// admin/reports.php
$page_title = 'Reports & Analytics';
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

// Get date range filters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'overview';

// =============================================
// STATISTICS
// =============================================

// Total missing children
$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Missing'");
$total_missing = $stmt->fetch()['total'];

// Total reunited children
$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports WHERE status = 'Reunited'");
$total_reunited = $stmt->fetch()['total'];

// Total found children (awaiting ID)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM found_reports WHERE status = 'Awaiting ID'");
$total_found = $stmt->fetch()['total'];

// Total reports
$stmt = $pdo->query("SELECT COUNT(*) as total FROM children_reports");
$total_reports = $stmt->fetch()['total'];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

// Reports in selected date range
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM children_reports WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$date_from, $date_to]);
$reports_this_month = $stmt->fetch()['total'];

// Reunification rate
$reunification_rate = $total_reports > 0 ? round(($total_reunited / $total_reports) * 100, 1) : 0;

// =============================================
// MONTHLY STATISTICS FOR CHART
// =============================================
$stmt = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    DATE_FORMAT(created_at, '%b %Y') as month_name,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Missing' THEN 1 ELSE 0 END) as missing,
    SUM(CASE WHEN status = 'Found' THEN 1 ELSE 0 END) as found,
    SUM(CASE WHEN status = 'Reunited' THEN 1 ELSE 0 END) as reunited
    FROM children_reports 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC");
$monthly_stats = $stmt->fetchAll();

$months = [];
$missing_counts = [];
$found_counts = [];
$reunited_counts = [];
foreach ($monthly_stats as $stat) {
    $months[] = $stat['month_name'];
    $missing_counts[] = $stat['missing'];
    $found_counts[] = $stat['found'];
    $reunited_counts[] = $stat['reunited'];
}

// =============================================
// CASES BY LOCATION
// =============================================
$stmt = $pdo->query("SELECT 
    last_seen_location as location, 
    COUNT(*) as count 
    FROM children_reports 
    WHERE last_seen_location IS NOT NULL AND last_seen_location != ''
    GROUP BY last_seen_location 
    ORDER BY count DESC 
    LIMIT 10");
$location_stats = $stmt->fetchAll();

// =============================================
// CASES BY GENDER
// =============================================
$stmt = $pdo->query("SELECT 
    gender, 
    COUNT(*) as count 
    FROM children_reports 
    WHERE gender IS NOT NULL AND gender != ''
    GROUP BY gender");
$gender_stats = $stmt->fetchAll();

$male_count = 0;
$female_count = 0;
foreach ($gender_stats as $stat) {
    if ($stat['gender'] == 'Male') $male_count = $stat['count'];
    if ($stat['gender'] == 'Female') $female_count = $stat['count'];
}

// =============================================
// CASES BY AGE GROUP
// =============================================
$stmt = $pdo->query("SELECT 
    CASE 
        WHEN age BETWEEN 0 AND 2 THEN '0-2 years'
        WHEN age BETWEEN 3 AND 5 THEN '3-5 years'
        WHEN age BETWEEN 6 AND 12 THEN '6-12 years'
        WHEN age BETWEEN 13 AND 17 THEN '13-17 years'
        ELSE 'Unknown'
    END as age_group,
    COUNT(*) as count
    FROM children_reports 
    WHERE age IS NOT NULL
    GROUP BY age_group
    ORDER BY MIN(age)");
$age_stats = $stmt->fetchAll();

// =============================================
// RECENT ACTIVITIES
// =============================================
$stmt = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
$recent_activities = $stmt->fetchAll();

// =============================================
// TOP REPORTERS
// =============================================
$stmt = $pdo->query("SELECT 
    reporter_name, 
    COUNT(*) as report_count 
    FROM children_reports 
    WHERE reporter_name IS NOT NULL 
    GROUP BY reporter_name 
    ORDER BY report_count DESC 
    LIMIT 5");
$top_reporters = $stmt->fetchAll();

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header - Compact -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Reports & Analytics</h1>
            <p class="text-gray-500 text-sm">View system statistics and generate reports</p>
        </div>
        <div>
            <button onclick="exportToCSV()" class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1 text-sm">
                <span class="material-symbols-outlined text-base">download</span>
                Export CSV
            </button>
        </div>
    </div>
</div>

<!-- Date Range Filter - Compact -->
<div class="bg-white rounded-lg border border-gray-200 p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-bold mb-1 text-gray-600">Date From</label>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="px-2 py-1.5 text-sm border rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-bold mb-1 text-gray-600">Date To</label>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="px-2 py-1.5 text-sm border rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-bold mb-1 text-gray-600">Report Type</label>
            <select name="report_type" class="px-2 py-1.5 text-sm border rounded-lg">
                <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                <option value="cases" <?php echo $report_type == 'cases' ? 'selected' : ''; ?>>Cases Report</option>
                <option value="activity" <?php echo $report_type == 'activity' ? 'selected' : ''; ?>>Activity Log</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-primary text-white px-3 py-1.5 rounded-lg text-sm hover:bg-primary/90">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Summary Statistics Cards - Compact -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 mb-4">
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-primary"><?php echo number_format($total_reports); ?></div>
        <div class="text-xs text-gray-500">Total Reports</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-red-600"><?php echo number_format($total_missing); ?></div>
        <div class="text-xs text-gray-500">Missing</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-yellow-600"><?php echo number_format($total_found); ?></div>
        <div class="text-xs text-gray-500">Found</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-green-600"><?php echo number_format($total_reunited); ?></div>
        <div class="text-xs text-gray-500">Reunited</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-blue-600"><?php echo number_format($total_users); ?></div>
        <div class="text-xs text-gray-500">Users</div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 p-2 text-center shadow-sm">
        <div class="text-lg font-bold text-purple-600"><?php echo $reunification_rate; ?>%</div>
        <div class="text-xs text-gray-500">Reunification Rate</div>
    </div>
</div>

<!-- Charts Row - Compact -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <!-- Monthly Trends Chart -->
    <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
        <h3 class="font-semibold text-md text-primary mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">trending_up</span>
            Monthly Case Trends
        </h3>
        <canvas id="trendsChart" height="200"></canvas>
    </div>
    
    <!-- Cases by Location Chart -->
    <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
        <h3 class="font-semibold text-md text-primary mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">location_on</span>
            Cases by Location
        </h3>
        <canvas id="locationChart" height="200"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <!-- Gender Distribution -->
    <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
        <h3 class="font-semibold text-md text-primary mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">wc</span>
            Gender Distribution
        </h3>
        <div class="flex items-center justify-center h-52">
            <canvas id="genderChart" width="250" height="200"></canvas>
        </div>
        <div class="flex justify-center gap-6 mt-2">
            <div class="text-center">
                <div class="text-lg font-bold text-blue-600"><?php echo number_format($male_count); ?></div>
                <div class="text-xs text-gray-500">Male</div>
            </div>
            <div class="text-center">
                <div class="text-lg font-bold text-pink-600"><?php echo number_format($female_count); ?></div>
                <div class="text-xs text-gray-500">Female</div>
            </div>
        </div>
    </div>
    
    <!-- Age Group Distribution -->
    <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
        <h3 class="font-semibold text-md text-primary mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">cake</span>
            Age Group Distribution
        </h3>
        <canvas id="ageChart" height="200"></canvas>
    </div>
</div>

<!-- Recent Activities Table - Compact -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm mb-4">
    <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
        <h3 class="font-semibold text-md text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">history</span>
            Recent System Activities
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-500">Time</th>
                    <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-500">User</th>
                    <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-500">Action</th>
                    <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-500">Description</th>
                    <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-500">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach($recent_activities as $activity): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-1.5 text-xs"><?php echo date('d M H:i', strtotime($activity['created_at'])); ?></td>
                    <td class="px-3 py-1.5 text-xs"><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></td>
                    <td class="px-3 py-1.5">
                        <span class="px-1.5 py-0.5 rounded-full text-xs font-semibold <?php 
                            echo match($activity['action_type'] ?? 'view') {
                                'create' => 'bg-green-100 text-green-800',
                                'update' => 'bg-blue-100 text-blue-800',
                                'delete' => 'bg-red-100 text-red-800',
                                'login' => 'bg-purple-100 text-purple-800',
                                'logout' => 'bg-gray-100 text-gray-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>">
                            <?php echo ucfirst($activity['action_type'] ?? 'view'); ?>
                        </span>
                    </td>
                    <td class="px-3 py-1.5 text-xs max-w-md truncate"><?php echo htmlspecialchars(substr($activity['description'] ?? $activity['action'] ?? '', 0, 60)); ?></td>
                    <td class="px-3 py-1.5 text-xs font-mono"><?php echo $activity['ip_address'] ?? '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Reporters - Compact -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
    <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
        <h3 class="font-semibold text-md text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">leaderboard</span>
            Top Reporters
        </h3>
    </div>
    <div class="divide-y">
        <?php foreach($top_reporters as $reporter): ?>
        <div class="p-2 flex items-center justify-between hover:bg-gray-50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-sm">person</span>
                </div>
                <div>
                    <div class="font-medium text-sm"><?php echo htmlspecialchars($reporter['reporter_name']); ?></div>
                    <div class="text-xs text-gray-500">Reporter</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-lg font-bold text-primary"><?php echo $reporter['report_count']; ?></div>
                <div class="text-xs text-gray-500">Reports</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Missing',
                    data: <?php echo json_encode($missing_counts); ?>,
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2
                },
                {
                    label: 'Found',
                    data: <?php echo json_encode($found_counts); ?>,
                    borderColor: '#eab308',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2
                },
                {
                    label: 'Reunited',
                    data: <?php echo json_encode($reunited_counts); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
        }
    });
    
    // Location Chart
    const locationCtx = document.getElementById('locationChart').getContext('2d');
    const locations = <?php echo json_encode(array_column($location_stats, 'location')); ?>;
    const locationCounts = <?php echo json_encode(array_column($location_stats, 'count')); ?>;
    
    new Chart(locationCtx, {
        type: 'bar',
        data: {
            labels: locations,
            datasets: [{
                label: 'Cases',
                data: locationCounts,
                backgroundColor: '#002045',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Gender Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [<?php echo $male_count; ?>, <?php echo $female_count; ?>],
                backgroundColor: ['#3b82f6', '#ec4899'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
        }
    });
    
    // Age Group Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    const ageGroups = <?php echo json_encode(array_column($age_stats, 'age_group')); ?>;
    const ageCounts = <?php echo json_encode(array_column($age_stats, 'count')); ?>;
    
    new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: ageGroups,
            datasets: [{
                label: 'Children',
                data: ageCounts,
                backgroundColor: '#0a6c44',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    // Export to CSV
    function exportToCSV() {
        const data = [];
        data.push(['Report Type', 'Value']);
        data.push(['Total Reports', <?php echo $total_reports; ?>]);
        data.push(['Missing Cases', <?php echo $total_missing; ?>]);
        data.push(['Found Cases', <?php echo $total_found; ?>]);
        data.push(['Reunited Cases', <?php echo $total_reunited; ?>]);
        data.push(['Total Users', <?php echo $total_users; ?>]);
        data.push(['Reunification Rate', '<?php echo $reunification_rate; ?>%']);
        data.push(['Reports This Period', <?php echo $reports_this_month; ?>]);
        data.push(['Date Range', '<?php echo $date_from; ?> to <?php echo $date_to; ?>']);
        
        const csvContent = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `report_<?php echo date('Y-m-d'); ?>.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
</script>

<?php require_once 'includes/admin-footer.php'; ?>