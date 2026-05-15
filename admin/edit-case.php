<?php
// admin/edit-case.php
$page_title = 'Edit Case';
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

// Get case ID and type
$case_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$case_type = $_GET['type'] ?? 'missing';

if ($case_id <= 0) {
    header("Location: cases.php");
    exit();
}

// Fetch case data based on type
if ($case_type == 'missing') {
    $stmt = $pdo->prepare("SELECT * FROM children_reports WHERE id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        header("Location: cases.php");
        exit();
    }
    
    // Fetch police stations for dropdown
    $stmt_police = $pdo->query("SELECT id, station_name FROM police_stations ORDER BY station_name");
    $police_stations = $stmt_police->fetchAll();
    
    // Fetch regions for dropdown
    $stmt_regions = $pdo->query("SELECT id, region_name FROM regions ORDER BY region_name");
    $regions = $stmt_regions->fetchAll();
    
    // Fetch all districts for JavaScript (PHP Direct)
    $stmt_districts = $pdo->query("SELECT id, region_id, district_name FROM districts ORDER BY district_name");
    $all_districts = $stmt_districts->fetchAll();
    
    // Group districts by region_id
    $districts_by_region = [];
    foreach ($all_districts as $district) {
        $districts_by_region[$district['region_id']][] = $district;
    }
    
} else {
    $stmt = $pdo->prepare("SELECT * FROM found_reports WHERE id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        header("Location: cases.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($case_type == 'missing') {
        $child_name = trim($_POST['child_name'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $gender = $_POST['gender'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $clothing = trim($_POST['clothing'] ?? '');
        $last_seen_location = trim($_POST['last_seen_location'] ?? '');
        $last_seen_date = $_POST['last_seen_date'] ?? null;
        $region_id = !empty($_POST['region_id']) ? intval($_POST['region_id']) : null;
        $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
        $status = $_POST['status'] ?? 'Missing';
        $case_priority = $_POST['case_priority'] ?? 'Medium';
        $police_station_id = !empty($_POST['police_station_id']) ? intval($_POST['police_station_id']) : null;
        
        // Handle photo upload
        $photo = $case['photo']; // Keep existing photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            // Delete old photo if exists
            if (!empty($case['photo']) && file_exists($upload_dir . $case['photo'])) {
                unlink($upload_dir . $case['photo']);
            }
            $photo = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
        }
        
        $sql = "UPDATE children_reports SET 
                    child_name = ?, age = ?, gender = ?, description = ?, clothing = ?,
                    last_seen_location = ?, last_seen_date = ?, region_id = ?, district_id = ?,
                    status = ?, case_priority = ?, police_station_id = ?, photo = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $child_name, $age, $gender, $description, $clothing,
            $last_seen_location, $last_seen_date, $region_id, $district_id,
            $status, $case_priority, $police_station_id, $photo, $case_id
        ];
        
        if ($stmt->execute($params)) {
            // Log activity
            if (function_exists('logActivity')) {
                logActivity(
                    "Case Updated",
                    "update",
                    "User {$_SESSION['user_name']} updated case {$case['case_number']}",
                    $case,
                    ['child_name' => $child_name, 'status' => $status]
                );
            }
            $_SESSION['success_message'] = "Case updated successfully";
            header("Location: cases.php?type=missing");
            exit();
        } else {
            $error_message = "Failed to update case";
        }
        
    } else {
        // Found case update
        $found_child_name = trim($_POST['found_child_name'] ?? '');
        $approximate_age = intval($_POST['approximate_age'] ?? 0);
        $gender = $_POST['gender'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $health_status = $_POST['health_status'] ?? 'Safe';
        $found_location = trim($_POST['found_location'] ?? '');
        $found_date = $_POST['found_date'] ?? null;
        $current_location = trim($_POST['current_location'] ?? '');
        $status = $_POST['status'] ?? 'Awaiting ID';
        
        // Handle photo upload
        $photo = $case['photo'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            if (!empty($case['photo']) && file_exists($upload_dir . $case['photo'])) {
                unlink($upload_dir . $case['photo']);
            }
            $photo = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
        }
        
        $sql = "UPDATE found_reports SET 
                    found_child_name = ?, approximate_age = ?, gender = ?, description = ?,
                    health_status = ?, found_location = ?, found_date = ?, current_location = ?,
                    status = ?, photo = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $found_child_name, $approximate_age, $gender, $description,
            $health_status, $found_location, $found_date, $current_location,
            $status, $photo, $case_id
        ];
        
        if ($stmt->execute($params)) {
            $_SESSION['success_message'] = "Found case updated successfully";
            header("Location: cases.php?type=found");
            exit();
        } else {
            $error_message = "Failed to update case";
        }
    }
}

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Edit Case</h1>
            <p class="text-gray-500 text-sm">Edit case details - <?php echo $case['case_number']; ?></p>
        </div>
        <a href="cases.php?type=<?php echo $case_type; ?>" class="bg-gray-500 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-gray-600 transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            Back to Cases
        </a>
    </div>
</div>

<?php if(isset($error_message)): ?>
<div class="mb-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <?php if($case_type == 'missing'): ?>
    <!-- Edit Missing Case Form -->
    <form method="POST" enctype="multipart/form-data" class="p-4 space-y-4">
        <!-- Child Information -->
        <div class="border-b border-gray-200 pb-3">
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">child_care</span>
                Child's Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Full Name</label>
                    <input type="text" name="child_name" value="<?php echo htmlspecialchars($case['child_name']); ?>" required 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Age (Years)</label>
                    <input type="number" name="age" value="<?php echo $case['age']; ?>" required 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Gender</label>
                    <select name="gender" required class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="Male" <?php echo $case['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $case['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $case['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Case Priority</label>
                    <select name="case_priority" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="High" <?php echo ($case['case_priority'] ?? 'Medium') == 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Medium" <?php echo ($case['case_priority'] ?? 'Medium') == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="Low" <?php echo ($case['case_priority'] ?? 'Medium') == 'Low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-xs font-bold mb-1 text-gray-600">Physical Description</label>
                <textarea name="description" rows="2" required 
                          class="w-full px-2 py-1.5 text-sm border rounded-lg"><?php echo htmlspecialchars($case['description']); ?></textarea>
            </div>
            <div class="mt-3">
                <label class="block text-xs font-bold mb-1 text-gray-600">Clothing Worn</label>
                <textarea name="clothing" rows="2" required 
                          class="w-full px-2 py-1.5 text-sm border rounded-lg"><?php echo htmlspecialchars($case['clothing']); ?></textarea>
            </div>
        </div>
        
        <!-- Location Information - PHP Direct -->
        <div class="border-b border-gray-200 pb-3">
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">location_on</span>
                Location Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Last Seen Location</label>
                    <input type="text" name="last_seen_location" value="<?php echo htmlspecialchars($case['last_seen_location']); ?>" required 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Last Seen Date & Time</label>
                    <input type="datetime-local" name="last_seen_date" value="<?php echo date('Y-m-d\TH:i', strtotime($case['last_seen_date'])); ?>" required 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Region / Mkoa</label>
                    <select name="region_id" id="region_id" class="w-full px-2 py-1.5 text-sm border rounded-lg" onchange="updateDistricts()">
                        <option value="">-- Select Region / Chagua Mkoa --</option>
                        <?php foreach($regions as $region): ?>
                        <option value="<?php echo $region['id']; ?>" <?php echo $case['region_id'] == $region['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($region['region_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">District / Wilaya</label>
                    <select name="district_id" id="district_id" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="">-- Select District / Chagua Wilaya --</option>
                        <?php 
                        // Show current district options if region is selected
                        if (!empty($case['region_id']) && isset($districts_by_region[$case['region_id']])) {
                            foreach ($districts_by_region[$case['region_id']] as $district) {
                                $selected = ($case['district_id'] == $district['id']) ? 'selected' : '';
                                echo '<option value="' . $district['id'] . '" ' . $selected . '>' . htmlspecialchars($district['district_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Case Status -->
        <div class="border-b border-gray-200 pb-3">
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">info</span>
                Case Status
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Status</label>
                    <select name="status" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="Missing" <?php echo $case['status'] == 'Missing' ? 'selected' : ''; ?>>Missing</option>
                        <option value="Found" <?php echo $case['status'] == 'Found' ? 'selected' : ''; ?>>Found</option>
                        <option value="Reunited" <?php echo $case['status'] == 'Reunited' ? 'selected' : ''; ?>>Reunited</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Police Station</label>
                    <select name="police_station_id" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="">-- Select Police Station --</option>
                        <?php foreach($police_stations as $station): ?>
                        <option value="<?php echo $station['id']; ?>" <?php echo $case['police_station_id'] == $station['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($station['station_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Photo -->
        <div>
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">photo_camera</span>
                Child's Photo
            </h2>
            <?php if(!empty($case['photo']) && file_exists('../assets/uploads/' . $case['photo'])): ?>
            <div class="mb-3">
                <img src="../assets/uploads/<?php echo $case['photo']; ?>" class="w-32 h-32 object-cover rounded-lg border">
                <p class="text-xs text-gray-500 mt-1">Current photo</p>
            </div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/*" class="w-full px-2 py-1.5 text-sm border rounded-lg">
            <p class="text-xs text-gray-500 mt-1">Leave empty to keep current photo</p>
        </div>
        
        <div class="flex gap-3 pt-3">
            <button type="submit" class="bg-primary text-white px-4 py-1.5 rounded-lg text-sm hover:bg-primary/90 transition-colors">
                Save Changes
            </button>
            <a href="cases.php?type=missing" class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg text-sm hover:bg-gray-300 transition-colors">
                Cancel
            </a>
        </div>
    </form>
    
    <?php else: ?>
    <!-- Edit Found Case Form -->
    <form method="POST" enctype="multipart/form-data" class="p-4 space-y-4">
        <div class="border-b border-gray-200 pb-3">
            <h2 class="text-sm font-bold text-primary mb-3 flex-items-center gap-2">
                <span class="material-symbols-outlined text-base">child_care</span>
                Child's Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Child's Name (if known)</label>
                    <input type="text" name="found_child_name" value="<?php echo htmlspecialchars($case['found_child_name']); ?>" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Approximate Age</label>
                    <input type="number" name="approximate_age" value="<?php echo $case['approximate_age']; ?>" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Gender</label>
                    <select name="gender" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="">Select</option>
                        <option value="Male" <?php echo $case['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $case['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Health Status</label>
                    <select name="health_status" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                        <option value="Safe" <?php echo $case['health_status'] == 'Safe' ? 'selected' : ''; ?>>Safe</option>
                        <option value="Injured" <?php echo $case['health_status'] == 'Injured' ? 'selected' : ''; ?>>Injured</option>
                        <option value="In Danger" <?php echo $case['health_status'] == 'In Danger' ? 'selected' : ''; ?>>In Danger</option>
                        <option value="Sick" <?php echo $case['health_status'] == 'Sick' ? 'selected' : ''; ?>>Sick</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-xs font-bold mb-1 text-gray-600">Description</label>
                <textarea name="description" rows="2" class="w-full px-2 py-1.5 text-sm border rounded-lg"><?php echo htmlspecialchars($case['description']); ?></textarea>
            </div>
        </div>
        
        <div class="border-b border-gray-200 pb-3">
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">location_on</span>
                Location Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Found Location</label>
                    <input type="text" name="found_location" value="<?php echo htmlspecialchars($case['found_location']); ?>" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Found Date & Time</label>
                    <input type="datetime-local" name="found_date" value="<?php echo date('Y-m-d\TH:i', strtotime($case['found_date'])); ?>" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold mb-1 text-gray-600">Current Location (where child is now)</label>
                    <input type="text" name="current_location" value="<?php echo htmlspecialchars($case['current_location']); ?>" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg">
                </div>
            </div>
        </div>
        
        <div>
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">info</span>
                Case Status
            </h2>
            <select name="status" class="w-full px-2 py-1.5 text-sm border rounded-lg">
                <option value="Awaiting ID" <?php echo $case['status'] == 'Awaiting ID' ? 'selected' : ''; ?>>Awaiting ID</option>
                <option value="Reunited" <?php echo $case['status'] == 'Reunited' ? 'selected' : ''; ?>>Reunited</option>
                <option value="In Care" <?php echo $case['status'] == 'In Care' ? 'selected' : ''; ?>>In Care</option>
            </select>
        </div>
        
        <div>
            <h2 class="text-sm font-bold text-primary mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">photo_camera</span>
                Child's Photo
            </h2>
            <?php if(!empty($case['photo']) && file_exists('../assets/uploads/' . $case['photo'])): ?>
            <div class="mb-3">
                <img src="../assets/uploads/<?php echo $case['photo']; ?>" class="w-32 h-32 object-cover rounded-lg border">
                <p class="text-xs text-gray-500 mt-1">Current photo</p>
            </div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/*" class="w-full px-2 py-1.5 text-sm border rounded-lg">
        </div>
        
        <div class="flex gap-3 pt-3">
            <button type="submit" class="bg-primary text-white px-4 py-1.5 rounded-lg text-sm hover:bg-primary/90 transition-colors">
                Save Changes
            </button>
            <a href="cases.php?type=found" class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg text-sm hover:bg-gray-300 transition-colors">
                Cancel
            </a>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // PHP Direct - Store all districts data in JavaScript
    const districtsByRegion = <?php 
        $districts_json = [];
        if (isset($districts_by_region)) {
            foreach ($districts_by_region as $region_id => $districts) {
                $district_list = [];
                foreach ($districts as $d) {
                    $district_list[] = ['id' => $d['id'], 'name' => addslashes($d['district_name'])];
                }
                $districts_json[$region_id] = $district_list;
            }
        }
        echo json_encode($districts_json);
    ?>;
    
    // Function to update districts based on selected region
    function updateDistricts() {
        const regionSelect = document.getElementById('region_id');
        const districtSelect = document.getElementById('district_id');
        const selectedRegionId = regionSelect.value;
        
        // Clear current options
        districtSelect.innerHTML = '<option value="">-- Select District / Chagua Wilaya --</option>';
        
        if (selectedRegionId && districtsByRegion[selectedRegionId]) {
            const districts = districtsByRegion[selectedRegionId];
            for (let i = 0; i < districts.length; i++) {
                const option = document.createElement('option');
                option.value = districts[i].id;
                option.textContent = districts[i].name;
                districtSelect.appendChild(option);
            }
        }
    }
    
    <?php if(isset($_SESSION['success_message'])): ?>
    Swal.fire({
        icon: 'success', 
        title: 'Success', 
        text: '<?php echo addslashes($_SESSION['success_message']); ?>', 
        confirmButtonColor: '#10b981', 
        timer: 2000, 
        showConfirmButton: false
    });
    <?php unset($_SESSION['success_message']); endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>