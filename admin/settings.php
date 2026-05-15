<?php
// admin/settings.php
$page_title = 'Settings';
require_once '../config/database.php';
require_once 'includes/admin-header.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($fullname) || empty($email)) {
        $error_message = "Full name and email are required";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$fullname, $email, $phone, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $fullname;
            $_SESSION['user_email'] = $email;
            $success_message = "Profile updated successfully";
        } else {
            $error_message = "Failed to update profile";
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($current_password, $user['password'])) {
        $error_message = "Current password is incorrect";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            $success_message = "Password updated successfully";
        } else {
            $error_message = "Failed to update password";
        }
    }
}

// Handle system settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system'])) {
    $site_name = trim($_POST['site_name'] ?? 'OkoaWatoto');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '112');
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // Store in session for demo
    $_SESSION['site_name'] = $site_name;
    $_SESSION['emergency_phone'] = $emergency_phone;
    
    $success_message = "System settings updated successfully";
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

require_once 'includes/admin-sidebar.php';
?>

<!-- Dashboard Header - Same as other pages -->
<div class="mb-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-primary">Settings</h1>
            <p class="text-gray-500 text-sm">Manage your account and system preferences</p>
        </div>
    </div>
</div>

<?php if($success_message): ?>
<div class="mb-4 p-2 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm">
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>

<?php if($error_message): ?>
<div class="mb-4 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
    <?php echo htmlspecialchars($error_message); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Profile Settings - Compact -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-base">person</span>
                Profile Settings
            </h2>
        </div>
        <form method="POST" class="p-3 space-y-3">
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Full Name</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" 
                       class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                       class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                       class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Role</label>
                <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled
                       class="w-full px-2 py-1.5 text-sm border rounded-lg bg-gray-100">
            </div>
            <button type="submit" name="update_profile" value="1" 
                    class="w-full bg-primary text-white py-1.5 rounded-lg text-sm hover:bg-primary/90 transition-colors">
                Update Profile
            </button>
        </form>
    </div>
    
    <!-- Password Settings - Compact -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-base">lock</span>
                Change Password
            </h2>
        </div>
        <form method="POST" class="p-3 space-y-3">
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Current Password</label>
                <input type="password" name="current_password" required 
                       class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">New Password</label>
                <input type="password" name="new_password" required 
                       class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1 text-gray-600">Confirm New Password</label>
                <input type="password" name="confirm_password" required 
                       class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit" name="update_password" value="1" 
                    class="w-full bg-yellow-600 text-white py-1.5 rounded-lg text-sm hover:bg-yellow-700 transition-colors">
                Change Password
            </button>
        </form>
    </div>
    
    <!-- System Settings - Full width, compact -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm lg:col-span-2">
        <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-base">settings</span>
                System Settings
            </h2>
        </div>
        <form method="POST" class="p-3 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Site Name</label>
                    <input type="text" name="site_name" value="OkoaWatoto" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1 text-gray-600">Emergency Phone Number</label>
                    <input type="text" name="emergency_phone" value="112" 
                           class="w-full px-2 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-primary">
                </div>
            </div>
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" class="w-3 h-3">
                    <span class="text-sm text-gray-600">Enable Maintenance Mode</span>
                </label>
                <p class="text-xs text-gray-400 mt-1">When enabled, only admins can access the site</p>
            </div>
            <button type="submit" name="update_system" value="1" 
                    class="w-full bg-green-600 text-white py-1.5 rounded-lg text-sm hover:bg-green-700 transition-colors">
                Save System Settings
            </button>
        </form>
    </div>
    
    <!-- System Information - Full width, compact -->
    <!-- <div class="bg-white rounded-lg border border-gray-200 shadow-sm lg:col-span-2">
        <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-base">info</span>
                System Information
            </h2>
        </div>
        <div class="p-3 space-y-2">
            <div class="flex justify-between py-1 border-b text-sm">
                <span class="text-gray-600">PHP Version</span>
                <span class="font-semibold"><?php echo phpversion(); ?></span>
            </div>
            <div class="flex justify-between py-1 border-b text-sm">
                <span class="text-gray-600">Database</span>
                <span class="font-semibold">MySQL / MariaDB</span>
            </div>
            <div class="flex justify-between py-1 border-b text-sm">
                <span class="text-gray-600">Server</span>
                <span class="font-semibold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></span>
            </div>
            <div class="flex justify-between py-1 border-b text-sm">
                <span class="text-gray-600">System Version</span>
                <span class="font-semibold">v2.0.0</span>
            </div>
            <div class="flex justify-between py-1 text-sm">
                <span class="text-gray-600">Last Login</span>
                <span class="font-semibold"><?php echo date('d M Y H:i:s'); ?></span>
            </div>
        </div>
    </div> -->
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php if($success_message): ?>
    Swal.fire({
        icon: 'success', 
        title: 'Success', 
        text: '<?php echo addslashes($success_message); ?>', 
        confirmButtonColor: '#10b981', 
        timer: 2000, 
        showConfirmButton: false
    });
    <?php endif; ?>
    
    <?php if($error_message): ?>
    Swal.fire({ 
        icon: 'error', 
        title: 'Error', 
        text: '<?php echo addslashes($error_message); ?>', 
        confirmButtonColor: '#dc2626' 
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/admin-footer.php'; ?>