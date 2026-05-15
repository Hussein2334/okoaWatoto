<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html class="light" lang="sw">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $page_title ?? 'OkoaWatoto'; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        body { font-family: 'Public Sans', sans-serif; }
    </style>
</head>
<body class="bg-white text-[#181c1e] min-h-screen flex flex-col">

<!-- URGENT BANNER - Moja tu -->
<div class="bg-[#002045] text-white w-full py-2 px-4 md:px-12 flex items-center justify-center gap-2 text-sm z-50">
    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">info</span>
    <span>For immediate emergencies, call Police toll-free: <strong>112</strong> / Kwa dharura, piga: <strong>112</strong></span>
</div>

<!-- NAVIGATION -->
<nav class="hidden md:flex justify-between items-center w-full px-12 py-2 bg-white border-b border-[#c4c6cf] sticky top-0 z-40">
    <div class="font-bold text-2xl text-[#002045]">OkoaWatoto</div>
    <div class="flex items-center gap-4">
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'text-[#002045] font-bold border-b-2 border-[#002045] pb-1 text-xs tracking-wide' : 'text-[#43474e] text-sm hover:bg-gray-100 px-2 py-1'; ?>">
            Home
        </a>
        <a href="children.php" class="<?php echo $current_page == 'children.php' ? 'text-[#002045] font-bold border-b-2 border-[#002045] pb-1 text-xs tracking-wide' : 'text-[#43474e] text-sm hover:bg-gray-100 px-2 py-1'; ?>">
            Children
        </a>
        <a href="report-missing.php" class="<?php echo $current_page == 'report-missing.php' ? 'text-[#002045] font-bold border-b-2 border-[#002045] pb-1 text-xs tracking-wide' : 'text-[#43474e] text-sm hover:bg-gray-100 px-2 py-1'; ?>">
            Report Missing
        </a>
        <a href="report-found.php" class="<?php echo $current_page == 'report-found.php' ? 'text-[#002045] font-bold border-b-2 border-[#002045] pb-1 text-xs tracking-wide' : 'text-[#43474e] text-sm hover:bg-gray-100 px-2 py-1'; ?>">
            Found Child
        </a>
        <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>
            <a href="admin/dashboard.php" class="text-[#0a6c44] text-sm font-bold">Dashboard</a>
            <a href="logout.php" class="text-red-600 text-sm">Logout</a>
        <?php else: ?>
            <a href="login.php" class="text-[#0a6c44] text-sm">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- MOBILE NAVIGATION -->
<div class="md:hidden flex justify-between items-center px-4 py-2 bg-white border-b border-[#c4c6cf] sticky top-0 z-40">
    <div class="font-bold text-xl text-[#002045]">OkoaWatoto</div>
    <button onclick="toggleMobileMenu()" class="text-[#002045] p-2">
        <span class="material-symbols-outlined">menu</span>
    </button>
</div>

<div id="mobileMenu" class="hidden fixed inset-0 bg-black/50 z-50" onclick="toggleMobileMenu()">
    <div class="bg-white w-64 h-full p-4" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <div class="font-bold text-xl text-[#002045]">Menu</div>
            <button onclick="toggleMobileMenu()" class="text-[#43474e]">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="flex flex-col gap-2">
            <a href="index.php" class="py-2 px-3 hover:bg-gray-100 rounded">Home</a>
            <a href="children.php" class="py-2 px-3 hover:bg-gray-100 rounded">Children</a>
            <a href="report-missing.php" class="py-2 px-3 hover:bg-gray-100 rounded">Report Missing</a>
            <a href="report-found.php" class="py-2 px-3 hover:bg-gray-100 rounded">Found Child</a>
            <div class="border-t my-2 pt-2">
                <?php if(function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <span class="py-2 px-3 text-[#0a6c44] block"><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
                    <a href="logout.php" class="py-2 px-3 text-red-600 hover:bg-gray-100 rounded block">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="py-2 px-3 hover:bg-gray-100 rounded block">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }
</script>

<main class="flex-grow">