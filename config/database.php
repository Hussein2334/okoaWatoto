<?php
// config/database.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'okoawatoto_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Include logger
if (file_exists(__DIR__ . '/../includes/logger.php')) {
    require_once __DIR__ . '/../includes/logger.php';
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'staff');
}

function isAdminOrStaff() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff');
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /okoaWatoto/login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /okoaWatoto/index.php");
        exit();
    }
}

// Redirect if not staff
function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        header("Location: /okoaWatoto/index.php");
        exit();
    }
}

function generateCaseNumber($prefix = 'CASE') {
    return $prefix . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
?>