<?php
// SIIPAK - Dashboard Penyewa
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

check_penyewa();
require_once 'includes/profile_handler.php';

$id_penyewa = $_SESSION['user_id'];

// Fetch all transactions of this penyewa
$stmt = $pdo->prepare("SELECT t.*, g.nama_gedung, g.harga_sewa, g.foto,
                        (SELECT GROUP_CONCAT(CONCAT(a.nama_aset, ' (', ta.jumlah_aset, ' Unit)') SEPARATOR ', ')
                         FROM transaksi_aset ta
                         JOIN aset a ON ta.id_aset = a.id_aset
                         WHERE ta.id_transaksi = t.id_transaksi) AS nama_aset,
                        (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran p WHERE p.id_transaksi = t.id_transaksi AND p.status_validasi = 'Valid') AS total_terbayar,
                        (SELECT COUNT(*) FROM pembayaran p WHERE p.id_transaksi = t.id_transaksi AND p.status_validasi = 'Menunggu') AS pending_verifikasi
                        FROM transaksi t
                        JOIN gedung g ON t.id_gedung = g.id_gedung
                        WHERE t.id_penyewa = :id
                        ORDER BY t.id_transaksi DESC");
$stmt->execute([':id' => $id_penyewa]);
$bookings = $stmt->fetchAll();

// PHP calculations for dynamic dashboard values
$active_bookings = 0;
$total_paid = 0.0;
$total_pending = 0.0;
$alert_booking = null;

foreach ($bookings as $b) {
    if (in_array($b['status_transaksi'], ['Menunggu Pembayaran', 'DP', 'Cicilan'])) {
        $active_bookings++;
        if ($alert_booking === null) {
            $alert_booking = $b;
        }
    }
    $total_paid += (float)$b['total_terbayar'];
    if ($b['status_transaksi'] !== 'Dibatalkan' && $b['status_transaksi'] !== 'Ditolak') {
        $total_pending += (float)($b['total_pembayaran'] - $b['total_terbayar']);
    }
}

// Sync user name with database to keep navbar and sidebar updated in real-time
$stmt_sync = $pdo->prepare("SELECT nama FROM penyewa WHERE id_penyewa = :id");
$stmt_sync->execute([':id' => $id_penyewa]);
$renter_db = $stmt_sync->fetch();
if ($renter_db) {
    $_SESSION['user_name'] = $renter_db['nama'];
}

// User Initials for Avatar
$names = explode(' ', $_SESSION['user_name']);
$initials = '';
if (count($names) > 0) {
    $initials .= strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
} else {
    $initials = 'U';
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIPAK - Dashboard Penyewa</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-bright": "#f8f9ff",
                        "primary": "#000e3a",
                        "on-tertiary-fixed-variant": "#0f521e",
                        "surface-variant": "#d3e3ff",
                        "surface-blue": "#f8f9ff",
                        "on-secondary-fixed": "#241a00",
                        "primary-fixed-dim": "#b5c4ff",
                        "error": "#ba1a1a",
                        "on-secondary-fixed-variant": "#584400",
                        "surface": "#f8f9ff",
                        "surface-container": "#e6eeff",
                        "on-primary-fixed-variant": "#2b4289",
                        "surface-container-high": "#dde9ff",
                        "on-error-container": "#93000a",
                        "on-tertiary-fixed": "#002106",
                        "on-background": "#0b1c30",
                        "secondary-fixed": "#ffe08d",
                        "secondary": "#745b00",
                        "tertiary-container": "#002f0b",
                        "on-tertiary-container": "#5d9d5f",
                        "surface-tint": "#455aa2",
                        "on-primary": "#ffffff",
                        "success-green": "#45bf59",
                        "on-tertiary": "#ffffff",
                        "primary-container": "#002068",
                        "on-error": "#ffffff",
                        "tertiary-fixed": "#aff3ad",
                        "on-primary-fixed": "#00164e",
                        "on-primary-container": "#758bd6",
                        "primary-fixed": "#dce1ff",
                        "inverse-primary": "#b5c4ff",
                        "secondary-container": "#fdd979",
                        "error-container": "#ffdad6",
                        "secondary-fixed-dim": "#e5c366",
                        "inverse-on-surface": "#ebf1ff",
                        "surface-dim": "#cbdbf6",
                        "on-surface": "#0b1c30",
                        "tertiary-fixed-dim": "#93d693",
                        "surface-container-low": "#eff4ff",
                        "error-red": "#ba1a1a",
                        "background": "#f8f9ff",
                        "tertiary": "#001803",
                        "inverse-surface": "#213146",
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#444651",
                        "warning-amber": "#fecb00",
                        "outline-muted": "#c4c5d5",
                        "outline-variant": "#c5c5d2",
                        "outline": "#757682",
                        "on-secondary-container": "#775e03",
                        "surface-container-highest": "#d3e3ff",
                        "on-secondary": "#ffffff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "touch-target": "40px",
                        "xs": "8px",
                        "md": "16px",
                        "gutter": "16px",
                        "xl": "24px",
                        "base": "4px",
                        "container-margin": "16px",
                        "sm": "12px",
                        "lg": "24px"
                    },
                    "fontFamily": {
                        "display-md": ["Plus Jakarta Sans"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "label-lg": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "display-lg": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["15px", {"lineHeight": "22px", "fontWeight": "700"}],
                        "headline-lg-mobile": ["20px", {"lineHeight": "26px", "fontWeight": "800"}],
                        "display-md": ["21px", {"lineHeight": "28px", "letterSpacing": "-0.01em", "fontWeight": "800"}],
                        "headline-lg": ["17px", {"lineHeight": "24px", "fontWeight": "700"}],
                        "display-lg": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.02em", "fontWeight": "800"}],
                        "body-md": ["13px", {"lineHeight": "18px", "fontWeight": "400"}],
                        "label-md": ["11px", {"lineHeight": "14px", "fontWeight": "600"}],
                        "body-lg": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-lg": ["13px", {"lineHeight": "18px", "fontWeight": "600"}]
                    }
                },
            },
        }
    </script>
    <style>
        body {
            background-color: #f8f9ff;
            color: #0b1c30;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: 20px;
        }
        .shadow-soft {
            box-shadow: 0px 2px 8px rgba(0,0,0,0.04);
        }
        .active-nav {
            border-left: 3px solid #fecb00;
            background: rgba(255, 224, 141, 0.12) !important;
            color: #ffe08d !important;
        }
    </style>
