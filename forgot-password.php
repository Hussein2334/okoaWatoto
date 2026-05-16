<?php
session_start();
require_once 'config/database.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'includes/phpmailer/PHPMailer.php';
require_once 'includes/phpmailer/SMTP.php';
require_once 'includes/phpmailer/Exception.php';

$error = '';
$success = '';
$email_or_id = ''; // Define variable here

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_id = trim($_POST['email_or_id'] ?? '');
    
    if (empty($email_or_id)) {
        $error = "Tafadhali ingiza barua pepe au Service ID yako";
    } else {
        // Check if user exists by email or phone
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$email_or_id, $email_or_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + (86400)); // 24 hours expiry (86400 seconds)
            
            // Save token to database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // Create reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/reset-password.php?token=" . $token;
            
            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'husseinali2334@gmail.com';
                $mail->Password   = 'lbxgachjdhlrjbjb';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Recipients
                $mail->setFrom('noreply@okoawatoto.go.tz', 'OkoaWatoto System');
                $mail->addAddress($user['email'], $user['fullname']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'OkoaWatoto - Reset Your Password';
                $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Reset Password - OkoaWatoto</title>
                    <style>
                        body { font-family: "Public Sans", Arial, sans-serif; background-color: #f7fafc; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #002045; color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
                        .content { background-color: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                        .button { display: inline-block; background-color: #0a6c44; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                        .alert { background-color: #ffdad6; border-left: 4px solid #ba1a1a; padding: 15px; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1 style="margin:0">OkoaWatoto</h1>
                            <p style="margin:5px 0 0; opacity:0.8">Child Protection System</p>
                        </div>
                        <div class="content">
                            <h2>Habari, ' . htmlspecialchars($user['fullname']) . '!</h2>
                            <p>Umepokea barua hii kwa sababu umetuma ombi la kurejesha nenosiri lako la OkoaWatoto System.</p>
                            <div class="alert">
                                <p style="margin:0"><strong>⚠️ Muhimu:</strong> Kiungo hiki kitaisha baada ya saa 24.</p>
                            </div>
                            <p>Bonyeza kitufe hapa chini kuweka nenosiri jipya:</p>
                            <div style="text-align: center;">
                                <a href="' . $reset_link . '" class="button">Weka Nenosiri Jipya</a>
                            </div>
                            <p>Au nakili kiungo hiki kwenye browser yako:</p>
                            <p style="background-color: #f1f4f6; padding: 10px; border-radius: 6px; word-break: break-all; font-size: 12px;">' . $reset_link . '</p>
                            <hr style="margin: 20px 0; border-color: #e0e3e5;">
                            <p style="color: #666; font-size: 13px;">Kama hukuweka ombi hili, tafadhali puuza barua hii. Nenosiri lako halitabadilika.</p>
                        </div>
                        <div class="footer">
                            <p>© ' . date('Y') . ' OkoaWatoto - Jamhuri ya Muungano wa Tanzania</p>
                            <p>Msaada: 112 | Sera ya Faragha | Tovuti Kuu ya Serikali</p>
                        </div>
                    </div>
                </body>
                </html>
                ';
                $mail->AltBody = 'Habari ' . $user['fullname'] . ', umepokea barua hii kwa sababu umetuma ombi la kurejesha nenosiri lako. Bonyeza kiungo hiki kuweka nenosiri jipya: ' . $reset_link . ' Kiungo hiki kitaisha baada ya saa 24.';
                
                $mail->send();
                $success = "Maelekezo ya kurejesha nenosiri yametumwa kwenye barua pepe yako. Tafadhali angaza folder ya spam kama haujaipokea. Kiungo kitakua halali kwa masaa 24.";
                
                // Log activity
                if (function_exists('logActivity')) {
                    logActivity(
                        "Password Reset Request",
                        "create",
                        "User {$user['fullname']} requested password reset",
                        null,
                        ['user_id' => $user['id'], 'email' => $user['email']]
                    );
                }
                
            } catch (Exception $e) {
                $error = "Barua pepe haikutumwa. Tafadhali jaribu tena au wasiliana na msimamizi.";
                error_log("Mailer Error: {$mail->ErrorInfo}");
            }
        } else {
            $error = "Hakuna akaunti inayopatana na barua pepe au Service ID uliyoingiza.";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="sw">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>OkoaWatoto | Umesahau Nywila</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-dim": "#d7dadc",
                        "secondary-fixed": "#9ff5c1",
                        "on-primary-fixed": "#001b3c",
                        "primary-fixed-dim": "#adc7f7",
                        "surface-tint": "#455f88",
                        "background": "#f7fafc",
                        "on-surface-variant": "#43474e",
                        "surface-container-high": "#e5e9eb",
                        "surface-container": "#ebeef0",
                        "outline": "#74777f",
                        "tertiary-container": "#73000c",
                        "surface-container-low": "#f1f4f6",
                        "on-surface": "#181c1e",
                        "on-secondary-fixed": "#002111",
                        "secondary": "#0a6c44",
                        "on-secondary-fixed-variant": "#005231",
                        "surface-container-highest": "#e0e3e5",
                        "on-tertiary-fixed": "#410004",
                        "on-primary-fixed-variant": "#2d476f",
                        "error-container": "#ffdad6",
                        "inverse-primary": "#adc7f7",
                        "on-background": "#181c1e",
                        "error": "#ba1a1a",
                        "tertiary-fixed": "#ffdad7",
                        "secondary-fixed-dim": "#83d8a6",
                        "on-error-container": "#93000a",
                        "on-error": "#ffffff",
                        "tertiary": "#4b0005",
                        "inverse-surface": "#2d3133",
                        "on-secondary": "#ffffff",
                        "surface": "#f7fafc",
                        "outline-variant": "#c4c6cf",
                        "tertiary-fixed-dim": "#ffb3ad",
                        "primary": "#002045",
                        "inverse-on-surface": "#eef1f3",
                        "surface-container-lowest": "#ffffff",
                        "surface-variant": "#e0e3e5",
                        "primary-container": "#1a365d",
                        "on-secondary-container": "#167249",
                        "on-tertiary-container": "#ff736c",
                        "on-tertiary": "#ffffff",
                        "surface-bright": "#f7fafc",
                        "on-tertiary-fixed-variant": "#930013",
                        "on-primary-container": "#86a0cd",
                        "primary-fixed": "#d6e3ff",
                        "on-primary": "#ffffff",
                        "secondary-container": "#9ff5c1"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "spacing": {
                        "sm": "16px",
                        "gutter": "16px",
                        "xs": "8px",
                        "margin-mobile": "16px",
                        "md": "24px",
                        "margin-desktop": "48px",
                        "xl": "64px",
                        "base": "4px",
                        "lg": "40px"
                    },
                    "fontFamily": {
                        "label-caps": ["Public Sans"],
                        "headline-md": ["Public Sans"],
                        "swahili-alt": ["Public Sans"],
                        "headline-lg-mobile": ["Public Sans"],
                        "body-md": ["Public Sans"],
                        "headline-lg": ["Public Sans"],
                        "body-lg": ["Public Sans"]
                    },
                    "fontSize": {
                        "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "swahili-alt": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "headline-lg-mobile": ["26px", {"lineHeight": "32px", "fontWeight": "700"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                        "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}]
                    }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .bento-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .hero-overlay {
            background: linear-gradient(to bottom, rgba(0, 32, 69, 0.4), rgba(0, 32, 69, 0.9));
        }
    </style>
</head>
<body class="bg-surface font-swahili-alt text-on-surface min-h-screen flex flex-col overflow-x-hidden">

<!-- Top AppBar - Language Switcher Removed -->
<header class="absolute top-0 right-0 z-50 flex justify-end items-center w-full px-margin-mobile md:px-margin-desktop py-xs">
    <!-- Language switcher removed -->
</header>

<main class="flex-grow flex flex-col md:flex-row h-screen overflow-hidden">
    <!-- Left Panel: Brand & Evocative Imagery -->
    <section class="hidden md:flex md:w-5/12 lg:w-1/2 bg-primary relative overflow-hidden flex-col justify-between p-xl">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover" alt="Tanzanian community" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCp7llu-Kdz6eXBet9-8xciNIzWuWTSBOJ6MZfsh4Ylhwxjar2T1bpkLzacPEpKQmvabliWisi56i7j0DmfNdvIGrMtU2F9q0WriR3qJRf7fW-Gx3bkxAFalTYpveE6qlsEWHUmKnwx-fPnI0a70MCMCG8_bLau89NOB8wQG4v5hKTjcwgEc8XM34So0BsdBioDQK_GmvjTShPehoHhVyRktynF7qk34l28BeWTxmpG5NUCV8yG4hL2vHWY2oFYlVL81ATJP5PRP6zm"/>
            <div class="absolute inset-0 hero-overlay"></div>
            <div class="absolute inset-0 bento-pattern opacity-30"></div>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-xs">
                <span class="material-symbols-outlined text-secondary-fixed text-4xl" style="font-variation-settings: 'FILL' 1">shield_with_heart</span>
                <h1 class="font-headline-md text-headline-md font-bold text-white tracking-tight">OkoaWatoto</h1>
            </div>
        </div>
        <div class="relative z-10 max-w-md">
            <p class="text-secondary-fixed font-label-caps text-label-caps mb-xs tracking-widest">DUNIA SALAMA KWA MTOTO</p>
            <h2 class="font-headline-lg text-headline-lg text-white mb-sm leading-tight">Kulinda Kesho Yetu.</h2>
            <div class="h-1 w-24 bg-secondary-fixed rounded-full"></div>
        </div>
        <div class="relative z-10 flex items-center gap-sm text-white/70">
            <span class="material-symbols-outlined" style="font-variation-settings: 'wght' 200">gavel</span>
            <p class="text-swahili-alt italic">Chini ya Wizara ya Maendeleo ya Jamii, Jinsia, Wanawake na Makundi Maalum.</p>
        </div>
    </section>

    <!-- Right Panel: Transactional Interface -->
    <section class="w-full md:w-7/12 lg:w-1/2 bg-surface-container-lowest flex flex-col justify-center px-margin-mobile md:px-xl relative overflow-y-auto">
        <div class="max-w-md mx-auto w-full py-xl">
            <!-- Back to Login Navigation -->
            <a href="login.php" class="inline-flex items-center gap-xs text-primary font-label-caps text-label-caps hover:translate-x-[-4px] transition-transform duration-300 mb-lg group">
                <span class="material-symbols-outlined text-primary group-hover:text-secondary">arrow_back</span>
                RUDI KWENYE INGIA (LOGIN)
            </a>
            
            <!-- Header Section -->
            <div class="mb-xl">
                <h2 class="font-headline-lg text-headline-lg text-primary mb-sm">Umesahau Nywila?</h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Tafadhali ingiza barua pepe au Service ID yako ili kupokea maelekezo ya kurejesha akaunti yako.
                    Kiungo kitakua halali kwa <strong class="text-primary">masaa 24</strong>.
                </p>
            </div>
            
            <!-- Reset Form -->
            <form method="POST" action="" id="resetForm" class="space-y-lg">
                <div class="space-y-base">
                    <label class="block font-label-caps text-label-caps text-primary uppercase" for="email_or_id">
                        EMAIL / SERVICE ID
                    </label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-sm top-1/2 -translate-y-1/2 material-symbols-outlined text-outline group-focus-within:text-primary transition-colors">
                            badge
                        </span>
                        <input class="w-full pl-12 pr-sm py-4 bg-surface border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-all font-body-md placeholder:text-outline-variant" 
                               id="email_or_id" 
                               name="email_or_id" 
                               placeholder="mfano@huduma.go.tz au 0712345678" 
                               type="text"
                               value="<?php echo htmlspecialchars($email_or_id ?? ''); ?>"
                               required/>
                    </div>
                </div>
                
                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">info</span>
                        <p class="text-sm text-blue-700">Kiungo cha kurejesha nenosiri kitakua halali kwa <strong>masaa 24</strong> baada ya ombi.</p>
                    </div>
                </div>
                
                <!-- Action Button -->
                <button type="submit" class="w-full py-4 bg-primary text-white font-headline-md text-headline-md rounded-lg shadow-lg hover:bg-primary-container active:scale-[0.98] transition-all flex items-center justify-center gap-sm group" id="submitBtn">
                    <span>Tuma Maelekezo</span>
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">send</span>
                </button>
            </form>
            
            <!-- Help & Footer Support -->
            <div class="mt-xl pt-lg border-t border-outline-variant flex flex-col gap-md">
                <div class="flex items-start gap-sm">
                    <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'opsz' 20">help</span>
                    <div class="space-y-base">
                        <p class="font-body-md text-on-surface-variant">Unahitaji msaada zaidi?</p>
                        <a href="tel:112" class="text-secondary font-bold hover:underline transition-all">Wasiliana na msimamizi wa mfumo.</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="absolute bottom-0 left-0 w-full px-margin-mobile md:px-xl py-lg bg-surface-container-highest border-t border-outline-variant grid grid-cols-1 md:grid-cols-2 gap-gutter items-center">
            <div class="space-y-xs">
                <div class="font-headline-md text-headline-md font-bold text-primary flex items-center gap-xs">
                    <span class="material-symbols-outlined text-2xl">account_balance</span>
                    Jamhuri ya Muungano wa Tanzania
                </div>
                <p class="font-swahili-alt text-swahili-alt text-on-surface-variant">© <?php echo date('Y'); ?> Jamhuri ya Muungano wa Tanzania. Huduma ya Umma.</p>
            </div>
            <div class="flex flex-wrap gap-md justify-start md:justify-end">
                <a href="tel:112" class="text-on-surface-variant hover:text-primary hover:underline font-swahili-alt text-swahili-alt transition-colors">Msaada: 112</a>
                <a href="#" class="text-on-surface-variant hover:text-primary hover:underline font-swahili-alt text-swahili-alt transition-colors">Sera ya Faragha</a>
                <a href="#" class="text-on-surface-variant hover:text-primary hover:underline font-swahili-alt text-swahili-alt transition-colors">Tovuti Kuu ya Serikali</a>
                <a href="#" class="text-on-surface-variant hover:text-primary hover:underline font-swahili-alt text-swahili-alt transition-colors">Vituo vya Polisi</a>
            </div>
        </footer>
    </section>
</main>

<script>
    // Form submission with SweetAlert
    const form = document.getElementById('resetForm');
    const submitBtn = document.getElementById('submitBtn');
    
    <?php if($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Hitilafu!',
        text: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#ba1a1a',
        confirmButtonText: 'Jaribu Tena',
        background: '#ffffff',
        customClass: {
            popup: 'rounded-xl'
        }
    });
    <?php endif; ?>
    
    <?php if($success): ?>
    Swal.fire({
        icon: 'success',
        title: 'Maelekezo Yatutumwa!',
        html: '<?php echo addslashes($success); ?>',
        confirmButtonColor: '#0a6c44',
        confirmButtonText: 'Sawa',
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
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const emailOrId = document.getElementById('email_or_id')?.value.trim();
            
            if (!emailOrId) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Sehemu Hazijajazwa!',
                    text: 'Tafadhali ingiza barua pepe au Service ID yako',
                    confirmButtonColor: '#002045',
                    confirmButtonText: 'Sawa'
                });
                return false;
            }
            
            // Show loading state
            if (submitBtn) {
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Inatuma...</span>
                `;
                submitBtn.disabled = true;
            }
        });
    }
</script>

</body>
</html>