<?php
// report-missing.php
$page_title = 'Report Missing Child';
require_once 'config/database.php';

// Start output buffering to prevent header errors
ob_start();

$error = '';
$success = '';

// Fetch police stations for dropdown
$stmt_police = $pdo->query("SELECT id, station_name FROM police_stations ORDER BY station_name");
$police_stations = $stmt_police->fetchAll();

// Fetch regions for dropdown
$stmt_regions = $pdo->query("SELECT id, region_name FROM regions ORDER BY region_name");
$regions = $stmt_regions->fetchAll();

// Fetch all districts for JavaScript (will be used to filter)
$stmt_districts = $pdo->query("SELECT id, region_id, district_name FROM districts ORDER BY district_name");
$all_districts = $stmt_districts->fetchAll();

// Group districts by region_id for easier filtering
$districts_by_region = [];
foreach ($all_districts as $district) {
    $districts_by_region[$district['region_id']][] = $district;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $child_name = trim($_POST['child_name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $clothing = trim($_POST['clothing'] ?? '');
    $last_seen_location = trim($_POST['last_seen_location'] ?? '');
    $last_seen_date = $_POST['last_seen_date'] ?? null;
    $region_id = !empty($_POST['region_id']) ? intval($_POST['region_id']) : null;
    $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $reporter_phone = trim($_POST['reporter_phone'] ?? '');
    $reporter_email = trim($_POST['reporter_email'] ?? '');
    $reporter_type = $_POST['reporter_type'] ?? '';
    $police_station_id = !empty($_POST['police_station_id']) ? intval($_POST['police_station_id']) : null;
    
    // Validate required fields
    if (empty($child_name) || empty($age) || empty($gender) || empty($description) || empty($clothing) || empty($last_seen_location) || empty($last_seen_date) || empty($reporter_name) || empty($reporter_phone)) {
        $error = "Tafadhali jaza sehemu zote zinazohitajika / Please fill all required fields";
    } else {
        $case_number = generateCaseNumber('CASE');
        
        // Handle photo upload
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'assets/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $photo = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
        }
        
        // Insert using correct column names from your database
        $sql = "INSERT INTO children_reports (
                    child_name, age, gender, description, clothing, 
                    health_status, status, last_seen_location, last_seen_date, 
                    photo, reporter_name, reporter_phone, reporter_email, 
                    reporter_type, police_station_id, region_id, district_id, case_number, case_priority
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    'Safe', 'Missing', ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 'High'
                )";
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $child_name, $age, $gender, $description, $clothing,
            $last_seen_location, $last_seen_date, $photo,
            $reporter_name, $reporter_phone, $reporter_email, 
            $reporter_type, $police_station_id, $region_id, $district_id, $case_number
        ];
        
        if ($stmt->execute($params)) {
            // LOG REPORT SUBMISSION
            if (function_exists('logActivity')) {
                logActivity(
                    "Missing Child Report Submitted",
                    "create",
                    "New missing child report submitted for {$child_name}. Case Number: {$case_number}",
                    null,
                    [
                        'child_name' => $child_name,
                        'age' => $age,
                        'gender' => $gender,
                        'location' => $last_seen_location,
                        'case_number' => $case_number,
                        'reporter_name' => $reporter_name
                    ]
                );
            }
            
            // Clear output buffer and redirect
            ob_end_clean();
            $_SESSION['swal_missing_success'] = true;
            $_SESSION['swal_case_number'] = $case_number;
            $_SESSION['swal_child_name'] = $child_name;
            header("Location: report-missing.php");
            exit();
        } else {
            $error = "Failed to submit report. Please try again.";
            // LOG FAILED SUBMISSION
            if (function_exists('logActivity')) {
                logActivity(
                    "Failed Report Submission",
                    "error",
                    "Failed to submit missing child report",
                    ['attempted_data' => $_POST],
                    null
                );
            }
        }
    }
}

// Check for SweetAlert success from session
if (isset($_SESSION['swal_missing_success'])) {
    $success = true;
    $case_number = $_SESSION['swal_case_number'] ?? '';
    $child_name = $_SESSION['swal_child_name'] ?? '';
    unset($_SESSION['swal_missing_success']);
    unset($_SESSION['swal_case_number']);
    unset($_SESSION['swal_child_name']);
}

