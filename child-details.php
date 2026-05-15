<?php
// child-details.php
$page_title = 'Child Details';
require_once 'config/database.php';
require_once 'includes/header.php';

$child_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? 'missing';

if ($child_id <= 0) {
    header("Location: children.php");
    exit();
}

// Fetch child details based on type
if ($type == 'missing') {
    $stmt = $pdo->prepare("SELECT * FROM children_reports WHERE id = ?");
    $stmt->execute([$child_id]);
    $child = $stmt->fetch();
    
    if (!$child) {
        header("Location: children.php");
        exit();
    }
    
    // Get region and district names
    $region_name = '';
    $district_name = '';
    if (!empty($child['region_id'])) {
        $stmt_region = $pdo->prepare("SELECT region_name FROM regions WHERE id = ?");
        $stmt_region->execute([$child['region_id']]);
        $region = $stmt_region->fetch();
        $region_name = $region['region_name'] ?? '';
    }
    if (!empty($child['district_id'])) {
        $stmt_district = $pdo->prepare("SELECT district_name FROM districts WHERE id = ?");
        $stmt_district->execute([$child['district_id']]);
        $district = $stmt_district->fetch();
        $district_name = $district['district_name'] ?? '';
    }
    
    // Get police station name
    $police_station_name = '';
    if (!empty($child['police_station_id'])) {
        $stmt_police = $pdo->prepare("SELECT station_name FROM police_stations WHERE id = ?");
        $stmt_police->execute([$child['police_station_id']]);
        $police = $stmt_police->fetch();
        $police_station_name = $police['station_name'] ?? '';
    }
    
} else {
    $stmt = $pdo->prepare("SELECT * FROM found_reports WHERE id = ?");
    $stmt->execute([$child_id]);
    $child = $stmt->fetch();
    
    if (!$child) {
        header("Location: children.php");
        exit();
    }
    
    // Get region and district names for found reports
    $region_name = '';
    $district_name = '';
    if (!empty($child['region_id'])) {
        $stmt_region = $pdo->prepare("SELECT region_name FROM regions WHERE id = ?");
        $stmt_region->execute([$child['region_id']]);
        $region = $stmt_region->fetch();
        $region_name = $region['region_name'] ?? '';
    }
    if (!empty($child['district_id'])) {
        $stmt_district = $pdo->prepare("SELECT district_name FROM districts WHERE id = ?");
        $stmt_district->execute([$child['district_id']]);
        $district = $stmt_district->fetch();
        $district_name = $district['district_name'] ?? '';
    }
}

