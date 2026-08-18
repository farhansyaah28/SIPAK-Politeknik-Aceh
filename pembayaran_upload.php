<?php
// SIIPAK - Unggah Bukti Pembayaran Bertahap (Termin)
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

check_penyewa();
require_once 'includes/profile_handler.php';

$id_transaksi = $_GET['id_transaksi'] ?? 0;
$id_penyewa = $_SESSION['user_id'];

// Fetch transaction details
$stmt = $pdo->prepare("SELECT t.*, g.nama_gedung, g.harga_sewa, g.foto,
                        (SELECT GROUP_CONCAT(CONCAT(a.nama_aset, ' (', ta.jumlah_aset, ' Unit)') SEPARATOR ', ')
                         FROM transaksi_aset ta
                         JOIN aset a ON ta.id_aset = a.id_aset
                         WHERE ta.id_transaksi = t.id_transaksi) AS nama_aset
                        FROM transaksi t
                        JOIN gedung g ON t.id_gedung = g.id_gedung
                        WHERE t.id_transaksi = :id AND t.id_penyewa = :id_penyewa");
$stmt->execute([':id' => $id_transaksi, ':id_penyewa' => $id_penyewa]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    set_flash('danger', 'Data transaksi tidak ditemukan.');
    header('Location: riwayat_booking.php');
    exit;
}

// Calculate total already paid & validated
$stmt_paid = $pdo->prepare("SELECT SUM(jumlah_bayar) FROM pembayaran WHERE id_transaksi = :id AND status_validasi = 'Valid'");
$stmt_paid->execute([':id' => $id_transaksi]);
$total_paid = (float)$stmt_paid->fetchColumn();

$sisa_pembayaran = $transaksi['total_pembayaran'] - $total_paid;

// Check existing payments to prevent double submittal of the same type
$stmt_dp = $pdo->prepare("SELECT COUNT(*) FROM pembayaran WHERE id_transaksi = :id AND jenis_pembayaran = 'DP' AND status_validasi != 'Ditolak'");
$stmt_dp->execute([':id' => $id_transaksi]);
$has_dp = $stmt_dp->fetchColumn() > 0;

$stmt_cicilan = $pdo->prepare("SELECT COUNT(*) FROM pembayaran WHERE id_transaksi = :id AND jenis_pembayaran = 'Cicilan' AND status_validasi != 'Ditolak'");
$stmt_cicilan->execute([':id' => $id_transaksi]);
$has_cicilan = $stmt_cicilan->fetchColumn() > 0;

$stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM pembayaran WHERE id_transaksi = :id AND status_validasi = 'Menunggu'");
$stmt_pending->execute([':id' => $id_transaksi]);
$has_pending = $stmt_pending->fetchColumn() > 0;

// Process file upload
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah_bayar = (float)($_POST['jumlah_bayar'] ?? 0);
    $jenis_pembayaran = $_POST['jenis_pembayaran'] ?? 'DP';

    if (in_array($transaksi['status_transaksi'], ['Dibatalkan', 'Ditolak'])) {
        $error = 'Transaksi ini telah dibatalkan atau ditolak. Anda tidak dapat melakukan pembayaran.';
    } elseif ($has_pending) {
        $error = 'Anda memiliki unggahan bukti transfer yang sedang dalam antrean verifikasi Admin. Harap tunggu hingga proses selesai.';
    } elseif ($jumlah_bayar <= 0) {
        $error = 'Nominal pembayaran harus lebih dari Rp 0.';
    } elseif ($jenis_pembayaran === 'DP' && $has_dp) {
        $error = 'Anda sudah melakukan pembayaran Uang Muka (DP). Silakan pilih skema Cicilan atau Pelunasan.';
    } elseif ($jenis_pembayaran === 'Cicilan' && (!$has_dp || $has_cicilan)) {
        $error = 'Skema Cicilan tidak valid untuk kondisi transaksi Anda saat ini.';
    } elseif ($jenis_pembayaran === 'Pelunasan' && abs($jumlah_bayar - $sisa_pembayaran) > 0.01) {
        $error = 'Untuk Pelunasan Total, nominal yang dimasukkan harus tepat senilai sisa tagihan: ' . format_rupiah($sisa_pembayaran) . '.';
    } elseif (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Silakan unggah foto atau berkas bukti transfer bank.';
    } else {
        $file = $_FILES['bukti_transfer'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ext, $allowed)) {
            $error = 'Format berkas tidak didukung. Harap unggah berkas JPG, PNG, atau PDF.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran berkas terlalu besar (Maksimal 5 MB).';
        } else {
            // Target upload dir
            $upload_dir = __DIR__ . '/assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $filename = 'bukti_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $stmt_ins = $pdo->prepare("INSERT INTO pembayaran 
                    (id_transaksi, jumlah_bayar, jenis_pembayaran, bukti_transfer, status_validasi) 
                    VALUES (:id_trx, :jumlah, :jenis, :bukti, 'Menunggu')");
                
                $stmt_ins->execute([
                    ':id_trx'  => $id_transaksi,
                    ':jumlah'  => $jumlah_bayar,
                    ':jenis'   => $jenis_pembayaran,
                    ':bukti'   => $filename
                ]);

                // Update status transaksi jika sebelumnya ditolak
                if ($transaksi['status_transaksi'] === 'Ditolak') {
                    $pdo->prepare("UPDATE transaksi SET status_transaksi = 'Menunggu Pembayaran' WHERE id_transaksi = :id")->execute([':id' => $id_transaksi]);
                }

                // Notify admin
                add_notification($pdo, null, 1, 'Bukti Transfer Baru', "Penyewa mengunggah bukti bayar $jenis_pembayaran sebesar " . format_rupiah($jumlah_bayar) . " untuk " . $transaksi['kode_transaksi'], "admin/pembayaran_validasi.php");

                set_flash('success', 'Bukti transfer berhasil diunggah! Mohon menunggu verifikasi manual oleh Admin Pengelola.');
                header('Location: riwayat_booking.php');
                exit;
            } else {
                $error = 'Gagal menyimpan berkas di server.';
            }
        }
    }
}

