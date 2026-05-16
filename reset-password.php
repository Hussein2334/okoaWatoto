<?php
// reset-password.php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Verify token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user && $token) {
    $error = "Kiungo hiki ni batili au kimeisha muda wake. Tafadhali omba tena reset.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 8) {
        $error = "Nenosiri lazima iwe angalau herufi 8";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Nenosiri lazima liwe na angalau namba moja (0-9)";
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "Nenosiri lazima liwe na angalau alama maalum moja (!@#$%^&*)";
    } elseif ($password !== $confirm_password) {
        $error = "Nenosiri hazilingani";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user['id']])) {
            if (function_exists('logActivity')) {
                logActivity(
                    "Password Reset Successful",
                    "update",
                    "User {$user['fullname']} successfully reset password",
                    null,
                    ['user_id' => $user['id']]
                );
            }
            $success = "Nenosiri limebadilishwa kwa mafanikio! Tafadhali ingia kwa nenosiri lako jipya.";
        } else {
            $error = "Kuna hitilafu, tafadhali jaribu tena.";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="sw">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>OkoaWatoto - Weka Nywila Mpya</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Public Sans', sans-serif;
            background-color: #f7fafc;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }
    </style>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "tertiary-fixed-dim": "#ffb3ad",
                    "primary": "#002045",
                    "tertiary-fixed": "#ffdad7",
                    "on-tertiary-fixed-variant": "#930013",
                    "on-surface": "#181c1e",
                    "on-error-container": "#93000a",
                    "secondary-fixed-dim": "#83d8a6",
                    "primary-container": "#1a365d",
                    "on-secondary-fixed-variant": "#005231",
                    "on-tertiary-fixed": "#410004",
                    "on-secondary": "#ffffff",
                    "secondary-container": "#9ff5c1",
                    "on-secondary-fixed": "#002111",
                    "on-primary-fixed-variant": "#2d476f",
                    "surface-container-lowest": "#ffffff",
                    "on-error": "#ffffff",
                    "error": "#ba1a1a",
                    "secondary": "#0a6c44",
                    "on-primary-fixed": "#001b3c",
                    "surface-container-low": "#f1f4f6",
                    "primary-fixed-dim": "#adc7f7",
                    "background": "#f7fafc",
                    "tertiary": "#4b0005",
                    "surface-container": "#ebeef0",
                    "secondary-fixed": "#9ff5c1",
                    "on-tertiary-container": "#ff736c",
                    "outline": "#74777f",
                    "on-secondary-container": "#167249",
                    "on-background": "#181c1e",
                    "tertiary-container": "#73000c",
                    "inverse-primary": "#adc7f7",
                    "surface-container-high": "#e5e9eb",
                    "surface-tint": "#455f88",
                    "on-primary": "#ffffff",
                    "primary-fixed": "#d6e3ff",
                    "inverse-on-surface": "#eef1f3",
                    "surface-variant": "#e0e3e5",
                    "outline-variant": "#c4c6cf",
                    "surface-container-highest": "#e0e3e5",
                    "on-tertiary": "#ffffff",
                    "on-primary-container": "#86a0cd",
                    "surface-dim": "#d7dadc",
                    "on-surface-variant": "#43474e",
                    "surface-bright": "#f7fafc",
                    "inverse-surface": "#2d3133",
                    "surface": "#f7fafc",
                    "error-container": "#ffdad6"
            },
            "borderRadius": {
                    "DEFAULT": "0.125rem",
                    "lg": "0.25rem",
                    "xl": "0.5rem",
                    "full": "0.75rem"
            },
            "spacing": {
                    "margin-mobile": "16px",
                    "xl": "64px",
                    "gutter": "16px",
                    "xs": "8px",
                    "md": "24px",
                    "sm": "16px",
                    "base": "4px",
                    "margin-desktop": "48px",
                    "lg": "40px"
            },
            "fontFamily": {
                    "headline-lg-mobile": ["Public Sans"],
                    "label-caps": ["Public Sans"],
                    "swahili-alt": ["Public Sans"],
                    "body-lg": ["Public Sans"],
                    "headline-md": ["Public Sans"],
                    "headline-lg": ["Public Sans"],
                    "body-md": ["Public Sans"]
            },
            "fontSize": {
                    "headline-lg-mobile": ["26px", {"lineHeight": "32px", "fontWeight": "700"}],
                    "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700"}],
                    "swahili-alt": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                    "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                    "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}]
            }
          },
        },
      }
    </script>
</head>
<body class="bg-background text-on-surface min-h-screen flex flex-col">

<!-- TopNavBar -->
<header class="bg-surface border-b border-outline-variant flex justify-between items-center w-full px-margin-desktop py-md max-w-full mx-auto fixed top-0 z-50">
    <div class="flex items-center gap-xs">
        <span class="material-symbols-outlined text-primary" style="font-size: 32px;">shield_person</span>
        <span class="font-headline-md text-headline-md font-bold text-primary">OkoaWatoto</span>
    </div>
    <div class="flex items-center gap-md">
        <a href="login.php" class="flex items-center gap-xs text-primary hover:text-secondary transition-colors duration-200 font-medium">
            <span class="material-symbols-outlined">login</span>
            <span class="font-body-md text-body-md">Ingia / Login</span>
        </a>
    </div>
