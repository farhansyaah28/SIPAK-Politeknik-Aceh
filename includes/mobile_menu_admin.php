<?php
// SIIPAK - Reusable Responsive Admin, Pimpinan, & Penyewa Mobile Drawer Helper
$admin_name_display = $admin_name ?? $user_name ?? $_SESSION['user_name'] ?? 'Pengguna';
$user_role_active = $_SESSION['user_role'] ?? 'admin';

// Determine Role Display Name
if ($user_role_active === 'pimpinan') {
    $admin_role_display = 'Pimpinan Kampus';
} elseif ($user_role_active === 'penyewa') {
    $admin_role_display = 'Penyewa';
} else {
    $admin_role_display = 'Admin Pengelola';
}

$is_pimpinan_menu = ($user_role_active === 'pimpinan');
$is_penyewa_menu = ($user_role_active === 'penyewa');

// Resolve base paths for relative URLs
$is_subfolder_active = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/pimpinan/') !== false);
$link_prefix = $is_subfolder_active ? '' : 'admin/';
$logout_prefix = $is_subfolder_active ? '../' : '';
?>
<!-- Mobile Menu Trigger (Hamburger Button) & Drawer Injected script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    if (header) {
        // Ensure header is relative so absolute child centering works
        header.classList.add('relative');
        
        // Hide the role badge on extra small screens to save space
        const roleBadge = header.querySelector('.bg-surface-container-low');
        if (roleBadge) {
            roleBadge.classList.add('hidden', 'sm:flex');
        }
        
        // Center the welcome text container on mobile screens
        const welcomeDiv = header.querySelector('.flex.flex-col.justify-center');
        if (welcomeDiv) {
            welcomeDiv.classList.add(
                'absolute', 'md:relative',
                'left-1/2', 'md:left-auto',
                '-translate-x-1/2', 'md:translate-x-0',
                'text-center', 'md:text-left',
                'w-max' // Avoid text wrapping on narrow screens
            );
        }
        
        // Prepend mobile menu button to the left of the header
        const btn = document.createElement('button');
        btn.id = "mobile-menu-drawer-trigger";
        btn.className = "md:hidden flex items-center justify-center p-1.5 rounded-lg text-primary hover:bg-surface-container-low transition-all duration-200 mr-3 cursor-pointer z-40";
        btn.innerHTML = '<span class="material-symbols-outlined text-lg">menu</span>';
        btn.onclick = (e) => {
            e.preventDefault();
            toggleMobileDrawer(true);
        };
        header.insertBefore(btn, header.firstChild);
    }
    
    // Inject mobile drawer HTML structure into document body
    const drawerHtml = `
        <div id="mobile-drawer-overlay" class="fixed inset-0 bg-black/40 z-[9998] transition-opacity duration-300 opacity-0 pointer-events-none" onclick="toggleMobileDrawer(false)"></div>
        <aside id="mobile-drawer" class="fixed top-0 left-0 h-full w-64 bg-primary text-white z-[9999] transform -translate-x-full transition-transform duration-300 flex flex-col py-md shadow-2xl border-r border-white/10">
            <div class="px-md mb-lg flex justify-between items-center">
                <div class="flex items-center gap-xs">
                    <div class="bg-warning-amber p-1.5 rounded-lg shadow-sm">
                        <span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings: 'FILL' 1;">corporate_fare</span>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-white leading-none">SIPAK</h1>
                        <p class="text-[9px] text-primary-fixed-dim opacity-80 mt-0.5">Politeknik Aceh</p>
                    </div>
                </div>
                <button class="flex items-center justify-center p-1.5 rounded-lg text-white hover:bg-white/10 cursor-pointer" onclick="toggleMobileDrawer(false)">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>
            
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-xs">
                <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Pilihan Menu</div>
                <?php if ($is_penyewa_menu): ?>
                    <!-- Renter/Penyewa Menu Links -->
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $logout_prefix ?>dashboard.php">
                        <span class="material-symbols-outlined mr-2">dashboard</span>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $logout_prefix ?>booking.php">
                        <span class="material-symbols-outlined mr-2">add_box</span>
                        <span class="text-xs font-semibold">Booking Baru</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'riwayat_booking.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $logout_prefix ?>riwayat_booking.php">
                        <span class="material-symbols-outlined mr-2">receipt_long</span>
                        <span class="text-xs font-semibold">Transaksi Saya</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'kalender.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $logout_prefix ?>kalender.php">
                        <span class="material-symbols-outlined mr-2">calendar_month</span>
                        <span class="text-xs font-semibold">Jadwal Aset</span>
                    </a>
                <?php elseif ($is_pimpinan_menu): ?>
                    <!-- Pimpinan Menu Links -->
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $is_subfolder_active ? 'index.php' : 'pimpinan/index.php' ?>">
                        <span class="material-symbols-outlined mr-2">dashboard</span>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $is_subfolder_active ? 'laporan.php' : 'pimpinan/laporan.php' ?>">
                        <span class="material-symbols-outlined mr-2">analytics</span>
                        <span class="text-xs font-semibold">Laporan Rekap</span>
                    </a>
                <?php else: ?>
                    <!-- Admin Menu Links -->
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>index.php">
                        <span class="material-symbols-outlined mr-2">dashboard</span>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'gedung_kelola.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>gedung_kelola.php">
                        <span class="material-symbols-outlined mr-2">corporate_fare</span>
                        <span class="text-xs font-semibold">Kelola Gedung</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'aset_kelola.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>aset_kelola.php">
                        <span class="material-symbols-outlined mr-2">widgets</span>
                        <span class="text-xs font-semibold">Kelola Aset</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'booking_kelola.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>booking_kelola.php">
                        <span class="material-symbols-outlined mr-2">book_online</span>
                        <span class="text-xs font-semibold">Kelola Booking</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'pembayaran_validasi.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>pembayaran_validasi.php">
                        <span class="material-symbols-outlined mr-2">price_check</span>
                        <span class="text-xs font-semibold">Validasi Bayar</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'penyewa_kelola.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>penyewa_kelola.php">
                        <span class="material-symbols-outlined mr-2">groups</span>
                        <span class="text-xs font-semibold">Kelola Penyewa</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>jadwal.php">
                        <span class="material-symbols-outlined mr-2">calendar_month</span>
                        <span class="text-xs font-semibold">Jadwal Sewa</span>
                    </a>
                    <a class="flex items-center px-md py-2.5 text-white hover:bg-surface-container-low/10 rounded-lg mr-2 my-0.5 transition-all decoration-none <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-warning-amber/20 text-warning-amber font-bold border-l-4 border-warning-amber' : '' ?>" href="<?= $link_prefix ?>laporan.php">
                        <span class="material-symbols-outlined mr-2">analytics</span>
                        <span class="text-xs font-semibold">Laporan Rekap</span>
                    </a>
                <?php endif; ?>
            </nav>
            
            <div class="mt-auto px-md space-y-0.5 border-t border-white/10 pt-md">
                <div class="flex items-center gap-xs px-xs py-1 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-xs shadow-md border border-white/15">
                        <?= strtoupper(substr($admin_name_display, 0, 1)) ?>
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <p class="text-xs font-semibold text-white truncate leading-none"><?= htmlspecialchars($admin_name_display) ?></p>
                        <p class="text-[9px] text-primary-fixed-dim opacity-70 mt-0.5 truncate uppercase font-bold"><?= $admin_role_display ?></p>
                    </div>
                </div>
                <a class="flex items-center px-md py-2.5 text-primary-fixed-dim hover:bg-error-container/20 hover:text-error rounded-lg mx-2 transition-all decoration-none font-semibold text-xs cursor-pointer" href="<?= $logout_prefix ?>logout.php">
                    <span class="material-symbols-outlined mr-2 text-sm">logout</span>
                    <span>Keluar</span>
                </a>
            </div>
        </aside>
    `;
    document.body.insertAdjacentHTML('beforeend', drawerHtml);
});

function toggleMobileDrawer(isOpen) {
    const overlay = document.getElementById('mobile-drawer-overlay');
    const drawer = document.getElementById('mobile-drawer');
    if (isOpen) {
        overlay.classList.remove('pointer-events-none', 'opacity-0');
        overlay.classList.add('pointer-events-auto', 'opacity-100');
        drawer.classList.remove('-translate-x-full');
    } else {
        overlay.classList.remove('pointer-events-auto', 'opacity-100');
        overlay.classList.add('pointer-events-none', 'opacity-0');
        drawer.classList.add('-translate-x-full');
    }
}
</script>
