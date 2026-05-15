<?php
// forgot-password.php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Tafadhali weka barua pepe yako / Please enter your email";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // In a real system, send email here
            $success = "Link ya kurejesha nenosiri imetumwa kwa barua pepe yako / Reset link sent to your email";
            
            // For demo purposes, show the reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/reset-password.php?token=" . $token;
            $success .= "<br><small>Demo Link: <a href='$reset_link'>$reset_link</a></small>";
        } else {
            $error = "Barua pepe haijapatikana / Email not found";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - OkoaWatoto</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-md mx-auto mt-20 p-6 bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold text-center text-[#002045] mb-4">Reset Password</h1>
        
        <?php if($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full p-3 border rounded">
            </div>
            <button type="submit" class="w-full bg-[#002045] text-white py-3 rounded hover:bg-blue-900">
                Send Reset Link
            </button>
        </form>
        
        <p class="text-center mt-4"><a href="login.php" class="text-[#002045]">Back to Login</a></p>
    </div>
</body>
</html>