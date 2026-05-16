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
    $stmt = $pdo->prepare("SELECT 
        cr.*, 
        r.region_name,
        d.district_name,
        ps.station_name as police_station
        FROM children_reports cr
        LEFT JOIN regions r ON cr.region_id = r.id
        LEFT JOIN districts d ON cr.district_id = d.id
        LEFT JOIN police_stations ps ON cr.police_station_id = ps.id
        WHERE cr.id = ?");
    $stmt->execute([$child_id]);
    $child = $stmt->fetch();
    
    if (!$child) {
        header("Location: children.php");
        exit();
    }
    
    // Get similar cases
    $stmt_similar = $pdo->prepare("SELECT id, child_name, age, gender, photo, case_number, status, last_seen_location 
                                   FROM children_reports 
                                   WHERE id != ? AND status = 'Missing'
                                   ORDER BY created_at DESC LIMIT 3");
    $stmt_similar->execute([$child_id]);
    $similar_cases = $stmt_similar->fetchAll();
    
} else {
    // Found report
    $stmt = $pdo->prepare("SELECT * FROM found_reports WHERE id = ?");
    $stmt->execute([$child_id]);
    $child = $stmt->fetch();
    
    if (!$child) {
        header("Location: children.php");
        exit();
    }
    
    $similar_cases = [];
}

// Helper function to get status color
function getStatusColor($status, $type) {
    if ($type == 'missing') {
        return match($status) {
            'Missing' => 'bg-red-50 border-red-200 text-red-700',
            'Found' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
            'Reunited' => 'bg-green-50 border-green-200 text-green-700',
            default => 'bg-gray-50 border-gray-200'
        };
    } else {
        return match($status) {
            'Safe' => 'bg-green-50 border-green-200 text-green-700',
            'Injured' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
            'In Danger' => 'bg-red-50 border-red-200 text-red-700',
            'Awaiting ID' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
            'Reunited' => 'bg-green-50 border-green-200 text-green-700',
            default => 'bg-gray-50 border-gray-200'
        };
    }
}

function getStatusIcon($status, $type) {
    if ($type == 'missing') {
        return match($status) {
            'Missing' => 'warning',
            'Found' => 'help',
            'Reunited' => 'check_circle',
            default => 'info'
        };
    } else {
        return match($status) {
            'Safe' => 'check_circle',
            'Injured' => 'healing',
            'In Danger' => 'warning',
            'Awaiting ID' => 'help',
            'Reunited' => 'check_circle',
            default => 'info'
        };
    }
}
?>

<!-- Back Button -->
<div class="max-w-6xl mx-auto px-4 md:px-8 pt-6">
    <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-[#002045] hover:underline">
        <span class="material-symbols-outlined">arrow_back</span>
        Back
    </a>
</div>

<div class="max-w-6xl mx-auto px-4 md:px-8 py-6">
    <!-- Status Banner -->
    <div class="mb-6 p-4 rounded-lg <?php echo getStatusColor($child['status'] ?? $child['health_status'] ?? '', $type); ?>">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">
                <?php echo getStatusIcon($child['status'] ?? $child['health_status'] ?? '', $type); ?>
            </span>
            <div>
                <span class="font-bold">
                    Case Status: <?php echo $type == 'missing' ? $child['status'] : ($child['status'] ?? $child['health_status'] ?? 'Active'); ?>
                </span>
                <p class="text-sm">
                    Case Number: <?php echo htmlspecialchars($child['case_number']); ?>
                </p>
                <?php if($type == 'found' && !empty($child['current_location'])): ?>
                <p class="text-sm mt-1">
                    📍 Currently at: <?php echo htmlspecialchars($child['current_location']); ?>
                </p>
                <?php endif; ?>
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
                
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Case #:</span>
                            <span class="font-mono text-sm font-bold"><?php echo htmlspecialchars($child['case_number']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Reported:</span>
                            <span class="text-sm"><?php echo date('d M Y, H:i', strtotime($child['created_at'])); ?></span>
                        </div>
                        <?php if($type == 'found' && !empty($child['finder_name'])): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Found by:</span>
                            <span class="text-sm"><?php echo htmlspecialchars($child['finder_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($type == 'found' && !empty($child['finder_phone'])): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Finder Phone:</span>
                            <span class="text-sm"><?php echo htmlspecialchars($child['finder_phone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
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
                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#002045]/5 to-transparent">
                    <h1 class="text-2xl md:text-3xl font-bold text-[#002045]">
                        <?php echo htmlspecialchars($type == 'missing' ? ($child['child_name'] ?? 'Unknown Child') : ($child['found_child_name'] ?? 'Unknown Child')); ?>
                    </h1>
                    <div class="flex flex-wrap gap-4 mt-2 text-gray-600">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">cake</span>
                            Age: <?php echo $type == 'missing' ? ($child['age'] ?? 'Unknown') : ($child['approximate_age'] ?? 'Unknown'); ?> years
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
                    
                    <!-- Clothing (Only for missing) -->
                    <?php if($type == 'missing' && !empty($child['clothing'])): ?>
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
                            <?php echo $type == 'missing' ? 'Last Seen Location' : 'Found Location'; ?>
                        </h2>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <p class="text-gray-700">
                                <strong>Location:</strong> <?php echo htmlspecialchars($type == 'missing' ? $child['last_seen_location'] : $child['found_location']); ?>
                            </p>
                            <?php if($type == 'missing' && !empty($child['last_seen_date'])): ?>
                            <p class="text-gray-700">
                                <strong>Date & Time:</strong> <?php echo date('F d, Y H:i', strtotime($child['last_seen_date'])); ?>
                            </p>
                            <?php endif; ?>
                            <?php if($type == 'found' && !empty($child['found_date'])): ?>
                            <p class="text-gray-700">
                                <strong>Date Found:</strong> <?php echo date('F d, Y H:i', strtotime($child['found_date'])); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Reporter/Finder Information -->
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">contact_support</span>
                            <?php echo $type == 'missing' ? 'Report Information' : 'Finder Information'; ?>
                        </h2>
                        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
                            <?php if($type == 'missing'): ?>
                                <p><strong>Reported by:</strong> <?php echo htmlspecialchars($child['reporter_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($child['reporter_phone']); ?></p>
                                <?php if(!empty($child['reporter_email'])): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($child['reporter_email']); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p><strong>Found by:</strong> <?php echo htmlspecialchars($child['finder_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($child['finder_phone']); ?></p>
                                <?php if(!empty($child['finder_email'])): ?>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($child['finder_email']); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Police Station (for missing) -->
                    <?php if($type == 'missing' && !empty($child['police_station'])): ?>
                    <div>
                        <h2 class="text-lg font-bold text-[#002045] mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined">local_police</span>
                            Police Station
                        </h2>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><?php echo htmlspecialchars($child['police_station']); ?></p>
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
    
    <!-- Similar Cases (Only for missing) -->
    <?php if($type == 'missing' && !empty($similar_cases)): ?>
    <div class="mt-12">
        <h2 class="text-xl font-bold text-[#002045] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">people</span>
            Other Missing Children
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
        if (navigator.share) {
            navigator.share({
                title: 'Missing Child Alert',
                text: 'Please help find this missing child',
                url: url
            }).catch(console.log);
        } else {
            navigator.clipboard.writeText(url);
            alert("Link copied to clipboard! You can share it now.");
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>