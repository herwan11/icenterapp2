<?php
// includes/footer.php
?>
            </main> <!-- Penutup .main-content -->
        </div> <!-- Penutup .main-wrapper -->
    </div> <!-- Penutup .app-container -->

    <!-- JavaScript untuk interaktivitas -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- LOGIKA DROPDOWN BARU YANG LEBIH CERDAS DAN STABIL ---
            const sidebarNav = document.querySelector('.sidebar-nav');

            sidebarNav.addEventListener('click', function(e) {
                // Cari elemen .nav-link terdekat yang diklik
                const link = e.target.closest('.nav-item-dropdown > .nav-link');

                // Jika yang diklik bukan link dropdown, abaikan
                if (!link) return;

                e.preventDefault(); // Mencegah link berpindah halaman

                const parentDropdown = link.parentElement;

                // Tutup semua dropdown lain yang "bersaudara" di level yang sama
                const siblingDropdowns = parentDropdown.parentElement.children;
                for (let sibling of siblingDropdowns) {
                    if (sibling.classList.contains('nav-item-dropdown') && sibling !== parentDropdown) {
                        sibling.classList.remove('open');
                    }
                }
                
                // Buka atau tutup dropdown yang diklik
                parentDropdown.classList.toggle('open');
            });


            // --- Logika untuk toggle sidebar di mobile ---
            const sidebarToggle = document.querySelector('.main-header .sidebar-toggle');
            const appContainer = document.querySelector('.app-container');
            const overlay = document.querySelector('.main-overlay');

            function toggleSidebar() {
                appContainer.classList.toggle('sidebar-open');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }
        });
    </script>
</body>
</html>