// Fetch payment history
$stmt_history = $pdo->prepare("SELECT * FROM pembayaran WHERE id_transaksi = :id ORDER BY id_pembayaran DESC");
$stmt_history->execute([':id' => $id_transaksi]);
$payment_history = $stmt_history->fetchAll();

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
    <title>Kelola Pembayaran | SIPAK Politeknik Aceh</title>
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
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="riwayat_booking.php">
                <span class="font-label-lg text-label-lg font-semibold">Transaksi Saya</span>
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
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary font-bold">Portal Pembayaran Bertahap (Termin)</h1>
                    <p class="text-xs text-on-surface-variant">Lakukan konfirmasi dan unggah bukti transaksi sewa gedung atau aset Anda di bawah ini.</p>
                </div>
                <a href="riwayat_booking.php" class="bg-white border border-outline-variant text-primary font-bold px-md h-9 rounded-lg shadow-sm hover:bg-surface-container-low transition-all flex items-center gap-xs text-xs decoration-none">
                    <span class="material-symbols-outlined text-sm text-[16px]">arrow_back</span> Kembali ke Transaksi
                </a>
            </div>

            <!-- Bento-style Grid System -->
            <div class="grid grid-cols-12 gap-md">
                <!-- Column Left: Form & Transfer Instructions -->
                <div class="col-span-12 lg:col-span-7 space-y-md">
                    <!-- Bank Account Info -->
                    <div class="bg-gradient-to-br from-primary to-primary-container p-md rounded-2xl text-white shadow-soft relative overflow-hidden bento-card">
                        <div class="relative z-10 flex items-start gap-md">
                            <div class="bg-warning-amber/10 p-2.5 rounded-xl text-warning-amber">
                                <span class="material-symbols-outlined text-xl">account_balance</span>
                            </div>
                            <div>
                                <span class="text-primary-fixed-dim text-[10px] block uppercase tracking-wider mb-0.5">Rekening Bank Transfer Resmi:</span>
                                <strong class="text-white text-base font-black leading-tight block">Bank Syariah Indonesia (BSI)</strong>
                                <h3 class="text-warning-amber text-lg font-black tracking-wider leading-none mt-1">7700 - 8811 - 99</h3>
                                <p class="text-primary-fixed-dim text-xs mt-1.5">Atas Nama: <strong class="text-white">Politeknik Aceh Rental System</strong></p>
                            </div>
                        </div>
                        <div class="absolute -right-6 -bottom-6 opacity-10">
                            <span class="material-symbols-outlined text-[100px]">credit_card</span>
                        </div>
                    </div>

                    <!-- Upload Bukti Transfer Form -->
                    <?php if (in_array($transaksi['status_transaksi'], ['Dibatalkan', 'Ditolak'])): ?>
                        <!-- Dibatalkan / Ditolak Banner -->
                        <div class="bg-error-container/20 border border-error/20 p-md rounded-2xl flex items-start gap-xs bento-card">
                            <span class="material-symbols-outlined text-error text-2xl flex-shrink-0">cancel</span>
                            <div class="space-y-base text-xs w-full">
                                <h4 class="font-bold text-error">Transaksi <?= $transaksi['status_transaksi'] === 'Dibatalkan' ? 'Dibatalkan' : 'Ditolak' ?></h4>
                                <p class="text-on-surface-variant leading-relaxed">
                                    <?php if ($transaksi['status_transaksi'] === 'Dibatalkan'): ?>
                                        Transaksi pemesanan ini telah dibatalkan (karena melewati batas pembayaran atau dibatalkan oleh pengguna/admin). Anda tidak dapat melakukan pembayaran untuk transaksi yang sudah dibatalkan.
                                    <?php else: ?>
                                        Transaksi pemesanan ini telah ditolak oleh Admin. Silakan lakukan pemesanan gedung yang baru.
                                    <?php endif; ?>
                                </p>
                                <div class="pt-2">
                                    <a href="riwayat_booking.php" class="inline-flex items-center gap-xs bg-primary hover:bg-primary-container text-white font-bold text-[11px] px-3.5 py-1.5 rounded-lg hover:shadow-md active:scale-95 transition-all decoration-none">
                                        Kembali ke Transaksi
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($sisa_pembayaran > 0 && in_array($transaksi['status_transaksi'], ['Menunggu Pembayaran', 'DP', 'Cicilan'])): ?>
                        <?php if ($has_pending): ?>
                            <div class="bg-white rounded-2xl p-md border border-outline-variant shadow-soft bento-card text-center flex flex-col items-center justify-center space-y-sm">
                                <div class="w-12 h-12 rounded-full bg-warning-amber/10 flex items-center justify-center text-warning-amber animate-pulse">
                                    <span class="material-symbols-outlined text-2xl">hourglass_empty</span>
                                </div>
                                <div class="space-y-xs">
                                    <h4 class="text-sm font-bold text-primary">Menunggu Validasi Pembayaran</h4>
                                    <p class="text-[10px] text-on-surface-variant leading-relaxed max-w-[320px] mx-auto">
                                        Bukti transfer sebelumnya yang Anda kirimkan saat ini sedang dalam proses pemeriksaan dan verifikasi oleh Administrator. 
                                        Anda baru dapat mengunggah bukti pembayaran baru setelah transfer sebelumnya disetujui atau ditolak oleh Admin.
                                    </p>
                                </div>
                                <a href="riwayat_booking.php" class="bg-primary text-white px-md py-1.5 rounded-lg font-bold text-[10px] hover:shadow-md decoration-none">Kembali ke Transaksi</a>
                            </div>
                        <?php else: ?>
                            <div class="bg-white rounded-2xl p-md border border-outline-variant shadow-soft bento-card">
                                <div class="flex items-center gap-xs mb-md">
                                    <span class="material-symbols-outlined text-primary text-sm">cloud_upload</span>
                                    <h4 class="text-sm font-bold text-primary">Unggah Bukti Transfer Baru</h4>
                                </div>

                                <?php if (!empty($error)): ?>
                                    <div class="bg-error-container text-on-error-container p-sm rounded-xl border border-error/20 mb-md flex items-center gap-xs text-xs">
                                        <span class="material-symbols-outlined text-sm text-error">error</span>
                                        <span class="font-medium"><?= htmlspecialchars($error) ?></span>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="pembayaran_upload.php?id_transaksi=<?= $id_transaksi ?>" enctype="multipart/form-data" class="space-y-sm">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-sm">
                                        <div class="space-y-base">
                                            <label for="jenis_pembayaran" class="font-semibold text-primary block text-xs">Jenis Skema Pembayaran *</label>
                                            <select name="jenis_pembayaran" id="jenis_pembayaran" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required>
                                                <?php if (!$has_dp): ?>
                                                    <option value="DP" selected>Uang Muka (DP / Termin 1)</option>
                                                <?php endif; ?>
                                                <?php if ($has_dp && !$has_cicilan): ?>
                                                    <option value="Cicilan" selected>Cicilan Tambahan (Termin 2)</option>
                                                <?php endif; ?>
                                                <option value="Pelunasan" <?= ($has_dp && $has_cicilan) ? 'selected' : '' ?>>Pelunasan Total</option>
                                            </select>
                                        </div>
                                        <div class="space-y-base">
                                            <label for="jumlah_bayar" class="font-semibold text-primary block text-xs">Nominal Yang Ditransfer (Rp) *</label>
                                            <input type="number" name="jumlah_bayar" id="jumlah_bayar" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface font-medium" required min="10000" max="<?= $transaksi['total_pembayaran'] ?>" value="<?= $sisa_pembayaran ?>"/>
                                        </div>
                                    </div>

                                    <div class="space-y-base">
                                        <label for="bukti_transfer" class="font-semibold text-primary block text-xs">Berkas Bukti Transfer (Foto/PDF) *</label>
                                        <input type="file" name="bukti_transfer" id="bukti_transfer" class="w-full p-1 rounded-lg border border-outline-variant bg-surface-container-lowest text-xs text-on-surface file:mr-md file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-container" accept="image/*,application/pdf" required/>
                                        <span class="text-[10px] text-on-surface-variant block mt-1">Mendukung berkas JPG, PNG, atau PDF. Maksimal ukuran 5 MB.</span>
                                    </div>

                                    <button type="submit" class="w-full h-9 bg-primary hover:bg-primary-container text-white font-bold text-xs rounded-lg flex items-center justify-center gap-xs shadow-md active:scale-95 transition-all">
                                        <span class="material-symbols-outlined text-sm">send</span> Kirim Bukti Pembayaran
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Lunas / Secure Banner -->
                        <div class="bg-success-green/10 border border-success-green/20 p-md rounded-2xl flex items-start gap-xs bento-card">
                            <span class="material-symbols-outlined text-success-green text-2xl flex-shrink-0">verified</span>
                            <div class="space-y-base text-xs w-full">
                                <h4 class="font-bold text-primary">Peminjaman Telah Lunas!</h4>
                                <p class="text-on-surface-variant leading-relaxed">
                                    Seluruh tagihan sewa gedung dan aset Anda telah dilunasi dan terverifikasi secara lunas oleh Admin kampus. Jadwal gedung resmi dikunci.
                                </p>
                                <div class="pt-2">
                                    <a href="kuitansi.php?token=<?= $transaksi['token_kuitansi'] ?>" target="_blank" class="inline-flex items-center gap-xs bg-success-green hover:bg-success-green/90 text-white font-bold text-[11px] px-3.5 py-1.5 rounded-lg hover:shadow-md active:scale-95 transition-all decoration-none">
                                        <span class="material-symbols-outlined text-sm">print</span> Cetak Kuitansi Resmi
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Column Right: Billing Summary & Details -->
                <div class="col-span-12 lg:col-span-5 space-y-md">
                    <!-- Rental Details Card -->
                    <div class="bg-white rounded-2xl p-md border border-outline-variant shadow-soft space-y-sm bento-card text-xs">
                        <div class="flex items-center justify-between">
                            <span class="bg-warning-amber/10 text-secondary text-[10px] px-2.5 py-0.5 rounded-full font-bold border border-warning-amber/20 uppercase">Kode: <?= htmlspecialchars($transaksi['kode_transaksi']) ?></span>
                            <?php
                            $status = $transaksi['status_transaksi'];
                            $badge = 'bg-warning-amber/10 text-secondary border border-warning-amber/20';
                            if ($status === 'Lunas') $badge = 'bg-success-green/10 text-success-green border border-success-green/20';
                            elseif ($status === 'DP' || $status === 'Cicilan') $badge = 'bg-[#2563eb]/10 text-[#2563eb] border border-[#2563eb]/20';
                            ?>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>">
                                <?= $status ?>
                            </span>
                        </div>

                        <div class="space-y-base">
                            <h4 class="text-primary flex items-center gap-xs font-bold"><span class="material-symbols-outlined text-sm">info</span> Informasi Sewa</h4>
                            <div class="p-sm bg-surface rounded-xl border border-outline-variant/60 space-y-xs text-[11px]">
                                <div class="flex flex-col">
                                    <span class="text-on-surface-variant">Gedung / Aula:</span>
                                    <strong class="text-primary font-bold"><?= htmlspecialchars($transaksi['nama_gedung']) ?></strong>
                                </div>
                                <?php if (!empty($transaksi['nama_aset'])): ?>
                                    <div class="flex flex-col">
                                        <span class="text-on-surface-variant">Aset Tambahan:</span>
                                        <strong class="text-primary font-bold"><?= htmlspecialchars($transaksi['nama_aset']) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="flex flex-col">
                                    <span class="text-on-surface-variant">Nama Kegiatan:</span>
                                    <span class="text-primary font-semibold">"<?= htmlspecialchars($transaksi['nama_kegiatan']) ?>"</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-on-surface-variant">Pelaksanaan:</span>
                                    <span class="text-primary font-semibold"><?= format_tanggal($transaksi['tanggal_mulai']) ?> s/d <?= format_tanggal($transaksi['tanggal_selesai']) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-on-surface-variant">Durasi Sewa:</span>
                                    <?php
                                    $d1 = new DateTime($transaksi['tanggal_mulai']);
                                    $d2 = new DateTime($transaksi['tanggal_selesai']);
                                    $durasi_sewa = $d2->diff($d1)->days + 1;
                                    ?>
                                    <strong class="text-primary font-bold"><?= $durasi_sewa ?> Hari</strong>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-base">
                            <h4 class="text-primary flex items-center gap-xs font-bold"><span class="material-symbols-outlined text-sm">payments</span> Rincian Tagihan</h4>
                            <div class="p-sm bg-surface rounded-xl border border-outline-variant/60 space-y-xs text-[11px]">
                                <div class="flex justify-between text-on-surface-variant">
                                    <span>Total Biaya Sewa:</span>
                                    <strong class="font-semibold text-primary"><?= format_rupiah($transaksi['total_pembayaran']) ?></strong>
                                </div>
                                <div class="flex justify-between text-success-green">
                                    <span>Dana Masuk (Valid):</span>
                                    <strong class="font-bold"><?= format_rupiah($total_paid) ?></strong>
                                </div>
                                <hr class="border-outline-variant my-1">
                                <div class="flex justify-between text-error-red">
                                    <strong class="font-bold">Sisa Tagihan:</strong>
                                    <strong class="font-black"><?= format_rupiah(max(0, $sisa_pembayaran)) ?></strong>
                                </div>
                                <?php if ($status !== 'Lunas' && $status !== 'Selesai' && $status !== 'Dibatalkan' && $status !== 'Ditolak'): ?>
                                    <?php 
                                     $tgl_mulai = new DateTime($transaksi['tanggal_mulai']);
                                     $tgl_deadline = $tgl_mulai->modify('-1 day')->format('Y-m-d');
                                    ?>
                                    <hr class="border-outline-variant my-1">
                                    <div class="flex flex-col text-[10px] text-error bg-error-container/20 p-2 rounded-lg border border-error/20 gap-1 mt-1 leading-relaxed">
                                        <span class="font-bold flex items-center gap-1 text-error">
                                            <span class="material-symbols-outlined text-[12px]">warning</span>
                                            Batas Akhir Pelunasan H-1:
                                        </span>
                                        <strong class="font-extrabold text-error"><?= format_tanggal($tgl_deadline) ?> (Pukul 23:59 WIB)</strong>
                                        <p class="text-on-surface-variant font-medium mt-0.5 text-[9px]">
                                            Apabila melewati batas tanggal di atas dan tagihan belum dilunasi, pemesanan akan <strong>dibatalkan secara otomatis</strong> oleh sistem dan <strong>seluruh dana DP yang masuk dinyatakan HANGUS (tidak dapat dikembalikan).</strong>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action section inside the card footer -->
                        <div class="pt-sm border-t border-outline-variant/60 flex items-center justify-between mt-sm">
                            <div class="flex flex-col">
                                <strong class="text-primary font-bold text-[10px]">Nota Tagihan Resmi (Invoice)</strong>
                                <span class="text-[9px] text-on-surface-variant">Gunakan untuk pencatatan/SPJ fisik.</span>
                            </div>
                            <a href="nota.php?token=<?= $transaksi['token_kuitansi'] ?>" target="_blank" class="inline-flex items-center gap-xs bg-surface-container hover:bg-surface-container-high text-primary font-bold text-[10px] px-2.5 py-1.5 rounded-lg border border-outline-variant/60 hover:shadow-xs transition-all decoration-none">
                                <span class="material-symbols-outlined text-[14px]">description</span> Cetak Nota (Invoice)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Payment History -->
                <div class="col-span-12 bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                    <div class="p-md bg-surface border-b border-outline-variant flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary text-sm">history</span>
                        <h4 class="text-sm font-bold text-primary">Riwayat Pengunggahan &amp; Validasi</h4>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse align-middle">
                            <thead>
                                <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-lg py-2">Tanggal Upload</th>
                                    <th class="px-lg py-2">Jenis</th>
                                    <th class="px-lg py-2 text-right">Nominal</th>
                                    <th class="px-lg py-2 text-center">Bukti Transfer</th>
                                    <th class="px-lg py-2 text-center">Status Verifikasi</th>
                                    <th class="px-lg py-2">Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                                <?php if (empty($payment_history)): ?>
                                    <tr>
                                        <td colspan="6" class="px-lg py-6 text-center text-on-surface-variant">Belum ada bukti transfer yang diunggah untuk transaksi ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payment_history as $ph): ?>
                                        <tr class="hover:bg-surface-container-lowest transition-colors">
                                            <td class="px-lg py-2.5 font-medium"><?= format_tanggal($ph['tanggal_bayar'], true) ?></td>
                                            <td class="px-lg py-2.5">
                                                <span class="bg-surface-container text-primary text-[10px] px-2 py-0.5 rounded-full font-bold border border-outline-variant/40"><?= $ph['jenis_pembayaran'] ?></span>
                                            </td>
                                            <td class="px-lg py-2.5 text-right font-bold text-primary"><?= format_rupiah($ph['jumlah_bayar']) ?></td>
                                            <td class="px-lg py-2.5 text-center">
                                                <a href="assets/uploads/<?= htmlspecialchars($ph['bukti_transfer']) ?>" target="_blank" class="inline-flex items-center gap-xs border border-primary text-primary px-2 py-0.5 rounded-lg text-[10px] font-bold hover:bg-primary hover:text-white transition-all decoration-none">
                                                    <span class="material-symbols-outlined text-[10px]">visibility</span> Buka Bukti
                                                </a>
                                            </td>
                                            <td class="px-lg py-2.5 text-center">
                                                <?php
                                                $st_val = $ph['status_validasi'];
                                                $badge_val = 'bg-warning-amber/10 text-secondary border-warning-amber/20';
                                                if ($st_val === 'Valid') $badge_val = 'bg-success-green/10 text-success-green border border-success-green/20';
                                                elseif ($st_val === 'Ditolak') $badge_val = 'bg-error-container text-on-error-container border border-error/20';
                                                ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badge_val ?>">
                                                    <?= $st_val ?>
                                                </span>
                                            </td>
                                            <td class="px-lg py-2.5 text-on-surface-variant italic text-[11px]"><?= htmlspecialchars($ph['catatan_admin'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<!-- Micro-interaction & Dynamic Payment Selector Script -->
<script>
    // UX Helper for Pelunasan Total
    document.addEventListener('DOMContentLoaded', () => {
        const jenisSelect = document.getElementById('jenis_pembayaran');
        const jumlahInput = document.getElementById('jumlah_bayar');
        const sisaPembayaran = <?= json_encode((float)$sisa_pembayaran) ?>;

        if (jenisSelect && jumlahInput) {
            const handlePaymentTypeChange = () => {
                if (jenisSelect.value === 'Pelunasan') {
                    jumlahInput.value = sisaPembayaran;
                    jumlahInput.setAttribute('readonly', 'true');
                    jumlahInput.classList.add('bg-outline-variant/10');
                } else {
                    jumlahInput.removeAttribute('readonly');
                    jumlahInput.classList.remove('bg-outline-variant/10');
                }
            };

            jenisSelect.addEventListener('change', handlePaymentTypeChange);
            handlePaymentTypeChange(); // Run initial state on load
        }
    });

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
</body>
</html>
