<?php
// report-found.php
$page_title = 'Report Found Child';
require_once 'config/database.php';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $found_child_name = trim($_POST['found_child_name'] ?? '');
    $approximate_age = !empty($_POST['approximate_age']) ? intval($_POST['approximate_age']) : null;
    $gender = $_POST['gender'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $health_status = $_POST['health_status'] ?? 'Safe';
    $found_location = trim($_POST['found_location'] ?? '');
    $found_date = $_POST['found_date'] ?? null;
    $finder_name = trim($_POST['finder_name'] ?? '');
    $finder_phone = trim($_POST['finder_phone'] ?? '');
    $finder_email = trim($_POST['finder_email'] ?? '');
    $current_location = trim($_POST['current_location'] ?? '');
    
    // Validation
    if (empty($found_location) || empty($found_date) || empty($finder_name) || empty($finder_phone)) {
        $error = "Tafadhali jaza sehemu zote zinazohitajika (Location, Date, Your Name, Phone)";
    } else {
        $case_number = generateCaseNumber('FOUND');
        
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
        
        $sql = "INSERT INTO found_reports (found_child_name, approximate_age, gender, description, health_status, found_location, found_date, photo, finder_name, finder_phone, finder_email, current_location, case_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $params = [
            !empty($found_child_name) ? $found_child_name : 'Unknown',
            $approximate_age,
            $gender,
            $description,
            $health_status,
            $found_location,
            $found_date,
            $photo,
            $finder_name,
            $finder_phone,
            $finder_email,
            $current_location,
            $case_number
        ];
        
        if ($stmt->execute($params)) {
            // LOG REPORT SUBMISSION
            if (function_exists('logActivity')) {
                logActivity(
                    "Found Child Report Submitted",
                    "create",
                    "New found child report submitted. Case Number: {$case_number}",
                    null,
                    [
                        'found_location' => $found_location,
                        'case_number' => $case_number,
                        'finder_name' => $finder_name
                    ]
                );
            }
            
            $_SESSION['success_message'] = "Found report submitted! Case Number: $case_number. Police will contact you.";
            
            // Store data for SweetAlert
            $_SESSION['swal_found_success'] = true;
            $_SESSION['swal_case_number'] = $case_number;
            
            header("Location: report-found.php");
            exit();
        } else {
            $error = "Failed to submit report. Please try again.";
            // LOG FAILED SUBMISSION
            if (function_exists('logActivity')) {
                logActivity(
                    "Failed Found Report Submission",
                    "error",
                    "Failed to submit found child report",
                    ['attempted_data' => $_POST],
                    null
                );
            }
        }
    }
}

// Check for SweetAlert success from session
if (isset($_SESSION['swal_found_success'])) {
    $success = true;
    $case_number = $_SESSION['swal_case_number'] ?? '';
    unset($_SESSION['swal_found_success']);
    unset($_SESSION['swal_case_number']);
}
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-green-50">
            <h1 class="text-2xl font-bold text-green-700">I Found a Child</h1>
            <p class="text-gray-600 mt-1">Nimepata Mtoto Aliyepotea - Tafadhali jaza fomu hii</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="foundForm" class="p-6 space-y-6">
            <!-- Child Information -->
            <div>
                <h2 class="text-lg font-bold text-[#002045] mb-4">Child's Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Child's Name (if known)</label>
                        <input type="text" name="found_child_name" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Unknown if not known">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Approximate Age</label>
                        <input type="number" name="approximate_age" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Gender</label>
                        <select name="gender" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">Select</option>
                            <option value="Male">Male / Kiume</option>
                            <option value="Female">Female / Kike</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Photo</label>
                        <input type="file" name="photo" accept="image/*" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-bold mb-1">Description (clothing, features)</label>
                    <textarea name="description" rows="3" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Rangi ya ngozi, mavazi, alama za kipekee..."></textarea>
                </div>
                
                <div class="mt-4">
                    <label class="block text-sm font-bold mb-1">Health Status</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-green-50 transition-colors has-[:checked]:bg-green-100 has-[:checked]:border-green-500">
                            <input type="radio" name="health_status" value="Safe" class="mr-2" checked> Safe / Salama
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-yellow-50 transition-colors has-[:checked]:bg-yellow-100 has-[:checked]:border-yellow-500">
                            <input type="radio" name="health_status" value="Injured" class="mr-2"> Injured / Majeruhi
                        </label>
                        <label class="flex items-center justify-center p-3 border rounded-lg cursor-pointer hover:bg-red-50 transition-colors has-[:checked]:bg-red-100 has-[:checked]:border-red-500">
                            <input type="radio" name="health_status" value="In Danger" class="mr-2"> In Danger / Hatari
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Location Information -->
            <div class="border-t pt-4">
                <h2 class="text-lg font-bold text-[#002045] mb-4">Where Found</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Found Location <span class="text-red-500">*</span></label>
                        <input type="text" name="found_location" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Area, District, City">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Date & Time Found <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="found_date" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-1">Current Location (where child is now)</label>
                        <input type="text" name="current_location" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="e.g., Police station, Children's home">
                    </div>
                </div>
            </div>
            
            <!-- Finder Information -->
            <div class="border-t pt-4">
                <h2 class="text-lg font-bold text-[#002045] mb-4">Your Contact Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold mb-1">Your Name <span class="text-red-500">*</span></label>
                        <input type="text" name="finder_name" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Phone Number <span class="text-red-500">*</span></label>
                        <input type="tel" name="finder_phone" required class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500" placeholder="07XX XXX XXX">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold mb-1">Email</label>
                        <input type="email" name="finder_email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-700">
                    <span class="material-symbols-outlined align-middle">check_circle</span>
                    <strong>Thank you for helping!</strong> The child will be kept safe while we try to find their family.
                </p>
            </div>
            
            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">volunteer_activism</span> Submit Found Report
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // SweetAlert for PHP error
    <?php if($error): ?>
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
        title: 'Asante Sana!',
        html: 'Ripoti yako imetumwa kwa mafanikio!<br>Case Number: <strong><?php echo $case_number; ?></strong><br><br>Polisi watakwasiliana nawe hivi karibuni.',
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
    document.getElementById('foundForm')?.addEventListener('submit', function(e) {
        const foundLocation = document.querySelector('input[name="found_location"]')?.value.trim();
        const foundDate = document.querySelector('input[name="found_date"]')?.value;
        const finderName = document.querySelector('input[name="finder_name"]')?.value.trim();
        const finderPhone = document.querySelector('input[name="finder_phone"]')?.value.trim();
        
        if (!foundLocation || !foundDate || !finderName || !finderPhone) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Sehemu Hazijajazwa!',
                text: 'Tafadhali jaza sehemu zote zenye nyekundu (*) / Please fill all required fields',
                confirmButtonColor: '#002045',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        // Validate phone number
        const phonePattern = /^[0-9]{9,10}$/;
        if (finderPhone && !phonePattern.test(finderPhone.replace(/[^0-9]/g, ''))) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Namba ya Simu Si Sahihi!',
                text: 'Tumia namba sahihi ya simu (mfano: 712345678) / Please enter valid phone number',
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        // Show loading alert before submission
        e.preventDefault();
        Swal.fire({
            title: 'Inatuma Ripoti...',
            text: 'Tafadhali subiri / Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                setTimeout(() => {
                    document.getElementById('foundForm').submit();
                }, 500);
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>