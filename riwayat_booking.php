<?php
// SIIPAK - Transaksi Saya (Riwayat Booking Penyewa)
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
                        (SELECT COUNT(*) FROM pembayaran p WHERE p.id_transaksi = t.id_transaksi AND p.status_validasi = 'Menunggu') AS pending_verifikasi,
                        (SELECT COALESCE(jumlah_bayar, 0) FROM pembayaran WHERE id_transaksi = t.id_transaksi AND status_validasi = 'Valid' ORDER BY id_pembayaran ASC LIMIT 1) AS termin1,
                        (SELECT COALESCE(jumlah_bayar, 0) FROM pembayaran WHERE id_transaksi = t.id_transaksi AND status_validasi = 'Valid' ORDER BY id_pembayaran ASC LIMIT 1 OFFSET 1) AS termin2
                        FROM transaksi t
                        JOIN gedung g ON t.id_gedung = g.id_gedung
                        WHERE t.id_penyewa = :id
                        ORDER BY t.id_transaksi DESC");
$stmt->execute([':id' => $id_penyewa]);
$bookings = $stmt->fetchAll();

// PHP calculations for dynamic values
$active_bookings = 0;
$total_paid = 0.0;
$total_pending = 0.0;
$alert_booking = null;
$success_bookings = 0;