</header>

<!-- Main Content Canvas -->
<main class="flex-grow flex flex-col md:flex-row pt-[72px]">
    <!-- Left Panel: Evocative Image -->
    <section class="hidden md:flex md:w-1/2 relative overflow-hidden bg-primary items-center justify-center p-xl">
        <img alt="Community scene" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCSd7JiDEIhNViSre5TDdqRfOtYvlKqsowLbb1dVem5pQfjfo_irkn3oQt4ms8ouaPnl3uDbhYfftr310SsBREt7GBele3-OQzF7WBetnfPue4guWBPBnw7obDu_eQtyhJTYY3nC6ShzCnVUy3F3oeEpxzVdyPDD-xBCvLeKnjPI2_edkBI1ExADwQyOFsAUR1B8T4e9UgIqMR2-NQEYNHgBWKgHgayaIKm46WfbpLnDwY3Ic8vI1X0zCqdv_l4hSoVRQmvzVLPi7Zs"/>
        <div class="relative z-10 text-center max-w-md">
            <h1 class="font-headline-lg text-headline-lg text-white mb-sm">Kulinda Kesho Yetu.</h1>
            <p class="font-body-lg text-body-lg text-primary-fixed opacity-90">Tunashirikiana nanyi kuhakikisha usalama na ustawi wa kila mtoto nchini Tanzania.</p>
        </div>
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>
    </section>
    
    <!-- Right Panel: Form Area -->
    <section class="w-full md:w-1/2 bg-surface-container-lowest flex items-center justify-center px-margin-mobile md:px-xl py-lg">
        <div class="w-full max-w-[440px]">
            <div class="mb-lg">
                <h2 class="font-headline-lg text-headline-lg text-primary mb-xs">Weka Nywila Mpya</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Tafadhali weka nywila mpya ili kulinda akaunti yako.</p>
            </div>
            
            <?php if($error && !$user): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="text-center mt-4">
                <a href="login.php" class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-container transition-colors font-semibold">
                    Ingia Sasa / Login Now
                </a>
            </div>
            <?php elseif($user): ?>
            
            <form method="POST" action="" id="resetForm" class="space-y-md">
                <!-- New Password Field -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant block">NYWILA MPYA</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline">lock</span>
                        <input class="w-full pl-[48px] pr-lg py-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white" 
                               id="new_password" 
                               name="password"
                               placeholder="••••••••" 
                               type="password"
                               required/>
                        <button class="absolute right-sm top-1/2 -translate-y-1/2 text-outline hover:text-primary" onclick="toggleVisibility('new_password')" type="button">
                            <span class="material-symbols-outlined" id="eye_icon_new">visibility</span>
                        </button>
                    </div>
                </div>
                
                <!-- Confirm Password Field -->
                <div class="space-y-base">
                    <label class="font-label-caps text-label-caps text-on-surface-variant block">THIBITISHA NYWILA</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 text-outline">lock_reset</span>
                        <input class="w-full pl-[48px] pr-lg py-sm border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white" 
                               id="confirm_password" 
                               name="confirm_password"
                               placeholder="••••••••" 
                               type="password"
                               required/>
                        <button class="absolute right-sm top-1/2 -translate-y-1/2 text-outline hover:text-primary" onclick="toggleVisibility('confirm_password')" type="button">
                            <span class="material-symbols-outlined" id="eye_icon_confirm">visibility</span>
                        </button>
                    </div>
                </div>
                
                <!-- Password Requirements Checklist -->
                <div class="bg-surface-container p-sm rounded-lg space-y-xs">
                    <div class="flex items-center gap-xs text-on-surface-variant transition-colors" id="req-char">
                        <span class="material-symbols-outlined text-[20px]" id="icon-char">circle</span>
                        <span class="font-body-md text-body-md">Angalau herufi 8</span>
                    </div>
                    <div class="flex items-center gap-xs text-on-surface-variant transition-colors" id="req-num">
                        <span class="material-symbols-outlined text-[20px]" id="icon-num">circle</span>
                        <span class="font-body-md text-body-md">Namba moja (0-9)</span>
                    </div>
                    <div class="flex items-center gap-xs text-on-surface-variant transition-colors" id="req-spec">
                        <span class="material-symbols-outlined text-[20px]" id="icon-spec">circle</span>
                        <span class="font-body-md text-body-md">Alama maalum (!@#$%^&amp;*)</span>
                    </div>
                </div>
                
                <!-- CTA -->
                <button class="w-full bg-primary text-white py-sm rounded-full font-bold text-body-lg hover:bg-primary-container transition-all active:scale-[0.98] shadow-sm hover:shadow-md mt-md" type="submit">
                    Hifadhi Nywila
                </button>
                
                <!-- Back to Login Button -->
                <div class="text-center pt-sm">
                    <a href="login.php" class="inline-flex items-center justify-center gap-2 w-full md:w-auto px-6 py-3 border-2 border-primary text-primary rounded-full font-semibold text-body-md hover:bg-primary/5 transition-all duration-200">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Rudi kwenye Ingia / Back to Login
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="bg-surface-container-low border-t border-outline-variant w-full px-margin-desktop py-lg max-w-full mx-auto flex flex-col md:flex-row justify-between items-center gap-md">
    <div class="flex flex-col items-center md:items-start gap-xs">
        <span class="font-headline-md text-headline-md text-primary">OkoaWatoto</span>
        <p class="font-label-caps text-label-caps text-on-surface-variant">© <?php echo date('Y'); ?> OkoaWatoto. Utumishi wa Umma.</p>
    </div>
    <div class="flex flex-wrap justify-center gap-md">
        <a href="tel:112" class="font-label-caps text-label-caps text-on-surface-variant hover:underline text-secondary">Msaada: 112</a>
        <a href="#" class="font-label-caps text-label-caps text-on-surface-variant hover:underline text-secondary">Faragha</a>
        <a href="#" class="font-label-caps text-label-caps text-on-surface-variant hover:underline text-secondary">Vigezo na Masharti</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleVisibility(id) {
        const input = document.getElementById(id);
        const iconId = id === 'new_password' ? 'eye_icon_new' : 'eye_icon_confirm';
        const icon = document.getElementById(iconId);
        
        if (input.type === "password") {
            input.type = "text";
            icon.innerText = "visibility_off";
        } else {
            input.type = "password";
            icon.innerText = "visibility";
        }
    }

    const passwordInput = document.getElementById('new_password');
    const reqChar = document.getElementById('req-char');
    const reqNum = document.getElementById('req-num');
    const reqSpec = document.getElementById('req-spec');
    
    const iconChar = document.getElementById('icon-char');
    const iconNum = document.getElementById('icon-num');
    const iconSpec = document.getElementById('icon-spec');

    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            
            if (val.length >= 8) {
                updateRequirement(reqChar, iconChar, true);
            } else {
                updateRequirement(reqChar, iconChar, false);
            }

            if (/\d/.test(val)) {
                updateRequirement(reqNum, iconNum, true);
            } else {
                updateRequirement(reqNum, iconNum, false);
            }

            if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) {
                updateRequirement(reqSpec, iconSpec, true);
            } else {
                updateRequirement(reqSpec, iconSpec, false);
            }
        });
    }

    function updateRequirement(element, icon, isValid) {
        if (isValid) {
            element.classList.remove('text-on-surface-variant');
            element.classList.add('text-secondary');
            icon.innerText = 'check_circle';
            icon.style.fontVariationSettings = "'FILL' 1";
        } else {
            element.classList.add('text-on-surface-variant');
            element.classList.remove('text-secondary');
            icon.innerText = 'circle';
            icon.style.fontVariationSettings = "'FILL' 0";
        }
    }
    
    const form = document.getElementById('resetForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('new_password')?.value;
            const confirmPassword = document.getElementById('confirm_password')?.value;
            
            if (!password || !confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Sehemu Hazijajazwa!',
                    text: 'Tafadhali jaza sehemu zote za nenosiri',
                    confirmButtonColor: '#002045',
                    confirmButtonText: 'Sawa'
                });
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nenosiri Fupi Sana!',
                    text: 'Nenosiri lazima iwe angalau herufi 8',
                    confirmButtonColor: '#ba1a1a',
                    confirmButtonText: 'Sawa'
                });
                return false;
            }
            
            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nenosiri Lazima Liwe na Namba!',
                    text: 'Tafadhali weka angalau namba moja (0-9)',
                    confirmButtonColor: '#ba1a1a',
                    confirmButtonText: 'Sawa'
                });
                return false;
            }
            
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nenosiri Lazima Liwe na Alama!',
                    text: 'Tafadhali weka angalau alama maalum moja (!@#$%^&*)',
                    confirmButtonColor: '#ba1a1a',
                    confirmButtonText: 'Sawa'
                });
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Nenosiri Hazilingani!',
                    text: 'Nenosiri na uthibitisho wake hazifanani',
                    confirmButtonColor: '#ba1a1a',
                    confirmButtonText: 'Sawa'
                });
                return false;
            }
        });
    }
    
    <?php if($success): ?>
    Swal.fire({
        icon: 'success',
        title: 'Mafanikio!',
        text: '<?php echo addslashes($success); ?>',
        confirmButtonColor: '#0a6c44',
        confirmButtonText: 'Ingia Sasa'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'login.php';
        }
    });
    <?php endif; ?>
    
    <?php if($error && $user): ?>
    Swal.fire({
        icon: 'error',
        title: 'Hitilafu!',
        text: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#ba1a1a',
        confirmButtonText: 'Jaribu Tena'
    });
    <?php endif; ?>
</script>

</body>
</html>