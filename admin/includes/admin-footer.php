<?php
// admin/includes/admin-footer.php
?>
    </main>
    </div>

    <footer class="w-full bg-white border-t border-gray-200 py-4 px-4 md:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-3">
            <div>
                <p class="text-xs text-gray-500">© <?php echo date('Y'); ?> OkoaWatoto - Jamhuri ya Muungano wa Tanzania</p>
            </div>
            <div class="flex gap-4">
                <a href="tel:112" class="text-xs text-gray-500 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-sm align-middle">call</span> Msaada: 112
                </a>
                <a href="#" class="text-xs text-gray-500 hover:text-primary transition-colors">Sera ya Faragha</a>
                <a href="#" class="text-xs text-gray-500 hover:text-primary transition-colors">Mawasiliano</a>
                <a href="#" class="text-xs text-gray-500 hover:text-primary transition-colors">Maswali</a>
            </div>
        </div>
    </footer>

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
        
        // Handle window resize - close mobile sidebar when screen becomes large
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768 && sidebar && sidebar.classList.contains('-translate-x-full') === false) {
                closeSidebar();
            }
        });
    </script>
    </body>
    </html>