foreach ($bookings as $b) {
    if (in_array($b['status_transaksi'], ['Menunggu Pembayaran', 'DP', 'Cicilan'])) {
        $active_bookings++;
        if ($alert_booking === null) {
            $alert_booking = $b;
        }
    }
    if (in_array($b['status_transaksi'], ['Lunas', 'Selesai'])) {
        $success_bookings++;
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
$names = explode(' ', $_SESSION['user_name'] ?? 'Penyewa');
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
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Transaksi Saya | SIPAK Politeknik Aceh</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "on-primary-fixed": "#00164e",
                    "secondary-fixed": "#ffe08d",
                    "on-tertiary-fixed": "#002106",
                    "surface-container-high": "#dde9ff",
                    "error-container": "#ffdad6",
                    "on-secondary-container": "#775e03",
                    "on-tertiary": "#ffffff",
                    "on-secondary-fixed-variant": "#584400",
                    "on-surface-variant": "#444651",
                    "on-surface": "#0b1c30",
                    "tertiary": "#001803",
                    "surface-variant": "#d3e3ff",
                    "surface-tint": "#455aa2",
                    "inverse-primary": "#b5c4ff",
                    "primary-container": "#002068",
                    "background": "#f8f9ff",
                    "surface-bright": "#f8f9ff",
                    "outline-muted": "#c4c5d5",
                    "surface-container": "#e6eeff",
                    "error-red": "#ba1a1a",
                    "error": "#ba1a1a",
                    "outline": "#757682",
                    "on-primary-container": "#758bd6",
                    "warning-amber": "#fecb00",
                    "outline-variant": "#c5c5d2",
                    "primary": "#000e3a",
                    "inverse-surface": "#213146",
                    "secondary": "#745b00",
                    "tertiary-container": "#002f0b",
                    "on-secondary-fixed": "#241a00",
                    "surface-container-lowest": "#ffffff",
                    "surface": "#f8f9ff",
                    "on-primary-fixed-variant": "#2b4289",
                    "tertiary-fixed": "#aff3ad",
                    "on-secondary": "#ffffff",
                    "on-error-container": "#93000a",
                    "surface-container-highest": "#d3e3ff",
                    "secondary-container": "#fdd979",
                    "success-green": "#45bf59",
                    "surface-container-low": "#eff4ff",
                    "primary-fixed-dim": "#b5c4ff",
                    "surface-blue": "#f8f9ff",
                    "on-tertiary-fixed-variant": "#0f521e",
                    "inverse-on-surface": "#ebf1ff",
                    "on-tertiary-container": "#5d9d5f",
                    "primary-fixed": "#dce1ff",
                    "on-primary": "#ffffff",
                    "on-background": "#0b1c30",
                    "on-error": "#ffffff",
                    "tertiary-fixed-dim": "#93d693",
                    "surface-dim": "#cbdbf6",
                    "secondary-fixed-dim": "#e5c366"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "gutter": "16px",
                    "base": "4px",
                    "md": "16px",
                    "lg": "24px",
                    "xs": "8px",
                    "sm": "12px",
                    "container-margin": "16px",
                    "touch-target": "40px",
                    "xl": "24px"
            },
            "fontFamily": {
                    "headline-md": ["Plus Jakarta Sans"],
                    "headline-lg-mobile": ["Plus Jakarta Sans"],
                    "display-md": ["Plus Jakarta Sans"],
                    "headline-lg": ["Plus Jakarta Sans"],
                    "display-lg": ["Plus Jakarta Sans"],
                    "body-md": ["Plus Jakarta Sans"],
                    "label-md": ["Plus Jakarta Sans"],
                    "body-lg": ["Plus Jakarta Sans"],
                    "label-lg": ["Plus Jakarta Sans"]
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
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.04);
        }
        .bento-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .bento-card:hover {
            transform: translateY(-1px);
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.06);
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
            <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Menu Utama</div>
            
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="dashboard.php">
                <span class="font-label-lg text-label-lg font-semibold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking.php">
                <span class="font-label-lg text-label-lg font-semibold">Booking Baru</span>
            </a>
            
            <!-- Active State Navigation -->
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="riwayat_booking.php">
                <span class="font-label-lg text-label-lg font-bold">Transaksi Saya</span>
            </a>
            
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="kalender.php">
                <span class="font-label-lg text-label-lg font-semibold">Kalender Kegiatan</span>
            </a>
        </nav>

        <!-- User Profile & Logout at sidebar bottom -->
        <div class="mt-auto px-md border-t border-outline/10 pt-md">
            <div class="bg-white/5 border border-white/10 rounded-xl p-2 flex items-center gap-2 mb-3">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-warning-amber to-amber-300 flex items-center justify-center text-primary font-black text-xs border border-white/10 shadow-sm shrink-0">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="flex flex-col min-w-0">
                    <p class="text-xs font-bold text-white truncate leading-tight"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                    <span class="text-[9px] text-primary-fixed-dim/60 font-medium tracking-wide mt-0.5">Penyewa</span>
                    <button type="button" onclick="openProfileModal()" class="mt-1 px-2 py-0.5 bg-white/10 hover:bg-white/20 text-warning-amber hover:text-white rounded text-[8px] font-extrabold uppercase tracking-wider transition-all flex items-center gap-1 w-fit cursor-pointer border-none outline-none">
                        Edit Profil
                    </button>
                </div>
            </div>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-error-container/20 hover:text-error rounded-lg mx-2 transition-all decoration-none font-semibold text-xs mb-2" href="logout.php">
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-surface-blue relative overflow-y-auto">
        <!-- Header -->
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

        <!-- Content Canvas -->
        <main class="p-lg space-y-md">
            <!-- Hero Title Section -->
            <div class="space-y-0.5">
                <h1 class="font-display-md text-display-md text-primary font-bold">Transaksi Saya</h1>
                <p class="text-xs text-on-surface-variant max-w-2xl">
                    Kelola dan pantau riwayat semua booking aset kampus Politeknik Aceh serta status pembayaran Anda secara real-time.
                </p>
            </div>

            <!-- Bento Layout Content -->
            <div class="grid grid-cols-12 gap-md">
                <!-- Summary Stats (Top Row) -->
                <div class="col-span-12 lg:col-span-4 bg-white p-sm rounded-xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-secondary-container/20 flex items-center justify-center text-secondary">
                        <span class="material-symbols-outlined text-xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant text-[10px] uppercase font-semibold tracking-wider">Menunggu</p>
                        <h4 class="text-base text-primary font-bold mt-0.5"><?= $active_bookings ?></h4>
                    </div>
                </div>
                
                <div class="col-span-12 lg:col-span-4 bg-white p-sm rounded-xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-success-green/10 flex items-center justify-center text-success-green">
                        <span class="material-symbols-outlined text-xl">check_circle</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant text-[10px] uppercase font-semibold tracking-wider">Berhasil</p>
                        <h4 class="text-base text-primary font-bold mt-0.5"><?= $success_bookings ?></h4>
                    </div>
                </div>
                
                <div class="col-span-12 lg:col-span-4 bg-white p-sm rounded-xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-xl">payments</span>
                    </div>
                    <div>
                        <p class="text-on-surface-variant text-[10px] uppercase font-semibold tracking-wider">Total Pembayaran</p>
                        <h4 class="text-base text-primary font-bold mt-0.5"><?= format_rupiah($total_paid) ?></h4>
                    </div>
                </div>

                <!-- Main Transaction Table Card -->
                <div class="col-span-12 bg-white rounded-xl border border-outline-variant shadow-soft overflow-hidden">
                    <div class="px-lg py-2.5 border-b border-outline-variant flex flex-col sm:flex-row justify-between items-center bg-surface-container-lowest gap-sm">
                        <div class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-primary text-sm">history</span>
                            <h5 class="text-sm font-bold text-primary">Riwayat Transaksi</h5>
                        </div>
                        <div class="flex gap-xs w-full sm:w-auto">
                            <div class="relative flex-grow">
                                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                                <input class="pl-8 pr-md py-1 bg-surface-container-low border-none rounded-lg text-xs focus:ring-1 focus:ring-primary/20 w-full sm:w-56 text-on-surface" placeholder="Cari Kode atau Gedung..." type="text" id="searchInput" onkeyup="filterTable()"/>
                            </div>
                        </div>
                    </div>

                    <!-- Policy notice banner -->
                    <div class="m-md bg-warning-amber/10 border border-warning-amber/20 p-sm rounded-xl flex items-start gap-xs text-[11px]">
                        <span class="material-symbols-outlined text-secondary text-sm flex-shrink-0">warning</span>
                        <div class="space-y-0.5 text-on-surface-variant">
                            <strong class="text-primary block font-bold">Kebijakan Batas Pelunasan H-1 &amp; Uang Muka (DP)</strong>
                            <p class="leading-relaxed">
                                Pelunasan pembayaran sewa wajib diselesaikan paling lambat pada <strong>H-1 acara</strong>. Jika sisa tagihan tidak dilunasi sebelum tanggal pelaksanaan, sistem akan membatalkan pesanan secara otomatis, jadwal dibebaskan kembali, dan <strong>dana DP dinyatakan HANGUS (tidak dapat dikembalikan).</strong>
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse align-middle">
                            <thead>
                                <tr class="bg-surface-container-low text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
                                    <th class="px-lg py-2 min-w-[140px]">Kode</th>
                                    <th class="px-lg py-2 min-w-[200px]">Aset / Gedung</th>
                                    <th class="px-lg py-2 min-w-[150px]">Tanggal</th>
                                    <th class="px-lg py-2 text-right min-w-[110px]">Termin 1</th>
                                    <th class="px-lg py-2 text-right min-w-[110px]">Termin 2</th>
                                    <th class="px-lg py-2 text-right min-w-[110px]">Sisa</th>
                                    <th class="px-lg py-2 text-right min-w-[110px]">Total</th>
                                    <th class="px-lg py-2 text-center min-w-[110px]">Status</th>
                                    <th class="px-lg py-2 text-center min-w-[150px]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface" id="transactionTable">
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="9" class="px-lg py-6 text-center text-on-surface-variant">
                                            Belum ada data transaksi sewa.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $b): ?>
                                        <?php
                                        $status = $b['status_transaksi'];
                                        $badge = 'bg-warning-amber/10 text-secondary border-warning-amber/20';
                                        if ($status === 'Lunas' || $status === 'Selesai') {
                                            $badge = 'bg-success-green/10 text-success-green border border-success-green/20';
                                        } elseif ($status === 'DP' || $status === 'Cicilan') {
                                            $badge = 'bg-[#2563eb]/10 text-[#2563eb] border border-[#2563eb]/20';
                                        } elseif ($status === 'Ditolak') {
                                            $badge = 'bg-error-container text-on-error-container border border-error/20';
                                        } elseif ($status === 'Dibatalkan') {
                                            $badge = 'bg-outline-variant/20 text-on-surface-variant border border-outline-variant/30';
                                        }
                                        ?>
                                        <tr class="hover:bg-surface-container-lowest transition-colors">
                                            <td class="px-lg py-2.5 font-bold text-primary"><?= htmlspecialchars($b['kode_transaksi']) ?></td>
                                            <td class="px-lg py-2.5">
                                                <div class="flex items-center gap-xs">
                                                    <div class="w-8 h-8 rounded-lg bg-surface-container-high overflow-hidden flex-shrink-0">
                                                        <img class="w-full h-full object-cover" src="assets/uploads/<?= htmlspecialchars($b['foto']) ?>" onerror="this.src='https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=800&q=80'"/>
                                                    </div>
                                                    <div class="flex flex-col min-w-0">
                                                        <span class="font-semibold text-on-surface truncate max-w-[150px]"><?= htmlspecialchars($b['nama_gedung']) ?></span>
                                                        <?php if (!empty($b['nama_aset'])): ?>
                                                            <span class="text-[9px] text-on-surface-variant font-medium mt-0.5 truncate max-w-[150px]">+ Aset: <?= htmlspecialchars($b['nama_aset']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                             <td class="px-lg py-2.5">
                                                 <span class="block text-on-surface font-semibold"><?= format_tanggal($b['tanggal_mulai']) ?></span>
                                                 <?php if ($status !== 'Lunas' && $status !== 'Selesai' && $status !== 'Dibatalkan' && $status !== 'Ditolak'): ?>
                                                     <?php 
                                                     $tgl_mulai = new DateTime($b['tanggal_mulai']);
                                                     $tgl_deadline = $tgl_mulai->modify('-1 day')->format('Y-m-d');
                                                     ?>
                                                     <span class="block text-[9px] text-error font-bold mt-0.5" title="Jika melewati tanggal ini tanpa pelunasan, DP hangus dan pemesanan dibatalkan otomatis.">
                                                         Batas Bayar: <?= format_tanggal($tgl_deadline) ?>
                                                     </span>
                                                 <?php endif; ?>
                                             </td>
                                              <td class="px-lg py-2.5 text-right font-semibold text-primary"><?= format_rupiah($b['termin1'] ?? 0) ?></td>
                                              <td class="px-lg py-2.5 text-right font-semibold text-primary"><?= format_rupiah($b['termin2'] ?? 0) ?></td>
                                              <td class="px-lg py-2.5 text-right font-bold text-error-red"><?= format_rupiah(max(0, $b['total_pembayaran'] - (($b['termin1'] ?? 0) + ($b['termin2'] ?? 0)))) ?></td>
                                              <td class="px-lg py-2.5 text-right font-extrabold text-primary"><?= format_rupiah($b['total_pembayaran']) ?></td>
                                            <td class="px-lg py-2.5 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full <?= $badge ?> text-[10px] font-bold">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                                <?php if ($b['pending_verifikasi'] > 0): ?>
                                                    <span class="block text-[8px] text-amber-600 font-bold mt-0.5" title="Pembayaran baru Anda sedang diperiksa oleh Admin.">
                                                        (Menunggu Verifikasi)
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-lg py-2.5 text-center">
                                                <?php if ($status === 'Lunas' || $status === 'Selesai'): ?>
                                                    <div class="flex items-center justify-center gap-1">
                                                        <a href="pembayaran_upload.php?id_transaksi=<?= $b['id_transaksi'] ?>" class="text-primary font-bold text-[10px] px-2 py-1 rounded-lg border border-primary hover:bg-primary/5 transition-all decoration-none inline-block">
                                                            Detail
                                                        </a>
                                                        <a href="kuitansi.php?token=<?= $b['token_kuitansi'] ?>" target="_blank" class="bg-success-green hover:bg-success-green/90 text-white font-bold text-[10px] px-2 py-1 rounded-lg hover:shadow-md transition-all decoration-none inline-block">
                                                            Kuitansi
                                                        </a>
                                                    </div>
                                                <?php elseif ($status === 'Dibatalkan'): ?>
                                                    <a href="pembayaran_upload.php?id_transaksi=<?= $b['id_transaksi'] ?>" class="text-primary font-bold text-[10px] px-2.5 py-1 rounded-lg border border-primary hover:bg-primary/5 transition-all decoration-none inline-block">
                                                        Lihat Detail
                                                    </a>
                                                <?php else: ?>
                                                    <a href="pembayaran_upload.php?id_transaksi=<?= $b['id_transaksi'] ?>" class="bg-primary text-on-primary font-bold text-[10px] px-2.5 py-1 rounded-lg hover:shadow-md active:scale-95 transition-all decoration-none inline-block">
                                                        Kelola Pembayaran
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Pagination & Count -->
                    <div class="px-lg py-2 border-t border-outline-variant flex items-center justify-between bg-surface-container-lowest text-[10px] text-on-surface-variant">
                        <p>Menampilkan <?= count($bookings) ?> transaksi</p>
                    </div>
                </div>

                <!-- Info Card Section -->
                <div class="col-span-12 lg:col-span-7 bg-primary text-on-primary p-md rounded-xl relative overflow-hidden bento-card">
                    <div class="relative z-10 space-y-xs">
                        <h6 class="text-xs font-bold text-white">Butuh Bantuan Pembayaran?</h6>
                        <p class="text-[11px] opacity-80 max-w-lg text-primary-fixed-dim leading-relaxed">
                            Jika Anda mengalami kendala saat melakukan konfirmasi pembayaran atau membutuhkan invoice fisik, silakan hubungi tim administrasi SIPAK melalui kanal bantuan kami.
                        </p>
                        <div class="flex gap-sm pt-2">
                            <a href="https://wa.me/6281269001234" target="_blank" class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-lg font-bold text-[11px] hover:shadow-lg transition-all decoration-none">
                                Hubungi Admin
                            </a>
                            <a href="bantuan.php" class="bg-white/10 text-white border border-white/20 px-3 py-1 rounded-lg font-bold text-[11px] hover:bg-white/20 transition-all decoration-none">
                                Pusat Bantuan
                            </a>
                        </div>
                    </div>
                    <!-- Decorative Element -->
                    <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-secondary-fixed/10 rounded-full blur-3xl"></div>
                    <span class="material-symbols-outlined absolute right-8 top-1/2 -translate-y-1/2 text-[90px] opacity-10 pointer-events-none">help_center</span>
                </div>

                <div class="col-span-12 lg:col-span-5 bg-white p-md rounded-xl border border-outline-variant shadow-soft bento-card flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-xs mb-2">
                            <span class="material-symbols-outlined text-warning-amber text-sm">warning</span>
                            <h6 class="text-[10px] text-on-surface uppercase tracking-wider font-bold">Peringatan Penting</h6>
                        </div>
                        <p class="text-[11px] text-on-surface-variant leading-relaxed">
                            <?php if ($alert_booking !== null): ?>
                                Pembayaran untuk kode sewa <span class="font-bold text-on-surface"><?= htmlspecialchars($alert_booking['kode_transaksi']) ?></span> (<?= htmlspecialchars($alert_booking['nama_gedung']) ?>) harap segera diselesaikan untuk mengamankan slot booking Anda.
                            <?php else: ?>
                                Seluruh booking Anda dalam keadaan aman. Terima kasih telah menggunakan layanan SIPAK Politeknik Aceh!
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="mt-4 pt-2 border-t border-outline-variant flex items-center justify-between text-[10px]">
                        <span class="text-on-surface-variant">Update terakhir: Baru saja</span>
                        <a class="text-primary font-bold underline underline-offset-4 decoration-none" href="bantuan.php">Lihat Ketentuan</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Search Bar filtering logic
    function filterTable() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("transactionTable");
        var tr = table.getElementsByTagName("tr");
        
        for (var i = 0; i < tr.length; i++) {
            var tdCode = tr[i].getElementsByTagName("td")[0];
            var tdAset = tr[i].getElementsByTagName("td")[1];
            if (tdCode || tdAset) {
                var codeText = tdCode.textContent || tdCode.innerText;
                var assetText = tdAset.textContent || tdAset.innerText;
                if (codeText.toUpperCase().indexOf(filter) > -1 || assetText.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    // Interactive button scaling
    document.querySelectorAll('button, a').forEach(btn => {
        btn.addEventListener('mousedown', () => {
            btn.style.transform = 'scale(0.96)';
        });
        btn.addEventListener('mouseup', () => {
            btn.style.transform = 'scale(1)';
        });
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = 'scale(1)';
        });
    });
</script>
<?php include 'includes/profile_modal.php'; ?>
<?php include 'includes/mobile_menu_admin.php'; ?>
</body>
</html>
