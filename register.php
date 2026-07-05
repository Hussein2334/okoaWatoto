<?php
// register.php
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$fullname = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
        $error = "Tafadhali jaza sehemu zote / Please fill all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Barua pepe si sahihi / Invalid email format";
    } elseif (!preg_match('/^[0-9]{9,10}$/', $phone)) {
        $error = "Namba ya simu si sahihi (Tumia 712345678) / Invalid phone number";
    } elseif (strlen($password) < 6) {
        $error = "Nenosiri lazima iwe angalau herufi 6 / Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Nenosiri hazilingani / Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Barua pepe tayari imesajiliwa / Email already registered";
        } else {
            // Check if phone already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = "Namba ya simu tayari imesajiliwa / Phone already registered";
            } else {
                // Default role: user
                $role = 'user';
                
                // Hash password and insert user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                
                if ($stmt->execute([$fullname, $email, $phone, $hashed_password, $role])) {
                    // Log registration
                    if (function_exists('logActivity')) {
                        logActivity(
                            "User Registration",
                            "create",
                            "New user registered: {$fullname} ({$email})",
                            null,
                            ['fullname' => $fullname, 'email' => $email, 'role' => $role]
                        );
                    }
                    
                    $success = "Usajili umefanikiwa! Tafadhali ingia / Registration successful! Please login.";
                    // Clear form
                    $fullname = $email = $phone = '';
                    $_POST = [];
                } else {
                    $error = "Kuna hitilafu, tafadhali jaribu tena / An error occurred, please try again";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="sw">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>OkoaWatoto - Jisajili</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <!-- SweetAlert2 CSS and JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "outline": "#74777f",
                        "surface-dim": "#d7dadc",
                        "primary": "#002045",
                        "secondary-container": "#9ff5c1",
                        "primary-fixed-dim": "#adc7f7",
                        "tertiary-fixed-dim": "#ffb3ad",
                        "surface-container-highest": "#e0e3e5",
                        "on-tertiary-fixed": "#410004",
                        "on-primary-container": "#86a0cd",
                        "inverse-primary": "#adc7f7",
                        "surface-container-low": "#f1f4f6",
                        "secondary-fixed-dim": "#83d8a6",
                        "on-error": "#ffffff",
                        "on-surface-variant": "#43474e",
                        "surface-container-lowest": "#ffffff",
                        "on-primary": "#ffffff",
                        "error-container": "#ffdad6",
                        "on-secondary-container": "#167249",
                        "secondary": "#0a6c44",
                        "surface-container": "#ebeef0",
                        "surface-container-high": "#e5e9eb",
                        "on-secondary-fixed-variant": "#005231",
                        "tertiary-fixed": "#ffdad7",
                        "outline-variant": "#c4c6cf",
                        "on-tertiary-fixed-variant": "#930013",
                        "on-primary-fixed": "#001b3c",
                        "on-tertiary-container": "#ff736c",
                        "tertiary": "#4b0005",
                        "surface-tint": "#455f88",
                        "on-error-container": "#93000a",
                        "inverse-on-surface": "#eef1f3",
                        "background": "#f7fafc",
                        "primary-fixed": "#d6e3ff",
                        "tertiary-container": "#73000c",
                        "inverse-surface": "#2d3133",
                        "error": "#ba1a1a",
                        "on-background": "#181c1e",
                        "on-surface": "#181c1e",
                        "surface-variant": "#e0e3e5",
                        "surface-bright": "#f7fafc",
                        "primary-container": "#1a365d",
                        "on-tertiary": "#ffffff",
                        "on-secondary-fixed": "#002111",
                        "on-secondary": "#ffffff",
                        "surface": "#f7fafc",
                        "secondary-fixed": "#9ff5c1",
                        "on-primary-fixed-variant": "#2d476f"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "spacing": {
                        "md": "24px",
                        "xs": "8px",
                        "gutter": "16px",
                        "xl": "64px",
                        "base": "4px",
                        "margin-mobile": "16px",
                        "sm": "16px",
                        "margin-desktop": "48px",
                        "lg": "40px"
                    },
                    "fontFamily": {
                        "headline-md": ["Public Sans"],
                        "headline-lg": ["Public Sans"],
                        "swahili-alt": ["Public Sans"],
                        "headline-lg-mobile": ["Public Sans"],
                        "body-lg": ["Public Sans"],
                        "body-md": ["Public Sans"],
                        "label-caps": ["Public Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                        "swahili-alt": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "headline-lg-mobile": ["26px", {"lineHeight": "32px", "fontWeight": "700"}],
                        "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700"}]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Public Sans', sans-serif; background-color: #f7fafc; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="flex flex-col min-h-screen text-on-surface">

<!-- Top Navigation Bar -->
<header class="sticky top-0 z-50 bg-surface-container-lowest border-b border-outline-variant flex justify-between items-center w-full px-margin-mobile md:px-margin-desktop py-xs">
    <a href="index.php" class="flex items-center gap-xs">
        <span class="font-headline-md text-headline-md font-bold text-primary">OkoaWatoto</span>
    </a>
    <div class="flex items-center gap-md">
        <a href="login.php" class="flex items-center gap-xs px-sm py-xs border border-outline rounded-lg font-label-caps text-label-caps hover:bg-surface-container-low transition-colors">
            <span class="material-symbols-outlined" style="font-size: 18px;">login</span>
            Ingia / Login
        </a>
    </div>
</header>

<main class="flex-grow flex flex-col md:flex-row">
    <!-- Left Side: Visual Anchor -->
    <section class="hidden md:flex md:w-1/2 relative bg-primary-container overflow-hidden items-center justify-center p-xl">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover opacity-60" alt="Family and children in Tanzania" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAYekvWJGIhG3Y2fvGsb8J5KBZFoPQbM8rMRw6T2zFfC1Ri6O34u1xZ1UUPsqbGbhs-FdmBoOXhqurRq7bnuQm7dcxjLUutOgFVprrZZpo0qRXfYpTn-eM4ZGPJSe1vu7C6eG-0Eop_v4PWUdDFgAoNjDqj4ecN_Y1TWhLPRSucMqUNOYE97hIJjtOpGbH2EeTVseB6uNhcoBPDJlN1WETJHD5S7cShcAzXIDLNhozY7379cPdfgnEYP1Wk9Ohju4_Wm_kwOH6VsxXb"/>
            <div class="absolute inset-0 bg-gradient-to-t from-primary-container via-transparent to-transparent"></div>
        </div>
        <div class="relative z-10 max-w-lg text-center md:text-left">
            <h1 class="font-headline-lg text-headline-lg text-white mb-md leading-tight">
                Jiunge na Juhudi zetu za Kulinda Watoto
            </h1>
            <p class="font-body-lg text-body-lg text-primary-fixed-dim">
                Kila mtoto anastahili usalama. Jiunge nasi leo kusaidia kuwalinda na kuwaunganisha watoto wa Tanzania na familia zao.
            </p>
        </div>
    </section>
    
    <!-- Right Side: Registration Form -->
    <section class="w-full md:w-1/2 flex items-center justify-center p-margin-mobile md:p-xl bg-surface">
        <div class="w-full max-w-md">
            <div class="mb-lg">
                <h2 class="font-headline-md text-headline-md text-primary mb-xs">Fungua Akaunti</h2>
                <p class="font-swahili-alt text-swahili-alt text-on-surface-variant">Tafadhali jaza maelezo yako hapa chini ili kuanza.</p>
            </div>
            
            <form method="POST" action="" id="registerForm" class="space-y-gutter">
                <!-- Hidden account type - default citizen -->
                <input type="hidden" name="account_type" value="citizen">
                
                <!-- Progress indicator -->
                <div class="flex gap-xs mb-md">
                    <div class="h-1 flex-grow bg-primary rounded-full"></div>
                    <div class="h-1 flex-grow bg-outline-variant rounded-full"></div>
                </div>
                
                <!-- Full Name -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant uppercase" for="fullname">Jina Kamili</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-outline">
                            <span class="material-symbols-outlined text-lg">person</span>
                        </span>
                        <input class="w-full pl-10 pr-4 py-sm rounded-lg border border-outline-variant bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-body-md" 
                               id="fullname" 
                               name="fullname" 
                               placeholder="Mfano: Juma Shabaan" 
                               type="text"
                               value="<?php echo htmlspecialchars($fullname); ?>"
                               required/>
                    </div>
                </div>
                
                <!-- Email -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant uppercase" for="email">Barua Pepe</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-outline">
                            <span class="material-symbols-outlined text-lg">mail</span>
                        </span>
                        <input class="w-full pl-10 pr-4 py-sm rounded-lg border border-outline-variant bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-body-md" 
                               id="email" 
                               name="email" 
                               placeholder="barua@mfano.go.tz" 
                               type="email"
                               value="<?php echo htmlspecialchars($email); ?>"
                               required/>
                    </div>
                </div>
                
                <!-- Phone Number -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant uppercase" for="phone">Namba ya Simu (Tanzania)</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-md rounded-l-lg border border-r-0 border-outline-variant bg-surface-container text-on-surface-variant font-body-md">+255</span>
                        <input class="w-full px-md py-sm rounded-r-lg border border-outline-variant bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-body-md" 
                               id="phone" 
                               name="phone" 
                               placeholder="712 345 678" 
                               type="tel"
                               value="<?php echo htmlspecialchars($phone); ?>"
                               required/>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant uppercase" for="password">Nywila</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-outline">
                            <span class="material-symbols-outlined text-lg">lock</span>
                        </span>
                        <input class="w-full pl-10 pr-10 py-sm rounded-lg border border-outline-variant bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-body-md" 
                               id="password" 
                               name="password" 
                               placeholder="••••••••" 
                               type="password"
                               required/>
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary" type="button" onclick="togglePassword('password', 'password-icon')">
                            <span class="material-symbols-outlined text-lg" id="password-icon">visibility</span>
                        </button>
                    </div>
                    <p class="font-swahili-alt text-[11px] text-on-surface-variant italic">Tumia angalau herufi 6 zenye namba na alama.</p>
                </div>
                
                <!-- Confirm Password -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant uppercase" for="confirm_password">Thibitisha Nywila</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-outline">
                            <span class="material-symbols-outlined text-lg">lock</span>
                        </span>
                        <input class="w-full pl-10 pr-10 py-sm rounded-lg border border-outline-variant bg-white focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-body-md" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="••••••••" 
                               type="password"
                               required/>
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary" type="button" onclick="togglePassword('confirm_password', 'confirm-icon')">
                            <span class="material-symbols-outlined text-lg" id="confirm-icon">visibility</span>
                        </button>
                    </div>
                </div>
                
                <!-- Action Button -->
                <button class="w-full py-md px-xl bg-primary text-white rounded-lg font-headline-md text-headline-md hover:bg-primary/90 transition-all active:scale-95 shadow-md flex items-center justify-center gap-2" type="submit">
                    <span class="material-symbols-outlined">how_to_reg</span>
                    Jisajili Sasa
                </button>
                
                <!-- Footer Links -->
                <div class="pt-md text-center">
                    <p class="font-swahili-alt text-swahili-alt text-on-surface-variant">
                        Tayari una akaunti? 
                        <a class="text-primary font-bold hover:underline ml-xs" href="login.php">Ingia Hapa</a>
                    </p>
                </div>
            </form>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="w-full px-margin-mobile md:px-margin-desktop py-lg grid grid-cols-1 md:grid-cols-2 gap-gutter border-t border-outline-variant bg-surface-container-highest">
    <div>
        <div class="font-headline-md text-headline-md font-bold text-primary mb-sm">OkoaWatoto</div>
        <p class="font-swahili-alt text-swahili-alt text-on-surface-variant">© 2026 Jamhuri ya Muungano wa Tanzania. Huduma ya Umma.</p>
    </div>
    <div class="flex flex-wrap gap-md md:justify-end items-center">
        <a class="font-swahili-alt text-swahili-alt text-on-surface-variant hover:text-primary transition-colors underline" href="tel:112">Msaada: 112</a>
        <a class="font-swahili-alt text-swahili-alt text-on-surface-variant hover:text-primary transition-colors underline" href="#">Sera ya Faragha</a>
        <a class="font-swahili-alt text-swahili-alt text-on-surface-variant hover:text-primary transition-colors underline" href="#">Tovuti Kuu ya Serikali</a>
        <a class="font-swahili-alt text-swahili-alt text-on-surface-variant hover:text-primary transition-colors underline" href="#">Vituo vya Polisi</a>
    </div>
</footer>

<script>
    function togglePassword(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            passwordInput.type = 'password';
            icon.textContent = 'visibility';
        }
    }
    
    // SweetAlert for PHP messages
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
    
    <?php if($success): ?>
    Swal.fire({
        icon: 'success',
        title: 'Usajili Umefanikiwa!',
        html: '<?php echo addslashes($success); ?>',
        confirmButtonColor: '#0a6c44',
        confirmButtonText: 'Ingia Sasa / Login Now',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-xl'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'login.php';
        }
    });
    <?php endif; ?>
    
    // Form validation with SweetAlert before submit
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        const fullname = document.getElementById('fullname')?.value.trim();
        const email = document.getElementById('email')?.value.trim();
        const phone = document.getElementById('phone')?.value.trim();
        const password = document.getElementById('password')?.value;
        const confirm_password = document.getElementById('confirm_password')?.value;
        
        if (!fullname || !email || !phone || !password || !confirm_password) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Sehemu Hazijajazwa!',
                text: 'Tafadhali jaza sehemu zote / Please fill all fields',
                confirmButtonColor: '#002045',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        const emailPattern = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
        if (!emailPattern.test(email)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Barua Pepe Si Sahihi!',
                text: 'Tafadhali ingiza barua pepe sahihi / Please enter valid email',
                confirmButtonColor: '#ba1a1a',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        const phonePattern = /^[0-9]{9,10}$/;
        if (!phonePattern.test(phone)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Namba ya Simu Si Sahihi!',
                text: 'Tumia namba sahihi ya simu (mfano: 712345678)',
                confirmButtonColor: '#ba1a1a',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Nenosiri Fupi Sana!',
                text: 'Nenosiri lazima iwe angalau herufi 6 / Password must be at least 6 characters',
                confirmButtonColor: '#ba1a1a',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        if (password !== confirm_password) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Nenosiri Hazilingani!',
                text: 'Tafadhali hakikisha nenosiri linalingana / Passwords do not match',
                confirmButtonColor: '#ba1a1a',
                confirmButtonText: 'Sawa / OK'
            });
            return false;
        }
        
        // Show loading alert before submission
        e.preventDefault();
        Swal.fire({
            title: 'Inasajili...',
            text: 'Tafadhali subiri / Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                // Submit the form after a short delay
                setTimeout(() => {
                    document.getElementById('registerForm').submit();
                }, 500);
            }
        });
    });
</script>

</body>
</html>