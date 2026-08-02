<?php
// SIIPAK - Tailwind Navbar Include
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/pimpinan/') !== false);
$base_path = $is_subfolder ? '../' : '';

$current_page = basename($_SERVER['PHP_SELF']);
$role = get_user_role();
$user_name = $_SESSION['user_name'] ?? 'Pengguna';

// Resolve links to sections on the homepage for single-page scrolling
$home_link = ($current_page === 'index.php') ? '#beranda' : $base_path . 'index.php#beranda';
$aset_link = ($current_page === 'index.php') ? '#aset-tersedia' : $base_path . 'index.php#aset-tersedia';
$cara_sewa_link = ($current_page === 'index.php') ? '#cara-sewa' : $base_path . 'index.php#cara-sewa';
$kontak_link = ($current_page === 'index.php') ? '#footer-contact' : $base_path . 'index.php#footer-contact';
?>
<header class="w-full sticky top-0 z-40 bg-[#eef2f6]/95 backdrop-blur-md border-b border-outline-variant/40 shadow-xs no-print transition-all duration-300 py-sm">
    <div class="max-w-[1140px] mx-auto px-lg flex justify-between items-center w-full">
        <!-- Mobile Menu Trigger Button (Visible only on Mobile) -->
        <button id="public-menu-btn" class="md:hidden flex items-center justify-center p-1.5 rounded-lg text-primary hover:bg-primary/5 transition-all duration-200 mr-2" onclick="togglePublicMobileDrawer(true)">
            <span class="material-symbols-outlined text-lg">menu</span>
        </button>

        <!-- Logo -->
        <div class="flex items-center gap-md">
            <div class="bg-primary p-1.5 rounded-xl shadow-xs">
                <span class="material-symbols-outlined text-white text-xl" style="font-variation-settings: 'FILL' 1;">domain</span>
            </div>
            <div class="flex flex-col -mt-0.5">
                <a href="<?= $base_path ?>index.php" class="decoration-none"><span class="text-headline-md font-extrabold text-primary leading-none">SIPAK</span></a>
                <span class="text-[10px] font-bold text-on-surface-variant/80 mt-0.5">Politeknik Aceh</span>
            </div>
        </div>
        
        <!-- Desktop Nav (Hidden on Mobile) -->
        <nav class="hidden md:flex items-center gap-md">
            <a class="text-on-surface-variant hover:text-primary transition-all duration-300 font-bold text-xs md:text-sm px-3 py-1.5 rounded-full hover:bg-primary/5 decoration-none" href="<?= $home_link ?>">Beranda</a>
            <a class="text-on-surface-variant hover:text-primary transition-all duration-300 font-bold text-xs md:text-sm px-3 py-1.5 rounded-full hover:bg-primary/5 decoration-none" href="<?= $cara_sewa_link ?>">Cara Sewa</a>
            <a class="text-on-surface-variant hover:text-primary transition-all duration-300 font-bold text-xs md:text-sm px-3 py-1.5 rounded-full hover:bg-primary/5 decoration-none" href="<?= $aset_link ?>">Aset Tersedia</a>
            <a class="text-on-surface-variant hover:text-primary transition-all duration-300 font-bold text-xs md:text-sm px-3 py-1.5 rounded-full hover:bg-primary/5 decoration-none" href="<?= $kontak_link ?>">Kontak</a>
        </nav>
        
        <!-- Desktop Actions (Hidden on Mobile) -->
        <div class="hidden md:flex items-center gap-md">
            <?php if (is_logged_in()): ?>
                <?php if ($role === 'admin'): ?>
                    <a href="<?= $base_path ?>admin/index.php" class="bg-white text-primary border border-outline-variant px-4 py-1.5 rounded-full font-bold shadow-xs hover:shadow-sm hover:border-primary active:scale-95 transition-all decoration-none text-xs">Dashboard Admin</a>
                <?php elseif ($role === 'pimpinan'): ?>
                    <a href="<?= $base_path ?>pimpinan/index.php" class="bg-white text-primary border border-outline-variant px-4 py-1.5 rounded-full font-bold shadow-xs hover:shadow-sm hover:border-primary active:scale-95 transition-all decoration-none text-xs">Dashboard Pimpinan</a>
                <?php else: ?>
                    <a href="<?= $base_path ?>riwayat_booking.php" class="bg-white text-primary border border-outline-variant px-4 py-1.5 rounded-full font-bold shadow-xs hover:shadow-sm hover:border-primary active:scale-95 transition-all decoration-none text-xs">Dashboard Penyewa</a>
                <?php endif; ?>
                <a href="<?= $base_path ?>logout.php" class="text-on-surface-variant hover:text-error transition-colors font-bold text-xs decoration-none ml-2">Keluar</a>
            <?php else: ?>
                <a href="<?= $base_path ?>login.php" class="bg-white text-primary border border-outline-variant px-4 py-1.5 rounded-full font-bold shadow-xs hover:shadow-sm hover:border-primary active:scale-95 transition-all decoration-none text-xs">Masuk Akun</a>
                <a href="<?= $base_path ?>register.php" class="text-on-surface-variant hover:text-primary transition-colors font-bold text-xs decoration-none ml-2">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Public Mobile Navigation Drawer Overlay & Content -->
