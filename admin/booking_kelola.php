<?php
// SIIPAK - Kelola Data Booking (Admin)
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_admin();

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch summary metrics (for pending notifications count)
$pending_val = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_validasi = 'Menunggu'")->fetchColumn();

// Cancel / Status update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_transaksi = (int)$_POST['id_transaksi'];
    $new_status   = $_POST['status_transaksi'];
    $catatan      = sanitize($_POST['catatan'] ?? '');

    $stmt = $pdo->prepare("UPDATE transaksi SET status_transaksi = :st, catatan = :catatan WHERE id_transaksi = :id");
    $stmt->execute([':st' => $new_status, ':catatan' => $catatan, ':id' => $id_transaksi]);

    set_flash('success', "Status transaksi berhasil diperbarui menjadi '$new_status'.");
    header('Location: booking_kelola.php');
    exit;
}

// Fetch all transactions
$filter_gedung = $_GET['id_gedung'] ?? '';
$search        = sanitize($_GET['q'] ?? '');

$sql = "SELECT t.*, g.nama_gedung, py.nama AS nama_penyewa, py.no_telepon,
        (SELECT GROUP_CONCAT(CONCAT(a.nama_aset, ' (', ta.jumlah_aset, ' Unit)') SEPARATOR ', ')
         FROM transaksi_aset ta
         JOIN aset a ON ta.id_aset = a.id_aset
         WHERE ta.id_transaksi = t.id_transaksi) AS nama_aset,
        (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran p WHERE p.id_transaksi = t.id_transaksi AND p.status_validasi = 'Valid') AS total_terbayar,
        (SELECT COALESCE(jumlah_bayar, 0) FROM pembayaran WHERE id_transaksi = t.id_transaksi AND status_validasi = 'Valid' ORDER BY id_pembayaran ASC LIMIT 1) AS termin1,
        (SELECT COALESCE(jumlah_bayar, 0) FROM pembayaran WHERE id_transaksi = t.id_transaksi AND status_validasi = 'Valid' ORDER BY id_pembayaran ASC LIMIT 1 OFFSET 1) AS termin2
        FROM transaksi t
        JOIN gedung g ON t.id_gedung = g.id_gedung
        JOIN penyewa py ON t.id_penyewa = py.id_penyewa
        WHERE 1=1";

$params = [];
if (!empty($filter_gedung)) {
    $sql .= " AND t.id_gedung = :id_gedung";
    $params[':id_gedung'] = $filter_gedung;
}
if (!empty($search)) {
    $sql .= " AND (t.kode_transaksi LIKE :q OR py.nama LIKE :q OR t.nama_kegiatan LIKE :q)";
    $params[':q'] = "%$search%";
}

