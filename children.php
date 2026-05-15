<?php
// children.php
$page_title = 'Children Registry';
require_once 'config/database.php';
require_once 'includes/header.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$region_filter = $_GET['region'] ?? '';
$gender_filter = $_GET['gender'] ?? '';
$age_min = $_GET['age_min'] ?? '';
$age_max = $_GET['age_max'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query for counting
$count_sql = "SELECT COUNT(*) as total FROM children_reports WHERE 1=1";
$data_sql = "SELECT * FROM children_reports WHERE 1=1";
$params = [];

// Apply filters
if ($status_filter !== 'all') {
    $count_sql .= " AND status = ?";
    $data_sql .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $count_sql .= " AND (child_name LIKE ? OR case_number LIKE ? OR last_seen_location LIKE ? OR description LIKE ?)";
    $data_sql .= " AND (child_name LIKE ? OR case_number LIKE ? OR last_seen_location LIKE ? OR description LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($region_filter)) {
    $count_sql .= " AND last_seen_location LIKE ?";
    $data_sql .= " AND last_seen_location LIKE ?";
    $params[] = "%$region_filter%";
}

if (!empty($gender_filter)) {
    $count_sql .= " AND gender = ?";
    $data_sql .= " AND gender = ?";
    $params[] = $gender_filter;
}

if (!empty($age_min)) {
    $count_sql .= " AND age >= ?";
    $data_sql .= " AND age >= ?";
    $params[] = (int)$age_min;
}

if (!empty($age_max)) {
    $count_sql .= " AND age <= ?";
    $data_sql .= " AND age <= ?";
    $params[] = (int)$age_max;
}

// Get total count for pagination
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);

// Get data with pagination
$data_sql .= " ORDER BY created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$children = $stmt->fetchAll();

// Get statistics for counters
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Missing' THEN 1 ELSE 0 END) as missing,
    SUM(CASE WHEN status = 'Found' THEN 1 ELSE 0 END) as found,
    SUM(CASE WHEN status = 'Reunited' THEN 1 ELSE 0 END) as reunited
    FROM children_reports";
$stats = $pdo->query($stats_sql)->fetch();

// Get unique regions for filter
$regions_sql = "SELECT DISTINCT last_seen_location FROM children_reports WHERE last_seen_location IS NOT NULL AND last_seen_location != '' ORDER BY last_seen_location LIMIT 20";
$regions = $pdo->query($regions_sql)->fetchAll();

// Get recent cases for sidebar
$recent_sql = "SELECT * FROM children_reports ORDER BY created_at DESC LIMIT 5";
$recent_cases = $pdo->query($recent_sql)->fetchAll();
?>

