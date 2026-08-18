<?php
// SIIPAK - Validasi Pembayaran Bertahap (Admin)
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_admin();

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = $_SESSION['user_id'];

// Process Validation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_val'])) {
    $id_pembayaran = (int)$_POST['id_pembayaran'];
    $status_val    = $_POST['action_val']; // 'Valid' atau 'Ditolak'
    $catatan       = sanitize($_POST['catatan_admin'] ?? '');

    // Fetch payment and transaction details
    $stmt = $pdo->prepare("SELECT p.*, t.id_transaksi, t.total_pembayaran, t.id_penyewa, t.kode_transaksi 
                           FROM pembayaran p
                           JOIN transaksi t ON p.id_transaksi = t.id_transaksi
                           WHERE p.id_pembayaran = :id");
    $stmt->execute([':id' => $id_pembayaran]);
    $payment = $stmt->fetch();

    if ($payment) {
        $id_transaksi = $payment['id_transaksi'];
        $id_penyewa   = $payment['id_penyewa'];

        // Update payment record
        $stmt_up = $pdo->prepare("UPDATE pembayaran SET status_validasi = :st, catatan_admin = :catatan, id_admin_validator = :id_admin WHERE id_pembayaran = :id");
        $stmt_up->execute([
            ':st'       => $status_val,
            ':catatan'  => $catatan,
            ':id_admin' => $admin_id,
            ':id'       => $id_pembayaran
        ]);

        if ($status_val === 'Valid') {
            // Calculate total valid payments so far
            $stmt_sum = $pdo->prepare("SELECT SUM(jumlah_bayar) FROM pembayaran WHERE id_transaksi = :id AND status_validasi = 'Valid'");
            $stmt_sum->execute([':id' => $id_transaksi]);
            $total_paid = (float)$stmt_sum->fetchColumn();

            if ($total_paid >= $payment['total_pembayaran']) {
                $new_trx_status = 'Lunas';
            } elseif ($payment['jenis_pembayaran'] === 'DP') {
                $new_trx_status = 'DP';
            } else {
                $new_trx_status = 'Cicilan';
            }

            // Update transaction status & set validator admin
            $pdo->prepare("UPDATE transaksi SET status_transaksi = :st, id_admin = :id_admin WHERE id_transaksi = :id")
                ->execute([':st' => $new_trx_status, ':id_admin' => $admin_id, ':id' => $id_transaksi]);

            // Add notification for Penyewa
            add_notification($pdo, $id_penyewa, $admin_id, "Pembayaran $status_val Disetujui", "Pembayaran " . $payment['jenis_pembayaran'] . " sebesar " . format_rupiah($payment['jumlah_bayar']) . " untuk kode " . $payment['kode_transaksi'] . " telah disetujui (Status: $new_trx_status). Jadwal dikunci.", "riwayat_booking.php");

            set_flash('success', "Pembayaran berhasil divalidasi sebagai VALID! Status transaksi diubah menjadi '$new_trx_status' dan jadwal telah resmi dikunci.");
        } else {
            // Rejected
            $pdo->prepare("UPDATE transaksi SET status_transaksi = 'Ditolak' WHERE id_transaksi = :id")
                ->execute([':id' => $id_transaksi]);

            add_notification($pdo, $id_penyewa, $admin_id, "Pembayaran Ditolak", "Bukti bayar untuk " . $payment['kode_transaksi'] . " ditolak dengan catatan: " . $catatan, "pembayaran_upload.php?id_transaksi=" . $id_transaksi);

            set_flash('warning', "Pembayaran telah DITOLAK. Notifikasi telah dikirimkan ke penyewa.");
        }
    }
    header('Location: pembayaran_validasi.php');
    exit;
}

// Fetch all payment records with filters
$filter_status = $_GET['status'] ?? 'Menunggu';

$sql = "SELECT p.*, t.kode_transaksi, t.nama_kegiatan, t.total_pembayaran, t.foto_identitas, py.nama AS nama_penyewa, py.no_telepon, g.nama_gedung 
        FROM pembayaran p
        JOIN transaksi t ON p.id_transaksi = t.id_transaksi
        JOIN penyewa py ON t.id_penyewa = py.id_penyewa
        JOIN gedung g ON t.id_gedung = g.id_gedung";

if (!empty($filter_status) && $filter_status !== 'semua') {
    $sql .= " WHERE p.status_validasi = :st";
    $stmt = $pdo->prepare($sql . " ORDER BY p.id_pembayaran DESC");
    $stmt->execute([':st' => $filter_status]);
} else {
    $stmt = $pdo->prepare($sql . " ORDER BY p.id_pembayaran DESC");
    $stmt->execute();
}
$payments = $stmt->fetchAll();

