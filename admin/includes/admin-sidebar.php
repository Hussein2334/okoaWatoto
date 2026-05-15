<?php
// admin/includes/admin-sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- TopNavBar -->
<header class="flex justify-between items-center w-full px-4 md:px-8 py-3 border-b border-gray-200 bg-white sticky top-0 z-50 shadow-sm">
    <div class="flex items-center gap-4">
        <button id="sidebarToggle" class="md:hidden text-primary p-2">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <a href="dashboard.php" class="font-bold text-2xl text-primary">OkoaWatoto</a>
        <nav class="hidden md:flex gap-2">
            <a href="../index.php" class="text-gray-600 text-sm hover:bg-gray-100 px-3 py-2 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-sm">home</span> Home
            </a>
        </nav>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-2 rounded-full border border-gray-200 bg-white">
            <span class="material-symbols-outlined text-primary">account_circle</span>
            <span class="text-sm font-semibold"><?php echo strtoupper($_SESSION['user_name'] ?? 'Admin'); ?></span>
            <span class="text-xs text-gray-500">(<?php echo $_SESSION['user_role'] ?? 'Staff'; ?>)</span>
            <a href="../logout.php" class="ml-2 text-red-600 hover:bg-red-50 p-1 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-sm">logout</span>
            </a>
        </div>
    </div>
</header>

<div class="flex bg-gray-50" style="height: calc(100vh - 60px);">
    <!-- Sidebar - Full height no gaps - width reduced -->
    <aside id="sidebar" class="fixed md:relative z-40 bg-white border-r border-gray-200 transition-transform duration-300 transform -translate-x-full md:translate-x-0 shadow-lg flex flex-col h-full" style="width: 220px; min-width: 220px; max-width: 220px;">
        
        <!-- Sidebar Navigation - Takes all available space -->
        <div class="flex-1 overflow-y-auto p-2">
            <nav class="space-y-0.5">
                <a href="dashboard.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'dashboard.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">dashboard</span>
                    <span class="text-sm">Dashboard</span>
                </a>
                <a href="cases.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'cases.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">description</span>
                    <span class="text-sm">Cases (Kesi)</span>
                </a>
                <a href="users.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'users.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">group</span>
                    <span class="text-sm">Users (Watumiaji)</span>
                </a>
                <a href="reports.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'reports.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">bar_chart</span>
                    <span class="text-sm">Reports (Ripoti)</span>
                </a>
                <a href="logs.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'logs.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">history</span>
                    <span class="text-sm">System Logs</span>
                </a>
                <a href="settings.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'settings.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">settings</span>
                    <span class="text-sm">Settings (Mipangilio)</span>
                </a>
            </nav>
        </div>
        
        <!-- System Status - At the very bottom, no gap -->
        <div class="border-t border-gray-200 bg-white">
            <div class="p-2">
                <div class="p-2 rounded-lg bg-gray-50">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-semibold text-gray-500">SYSTEM STATUS</p>
                        <div class="flex items-center gap-1">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-xs text-green-600">Active</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span><?php echo date('d M Y'); ?></span>
                        <span><?php echo date('H:i'); ?></span>
                    </div>
                    <div class="mt-1 pt-1 border-t border-gray-200 text-xs text-gray-400">
                        <div class="truncate"><span class="font-medium">User:</span> <?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                        <div><span class="font-medium">Role:</span> <?php echo ucfirst($_SESSION['user_role'] ?? 'Staff'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden" onclick="closeSidebar()"></div>
    
    <main class="flex-1 overflow-auto">