<!-- Hero Section -->
<section class="relative w-full bg-white py-12 px-4 md:px-12 border-b border-[#c4c6cf]">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-[#002045] mb-4">Children Registry</h1>
            <p class="text-lg text-[#43474e] max-w-2xl mx-auto">
                Sajili ya Watoto Wanaotafutwa, Waliopatikana na Waliounganishwa tena na Familia Zao
            </p>
        </div>
        
        <!-- Advanced Search Form -->
        <form method="GET" action="" class="bg-white rounded-xl shadow-lg p-6 border border-[#c4c6cf]">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#43474e]">search</span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search by name, case number, or description..."
                           class="w-full pl-10 pr-4 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/20 bg-white">
                </div>
                
                <select name="status" class="px-4 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] bg-white">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="Missing" <?php echo $status_filter == 'Missing' ? 'selected' : ''; ?>>Missing - Wanaotafutwa</option>
                    <option value="Found" <?php echo $status_filter == 'Found' ? 'selected' : ''; ?>>Found - Waliopatikana</option>
                    <option value="Reunited" <?php echo $status_filter == 'Reunited' ? 'selected' : ''; ?>>Reunited - Wameunganishwa</option>
                </select>
                
                <select name="region" class="px-4 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] bg-white">
                    <option value="">All Regions</option>
                    <?php foreach($regions as $region): ?>
                    <option value="<?php echo htmlspecialchars($region['last_seen_location']); ?>" 
                            <?php echo $region_filter == $region['last_seen_location'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($region['last_seen_location']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <select name="gender" class="px-4 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] bg-white">
                    <option value="">All Genders</option>
                    <option value="Male" <?php echo $gender_filter == 'Male' ? 'selected' : ''; ?>>Male / Kiume</option>
                    <option value="Female" <?php echo $gender_filter == 'Female' ? 'selected' : ''; ?>>Female / Kike</option>
                </select>
                
                <div class="flex gap-2">
                    <input type="number" name="age_min" value="<?php echo htmlspecialchars($age_min); ?>" 
                           placeholder="Min Age" class="w-1/2 px-3 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] bg-white">
                    <input type="number" name="age_max" value="<?php echo htmlspecialchars($age_max); ?>" 
                           placeholder="Max Age" class="w-1/2 px-3 py-3 border border-[#c4c6cf] rounded-lg focus:border-[#002045] bg-white">
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-[#002045] text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-900 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">search</span>
                        Search
                    </button>
                    <?php if(!empty($search_query) || !empty($region_filter) || $status_filter !== 'all' || !empty($gender_filter) || !empty($age_min) || !empty($age_max)): ?>
                    <a href="children.php" class="px-6 py-3 bg-gray-200 text-[#43474e] rounded-lg font-bold hover:bg-gray-300 transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined">clear</span>
                        Clear
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Statistics Summary -->
<section class="py-12 px-4 md:px-12 border-y border-[#c4c6cf] bg-gray-50">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
                <div class="text-3xl font-bold text-[#002045] mb-2"><?php echo number_format($stats['total']); ?></div>
                <div class="text-[#43474e]">Total Reports</div>
            </div>
            <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
                <div class="text-3xl font-bold text-[#ba1a1a] mb-2"><?php echo number_format($stats['missing']); ?></div>
                <div class="text-[#43474e]">Missing</div>
            </div>
            <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
                <div class="text-3xl font-bold text-[#eab308] mb-2"><?php echo number_format($stats['found']); ?></div>
                <div class="text-[#43474e]">Found</div>
            </div>
            <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
                <div class="text-3xl font-bold text-[#0a6c44] mb-2"><?php echo number_format($stats['reunited']); ?></div>
                <div class="text-[#43474e]">Reunited</div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content Area with Sidebar -->
<div class="max-w-7xl mx-auto px-4 md:px-12 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content - Children Grid -->
        <div class="flex-1">
            <div class="flex justify-between items-center mb-6">
                <p class="text-[#43474e]">
                    Showing <span class="font-bold"><?php echo count($children); ?></span> of 
                    <span class="font-bold"><?php echo number_format($total_records); ?></span> children
                </p>
            </div>
            
            <?php if(empty($children)): ?>
            <div class="text-center py-16 bg-white rounded-xl border border-[#c4c6cf]">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">child_care</span>
                <h3 class="text-xl font-bold text-[#181c1e] mb-2">No children found</h3>
                <p class="text-[#43474e] mb-4">Try adjusting your search or filter criteria</p>
                <a href="children.php" class="inline-flex items-center gap-2 text-[#002045] font-bold hover:underline">
                    <span class="material-symbols-outlined">refresh</span>
                    Reset all filters
                </a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach($children as $child): ?>
                <div class="bg-white border-2 <?php echo match($child['status']) {
                    'Missing' => 'border-red-500/50',
                    'Found' => 'border-yellow-500/50',
                    'Reunited' => 'border-green-500/50',
                    default => 'border-[#c4c6cf]'
                }; ?> rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all relative">
                    <div class="absolute top-3 left-3 z-10">
                        <span class="<?php echo match($child['status']) {
                            'Missing' => 'bg-[#ba1a1a] text-white',
                            'Found' => 'bg-[#eab308] text-white',
                            'Reunited' => 'bg-[#0a6c44] text-white',
                            default => 'bg-gray-500 text-white'
                        }; ?> text-xs font-bold px-2 py-1 rounded-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">
                                <?php echo $child['status'] == 'Missing' ? 'warning' : ($child['status'] == 'Found' ? 'help' : 'check_circle'); ?>
                            </span>
                            <?php echo $child['status']; ?>
                        </span>
                    </div>
                    
                    <div class="absolute top-3 right-3 z-10 bg-black/60 text-white text-xs px-2 py-1 rounded-md font-mono">
                        <?php echo htmlspecialchars($child['case_number']); ?>
                    </div>
                    
                    <div class="h-48 w-full bg-gray-100 overflow-hidden relative">
                        <?php if(!empty($child['photo']) && file_exists('assets/uploads/' . $child['photo'])): ?>
                            <img src="assets/uploads/<?php echo $child['photo']; ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-6xl text-gray-300">child_care</span>
                            </div>
                        <?php endif; ?>
                        <div class="absolute bottom-3 right-3 bg-black/70 text-white text-sm px-2 py-1 rounded-full">
                            <?php echo $child['age'] ?? '?'; ?> yrs
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="font-bold text-lg text-[#181c1e] mb-1"><?php echo htmlspecialchars($child['child_name'] ?? 'Unknown'); ?></h3>
                        <div class="text-sm text-[#43474e] mb-3">📍 <?php echo htmlspecialchars($child['last_seen_location'] ?? 'Unknown'); ?></div>
                        <div class="mt-3 pt-3 border-t border-[#c4c6cf] flex justify-between">
                            <span class="text-xs text-[#43474e]"><?php echo date('d M Y', strtotime($child['created_at'])); ?></span>
                            <a href="child-details.php?id=<?php echo $child['id']; ?>&type=missing" class="text-[#002045] font-bold text-sm">View Details →</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY)); ?>" class="px-4 py-2 border border-[#c4c6cf] rounded-lg hover:bg-gray-50">Previous</a>
                <?php endif; ?>
                <?php for($i = 1; $i <= min(5, $total_pages); $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY)); ?>" class="px-4 py-2 <?php echo $page == $i ? 'bg-[#002045] text-white' : 'border border-[#c4c6cf] hover:bg-gray-50'; ?> rounded-lg"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY)); ?>" class="px-4 py-2 border border-[#c4c6cf] rounded-lg hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="lg:w-80 space-y-6">
            <div class="bg-white rounded-xl border border-[#c4c6cf] p-5">
                <h3 class="font-bold text-lg text-[#002045] mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="report-missing.php" class="w-full bg-[#ba1a1a] text-white px-4 py-3 rounded-lg font-bold hover:bg-red-700 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">warning</span> Report Missing
                    </a>
                    <a href="report-found.php" class="w-full bg-[#0a6c44] text-white px-4 py-3 rounded-lg font-bold hover:bg-green-700 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">volunteer_activism</span> I Found a Child
                    </a>
                </div>
            </div>
            
            <div class="bg-red-50 rounded-xl border border-red-200 p-5">
                <h3 class="font-bold text-lg text-[#ba1a1a] mb-3">Emergency: 112</h3>
                <p class="text-sm text-[#43474e]">Call immediately if you see a missing child</p>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<section class="py-12 px-4 md:px-12 bg-gradient-to-r from-[#002045] to-[#0a6c44] mt-8">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-2xl font-bold text-white mb-3">Have Information About a Child?</h2>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="report-missing.php" class="bg-white text-[#ba1a1a] px-6 py-3 rounded-lg font-bold">Report Missing</a>
            <a href="report-found.php" class="bg-white text-[#0a6c44] px-6 py-3 rounded-lg font-bold">I Found a Child</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>