// Fetch summary metrics (for pending notifications count in sidebar)
$pending_val = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_validasi = 'Menunggu'")->fetchColumn();

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
    <title>Validasi Pembayaran | SIPAK Politeknik Aceh</title>
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
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking_kelola.php">
                <span class="font-label-lg text-label-lg">Kelola Booking</span>
            </a>
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none relative" href="pembayaran_validasi.php">
                <span class="font-label-lg text-label-lg font-bold">Validasi Bayar</span>
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
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-sm">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary font-bold">Validasi Pembayaran Bertahap (Termin)</h1>
                    <p class="text-xs text-on-surface-variant">Verifikasi bukti transfer dari penyewa untuk menyetujui pemesanan &amp; mengunci jadwal gedung secara sah.</p>
                </div>
                
                <!-- Filter Status Pills -->
                <div class="bg-white p-1 rounded-xl border border-outline-variant shadow-sm flex flex-wrap gap-1">
                    <a href="pembayaran_validasi.php?status=Menunggu" class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all decoration-none flex items-center gap-0.5 <?= $filter_status === 'Menunggu' ? 'bg-warning-amber text-primary' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">hourglass_empty</span> Menunggu
                    </a>
                    <a href="pembayaran_validasi.php?status=Valid" class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all decoration-none flex items-center gap-0.5 <?= $filter_status === 'Valid' ? 'bg-success-green text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">check_circle</span> Valid
                    </a>
                    <a href="pembayaran_validasi.php?status=Ditolak" class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all decoration-none flex items-center gap-0.5 <?= $filter_status === 'Ditolak' ? 'bg-error-red text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                        <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">cancel</span> Ditolak
                    </a>
                    <a href="pembayaran_validasi.php?status=semua" class="px-3 py-1 text-[10px] font-bold rounded-lg transition-all decoration-none flex items-center gap-0.5 <?= $filter_status === 'semua' ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                        Semua
                    </a>
                </div>
            </div>

            <!-- Flash Session Messaging -->
            <?php if (isset($_SESSION['flash'])): ?>
                <?php
                $flash_type = $_SESSION['flash']['type'];
                $flash_bg = $flash_type === 'success' ? 'bg-success-green/10 text-success-green border-success-green/20' : 'bg-warning-amber/10 text-secondary border border-warning-amber/20';
                ?>
                <div class="<?= $flash_bg ?> p-sm rounded-xl flex items-center gap-xs text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm"><?= $flash_type === 'success' ? 'check_circle' : 'error' ?></span>
                    <span><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse align-middle">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-lg py-2">Tanggal Upload</th>
                                <th class="px-lg py-2">Kode &amp; Penyewa</th>
                                <th class="px-lg py-2">Gedung / Acara</th>
                                <th class="px-lg py-2">Skema &amp; Nominal</th>
                                <th class="px-lg py-2 text-center">Bukti Transfer</th>
                                <th class="px-lg py-2 text-center">Status</th>
                                <th class="px-lg py-2 text-center">Aksi Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="7" class="px-lg py-6 text-center text-on-surface-variant">
                                        Tidak ada data pembayaran dengan status <strong><?= htmlspecialchars($filter_status) ?></strong>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $p): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                         <td class="px-lg py-2.5 whitespace-nowrap">
                                             <span class="block text-on-surface font-semibold text-[11px]"><?= date('d/m/Y', strtotime($p['tanggal_bayar'])) ?></span>
                                             <span class="block text-on-surface-variant text-[10px] mt-0.5"><?= date('H:i', strtotime($p['tanggal_bayar'])) ?> WIB</span>
                                         </td>
                                         <td class="px-lg py-2.5">
                                             <strong class="text-primary block font-bold"><?= htmlspecialchars($p['kode_transaksi']) ?></strong>
                                             <span class="text-on-surface font-bold block mt-0.5"><?= htmlspecialchars($p['nama_penyewa']) ?></span>
                                             <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_telepon']) ?>" target="_blank" class="text-[10px] text-on-surface-variant flex items-center gap-xs mt-0.5 hover:underline decoration-none">
                                                 <span class="material-symbols-outlined text-[12px]">phone_iphone</span> <?= htmlspecialchars($p['no_telepon']) ?>
                                             </a>
                                             <?php if (!empty($p['foto_identitas'])): ?>
                                                 <a href="../assets/uploads/<?= htmlspecialchars($p['foto_identitas']) ?>" target="_blank" class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 rounded-full bg-success-green/10 text-success-green hover:bg-success-green hover:text-white border border-success-green/20 text-[10px] font-bold shadow-xs active:scale-95 transition-all duration-200 decoration-none">
                                                     <span class="material-symbols-outlined text-[13px]">badge</span>
                                                     <span>Lihat Identitas</span>
                                                 </a>
                                             <?php endif; ?>
                                         </td>
                                         <td class="px-lg py-2.5">
                                             <strong class="block text-on-surface font-semibold"><?= htmlspecialchars($p['nama_gedung']) ?></strong>
                                             <span class="text-[10px] text-on-surface-variant italic block mt-0.5">"<?= htmlspecialchars($p['nama_kegiatan']) ?>"</span>
                                         </td>
                                         <td class="px-lg py-2.5 whitespace-nowrap">
                                             <div class="flex items-center gap-1.5 mb-1">
                                                 <span class="inline-block bg-surface-container text-primary text-[9px] px-1.5 py-0.5 rounded-md font-bold border border-outline-variant/40"><?= $p['jenis_pembayaran'] ?></span>
                                             </div>
                                             <div class="text-[13px] font-extrabold text-success-green leading-none mb-1">
                                                 <?= format_rupiah($p['jumlah_bayar']) ?>
                                             </div>
                                             <div class="text-[10px] text-on-surface-variant leading-none">
                                                 Tagihan: <?= format_rupiah($p['total_pembayaran']) ?>
                                             </div>
                                         </td>
                                        <td class="px-lg py-2.5 text-center">
                                            <a href="../assets/uploads/<?= htmlspecialchars($p['bukti_transfer']) ?>" target="_blank" class="inline-flex items-center gap-xs border border-primary text-primary px-2.5 py-0.5 rounded-lg text-[10px] font-bold hover:bg-primary hover:text-white transition-all decoration-none">
                                                <span class="material-symbols-outlined text-xs">visibility</span> Buka Bukti
                                            </a>
                                        </td>
                                        <td class="px-lg py-2.5 text-center">
                                            <?php
                                            $st_val = $p['status_validasi'];
                                            $badge_val = 'bg-warning-amber/10 text-secondary border-warning-amber/20';
                                            if ($st_val === 'Valid') $badge_val = 'bg-success-green/10 text-success-green border-success-green/20';
                                            elseif ($st_val === 'Ditolak') $badge_val = 'bg-error-container text-on-error-container border border-error/20';
                                            ?>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge_val ?>">
                                                <?= $st_val ?>
                                            </span>
                                        </td>
                                        <td class="px-lg py-2.5 text-center">
                                            <?php if ($p['status_validasi'] === 'Menunggu'): ?>
                                                <div class="flex justify-center gap-sm">
                                                    <button type="button" class="bg-success-green hover:bg-success-green/90 text-white text-[10px] font-bold px-2 py-1 rounded-lg hover:shadow-md active:scale-95 transition-all flex items-center gap-xs" onclick="openValModal(<?= $p['id_pembayaran'] ?>, '<?= htmlspecialchars($p['kode_transaksi']) ?>', 'Valid', '<?= htmlspecialchars($p['bukti_transfer']) ?>', '<?= htmlspecialchars($p['foto_identitas'] ?? '') ?>')">
                                                        Setuju
                                                    </button>
                                                    <button type="button" class="bg-error-red hover:bg-error-red/90 text-white text-[10px] font-bold px-2 py-1 rounded-lg hover:shadow-md active:scale-95 transition-all flex items-center gap-xs" onclick="openValModal(<?= $p['id_pembayaran'] ?>, '<?= htmlspecialchars($p['kode_transaksi']) ?>', 'Ditolak', '<?= htmlspecialchars($p['bukti_transfer']) ?>', '<?= htmlspecialchars($p['foto_identitas'] ?? '') ?>')">
                                                        Tolak
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-[10px] text-on-surface-variant italic font-semibold">Tervalidasi</span>
                                            <?php endif; ?>
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