$sql .= " ORDER BY t.id_transaksi DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$gedung_list = $pdo->query("SELECT * FROM gedung ORDER BY nama_gedung ASC")->fetchAll();

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
    <title>Kelola Booking | SIPAK Politeknik Aceh</title>
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
            
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="index.php">
                <span class="font-label-lg text-label-lg font-semibold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking_kelola.php">
                <span class="font-label-lg text-label-lg font-bold">Kelola Booking</span>
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
                    <p class="text-[9px] text-primary-fixed-dim opacity-70 mt-0.5 truncate font-medium">Administrator</p>
                </div>
            </div>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-error-container/20 hover:text-error rounded-lg mx-2 transition-all decoration-none font-semibold text-xs" href="../logout.php">
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
                <h1 class="font-display-md text-display-md text-primary font-bold">Kelola Data Pemesanan (Booking)</h1>
                <p class="text-xs text-on-surface-variant">Pantau, setujui, batalkan, atau ubah status transaksi pemesanan gedung dan aset kampus.</p>
            </div>

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

            <!-- Search and Filter Bar Card -->
            <div class="bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                <form method="GET" action="booking_kelola.php" class="grid grid-cols-1 md:grid-cols-12 gap-sm items-center">
                    <div class="md:col-span-5 relative">
                        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                        <input type="text" name="q" class="w-full pl-9 pr-md py-1.5 bg-surface-container-low border-none rounded-lg text-xs focus:ring-1 focus:ring-primary/20 text-on-surface" placeholder="Cari Kode Transaksi / Nama Penyewa / Acara..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="md:col-span-4">
                        <select name="id_gedung" class="w-full py-1.5 px-md bg-surface-container-low border-none rounded-lg text-xs focus:ring-1 focus:ring-primary/20 text-on-surface" onchange="this.form.submit()">
                            <option value="">-- Semua Gedung --</option>
                            <?php foreach ($gedung_list as $g): ?>
                                <option value="<?= $g['id_gedung'] ?>" <?= $filter_gedung == $g['id_gedung'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama_gedung']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-3 flex gap-xs">
                        <button type="submit" class="flex-1 h-9 bg-primary hover:bg-primary-container text-white font-bold text-xs rounded-lg flex items-center justify-center gap-xs shadow-md active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-sm">filter_alt</span> Filter
                        </button>
                        <?php if (!empty($search) || !empty($filter_gedung)): ?>
                            <a href="booking_kelola.php" class="w-9 h-9 bg-surface-container-high hover:bg-outline-variant text-on-surface-variant rounded-lg flex items-center justify-center hover:text-primary active:scale-95 transition-all">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Bookings List Table Card -->
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse align-middle">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-md py-2 min-w-[160px]">Kode &amp; Tanggal</th>
                                <th class="px-md py-2 min-w-[180px]">Penyewa &amp; Kontak</th>
                                <th class="px-md py-2 min-w-[220px]">Gedung &amp; Acara</th>
                                <th class="px-md py-2 min-w-[150px]">Tanggal Sewa</th>
                                <th class="px-md py-2 text-right min-w-[110px]">Termin 1</th>
                                <th class="px-md py-2 text-right min-w-[110px]">Termin 2</th>
                                <th class="px-md py-2 text-right min-w-[110px]">Sisa</th>
                                <th class="px-md py-2 text-right min-w-[110px]">Total</th>
                                <th class="px-md py-2 text-center min-w-[110px]">Status</th>
                                <th class="px-md py-2 text-center min-w-[150px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="10" class="px-md py-6 text-center text-on-surface-variant">Belum ada data transaksi sewa.</td>
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
                                        <td class="px-md py-2.5">
                                            <strong class="text-primary block font-bold"><?= htmlspecialchars($b['kode_transaksi']) ?></strong>
                                            <span class="text-[10px] text-on-surface-variant"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></span>
                                        </td>
                                        <td class="px-md py-2.5">
                                            <strong class="text-on-surface block font-bold"><?= htmlspecialchars($b['nama_penyewa']) ?></strong>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_telepon']) ?>" target="_blank" class="text-[10px] text-primary font-semibold flex items-center gap-base mt-0.5 hover:underline decoration-none">
                                                <span class="material-symbols-outlined text-[12px]">phone_iphone</span>
                                                <?= htmlspecialchars($b['no_telepon']) ?>
                                            </a>
                                            <?php if (!empty($b['foto_identitas'])): ?>
                                                <a href="../assets/uploads/<?= htmlspecialchars($b['foto_identitas']) ?>" target="_blank" class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-full bg-success-green/10 text-success-green hover:bg-success-green hover:text-white border border-success-green/20 text-[10px] font-bold shadow-xs active:scale-95 transition-all duration-200 decoration-none">
                                                    <span class="material-symbols-outlined text-[13px]">badge</span>
                                                    <span>Lihat Identitas</span>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-md py-2.5">
                                            <strong class="block text-on-surface font-semibold"><?= htmlspecialchars($b['nama_gedung']) ?></strong>
                                            <span class="text-[10px] text-on-surface-variant italic">"<?= htmlspecialchars($b['nama_kegiatan']) ?>"</span>
                                            <?php if (!empty($b['nama_aset'])): ?>
                                                <span class="inline-block bg-surface-container text-primary text-[9px] px-1.5 py-0.5 rounded-md font-bold border border-outline-variant/40 mt-0.5">+ <?= htmlspecialchars($b['nama_aset']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-md py-2.5">
                                            <span class="block text-on-surface text-[11px] font-semibold"><?= format_tanggal($b['tanggal_mulai']) ?></span>
                                            <span class="block text-on-surface-variant text-[10px]">s/d <?= format_tanggal($b['tanggal_selesai']) ?></span>
                                        </td>
                                        <td class="px-md py-2.5 text-right font-semibold text-primary"><?= format_rupiah($b['termin1'] ?? 0) ?></td>
                                        <td class="px-md py-2.5 text-right font-semibold text-primary"><?= format_rupiah($b['termin2'] ?? 0) ?></td>
                                        <td class="px-md py-2.5 text-right font-bold text-error-red"><?= format_rupiah(max(0, $b['total_pembayaran'] - (($b['termin1'] ?? 0) + ($b['termin2'] ?? 0)))) ?></td>
                                        <td class="px-md py-2.5 text-right font-extrabold text-primary"><?= format_rupiah($b['total_pembayaran']) ?></td>
                                        <td class="px-md py-2.5 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                        <td class="px-md py-2.5 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" class="bg-primary text-white text-[10px] font-bold px-2 py-1 rounded-lg hover:shadow-md active:scale-95 transition-all flex items-center justify-center gap-xs" onclick="openStatusModal(<?= $b['id_transaksi'] ?>, '<?= htmlspecialchars($b['kode_transaksi']) ?>', '<?= $b['status_transaksi'] ?>')">
                                                    <span class="material-symbols-outlined text-[12px]">settings</span> Status
                                                </button>
                                                <?php if ($status === 'Lunas' || $status === 'Selesai'): ?>
                                                    <a href="../kuitansi.php?token=<?= $b['token_kuitansi'] ?>" target="_blank" class="bg-success-green hover:bg-success-green/90 text-white font-bold text-[10px] px-2 py-1 rounded-lg hover:shadow-md transition-all decoration-none flex items-center justify-center gap-xs">
                                                        <span class="material-symbols-outlined text-[12px]">print</span> Kuitansi
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>
</div>

<!-- Modal Update Status Booking -->
<div id="statusModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-md">
    <div class="bg-white rounded-2xl max-w-sm w-full overflow-hidden border border-outline-variant shadow-lg animate-scale-up">
        <div class="bg-primary text-white p-md flex items-center justify-between">
            <h5 class="font-headline-md text-headline-md font-bold text-white">Ubah Status Pemesanan</h5>
            <button type="button" onclick="closeStatusModal()" class="text-white hover:text-warning-amber transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <form method="POST" action="booking_kelola.php">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="id_transaksi" id="st_id_transaksi">
            <div class="p-md space-y-sm text-xs">
                <p class="text-on-surface-variant">Update status untuk kode transaksi <strong class="text-primary font-bold" id="st_kode_trx"></strong>:</p>
                
                <div class="space-y-base">
                    <label for="st_status_transaksi" class="font-semibold text-primary block">Pilih Status Baru *</label>
                    <select name="status_transaksi" id="st_status_transaksi" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required>
                        <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
                        <option value="DP">DP (Uang Muka)</option>
                        <option value="Cicilan">Cicilan</option>
                        <option value="Lunas">Lunas &amp; Jadwal Terkunci</option>
                        <option value="Ditolak">Ditolak</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                </div>
                
                <div class="space-y-base">
                    <label for="catatan" class="font-semibold text-primary block">Catatan Admin (Opsional)</label>
                    <textarea name="catatan" id="catatan" rows="3" class="w-full p-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" placeholder="Tulis alasan atau instruksi tambahan untuk penyewa."></textarea>
                </div>
            </div>
            <div class="px-md py-2.5 bg-surface-container-low border-t border-outline-variant/40 flex justify-end gap-sm">
                <button type="button" onclick="closeStatusModal()" class="px-md py-1.5 border border-outline text-on-surface-variant text-[11px] rounded-lg hover:bg-surface-container-high transition-colors">Batal</button>
                <button type="submit" class="px-md py-1.5 bg-primary text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(idTrx, kodeTrx, currentStatus) {
    document.getElementById('st_id_transaksi').value = idTrx;
    document.getElementById('st_kode_trx').innerText = kodeTrx;
    document.getElementById('st_status_transaksi').value = currentStatus;
    
    const modal = document.getElementById('statusModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeStatusModal() {
    const modal = document.getElementById('statusModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

// Micro-interaction styling
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