</head>
<body class="bg-surface-blue text-on-surface font-body-md overflow-hidden text-xs md:text-sm">
<!-- Main Wrapper -->
<div class="flex h-screen w-full">
    <!-- SideNavBar - Penyewa Dashboard Nav -->
    <aside class="hidden md:flex flex-col h-full py-md bg-primary dark:bg-on-primary w-56 left-0 top-0 border-r border-outline-variant dark:border-outline shadow-soft z-50">
        <div class="px-md mb-lg">
            <div class="flex items-center gap-xs">
                <div class="bg-warning-amber p-1.5 rounded-lg shadow-sm">
                    <span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings: 'FILL' 1;">corporate_fare</span>
                </div>
                <div>
                    <h1 class="text-base font-bold text-white leading-none">SIPAK</h1>
                    <p class="text-[9px] text-primary-fixed-dim opacity-80 mt-0.5">Politeknik Aceh</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 space-y-0.5">
            <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Penyewa Menu</div>
            <!-- Active Nav: Dashboard -->
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="dashboard.php">
                <span class="material-symbols-outlined mr-2" style="font-variation-settings: 'FILL' 1;">dashboard</span>
                <span class="font-label-lg text-label-lg font-bold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking.php">
                <span class="material-symbols-outlined mr-2">add_box</span>
                <span class="font-label-lg text-label-lg font-semibold">Booking Baru</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="riwayat_booking.php">
                <span class="material-symbols-outlined mr-2">receipt_long</span>
                <span class="font-label-lg text-label-lg font-semibold">Transaksi Saya</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="kalender.php">
                <span class="material-symbols-outlined mr-2">calendar_month</span>
                <span class="font-label-lg text-label-lg font-semibold">Jadwal Aset</span>
            </a>
        </nav>
        <div class="mt-auto px-md border-t border-outline/10 pt-md">
            <div class="bg-white/5 border border-white/10 rounded-xl p-2 flex items-center gap-2 mb-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-warning-amber to-amber-300 flex items-center justify-center text-primary font-black text-xs border border-white/10 shadow-sm shrink-0">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="flex flex-col min-w-0">
                    <p class="text-xs font-bold text-white truncate leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                    <span class="text-[9px] text-primary-fixed-dim/60 font-medium tracking-wide mt-0.5">Penyewa</span>
                    <button type="button" onclick="openProfileModal()" class="mt-1 px-2 py-0.5 bg-white/10 hover:bg-white/20 text-warning-amber hover:text-white rounded text-[8px] font-extrabold uppercase tracking-wider transition-all flex items-center gap-1 w-fit cursor-pointer border-none outline-none">
                        <span class="material-symbols-outlined text-[9px]">edit</span> Edit Profil
                    </button>
                </div>
            </div>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-error-container/20 hover:text-error rounded-lg mx-2 transition-all decoration-none font-semibold text-xs mb-2" href="logout.php">
                <span class="material-symbols-outlined mr-2 text-sm">logout</span>
                <span>Keluar</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-surface-blue relative overflow-y-auto">
        <!-- TopNavBar -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-outline-variant flex items-center justify-between px-lg py-2 min-h-[50px] shadow-sm">
            <div class="flex flex-col justify-center">
                <p class="text-[9px] font-medium text-on-surface-variant/80 uppercase tracking-wider">Selamat datang,</p>
                <h2 class="text-base font-bold text-primary leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
            </div>
            <div class="flex items-center gap-xs">
                <div class="bg-surface-container-low px-2.5 py-1 rounded-full border border-outline-variant flex items-center gap-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-success-green inline-block shadow-sm"></span>
                    <span class="text-[10px] font-semibold text-primary">Role: <b>Penyewa</b></span>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <main class="p-lg space-y-md">
            <!-- Header Section -->
            <div class="flex justify-between items-center">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary">Dashboard Penyewa</h1>
                    <p class="text-xs text-on-surface-variant">Kelola reservasi gedung, aset tambahan, dan validasi termin pembayaran Anda.</p>
                </div>
            </div>
            
            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <!-- Active Booking Card -->
                <div class="bg-gradient-to-br from-primary to-[#002068] text-white p-md rounded-2xl shadow-soft border border-primary/20 relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-[10px] text-primary-fixed mb-0.5 opacity-90 uppercase font-semibold tracking-wider">Booking Aktif</p>
                        <h3 class="text-lg mb-0.5 font-black"><?= $active_bookings ?></h3>
                        <p class="text-[10px] text-primary-fixed opacity-75">Sedang berjalan / verifikasi</p>
                    </div>
                    <div class="absolute -right-2 -bottom-2 opacity-10 group-hover:scale-105 transition-transform duration-500">
                        <span class="material-symbols-outlined text-[80px]">confirmation_number</span>
                    </div>
                </div>
                <!-- Total Paid Card -->
                <div class="bg-white p-md rounded-2xl shadow-soft border border-outline-variant flex items-center gap-md hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-success-green/10 flex items-center justify-center text-success-green">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Total Dibayar</p>
                        <h3 class="text-base text-primary font-black mt-0.5"><?= format_rupiah($total_paid) ?></h3>
                    </div>
                </div>
                <!-- Pending Payment Card -->
                <div class="bg-white p-md rounded-2xl shadow-soft border border-outline-variant flex items-center gap-md hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-warning-amber/10 flex items-center justify-center text-warning-amber">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">schedule</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Menunggu Pelunasan</p>
                        <h3 class="text-base text-primary font-black mt-0.5"><?= format_rupiah(max(0, $total_pending)) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Booking List Section -->
            <div class="bg-white rounded-2xl shadow-soft border border-outline-variant overflow-hidden">
                <div class="px-lg py-3 border-b border-outline-variant flex justify-between items-center bg-surface-container-lowest">
                    <h2 class="text-sm text-primary font-bold">Booking Saya</h2>
                    <a class="text-primary font-bold text-[11px] hover:underline decoration-none flex items-center gap-xs" href="riwayat_booking.php">
                        Lihat Semua <span class="material-symbols-outlined text-xs">arrow_forward</span>
                    </a>
                </div>
                <div class="p-md space-y-sm">
                    <?php if (empty($bookings)): ?>
                        <div class="py-lg text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center space-y-sm">
                                <span class="material-symbols-outlined text-4xl text-outline-variant">folder_off</span>
                                <div class="space-y-base">
                                    <p class="font-bold text-primary text-sm">Belum Ada Pemesanan Gedung</p>
                                    <p class="text-[11px] text-on-surface-variant">Riwayat transaksi sewa Anda akan muncul secara detail di halaman ini.</p>
                                </div>
                                <a href="booking.php" class="bg-primary text-white px-md py-1.5 rounded-lg font-bold text-xs hover:shadow-md decoration-none">Sewa Gedung Sekarang</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php 
                        // Show only up to 3 for summary
                        $limited_bookings = array_slice($bookings, 0, 3);
                        foreach ($limited_bookings as $b): 
                        ?>
                            <!-- Individual Booking Row -->
                            <div class="flex flex-col md:flex-row items-center gap-md p-md border border-outline-variant rounded-2xl bg-white hover:border-primary/25 hover:shadow-md transition-all duration-300 group">
                                <div class="w-full md:w-40 h-28 rounded-xl overflow-hidden flex-shrink-0 bg-surface-container shadow-inner border border-outline-variant/40">
                                    <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="assets/uploads/<?= htmlspecialchars($b['foto']) ?>" onerror="this.src='https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=800&q=80'"/>
                                </div>
                                <div class="flex-1 min-w-0 w-full">
                                    <h4 class="text-sm md:text-base text-primary mb-1 font-bold truncate leading-tight"><?= htmlspecialchars($b['nama_gedung']) ?></h4>
                                    <p class="text-xs text-on-surface-variant font-medium opacity-90 truncate">"<?= htmlspecialchars($b['nama_kegiatan']) ?>"</p>
                                    <div class="flex flex-wrap items-center gap-2 mt-2.5">
                                        <span class="flex items-center gap-xs font-semibold text-xs bg-slate-100/90 text-slate-700 px-2.5 py-0.5 rounded-lg border-none shadow-xs">
                                            <span class="material-symbols-outlined text-[13px] text-slate-500">tag</span>
                                            <?= htmlspecialchars($b['kode_transaksi']) ?>
                                        </span>
                                        <span class="flex items-center gap-xs font-semibold text-xs bg-primary/5 text-primary px-2.5 py-0.5 rounded-lg border border-primary/10 shadow-xs">
                                            <span class="material-symbols-outlined text-[13px]">event</span>
                                            <?= format_tanggal($b['tanggal_mulai']) ?> s/d <?= format_tanggal($b['tanggal_selesai']) ?>
                                        </span>
                                        <?php if ($b['status_transaksi'] !== 'Lunas' && $b['status_transaksi'] !== 'Selesai' && $b['status_transaksi'] !== 'Dibatalkan' && $b['status_transaksi'] !== 'Ditolak'): ?>
                                            <?php 
                                            $tgl_mulai = new DateTime($b['tanggal_mulai']);
                                            $tgl_deadline = $tgl_mulai->modify('-1 day')->format('Y-m-d');
                                            ?>
                                            <span class="flex items-center gap-xs font-semibold text-xs bg-rose-50 text-rose-600 px-2.5 py-0.5 rounded-lg border border-rose-100 shadow-xs">
                                                <span class="material-symbols-outlined text-[13px] text-rose-500">warning</span>
                                                Batas Bayar: <?= format_tanggal($tgl_deadline) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['nama_aset'])): ?>
                                            <span class="flex items-center gap-xs font-semibold text-xs bg-teal-50 text-teal-700 px-2.5 py-0.5 rounded-lg border border-teal-100 shadow-xs">
                                                <span class="material-symbols-outlined text-[13px] text-teal-600">widgets</span>
                                                + <?= htmlspecialchars($b['nama_aset']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-col md:items-end items-start gap-sm shrink-0 w-full md:w-auto mt-sm md:mt-0">
                                    <!-- Pricing & Remaining Balance -->
                                    <div class="text-left md:text-right">
                                        <p class="text-sm md:text-base text-primary font-black"><?= format_rupiah($b['total_pembayaran']) ?></p>
                                        <?php if ($b['status_transaksi'] === 'DP' || $b['status_transaksi'] === 'Cicilan'): ?>
                                            <span class="inline-block text-xs text-[#2563eb] font-bold bg-blue-50/80 px-2 py-0.5 rounded mt-0.5 shadow-xs">Sisa: <?= format_rupiah($b['total_pembayaran'] - $b['total_terbayar']) ?></span>
                                        <?php elseif ($b['status_transaksi'] === 'Menunggu Pembayaran'): ?>
                                            <span class="inline-block text-xs text-secondary font-bold bg-orange-50 px-2 py-0.5 rounded mt-0.5 shadow-xs">Sisa: <?= format_rupiah($b['total_pembayaran']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Status Badges -->
                                    <div class="flex items-center gap-xs mt-0.5">
                                        <?php
                                        $status = $b['status_transaksi'];
                                        $badge = 'bg-warning-amber/10 text-secondary border-warning-amber/20';
                                        if ($status === 'Lunas') {
                                            $badge = 'bg-success-green/10 text-success-green border-success-green/20';
                                        } elseif ($status === 'DP' || $status === 'Cicilan') {
                                            $badge = 'bg-[#2563eb]/10 text-[#2563eb] border border-[#2563eb]/20';
                                        } elseif ($status === 'Ditolak') {
                                            $badge = 'bg-error-container text-on-error-container border-error/20';
                                        } elseif ($status === 'Dibatalkan') {
                                            $badge = 'bg-outline-variant/20 text-on-surface-variant border-outline-variant/30';
                                        }
                                        ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $badge ?>">
                                            <?= $status ?>
                                        </span>
                                        <?php if ($b['pending_verifikasi'] > 0): ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border bg-amber-500/10 text-amber-600 border-amber-500/20" title="Pembayaran baru Anda sedang diperiksa oleh Admin.">
                                                Menunggu Verifikasi
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Action Buttons -->
                                    <div class="w-full md:w-auto text-right mt-1">
                                        <?php if ($status === 'Lunas' || $status === 'Selesai'): ?>
                                            <div class="flex gap-1.5">
                                                <a href="pembayaran_upload.php?id_transaksi=<?= $b['id_transaksi'] ?>" class="inline-block bg-white border border-outline-variant/60 text-primary font-semibold px-3 py-1.5 rounded-lg hover:bg-surface-container-low transition-all decoration-none text-xs text-center active:scale-95 shadow-xs">
                                                    Detail
                                                </a>
                                                <a href="kuitansi.php?id_transaksi=<?= $b['id_transaksi'] ?>" target="_blank" class="inline-flex items-center justify-center gap-xs bg-success-green hover:bg-success-green/90 text-white font-bold px-3 py-1.5 rounded-lg hover:shadow-md transition-all decoration-none text-xs text-center active:scale-95 shadow-xs">
                                                    <span class="material-symbols-outlined text-xs">print</span> Kuitansi
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <a href="pembayaran_upload.php?id_transaksi=<?= $b['id_transaksi'] ?>" class="w-full md:w-auto inline-flex items-center justify-center gap-xs bg-primary hover:bg-primary-container text-white font-bold px-4 py-1.5 rounded-lg hover:shadow-md transition-all decoration-none text-xs text-center active:scale-95 shadow-md">
                                                <span class="material-symbols-outlined text-xs">payment</span> Bayar / Detail
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer Alert / Info -->
            <div class="p-md bg-surface-container rounded-2xl border border-outline-variant flex items-start gap-md hover:shadow-sm transition-all duration-300">
                <span class="material-symbols-outlined text-primary text-xl">info</span>
                <div>
                    <h5 class="text-xs text-primary font-bold">Informasi Penting</h5>
                    <?php if ($alert_booking !== null): ?>
                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">Harap lakukan pelunasan untuk booking <strong class="text-primary">"<?= htmlspecialchars($alert_booking['nama_gedung']) ?>"</strong> (Kode: <?= htmlspecialchars($alert_booking['kode_transaksi']) ?>) paling lambat <b>H-1 sebelum hari pelaksanaan</b> (Sebelum tanggal <?= format_tanggal($alert_booking['tanggal_mulai']) ?>). Jika melewati batas H-1, sistem akan secara otomatis membatalkan pesanan Anda dan membebaskan jadwal gedung.</p>
                    <?php else: ?>
                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">Seluruh booking Anda dalam keadaan aman. Terima kasih telah menggunakan layanan SIPAK Politeknik Aceh!</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<!-- Micro-interaction Script -->
<script>
    document.querySelectorAll('button, a').forEach(elem => {
        elem.addEventListener('mousedown', () => {
            elem.style.transform = 'scale(0.97)';
        });
        elem.addEventListener('mouseup', () => {
            elem.style.transform = 'scale(1)';
        });
        elem.addEventListener('mouseleave', () => {
            elem.style.transform = 'scale(1)';
        });
    });
</script>
<?php include 'includes/profile_modal.php'; ?>
<?php include 'includes/mobile_menu_admin.php'; ?>
</body>
</html>
