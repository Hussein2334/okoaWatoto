<?php
// staff/includes/staff-sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- TopNavBar -->
<header class="flex justify-between items-center w-full px-4 md:px-8 py-3 border-b border-gray-200 bg-white sticky top-0 z-50 shadow-sm">
    <div class="flex items-center gap-4">
        <button id="sidebarToggle" class="md:hidden text-primary p-2">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <a href="../dashboard.php" class="font-bold text-2xl text-primary">OkoaWatoto Staff</a>
        <nav class="hidden md:flex gap-2">
            <a href="../index.php" class="text-gray-600 text-sm hover:bg-gray-100 px-3 py-2 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-sm">home</span> Home
            </a>
        </nav>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-2 rounded-full border border-gray-200 bg-white">
            <span class="material-symbols-outlined text-primary">account_circle</span>
            <span class="text-sm font-semibold"><?php echo strtoupper($_SESSION['user_name'] ?? 'Staff'); ?></span>
            <span class="text-xs text-gray-500">(Staff)</span>
            <!-- Logout button with SweetAlert -->
            <a href="#" class="ml-2 text-red-600 hover:bg-red-50 p-1 rounded-lg transition-colors" id="logoutBtn">
                <span class="material-symbols-outlined text-sm">logout</span>
            </a>
        </div>
    </div>
</header>

<div class="flex bg-gray-50" style="height: calc(100vh - 60px);">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:relative z-40 bg-white border-r border-gray-200 transition-transform duration-300 transform -translate-x-full md:translate-x-0 shadow-lg flex flex-col h-full" style="width: 220px; min-width: 220px; max-width: 220px;">
        
        <div class="flex-1 overflow-y-auto p-2">
            <nav class="space-y-0.5">
                <!-- Dashboard -->
                <a href="dashboard.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'dashboard.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">dashboard</span>
                    <span class="text-sm">Dashboard</span>
                </a>
                
                <!-- Cases -->
                <a href="cases.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'cases.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">description</span>
                    <span class="text-sm">Cases (Kesi)</span>
                </a>

                                    <!-- Add after Reports link -->
                    <a href="reporters.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'reporters.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <span class="material-symbols-outlined text-lg">people</span>
                        <span class="text-sm">Reporters (Waliotoripoti)</span>
                    </a>

                <!-- Settings -->
                <a href="settings.php" class="flex items-center gap-2 px-2 py-2 rounded-lg transition-all duration-200 <?php echo $current_page == 'settings.php' ? 'bg-primary text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <span class="material-symbols-outlined text-lg">settings</span>
                    <span class="text-sm">Settings (Mipangilio)</span>
                </a>

                <div class="border-t border-gray-200 my-2 pt-2">
                    <a href="../report-missing.php" class="flex items-center gap-2 px-2 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-all duration-200">
                        <span class="material-symbols-outlined text-lg">warning</span>
                        <span class="text-sm">Report Missing</span>
                    </a>
                    <a href="../report-found.php" class="flex items-center gap-2 px-2 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-all duration-200">
                        <span class="material-symbols-outlined text-lg">volunteer_activism</span>
                        <span class="text-sm">Report Found</span>
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- System Status -->
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
                        <div><span class="font-medium">User:</span> <?php echo $_SESSION['user_name'] ?? 'Staff'; ?></div>
                        <div><span class="font-medium">Role:</span> Staff</div>
                    </div>
                </div>
            </div>
        </div>
    </aside>
    
    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden" onclick="closeSidebar()"></div>
    
    <main class="flex-1 overflow-auto p-4 md:p-6">

<!-- Add SweetAlert CDN if not already in header -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Mobile sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    function openSidebar() {
        if (sidebar) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
        }
        if (overlay) {
            overlay.classList.remove('hidden');
        }
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
        }
        if (overlay) {
            overlay.classList.add('hidden');
        }
        document.body.style.overflow = '';
    }
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', openSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && !sidebar.classList.contains('-translate-x-full')) {
            closeSidebar();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768 && sidebar && sidebar.classList.contains('-translate-x-full') === false) {
            closeSidebar();
        }
    });
    
    // SweetAlert for logout confirmation
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Toka Mfumo?',
                text: 'Je, una uhakika unataka kutoka kwenye mfumo?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ndiyo, Toka',
                cancelButtonText: 'Hapana',
                background: '#ffffff',
                customClass: {
                    popup: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        });
    }
</script>