<div id="public-drawer-overlay" class="fixed inset-0 bg-black/40 z-50 transition-opacity duration-300 opacity-0 pointer-events-none" onclick="togglePublicMobileDrawer(false)"></div>
<aside id="public-drawer" class="fixed top-0 left-0 h-full w-64 bg-[#eef2f6] text-on-background z-50 transform -translate-x-full transition-transform duration-300 flex flex-col py-md shadow-2xl border-r border-outline-variant/40">
    <div class="px-md mb-lg flex justify-between items-center">
        <div class="flex items-center gap-xs">
            <div class="bg-primary p-1.5 rounded-lg shadow-sm">
                <span class="material-symbols-outlined text-white text-base" style="font-variation-settings: 'FILL' 1;">domain</span>
            </div>
            <div>
                <h1 class="text-sm font-extrabold text-primary leading-none">SIPAK</h1>
                <p class="text-[9px] text-on-surface-variant/80 mt-0.5">Politeknik Aceh</p>
            </div>
        </div>
        <!-- Close Button -->
        <button class="flex items-center justify-center p-1.5 rounded-lg text-primary hover:bg-primary/5" onclick="togglePublicMobileDrawer(false)">
            <span class="material-symbols-outlined text-base">close</span>
        </button>
    </div>
    
    <!-- Drawer Links -->
    <nav class="flex-1 space-y-md px-md pt-xs overflow-y-auto">
        <div class="space-y-sm flex flex-col">
            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">Navigasi</span>
            <a class="text-on-surface hover:text-primary transition-all duration-200 font-bold text-xs py-1.5 border-b border-outline-variant/20 decoration-none" href="<?= $home_link ?>" onclick="togglePublicMobileDrawer(false)">Beranda</a>
            <a class="text-on-surface hover:text-primary transition-all duration-200 font-bold text-xs py-1.5 border-b border-outline-variant/20 decoration-none" href="<?= $cara_sewa_link ?>" onclick="togglePublicMobileDrawer(false)">Cara Sewa</a>
            <a class="text-on-surface hover:text-primary transition-all duration-200 font-bold text-xs py-1.5 border-b border-outline-variant/20 decoration-none" href="<?= $aset_link ?>" onclick="togglePublicMobileDrawer(false)">Aset Tersedia</a>
            <a class="text-on-surface hover:text-primary transition-all duration-200 font-bold text-xs py-1.5 decoration-none" href="<?= $kontak_link ?>" onclick="togglePublicMobileDrawer(false)">Kontak</a>
        </div>
        
        <div class="space-y-sm flex flex-col pt-md border-t border-outline-variant/40">
            <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider">Akun Anda</span>
            <?php if (is_logged_in()): ?>
                <div class="flex items-center gap-xs px-xs py-1 bg-surface rounded-lg mb-2 border border-outline-variant/40">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-xs shadow-md">
                        <?= strtoupper(substr($user_name, 0, 1)) ?>
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <p class="text-xs font-semibold text-on-surface truncate leading-none"><?= htmlspecialchars($user_name) ?></p>
                        <p class="text-[9px] text-on-surface-variant opacity-75 mt-0.5 truncate uppercase font-bold"><?= $role ?></p>
                    </div>
                </div>
                <?php if ($role === 'admin'): ?>
                    <a href="<?= $base_path ?>admin/index.php" class="bg-primary text-white text-center py-2 rounded-lg font-bold text-xs hover:bg-primary-container active:scale-95 transition-all decoration-none">Dashboard Admin</a>
                <?php elseif ($role === 'pimpinan'): ?>
                    <a href="<?= $base_path ?>pimpinan/index.php" class="bg-primary text-white text-center py-2 rounded-lg font-bold text-xs hover:bg-primary-container active:scale-95 transition-all decoration-none">Dashboard Pimpinan</a>
                <?php else: ?>
                    <a href="<?= $base_path ?>riwayat_booking.php" class="bg-primary text-white text-center py-2 rounded-lg font-bold text-xs hover:bg-primary-container active:scale-95 transition-all decoration-none">Dashboard Penyewa</a>
                <?php endif; ?>
                <a href="<?= $base_path ?>logout.php" class="bg-error-container text-error-red text-center py-2 rounded-lg font-bold text-xs hover:bg-error-red hover:text-white border border-error-red/20 active:scale-95 transition-all decoration-none mt-2">Keluar</a>
            <?php else: ?>
                <a href="<?= $base_path ?>login.php" class="bg-primary text-white text-center py-2 rounded-lg font-bold text-xs hover:bg-primary-container active:scale-95 transition-all decoration-none">Masuk Akun</a>
                <a href="<?= $base_path ?>register.php" class="text-primary text-center py-2 rounded-lg font-bold text-xs border border-primary/20 hover:bg-primary/5 active:scale-95 transition-all decoration-none mt-2">Daftar</a>
            <?php endif; ?>
        </div>
    </nav>
</aside>

<script>
function togglePublicMobileDrawer(isOpen) {
    const overlay = document.getElementById('public-drawer-overlay');
    const drawer = document.getElementById('public-drawer');
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