// Include header after all processing
require_once 'includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-red-50">
            <h1 class="text-2xl font-bold text-red-700">Report a Missing Child</h1>
            <p class="text-gray-600 mt-1">Tafadhali jaza taarifa zote kwa usahihi</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="missingForm" class="p-6 space-y-6">
            <!-- Child Information -->
            <div class="border-b pb-4">
                <h2 class="text-lg font-bold text-[#002045] mb-4">Child's Information / Taarifa za Mtoto</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="child_name" id="child_name" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Age (Years) <span class="text-red-500">*</span></label>
                        <input type="number" name="age" id="age" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" id="gender" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">Select</option>
                            <option value="Male">Male / Kiume</option>
                            <option value="Female">Female / Kike</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Photo</label>
                        <input type="file" name="photo" accept="image/*" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-red-500">
                        <p class="text-xs text-gray-500 mt-1">Upload recent photo if available</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-bold mb-1">Physical Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="3" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Height, skin color, hair style, scars, distinctive features..."></textarea>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-bold mb-1">Clothing Worn <span class="text-red-500">*</span></label>
                    <textarea name="clothing" id="clothing" rows="2" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500" placeholder="What was the child wearing? Color of shirt, pants, shoes, etc."></textarea>
                </div>
            </div>
            
            <!-- Last Seen Information -->
            <div class="border-b pb-4">
                <h2 class="text-lg font-bold text-[#002045] mb-4">Last Seen Information / Mahali Alipoonekana</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Last Seen Location <span class="text-red-500">*</span></label>
                        <input type="text" name="last_seen_location" id="last_seen_location" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Area, Street, Specific place">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Date & Time Last Seen <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="last_seen_date" id="last_seen_date" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                </div>
                
                <!-- Region and District Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Region / Mkoa <span class="text-red-500">*</span></label>
                        <select name="region_id" id="region_id" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500" onchange="updateDistricts()">
                            <option value="">-- Select Region / Chagua Mkoa --</option>
                            <?php foreach($regions as $region): ?>
                            <option value="<?php echo $region['id']; ?>">
                                <?php echo htmlspecialchars($region['region_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">District / Wilaya <span class="text-red-500">*</span></label>
                        <select name="district_id" id="district_id" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">-- First Select Region --</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Reporter Information -->
            <div class="border-b pb-4">
                <h2 class="text-lg font-bold text-[#002045] mb-4">Your Information / Taarifa Zako</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Your Name <span class="text-red-500">*</span></label>
                        <input type="text" name="reporter_name" id="reporter_name" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="reporter_phone" id="reporter_phone" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500" placeholder="07XX XXX XXX">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Email</label>
                        <input type="email" name="reporter_email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Reporter Type <span class="text-red-500">*</span></label>
                        <select name="reporter_type" id="reporter_type" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="Parent">Parent / Mzazi</option>
                            <option value="Police">Police / Polisi</option>
                            <option value="Witness">Witness / Shahidi</option>
                            <option value="Relative">Relative / Jamaa</option>
                            <option value="Teacher">Teacher / Mwalimu</option>
                            <option value="Other">Other / Nyingine</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Police Station Selection -->
            <div>
                <h2 class="text-lg font-bold text-[#002045] mb-4">Police Station / Kituo cha Polisi</h2>
                <div>
                    <label class="block text-sm font-bold mb-1">Select Police Station (Optional)</label>
                    <select name="police_station_id" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">-- Select Police Station / Chagua Kituo --</option>
                        <?php foreach($police_stations as $station): ?>
                        <option value="<?php echo $station['id']; ?>">
                            <?php echo htmlspecialchars($station['station_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Leave empty if you don't know or haven't reported to police yet</p>
                </div>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-700">
                    <span class="material-symbols-outlined align-middle">warning</span>
                    <strong>Important:</strong> Filing a false report is a criminal offense. Please provide accurate information.
                </p>
            </div>
            
            <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">send</span> Submit Missing Child Report
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // PHP Direct - Store all districts data in JavaScript
    const districtsByRegion = <?php 
        $districts_json = [];
        foreach ($districts_by_region as $region_id => $districts) {
            $district_list = [];
            foreach ($districts as $d) {
                $district_list[] = ['id' => $d['id'], 'name' => addslashes($d['district_name'])];
            }
            $districts_json[$region_id] = $district_list;
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
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const regionSelect = document.getElementById('region_id');
        if (regionSelect && regionSelect.value) {
            updateDistricts();
        }
    });
    
    // SweetAlert for PHP error
    <?php if($error && !$success): ?>
    Swal.fire({
        icon: 'error',
        title: 'Hitilafu / Error',
        html: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Jaribu Tena / Try Again',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-xl'
        }
    });
    <?php endif; ?>
    
    // SweetAlert for success after redirect
    <?php if(isset($success) && $success): ?>
    Swal.fire({
        icon: 'success',
        title: 'Ripoti Imetumwa!',
        html: '<strong><?php echo addslashes($child_name); ?></strong><br><br>Case Number: <strong><?php echo $case_number; ?></strong><br><br>Taarifa zako zimepokelewa. Polisi watakwasiliana nawe hivi karibuni.<br>Asante kwa kutusaidia!',
        confirmButtonColor: '#10b981',
        confirmButtonText: 'Sawa / OK',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-xl'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php';
        }
    });
    <?php endif; ?>
    
    // Form validation with SweetAlert before submit
    document.getElementById('missingForm')?.addEventListener('submit', function(e) {
        // Get all required fields
        const childName = document.getElementById('child_name')?.value.trim();
        const age = document.getElementById('age')?.value;
        const gender = document.getElementById('gender')?.value;
        const description = document.getElementById('description')?.value.trim();
        const clothing = document.getElementById('clothing')?.value.trim();
        const lastSeenLocation = document.getElementById('last_seen_location')?.value.trim();
        const lastSeenDate = document.getElementById('last_seen_date')?.value;
        const reporterName = document.getElementById('reporter_name')?.value.trim();
        const reporterPhone = document.getElementById('reporter_phone')?.value.trim();
        const regionId = document.getElementById('region_id')?.value;
        const districtId = document.getElementById('district_id')?.value;
        
        // Check if all required fields are filled
        if (!childName || !age || !gender || !description || !clothing || !lastSeenLocation || !lastSeenDate || !reporterName || !reporterPhone) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Sehemu Hazijajazwa!',
                text: 'Tafadhali jaza sehemu zote zenye nyekundu (*)',
                confirmButtonColor: '#002045',
                confirmButtonText: 'Sawa'
            });
            return false;
        }
        
        // Validate region and district
        if (!regionId || !districtId) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Chagua Mkoa na Wilaya!',
                text: 'Tafadhali chagua mkoa na wilaya',
                confirmButtonColor: '#002045',
                confirmButtonText: 'Sawa'
            });
            return false;
        }
        
        // Validate age
        if (age < 0 || age > 18) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Umri Si Sahihi!',
                text: 'Umri wa mtoto lazima uwe kati ya 0 na 18 miaka',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sawa'
            });
            return false;
        }
        
        // Validate phone number
        const phonePattern = /^[0-9]{9,10}$/;
        const cleanPhone = reporterPhone.replace(/[^0-9]/g, '');
        if (!phonePattern.test(cleanPhone)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Namba ya Simu Si Sahihi!',
                text: 'Tumia namba sahihi ya simu (mfano: 712345678)',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sawa'
            });
            return false;
        }
        
        // Validate last seen date
        const selectedDate = new Date(lastSeenDate);
        const now = new Date();
        if (selectedDate > now) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Tarehe Si Sahihi!',
                text: 'Tarehe ya mwisho kuonekana haiwezi kuwa baada ya leo',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sawa'
            });
            return false;
        }
        
        // Confirm before submission
        e.preventDefault();
        Swal.fire({
            title: 'Thibitisha Ripoti',
            html: 'Je, una uhakika unataka kutuma ripoti hii?<br><br><strong>Mtoto:</strong> ' + childName,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ndio, Tuma',
            cancelButtonText: 'Hapana'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Inatuma Ripoti...',
                    text: 'Tafadhali subiri...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                        document.getElementById('missingForm').submit();
                    }
                });
            }
        });
    });
    
    // Auto-set last seen date
    const dateInput = document.getElementById('last_seen_date');
    if (dateInput && !dateInput.value) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
</script>

<?php require_once 'includes/footer.php'; ?>