<?php
// index.php
$page_title = 'Home';
require_once 'config/database.php';
require_once 'includes/header.php';

// Fetch recent reports - combine missing and found
$stmt = $pdo->query("SELECT 
    id, child_name as name, age, gender, description, clothing,
    last_seen_location as location, last_seen_date as date,
    photo, case_number, status, 'missing' as type, created_at
    FROM children_reports 
    WHERE status = 'Missing' 
    ORDER BY created_at DESC LIMIT 6");
$recent_children = $stmt->fetchAll();

$stmt2 = $pdo->query("SELECT 
    id, found_child_name as name, approximate_age as age, gender, description,
    found_location as location, found_date as date,
    photo, case_number, health_status as status, 'found' as type, created_at,
    finder_name, finder_phone, current_location
    FROM found_reports 
    ORDER BY created_at DESC LIMIT 4");
$found_children = $stmt2->fetchAll();

// Merge and sort recent reports
$all_reports = array_merge($recent_children, $found_children);
usort($all_reports, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_reports = array_slice($all_reports, 0, 6);

// =============================================
// STATISTICS
// =============================================

// Children Reunited
$stmt_reunited = $pdo->query("SELECT COUNT(*) as count FROM children_reports WHERE status = 'Reunited'");
$children_reunited = $stmt_reunited->fetch()['count'];

// Active Cases (Missing)
$stmt_active = $pdo->query("SELECT COUNT(*) as count FROM children_reports WHERE status = 'Missing'");
$active_cases = $stmt_active->fetch()['count'];

// Total Reports
$stmt_total = $pdo->query("SELECT COUNT(*) as count FROM children_reports");
$total_reports = $stmt_total->fetch()['count'];

// Found Children (Awaiting ID)
$stmt_found = $pdo->query("SELECT COUNT(*) as count FROM found_reports");
$found_count = $stmt_found->fetch()['count'];

// Reunification Rate
$reunification_rate = $total_reports > 0 ? round(($children_reunited / $total_reports) * 100) : 0;

// Top Reporters
$stmt_top_reporters = $pdo->query("SELECT 
    reporter_name, COUNT(*) as report_count, MAX(created_at) as last_report
    FROM children_reports 
    WHERE reporter_name IS NOT NULL AND reporter_name != ''
    GROUP BY reporter_name 
    ORDER BY report_count DESC LIMIT 5");
$top_reporters = $stmt_top_reporters->fetchAll();

// Search by Case ID
$search_result = null;
$search_error = '';
$search_case_id = $_GET['search_case'] ?? '';

if (!empty($search_case_id)) {
    // Search in children_reports
    $stmt_search = $pdo->prepare("SELECT *, 'missing' as report_type FROM children_reports WHERE case_number = ?");
    $stmt_search->execute([$search_case_id]);
    $search_result = $stmt_search->fetch();
    
    // If not found, search in found_reports
    if (!$search_result) {
        $stmt_search2 = $pdo->prepare("SELECT *, 'found' as report_type FROM found_reports WHERE case_number = ?");
        $stmt_search2->execute([$search_case_id]);
        $search_result = $stmt_search2->fetch();
    }
    
    if (!$search_result) {
        $search_error = "No case found with Case Number: " . htmlspecialchars($search_case_id);
    }
}
?>

<!-- Hero Section -->
<section class="relative w-full bg-white py-12 px-4 md:px-12 flex flex-col md:flex-row items-center gap-8 border-b border-[#c4c6cf]">
    <div class="w-full md:w-1/2 flex flex-col gap-4">
        <h1 class="text-[26px] md:text-[32px] font-bold text-[#002045]">
            Helping Every Child Find Their Way Home.
            <span class="text-[#0a6c44] block text-2xl mt-2">Kusaidia Kila Mtoto Kupata Njia Yake ya Nyumbani.</span>
        </h1>
        <p class="text-lg text-[#43474e]">A national registry for missing and found children in Tanzania.</p>
        <div class="flex flex-col sm:flex-row gap-4 mt-4">
            <a href="report-missing.php" class="bg-[#ba1a1a] text-white font-bold py-3 px-6 rounded shadow-md hover:bg-red-700 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">campaign</span> Report a Missing Child
            </a>
            <a href="report-found.php" class="bg-[#002045] text-white font-bold py-3 px-6 rounded shadow-md hover:bg-blue-900 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">person_add</span> I Found a Child
            </a>
        </div>
    </div>
</section>

<!-- Search by Case ID Section -->
<section class="py-8 px-4 md:px-12 bg-gray-50 border-b border-[#c4c6cf]">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-4">
            <h2 class="text-xl font-bold text-[#002045]">Track a Case</h2>
            <p class="text-sm text-[#43474e]">Enter Case Number to track a missing or found child</p>
        </div>
        <form method="GET" action="" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#43474e]">search</span>
                <input type="text" name="search_case" placeholder="Enter Case Number (e.g., CASE-2024-001 or FOUND-2024-001)" 
                       value="<?php echo htmlspecialchars($search_case_id); ?>"
                       class="w-full pl-10 pr-4 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/20 bg-white">
            </div>
            <button type="submit" class="bg-[#002045] text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-900 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">track_changes</span>
                Track Case
            </button>
        </form>
        
        <!-- Search Result -->
        <?php if($search_result): ?>
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold text-green-700">Case Found!</p>
                    <p class="text-xs text-gray-600">Case Number: <strong><?php echo htmlspecialchars($search_result['case_number']); ?></strong></p>
                    <p class="text-xs text-gray-600">Type: <?php echo ucfirst($search_result['report_type']); ?> Child</p>
                </div>
                <a href="child-details.php?id=<?php echo $search_result['id']; ?>&type=<?php echo $search_result['report_type']; ?>" 
                   class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                    View Details →
                </a>
            </div>
        </div>
        <?php elseif($search_error): ?>
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700"><?php echo $search_error; ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Stats Section -->
<section class="py-12 px-4 md:px-12 border-y border-[#c4c6cf] bg-gray-50">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-5 gap-4 text-center">
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#0a6c44] mb-2"><?php echo number_format($children_reunited); ?></div>
            <div class="text-[#43474e] text-sm">Children Reunited</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#ba1a1a] mb-2"><?php echo number_format($active_cases); ?></div>
            <div class="text-[#43474e] text-sm">Active Cases</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#eab308] mb-2"><?php echo number_format($found_count); ?></div>
            <div class="text-[#43474e] text-sm">Found Children</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#002045] mb-2"><?php echo number_format($total_reports); ?></div>
            <div class="text-[#43474e] text-sm">Total Reports</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#002045] mb-2"><?php echo $reunification_rate; ?>%</div>
            <div class="text-[#43474e] text-sm">Reunification Rate</div>
        </div>
    </div>
</section>

<!-- Recent Reports Section - Combines Missing and Found -->
<div class="max-w-7xl mx-auto px-4 md:px-12 py-12">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-[#002045]">Recent Reports</h2>
            <p class="text-sm text-gray-500">Latest missing and found children reports</p>
        </div>
        <a href="children.php" class="text-[#002045] font-bold text-sm hover:underline">View All →</a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($recent_reports as $report): ?>
        <div class="bg-white border-2 <?php echo $report['type'] == 'missing' ? 'border-red-500/50' : 'border-green-500/50'; ?> rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all group relative">
            <!-- Status Badge -->
            <div class="absolute top-3 left-3 z-10">
                <span class="<?php echo $report['type'] == 'missing' ? 'bg-red-600' : 'bg-green-600'; ?> text-white text-xs font-bold px-2 py-1 rounded-md flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">
                        <?php echo $report['type'] == 'missing' ? 'warning' : 'volunteer_activism'; ?>
                    </span>
                    <?php echo $report['type'] == 'missing' ? 'MISSING' : 'FOUND'; ?>
                </span>
            </div>
            
            <!-- Case Number -->
            <div class="absolute top-3 right-3 z-10 bg-black/60 text-white text-xs px-2 py-1 rounded-md font-mono">
                <?php echo htmlspecialchars($report['case_number']); ?>
            </div>
            
            <!-- Photo -->
            <div class="h-52 w-full bg-gray-100 overflow-hidden relative">
                <?php if(!empty($report['photo']) && file_exists('assets/uploads/' . $report['photo'])): ?>
                    <img src="assets/uploads/<?php echo $report['photo']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                        <span class="material-symbols-outlined text-6xl text-gray-300">child_care</span>
                        <p class="text-sm text-gray-400 mt-2">No photo available</p>
                    </div>
                <?php endif; ?>
                
                <!-- Age Badge -->
                <div class="absolute bottom-3 right-3 bg-black/70 text-white text-sm px-2 py-1 rounded-full flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">cake</span>
                    <?php echo $report['age'] ?? '?'; ?> yrs
                </div>
            </div>
            
            <!-- Info -->
            <div class="p-4">
                <h3 class="font-bold text-lg text-[#181c1e] mb-1 line-clamp-1">
                    <?php echo htmlspecialchars($report['name'] ?? 'Unknown Child'); ?>
                </h3>
                
                <div class="flex flex-wrap gap-2 text-sm text-gray-500 mb-2">
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">wc</span>
                        <?php echo $report['gender'] ?? 'N/A'; ?>
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">calendar_month</span>
                        <?php echo date('d M Y', strtotime($report['created_at'])); ?>
                    </span>
                </div>
                
                <div class="flex items-start gap-1 text-sm text-gray-600 mb-3 bg-gray-50 p-2 rounded">
                    <span class="material-symbols-outlined text-sm mt-0.5">location_on</span>
                    <span class="flex-1 line-clamp-1"><?php echo htmlspecialchars($report['location'] ?? 'Unknown location'); ?></span>
                </div>
                
                <?php if($report['type'] == 'found' && !empty($report['current_location'])): ?>
                <div class="flex items-start gap-1 text-xs text-gray-500 mb-3">
                    <span class="material-symbols-outlined text-xs">home</span>
                    <span>Currently at: <?php echo htmlspecialchars($report['current_location']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if($report['type'] == 'found' && !empty($report['finder_name'])): ?>
                <div class="flex items-start gap-1 text-xs text-gray-500 mb-3">
                    <span class="material-symbols-outlined text-xs">person</span>
                    <span>Found by: <?php echo htmlspecialchars($report['finder_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-xs text-gray-400">
                        <?php 
                        $time = strtotime($report['created_at']);
                        $diff = time() - $time;
                        if($diff < 3600) {
                            echo floor($diff/60) . " minutes ago";
                        } elseif($diff < 86400) {
                            echo floor($diff/3600) . " hours ago";
                        } else {
                            echo floor($diff/86400) . " days ago";
                        }
                        ?>
                    </span>
                    <a href="child-details.php?id=<?php echo $report['id']; ?>&type=<?php echo $report['type']; ?>" 
                       class="text-[#002045] font-bold text-sm hover:underline flex items-center gap-1">
                        View Details
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Top Reporters Sidebar Section -->
<div class="max-w-7xl mx-auto px-4 md:px-12 pb-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <!-- Quick Stats Card -->
            <div class="bg-gradient-to-r from-[#002045] to-[#0a6c44] rounded-xl p-6 text-white">
                <h3 class="font-bold text-xl mb-2">Need Help?</h3>
                <p class="text-sm opacity-90 mb-4">Our 24/7 emergency hotline is always available</p>
                <a href="tel:112" class="inline-block bg-white text-[#002045] px-6 py-3 rounded-lg font-bold text-lg hover:bg-gray-100 transition-colors">
                    📞 Call 112 Now
                </a>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-[#c4c6cf] overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-[#c4c6cf] bg-gray-50">
                    <h3 class="font-bold text-lg text-[#002045] flex items-center gap-2">
                        <span class="material-symbols-outlined">leaderboard</span>
                        Top Reporters
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if(empty($top_reporters)): ?>
                    <div class="p-4 text-center text-gray-500">No reporters found</div>
                    <?php else: ?>
                        <?php $rank = 1; foreach($top_reporters as $reporter): ?>
                        <div class="p-3 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full <?php echo $rank == 1 ? 'bg-yellow-100 text-yellow-600' : ($rank == 2 ? 'bg-gray-100 text-gray-600' : 'bg-primary/10 text-primary'); ?> flex items-center justify-center font-bold">
                                    <?php echo $rank; ?>
                                </div>
                                <div>
                                    <div class="font-medium text-sm"><?php echo htmlspecialchars($reporter['reporter_name']); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo $reporter['report_count']; ?> reports</div>
                                </div>
                            </div>
                        </div>
                        <?php $rank++; endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>