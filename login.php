<?php
// login.php
require_once 'config/database.php';

if (isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Tafadhali jaza sehemu zote";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // LOG SUCCESSFUL LOGIN
            if (function_exists('logActivity')) {
                logActivity(
                    "User Login",
                    "login",
                    "User {$user['fullname']} logged in successfully from IP: {$_SERVER['REMOTE_ADDR']}",
                    null,
                    ['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]
                );
            }
            
            $success = "Login successful! Redirecting...";
            
            // Store user info for SweetAlert
            $_SESSION['swal_success'] = true;
            $_SESSION['swal_user_name'] = $user['fullname'];
            $_SESSION['swal_user_role'] = $user['role'];
            
            if ($user['role'] === 'admin' || $user['role'] === 'staff') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            // LOG FAILED LOGIN ATTEMPT
            if (function_exists('logActivity')) {
                logActivity(
                    "Failed Login Attempt",
                    "error",
                    "Failed login attempt for email/phone: $email from IP: {$_SERVER['REMOTE_ADDR']}",
                    ['attempted_email' => $email],
                    null
                );
            }
            $error = "Barua pepe au nenosiri si sahihi";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="sw">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - OkoaWatoto Child Protection System</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <!-- SweetAlert2 CSS and JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        body { font-family: 'Public Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f7fafc] text-[#181c1e] min-h-screen flex flex-col">

<!-- Top Navigation - Simple without language switcher -->
<header class="w-full flex justify-between items-center px-4 md:px-12 py-3 bg-white border-b border-gray-200">
    <a href="index.php" class="font-bold text-2xl text-[#002045] hover:opacity-80 transition-colors">
        OkoaWatoto
    </a>
    <a href="index.php" class="text-[#002045] hover:bg-gray-100 px-3 py-2 rounded-lg transition-colors flex items-center gap-1">
        <span class="material-symbols-outlined">arrow_back</span>
        <span>Back to Home</span>
    </a>
</header>

<main class="flex-grow flex flex-col md:flex-row">
    <!-- Visual Anchor Column -->
    <section class="hidden md:flex md:w-1/2 relative overflow-hidden bg-[#002045]">
        <img alt="Community" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDrtUdQe0-RxqUigk7ldgqYZLJ3PsWrklGSnZVTvM8Kn64agGdCA6ObewysHiv5IE2crSG3PQW3F_lymCazLkLlduzEeLsbJQCNDKg58MPEVmMtPX4wlVOVv346U2bcum1QzgJbXpBChicHoy9maHQErAPaJ2529y5S6Aj_Y7ptkjPWDK1jFg4QE4nR6TMKvOmVk6oBz9Em3tiDW9QnAPzg8eM2Lux32nigZoPZMg8UhHBOF_smbgOXU-w3Ye-7cZLxJKKlJn3f_4Zp"/>
        <div class="relative z-10 flex flex-col justify-end p-8 bg-gradient-to-t from-[#002045]/80 to-transparent w-full h-full text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Kulinda Kesho Yetu.</h1>
            <p class="text-lg max-w-md opacity-90">Tunaimarisha usalama wa watoto kupitia teknolojia na ushirikiano wa kijamii nchini Tanzania.</p>
        </div>
    </section>
    
    <!-- Form Column -->
    <section class="flex-grow flex items-center justify-center px-4 md:px-8 py-8 bg-[#f7fafc]">
        <div class="w-full max-w-md space-y-6">
            <div class="text-center md:text-left">
                <h2 class="text-2xl md:text-3xl font-bold text-[#002045] mb-1">Welcome Back</h2>
                <p class="text-[#43474e]">Karibu tena. Tafadhali ingia ili kuendelea na huduma.</p>
            </div>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-[#43474e] mb-1" for="email">
                        Email au Namba ya Simu / Email or Phone
                    </label>
                    <input class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/20 outline-none transition-all bg-white" 
                           id="email" 
                           name="email" 
                           placeholder="mfano: admin@okoawatoto.com au 0712345678" 
                           type="text" 
                           required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"/>
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase text-[#43474e] mb-1" for="password">
                        Nenosiri / Password
                    </label>
                    <div class="relative">
                        <input class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/20 outline-none transition-all bg-white" 
                               id="password" 
                               name="password" 
                               placeholder="••••••••" 
                               type="password" 
                               required/>
                        <button class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-[#002045]" type="button" onclick="togglePassword()">
                            <span class="material-symbols-outlined" id="password-icon">visibility</span>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between py-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input class="w-4 h-4 rounded border-gray-300 text-[#002045] focus:ring-[#002045]" 
                               type="checkbox" 
                               name="remember" 
                               id="remember"
                               <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>/>
                        <span class="text-sm text-[#43474e] group-hover:text-[#181c1e]">Nikumbuke / Remember Me</span>
                    </label>
                    <a class="text-sm text-[#002045] hover:underline" href="forgot-password.php">Forgot Password?</a>
                </div>
                
                <button class="w-full bg-[#002045] text-white py-3 rounded-lg font-bold text-base shadow-sm hover:bg-blue-900 active:scale-[0.98] transition-all flex items-center justify-center gap-2" 
                        type="submit">
                    <span class="material-symbols-outlined">login</span>
                    Ingia / Log In
                </button>
            </form>
            
            <div class="relative py-3">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-[#f7fafc] px-3 text-[#43474e]">Au / Or</span>
                </div>
            </div>
            
            <div class="space-y-3">
                <a href="report-missing.php" class="w-full border-2 border-[#002045] text-[#002045] py-3 rounded-lg font-semibold hover:bg-[#002045]/5 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">person_search</span>
                    Ripoti kama Mgeni / Report as Guest
                </a>
                <p class="text-center text-sm text-[#43474e]">
                    Huna akaunti? / Don't have an account? 
                    <a class="text-[#002045] font-bold hover:underline" href="register.php">Jisajili Hapa / Register Here</a>
                </p>
            </div>
        </div>
    </section>
</main>

<!-- Simple Footer -->
<footer class="w-full px-4 md:px-12 py-6 grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-200 bg-gray-50">
    <div>
        <div class="font-bold text-xl text-[#002045] mb-1">OkoaWatoto</div>
        <p class="text-xs text-[#43474e]">© 2024 Jamhuri ya Muungano wa Tanzania. Huduma ya Umma.</p>
    </div>
    <div class="flex flex-wrap gap-4 items-start md:justify-end">
        <a href="tel:112" class="text-xs text-[#43474e] hover:text-[#002045] underline">Msaada: 112</a>
        <a href="#" class="text-xs text-[#43474e] hover:text-[#002045] underline">Sera ya Faragha</a>
        <a href="index.php" class="text-xs text-[#43474e] hover:text-[#002045] underline">Nyumbani</a>
    </div>
</footer>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('password-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            passwordInput.type = 'password';
            icon.textContent = 'visibility';
        }
    }
    
    // SweetAlert for PHP error messages
    <?php if($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Hitilafu / Error',
        html: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#ba1a1a',
        confirmButtonText: 'Jaribu Tena / Try Again',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-xl'
        }
    });
    <?php endif; ?>
    
    <?php if(isset($_SESSION['swal_success']) && $_SESSION['swal_success']): ?>
    <?php 
    $user_name = $_SESSION['swal_user_name'] ?? '';
    $user_role = $_SESSION['swal_user_role'] ?? '';
    $redirect_url = ($user_role === 'admin' || $user_role === 'staff') ? 'admin/dashboard.php' : 'index.php';
    unset($_SESSION['swal_success']);
    unset($_SESSION['swal_user_name']);
    unset($_SESSION['swal_user_role']);
    ?>
    Swal.fire({
        icon: 'success',
        title: 'Karibu Sana!',
        html: 'Karibu <?php echo addslashes($user_name); ?>!<br>Unaingizwa kwenye mfumo...',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false,
        background: '#ffffff',
        didOpen: () => {
            Swal.showLoading();
        },
        willClose: () => {
            window.location.href = '<?php echo $redirect_url; ?>';
        }
    });
    <?php endif; ?>
    
    // Form validation with SweetAlert before submit
    document.getElementById('loginForm')?.addEventListener('submit', function(e) {
        const email = document.getElementById('email')?.value.trim();
        const password = document.getElementById('password')?.value;
        
        if (!email || !password) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Sehemu Hazijajazwa!',
                text: 'Tafadhali jaza barua pepe na nenosiri / Please fill email and password',
                confirmButtonColor: '#002045',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        // Show loading alert before submission
        e.preventDefault();
        Swal.fire({
            title: 'Inaingiza...',
            text: 'Tafadhali subiri / Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                // Submit the form after a short delay
                setTimeout(() => {
                    document.getElementById('loginForm').submit();
                }, 500);
            }
        });
    });
</script>

</body>
</html>