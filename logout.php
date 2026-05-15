<?php
// logout.php
require_once 'config/database.php';

// Log the logout action before destroying session
if (isLoggedIn()) {
    logActivity(
        "User Logout",
        "logout",
        "User {$_SESSION['user_name']} logged out successfully",
        null,
        ['user_id' => $_SESSION['user_id'], 'email' => $_SESSION['user_email']]
    );
}

// Destroy session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

header("Location: login.php");
exit();
?>