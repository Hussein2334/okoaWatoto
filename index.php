<?php
// index.php
$page_title = 'Home';
require_once 'config/database.php';
require_once 'includes/header.php';

// Fetch recent reports
$stmt = $pdo->query("SELECT * FROM children_reports WHERE status = 'Missing' ORDER BY created_at DESC LIMIT 4");
$missing_children = $stmt->fetchAll();

$stmt2 = $pdo->query("SELECT * FROM found_reports WHERE status = 'Awaiting ID' ORDER BY created_at DESC LIMIT 2");
$found_children = $stmt2->fetchAll();

// =============================================
// STATISTICS ZA KWELI KUTOKA DATABASE
// =============================================

// 1. Children Reunited - Hesabu watoto walio na status 'Reunited'
$stmt_reunited = $pdo->query("SELECT COUNT(*) as count FROM children_reports WHERE status = 'Reunited'");
$children_reunited = $stmt_reunited->fetch()['count'];

// 2. Active Cases - Hesabu watoto walio na status 'Missing'
$stmt_active = $pdo->query("SELECT COUNT(*) as count FROM children_reports WHERE status = 'Missing'");
$active_cases = $stmt_active->fetch()['count'];

// 3. Total Reports - Jumla ya ripoti zote
$stmt_total = $pdo->query("SELECT COUNT(*) as count FROM children_reports");
$total_reports = $stmt_total->fetch()['count'];

// 4. Found Children (Awaiting ID)
$stmt_found = $pdo->query("SELECT COUNT(*) as count FROM found_reports WHERE status = 'Awaiting ID'");
$found_count = $stmt_found->fetch()['count'];

// 5. Reunification Rate (%) - Asilimia ya watoto waliounganishwa
$reunification_rate = $total_reports > 0 ? round(($children_reunited / $total_reports) * 100) : 0;
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

<!-- Stats Section - NUMBERS ZA KWELI KUTOKA DATABASE -->
<section class="py-12 px-4 md:px-12 border-y border-[#c4c6cf] bg-gray-50">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#0a6c44] mb-2"><?php echo number_format($children_reunited); ?></div>
            <div class="text-[#43474e]">Children Reunited</div>
            <div class="text-xs text-gray-400 mt-1">Watoto Wameunganishwa</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#ba1a1a] mb-2"><?php echo number_format($active_cases); ?></div>
            <div class="text-[#43474e]">Active Cases</div>
            <div class="text-xs text-gray-400 mt-1">Kesi Amilifu</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#002045] mb-2"><?php echo number_format($found_count); ?></div>
            <div class="text-[#43474e]">Found (Awaiting ID)</div>
            <div class="text-xs text-gray-400 mt-1">Waliopatikana</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-[#c4c6cf]">
            <div class="text-3xl font-bold text-[#002045] mb-2"><?php echo $reunification_rate; ?>%</div>
            <div class="text-[#43474e]">Reunification Rate</div>
            <div class="text-xs text-gray-400 mt-1">Kiwango cha Kuunganishwa</div>
        </div>
    </div>
</section>

<!-- Recent Missing Children -->
<section class="py-12 px-4 md:px-12">
    <div class="flex justify-between items-end mb-6">
        <h2 class="text-2xl font-bold text-[#002045]">Recent Missing Cases</h2>
        <a href="children.php?status=missing" class="text-[#002045] font-bold text-sm hover:underline">View All →</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php if(empty($missing_children)): ?>
            <div class="col-span-full text-center py-8 text-gray-500">No missing children reports found</div>
        <?php else: ?>
            <?php foreach($missing_children as $child): ?>
            <div class="bg-white border-2 border-red-500/50 rounded-xl overflow-hidden shadow-sm hover:border-red-500 transition-colors relative">
                <div class="absolute bg-red-600 text-white text-xs px-2 py-1 m-2 rounded z-10">MISSING</div>
                <div class="h-48 w-full bg-gray-200 flex items-center justify-center">
                    <?php if(!empty($child['photo']) && file_exists('assets/uploads/' . $child['photo'])): ?>
                        <img src="assets/uploads/<?php echo $child['photo']; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="material-symbols-outlined text-6xl text-gray-400">child_care</span>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($child['child_name']); ?></h3>
                    <div class="text-sm text-gray-600">Age: <?php echo $child['age']; ?> years</div>
                    <div class="text-sm text-gray-600">Last seen: <?php echo htmlspecialchars($child['last_seen_location']); ?></div>
                    <div class="mt-3 pt-3 border-t flex justify-between">
                        <span class="text-sm text-gray-500">Case: <?php echo htmlspecialchars($child['case_number']); ?></span>
                        <a href="child-details.php?id=<?php echo $child['id']; ?>&type=missing" class="text-[#002045] font-bold text-sm">View Details →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>