<!-- Modal Action Validasi -->
<div id="valModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-md animate-fade-in">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden border border-outline-variant shadow-lg animate-scale-up">
        <div class="bg-primary text-white p-md flex items-center justify-between" id="valModalHeader">
            <h5 class="font-headline-md text-headline-md font-bold text-white" id="valModalTitle">Verifikasi Pembayaran</h5>
            <button type="button" onclick="closeValModal()" class="text-white hover:text-warning-amber transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <form method="POST" action="pembayaran_validasi.php">
            <input type="hidden" name="id_pembayaran" id="val_id_pembayaran">
            <input type="hidden" name="action_val" id="val_action">
            
            <!-- Side-by-side Document Preview Grid -->
            <div class="grid grid-cols-2 gap-sm p-md bg-surface border-b border-outline-variant/40">
                <!-- Payment Proof Preview -->
                <div class="space-y-xs text-center">
                    <span class="text-[10px] font-bold text-primary block">Bukti Transfer</span>
                    <div class="h-40 rounded-lg border border-outline-variant/60 bg-white overflow-hidden flex items-center justify-center relative group">
                        <img id="val_preview_bukti" src="" class="w-full h-full object-contain cursor-zoom-in" onclick="window.open(this.src, '_blank')">
                        <span class="absolute inset-0 bg-black/45 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[9px] font-semibold transition-opacity pointer-events-none">Klik untuk Memperbesar</span>
                    </div>
                </div>
                <!-- Identity Document Preview -->
                <div class="space-y-xs text-center">
                    <span class="text-[10px] font-bold text-primary block">Identitas Penyewa</span>
                    <div class="h-40 rounded-lg border border-outline-variant/60 bg-white overflow-hidden flex items-center justify-center relative group" id="val_identitas_container">
                        <img id="val_preview_identitas" src="" class="w-full h-full object-contain cursor-zoom-in hidden" onclick="window.open(this.src, '_blank')">
                        <a id="val_preview_identitas_pdf" href="" target="_blank" class="w-full h-full flex flex-col items-center justify-center gap-xs text-primary hover:text-primary-container transition-colors hidden">
                            <span class="material-symbols-outlined text-3xl">picture_as_pdf</span>
                            <span class="text-[9px] font-bold">Buka Berkas PDF</span>
                        </a>
                        <span class="text-[10px] text-on-surface-variant font-medium flex items-center justify-center h-full hidden" id="val_no_identitas">Tidak Ada Identitas</span>
                        <span class="absolute inset-0 bg-black/45 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[9px] font-semibold transition-opacity pointer-events-none hidden" id="val_identitas_hover_label">Klik untuk Memperbesar</span>
                    </div>
                </div>
            </div>

            <div class="p-md space-y-sm text-xs">
                <p class="text-on-surface-variant">Konfirmasi verifikasi bukti transfer untuk kode transaksi <strong class="text-primary font-bold" id="val_kode_trx"></strong>:</p>
                
                <div class="space-y-base">
                    <label for="catatan_admin" class="font-semibold text-primary block">Catatan Admin / Pengelola (Opsional untuk Setujui, Wajib jika Tolak)</label>
                    <textarea name="catatan_admin" id="catatan_admin" rows="3" class="w-full p-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" placeholder="Contoh: Transfer BSI tervalidasi lunas sesuai mutasi / Bukti transfer buram tidak terbaca."></textarea>
                </div>
            </div>
            <div class="px-md py-2.5 bg-surface-container-low border-t border-outline-variant/40 flex justify-end gap-sm">
                <button type="button" onclick="closeValModal()" class="px-md py-1.5 border border-outline text-on-surface-variant text-[11px] rounded-lg hover:bg-surface-container-high transition-colors">Batal</button>
                <button type="submit" class="px-md py-1.5 font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all text-white" id="valSubmitBtn">Proses Validasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openValModal(idPembayaran, kodeTrx, action, buktiTransfer, fotoIdentitas) {
    document.getElementById('val_id_pembayaran').value = idPembayaran;
    document.getElementById('val_action').value = action;
    document.getElementById('val_kode_trx').innerText = kodeTrx;
    
    // Set Bukti Transfer image source
    document.getElementById('val_preview_bukti').src = '../assets/uploads/' + buktiTransfer;
    
    // Set Identitas Penyewa source
    const imgPreview = document.getElementById('val_preview_identitas');
    const pdfPreview = document.getElementById('val_preview_identitas_pdf');
    const noIdentitas = document.getElementById('val_no_identitas');
    const hoverLabel = document.getElementById('val_identitas_hover_label');
    
    imgPreview.classList.add('hidden');
    pdfPreview.classList.add('hidden');
    noIdentitas.classList.add('hidden');
    hoverLabel.classList.add('hidden');
    
    if (fotoIdentitas && fotoIdentitas.trim() !== '') {
        const fileExt = fotoIdentitas.split('.').pop().toLowerCase();
        if (fileExt === 'pdf') {
            pdfPreview.href = '../assets/uploads/' + fotoIdentitas;
            pdfPreview.classList.remove('hidden');
        } else {
            imgPreview.src = '../assets/uploads/' + fotoIdentitas;
            imgPreview.classList.remove('hidden');
            hoverLabel.classList.remove('hidden');
        }
    } else {
        noIdentitas.classList.remove('hidden');
    }
    
    var submitBtn = document.getElementById('valSubmitBtn');
    var header = document.getElementById('valModalHeader');
    var title = document.getElementById('valModalTitle');
    
    if (action === 'Valid') {
        title.innerText = 'Konfirmasi Disetujui (VALID)';
        submitBtn.className = 'px-md py-1.5 bg-success-green hover:bg-success-green/90 text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all';
        submitBtn.innerText = 'Setujui & Kunci';
    } else {
        title.innerText = 'Konfirmasi Penolakan';
        submitBtn.className = 'px-md py-1.5 bg-error-red hover:bg-error-red/90 text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all';
        submitBtn.innerText = 'Tolak Bukti';
    }
    
    const modal = document.getElementById('valModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeValModal() {
    const modal = document.getElementById('valModal');
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