// Get similar cases (other missing children in same region)
$similar_cases = [];
if ($type == 'missing' && !empty($child['region_id'])) {
    $stmt_similar = $pdo->prepare("SELECT id, child_name, age, gender, photo, case_number, status, last_seen_location 
                                   FROM children_reports 
                                   WHERE region_id = ? AND id != ? AND status = 'Missing'
                                   ORDER BY created_at DESC LIMIT 3");
    $stmt_similar->execute([$child['region_id'], $child_id]);
    $similar_cases = $stmt_similar->fetchAll();
}
?>

<!-- Back Button -->
<div class="max-w-6xl mx-auto px-4 md:px-8 pt-6">
    <a href="children.php" class="inline-flex items-center gap-2 text-[#002045] hover:underline">
        <span class="material-symbols-outlined">arrow_back</span>
        Back to Children Registry
    </a>
</div>

<div class="max-w-6xl mx-auto px-4 md:px-8 py-6">
    <!-- Status Banner -->
    <div class="mb-6 p-4 rounded-lg <?php 
        echo match($child['status'] ?? $child['health_status'] ?? '') {
            'Missing' => 'bg-red-50 border border-red-200 text-red-700',
            'Found', 'Awaiting ID' => 'bg-yellow-50 border border-yellow-200 text-yellow-700',
            'Reunited' => 'bg-green-50 border border-green-200 text-green-700',
            default => 'bg-gray-50 border border-gray-200'
        };
    ?>">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">
                <?php 
                echo match($child['status'] ?? $child['health_status'] ?? '') {
                    'Missing' => 'warning',
                    'Found', 'Awaiting ID' => 'help',
                    'Reunited' => 'check_circle',
                    default => 'info'
                };
                ?>
            </span>
            <div>
                <span class="font-bold">
                    Case Status: <?php echo $child['status'] ?? $child['health_status'] ?? 'Active'; ?>
                </span>
                <p class="text-sm">
                    Case Number: <?php echo htmlspecialchars($child['case_number']); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Photo -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden sticky top-24 shadow-sm">
                <div class="aspect-square bg-gray-100 flex items-center justify-center">
                    <?php if(!empty($child['photo']) && file_exists('assets/uploads/' . $child['photo'])): ?>
                        <img src="assets/uploads/<?php echo $child['photo']; ?>" 
                             alt="<?php echo htmlspecialchars($child['child_name'] ?? $child['found_child_name'] ?? 'Child'); ?>"
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="text-center p-8">
                            <span class="material-symbols-outlined text-7xl text-gray-300">child_care</span>
                            <p class="text-gray-400 mt-2">No photo available</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status Badge -->
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Case #:</span>
                        <span class="font-mono text-sm font-bold"><?php echo htmlspecialchars($child['case_number']); ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-500">Reported:</span>
                        <span class="text-sm"><?php echo date('d M Y, H:i', strtotime($child['created_at'])); ?></span>
                    </div>
                    <?php if(isset($child['updated_at']) && $child['updated_at'] != $child['created_at']): ?>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-500">Last Updated:</span>
                        <span class="text-sm"><?php echo date('d M Y, H:i', strtotime($child['updated_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="p-4 border-t border-gray-200 space-y-3">
                    <a href="tel:112" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">call</span>
                        Call Police: 112
                    </a>
                    <?php if($type == 'missing'): ?>
                    <button onclick="shareCase()" class="w-full border border-[#002045] text-[#002045] py-3 rounded-lg font-bold hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">share</span>
                        Share Case
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Details -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <!-- Header -->
                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#002045]/5 to-transparent">
                    <h1 class="text-2xl md:text-3xl font-bold text-[#002045]">
                        <?php echo htmlspecialchars($child['child_name'] ?? $child['found_child_name'] ?? 'Unknown Child'); ?>
                    </h1>
                    <div class="flex flex-wrap gap-4 mt-2 text-gray-600">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">cake</span>
                            Age: <?php echo $child['age'] ?? $child['approximate_age'] ?? 'Unknown'; ?> years
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">wc</span>
                            Gender: <?php echo $child['gender'] ?? 'Unknown'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Description -->
                    <?php if(!empty($child['description'])): ?>
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">description</span>
                            Description / Maelezo
                        </h2>
                        <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($child['description'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Clothing -->
                    <?php if(!empty($child['clothing'])): ?>
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">checkroom</span>
                            Clothing Worn / Mavazi
                        </h2>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($child['clothing'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Location Information -->
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">location_on</span>
                            <?php echo isset($child['last_seen_location']) ? 'Last Seen Location' : 'Found Location'; ?>
                        </h2>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <p class="text-gray-700">
                                <strong>Area:</strong> <?php echo htmlspecialchars($child['last_seen_location'] ?? $child['found_location'] ?? 'Unknown'); ?>
                            </p>
                            <?php if($region_name): ?>
                            <p class="text-gray-700">
                                <strong>Region:</strong> <?php echo htmlspecialchars($region_name); ?>
                            </p>
                            <?php endif; ?>
                            <?php if($district_name): ?>
                            <p class="text-gray-700">
                                <strong>District:</strong> <?php echo htmlspecialchars($district_name); ?>
                            </p>
                            <?php endif; ?>
                            <?php if(isset($child['last_seen_date']) && $child['last_seen_date'] && $child['last_seen_date'] != '0000-00-00 00:00:00'): ?>
                            <p class="text-gray-700">
                                <strong>Date & Time:</strong> <?php echo date('F d, Y H:i', strtotime($child['last_seen_date'])); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Reporter Information -->
                    <?php if(isset($child['reporter_name']) && !empty($child['reporter_name'])): ?>
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">contact_support</span>
                            Report Information
                        </h2>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <p><strong>Reported by:</strong> <?php echo htmlspecialchars($child['reporter_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($child['reporter_phone']); ?></p>
                            <?php if(!empty($child['reporter_email'])): ?>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($child['reporter_email']); ?></p>
                            <?php endif; ?>
                            <p><strong>Reporter Type:</strong> <?php echo htmlspecialchars($child['reporter_type'] ?? 'Not specified'); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Police Station -->
                    <?php if(!empty($police_station_name)): ?>
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">local_police</span>
                            Police Station / Kituo cha Polisi
                        </h2>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><?php echo htmlspecialchars($police_station_name); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Emergency Notice -->
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-red-600">emergency</span>
                            <div>
                                <p class="font-bold text-red-700">Have Information About This Child?</p>
                                <p class="text-sm text-red-600 mt-1">
                                    If you have any information about this child, please contact the police immediately.
                                </p>
                                <a href="tel:112" class="inline-flex items-center gap-2 mt-3 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm">
                                    <span class="material-symbols-outlined text-sm">call</span>
                                    Call Police Emergency: 112
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Similar Cases Section -->
    <?php if(!empty($similar_cases)): ?>
    <div class="mt-12">
        <h2 class="text-xl font-bold text-[#002045] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">people</span>
            Other Missing Children in Same Region
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach($similar_cases as $similar): ?>
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="h-32 bg-gray-100 flex items-center justify-center">
                    <?php if(!empty($similar['photo']) && file_exists('assets/uploads/' . $similar['photo'])): ?>
                        <img src="assets/uploads/<?php echo $similar['photo']; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="material-symbols-outlined text-4xl text-gray-300">child_care</span>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <h3 class="font-bold"><?php echo htmlspecialchars($similar['child_name']); ?></h3>
                    <p class="text-sm text-gray-500">Age: <?php echo $similar['age']; ?> | <?php echo $similar['gender']; ?></p>
                    <a href="child-details.php?id=<?php echo $similar['id']; ?>&type=missing" class="text-[#002045] text-sm font-bold hover:underline mt-2 inline-block">
                        View Details →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function shareCase() {
        const url = window.location.href;
        const text = "Please help find this missing child. View details here: ";
        
        if (navigator.share) {
            navigator.share({
                title: 'Missing Child Alert',
                text: text,
                url: url
            }).catch(console.log);
        } else {
            // Fallback - copy to clipboard
            navigator.clipboard.writeText(url);
            alert("Link copied to clipboard! You can share it now.");
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>