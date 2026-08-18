<?php
// SIIPAK - Dashboard Admin Pengelola
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_admin();

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch summary metrics
$total_booking = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
$pending_val = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_validasi = 'Menunggu'")->fetchColumn();
$total_lunas = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_transaksi = 'Lunas'")->fetchColumn();
$total_pendapatan = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran WHERE status_validasi = 'Valid'")->fetchColumn();

// Fetch recent pending validations
$stmt_pending = $pdo->query("SELECT p.*, t.kode_transaksi, t.nama_kegiatan, py.nama AS nama_penyewa, g.nama_gedung 
                             FROM pembayaran p
                             JOIN transaksi t ON p.id_transaksi = t.id_transaksi
                             JOIN penyewa py ON t.id_penyewa = py.id_penyewa
                             JOIN gedung g ON t.id_gedung = g.id_gedung
                             WHERE p.status_validasi = 'Menunggu'
                             ORDER BY p.id_pembayaran DESC LIMIT 5");
$pending_list = $stmt_pending->fetchAll();

// Fetch recent transactions
$stmt_recent = $pdo->query("SELECT t.*, g.nama_gedung, py.nama AS nama_penyewa 
                            FROM transaksi t
                            JOIN gedung g ON t.id_gedung = g.id_gedung
                            JOIN penyewa py ON t.id_penyewa = py.id_penyewa
                            ORDER BY t.id_transaksi DESC LIMIT 5");
$recent_list = $stmt_recent->fetchAll();

// User Initials for Avatar
$names = explode(' ', $admin_name);
$initials = '';
if (count($names) > 0) {
    $initials .= strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
} else {
    $initials = 'A';
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard Admin | SIPAK Politeknik Aceh</title>
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
    <!-- SideNavBar - Admin Dashboard Nav -->
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
        
        <nav class="flex-1 space-y-0.5 overflow-y-auto">
            <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Admin Menu</div>
            
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="index.php">
                <span class="font-label-lg text-label-lg font-bold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking_kelola.php">
                <span class="font-label-lg text-label-lg">Kelola Booking</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none relative" href="pembayaran_validasi.php">
                <span class="font-label-lg text-label-lg">Validasi Bayar</span>
                <?php if ($pending_val > 0): ?>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 bg-error-red text-white text-[9px] rounded-full flex items-center justify-center font-bold">
                        <?= $pending_val ?>
                    </span>
                <?php endif; ?>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="jadwal.php">
                <span class="font-label-lg text-label-lg">Kalender Kegiatan</span>
            </a>
            
            <div class="h-[1px] bg-outline-muted/5 my-1.5 mx-md"></div>
            <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Data Master</div>

            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="gedung_kelola.php">
                <span class="font-label-lg text-label-lg">Data Ruang</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="aset_kelola.php">
                <span class="font-label-lg text-label-lg">Data Aset</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="penyewa_kelola.php">
                <span class="font-label-lg text-label-lg">Data Penyewa</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="laporan.php">
                <span class="font-label-lg text-label-lg">Laporan Rekap</span>
            </a>
        </nav>

        <!-- User Profile & Logout at bottom -->
        <div class="mt-auto px-md space-y-0.5 border-t border-outline/10 pt-md">
            <div class="flex items-center gap-xs px-xs py-1 mb-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-xs shadow-md">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <p class="text-xs font-semibold text-white truncate leading-none"><?= htmlspecialchars($admin_name) ?></p>
                    <p class="text-[9px] text-primary-fixed-dim opacity-70 mt-0.5 truncate">Administrator</p>
                </div>
            </div>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-error-container/20 hover:text-error rounded-lg mx-2 transition-all decoration-none font-semibold text-xs" href="../logout.php">
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
                <h2 class="text-base font-bold text-primary leading-tight"><?= htmlspecialchars($admin_name) ?></h2>
            </div>
            <div class="flex items-center gap-xs">
                <div class="bg-surface-container-low px-2.5 py-1 rounded-full border border-outline-variant flex items-center gap-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-error-red inline-block shadow-sm"></span>
                    <span class="text-[10px] font-semibold text-primary">Role: <b>Admin Pengelola</b></span>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="p-lg space-y-md">
            <!-- Header Section -->
            <div class="space-y-0.5">
                <h1 class="font-display-md text-display-md text-primary font-bold">Dashboard Admin Pengelola</h1>
                <p class="text-xs text-on-surface-variant">Ringkasan sistem penyewaan gedung &amp; validasi pembayaran kampus Politeknik Aceh.</p>
            </div>

            <!-- Flash Session Messaging -->
            <!-- Flash Session Messaging -->
            <?php if (isset($_SESSION['flash'])): ?>
                <?php 
                $type = $_SESSION['flash']['type'] ?? 'success';
                $msgClass = 'bg-success-green/10 text-success-green border-success-green/20';
                $icon = 'check_circle';
                
                if ($type === 'danger' || $type === 'error') {
                    $msgClass = 'bg-error-container text-on-error-container border-error/20';
                    $icon = 'error';
                } elseif ($type === 'warning') {
                    $msgClass = 'bg-warning-amber/10 text-secondary border-warning-amber/20';
                    $icon = 'warning';
                }
                ?>
                <div class="<?= $msgClass ?> p-sm rounded-xl border flex items-center gap-xs text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm"><?= $icon ?></span>
                    <span><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
                <!-- Stat Card 1 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-primary-container/5 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-xl">book_online</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Total Pemesanan</p>
                        <h3 class="text-base text-primary font-black mt-0.5"><?= $total_booking ?></h3>
                    </div>
                </div>
                <!-- Stat Card 2 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card relative">
                    <div class="w-10 h-10 rounded-xl bg-warning-amber/10 flex items-center justify-center text-secondary">
                        <span class="material-symbols-outlined text-xl">pending_actions</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Butuh Validasi</p>
                        <h3 class="text-base text-warning-amber font-black mt-0.5"><?= $pending_val ?></h3>
                    </div>
                    <?php if ($pending_val > 0): ?>
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-error-red animate-pulse"></span>
                    <?php endif; ?>
                </div>
                <!-- Stat Card 3 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-success-green/10 flex items-center justify-center text-success-green">
                        <span class="material-symbols-outlined text-xl">verified</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Selesai / Lunas</p>
                        <h3 class="text-base text-success-green font-black mt-0.5"><?= $total_lunas ?></h3>
                    </div>
                </div>
                <!-- Stat Card 4 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-[#2563eb]/10 flex items-center justify-center text-[#2563eb]">
                        <span class="material-symbols-outlined text-xl">monetization_on</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Total Pendapatan</p>
                        <h3 class="text-base text-primary font-black mt-0.5"><?= format_rupiah($total_pendapatan) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Urgent Payment Validation Action List -->
            <?php if (!empty($pending_list)): ?>
                <div class="bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                    <div class="px-lg py-sm border-b border-outline-variant bg-warning-amber/10 flex items-center justify-between">
                        <div class="flex items-center gap-xs">
                            <span class="material-symbols-outlined text-secondary text-sm">notification_important</span>
                            <h4 class="text-xs text-on-surface font-bold">Verifikasi Pembayaran Baru (Tindakan Segera)</h4>
                        </div>
                        <a href="pembayaran_validasi.php" class="bg-primary text-white px-2.5 py-1 rounded-lg text-[10px] font-bold hover:bg-primary-container decoration-none transition-all">Lihat Semua</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse align-middle">
                            <thead>
                                <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-lg py-2">Kode Booking</th>
                                    <th class="px-lg py-2">Penyewa &amp; Gedung</th>
                                    <th class="px-lg py-2">Skema</th>
                                    <th class="px-lg py-2 text-right">Nominal</th>
                                    <th class="px-lg py-2 text-center">Berkas Bukti</th>
                                    <th class="px-lg py-2 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                                <?php foreach ($pending_list as $pl): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                        <td class="px-lg py-2 font-bold text-primary"><?= htmlspecialchars($pl['kode_transaksi']) ?></td>
                                        <td class="px-lg py-2">
                                            <div class="flex flex-col">
                                                <strong class="text-on-surface font-semibold"><?= htmlspecialchars($pl['nama_penyewa']) ?></strong>
                                                <span class="text-[10px] text-on-surface-variant"><?= htmlspecialchars($pl['nama_gedung']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-lg py-2">
                                            <span class="bg-surface-container text-primary text-[10px] px-2 py-0.5 rounded-full font-bold border border-outline-variant/40"><?= $pl['jenis_pembayaran'] ?></span>
                                        </td>
                                        <td class="px-lg py-2 text-right font-bold text-primary"><?= format_rupiah($pl['jumlah_bayar']) ?></td>
                                        <td class="px-lg py-2 text-center">
                                            <a href="../assets/uploads/<?= htmlspecialchars($pl['bukti_transfer']) ?>" target="_blank" class="inline-flex items-center gap-xs border border-primary text-primary px-2.5 py-0.5 rounded-lg text-[10px] font-bold hover:bg-primary hover:text-white transition-all decoration-none">
                                                <span class="material-symbols-outlined text-[12px]">visibility</span> Buka Bukti
                                            </a>
                                        </td>
                                        <td class="px-lg py-2 text-center">
                                            <a href="pembayaran_validasi.php?id=<?= $pl['id_pembayaran'] ?>" class="bg-success-green hover:bg-success-green/90 text-white px-2.5 py-1 rounded-lg text-[10px] font-bold hover:shadow-sm decoration-none inline-block transition-all">
                                                Verifikasi Sekarang
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Transactions Table Card -->
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                <div class="px-lg py-3 border-b border-outline-variant flex items-center justify-between bg-surface-container-lowest">
                    <div class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary text-sm">history</span>
                        <h4 class="text-xs text-primary font-bold">Pemesanan &amp; Transaksi Sewa Terbaru</h4>
                    </div>
                    <a href="booking_kelola.php" class="text-primary font-bold text-[11px] hover:underline decoration-none flex items-center gap-0.5">
                        Kelola Semua <span class="material-symbols-outlined text-xs">arrow_forward</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse align-middle">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-lg py-2">Kode</th>
                                <th class="px-lg py-2">Penyewa</th>
                                <th class="px-lg py-2">Gedung / Aula</th>
                                <th class="px-lg py-2">Tanggal Sewa</th>
                                <th class="px-lg py-2 text-right">Total Tagihan</th>
                                <th class="px-lg py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                            <?php foreach ($recent_list as $rl): ?>
                                <?php
                                $status = $rl['status_transaksi'];
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
                                    <td class="px-lg py-2 font-bold text-primary"><?= htmlspecialchars($rl['kode_transaksi']) ?></td>
                                    <td class="px-lg py-2 font-semibold text-on-surface"><?= htmlspecialchars($rl['nama_penyewa']) ?></td>
                                    <td class="px-lg py-2 text-on-surface-variant"><?= htmlspecialchars($rl['nama_gedung']) ?></td>
                                    <td class="px-lg py-2 text-on-surface-variant text-[10px]"><?= format_tanggal($rl['tanggal_mulai']) ?> s/d <?= format_tanggal($rl['tanggal_selesai']) ?></td>
                                    <td class="px-lg py-2 text-right font-bold text-primary"><?= format_rupiah($rl['total_pembayaran']) ?></td>
                                    <td class="px-lg py-2 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
<?php include '../includes/mobile_menu_admin.php'; ?>
</body>
</html>
