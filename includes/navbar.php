<?php
// SIIPAK - Global Navbar Include
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/pimpinan/') !== false);
$base_path = $is_subfolder ? '../' : '';

$role = get_user_role();
$user_name = $_SESSION['user_name'] ?? 'Pengguna';
?>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom no-print">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= $base_path ?>index.php">
            <div class="bg-white rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-building-gear text-primary fs-4"></i>
            </div>
            <div>
                <span class="brand-text-title">SIIPAK</span>
                <span class="brand-text-sub">Politeknik Aceh</span>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' && !$is_subfolder ? 'active' : '' ?>" href="<?= $base_path ?>index.php">
                        <i class="bi bi-house-door me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'gedung.php' ? 'active' : '' ?>" href="<?= $base_path ?>gedung.php">
                        <i class="bi bi-building me-1"></i>Gedung & Aset
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kalender.php' ? 'active' : '' ?>" href="<?= $base_path ?>kalender.php">
                        <i class="bi bi-calendar3 me-1"></i>Kalender Jadwal
                    </a>
                </li>

                <?php if ($role === 'penyewa'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'riwayat_booking.php' ? 'active' : '' ?>" href="<?= $base_path ?>riwayat_booking.php">
                            <i class="bi bi-journal-text me-1"></i>Pemesanan Saya
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active text-warning" href="#" id="adminMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-speedometer2 me-1"></i>Menu Admin
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="adminMenu">
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/index.php"><i class="bi bi-grid-fill me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/booking_kelola.php"><i class="bi bi-journal-check me-2"></i>Kelola Booking</a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/pembayaran_validasi.php"><i class="bi bi-credit-card-checks me-2"></i>Validasi Pembayaran</a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/jadwal.php"><i class="bi bi-calendar-range me-2"></i>Kalender Kegiatan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/gedung_kelola.php"><i class="bi bi-building me-2"></i>Data Ruang</a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/aset_kelola.php"><i class="bi bi-box-seam me-2"></i>Data Aset</a></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/penyewa_kelola.php"><i class="bi bi-people me-2"></i>Data Penyewa</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>admin/laporan.php"><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Rekapitulasi</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($role === 'pimpinan'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-info active" href="<?= $base_path ?>pimpinan/index.php">
                            <i class="bi bi-pie-chart-fill me-1"></i>Dashboard Pimpinan
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span><?= htmlspecialchars($user_name) ?></span>
                            <span class="badge bg-warning text-dark text-uppercase ms-1" style="font-size: 0.7rem;"><?= htmlspecialchars($role) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenu">
                            <li><span class="dropdown-item-text text-muted small">Login sebagai: <strong><?= htmlspecialchars($role) ?></strong></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($role === 'penyewa'): ?>
                                <li><a class="dropdown-item" href="<?= $base_path ?>riwayat_booking.php"><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi</a></li>
                            <?php elseif ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= $base_path ?>admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</a></li>
                            <?php elseif ($role === 'pimpinan'): ?>
                                <li><a class="dropdown-item" href="<?= $base_path ?>pimpinan/index.php"><i class="bi bi-bar-chart-line me-2"></i>Laporan Pimpinan</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_path ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= $base_path ?>login.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                    </a>
                    <a href="<?= $base_path ?>register.php" class="btn btn-warning text-dark font-weight-bold">
                        <i class="bi bi-person-plus me-1"></i>Daftar Penyewa
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
