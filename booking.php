<?php
// SIIPAK - Form Pemesanan Gedung & Aset (Penyewa)
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

check_penyewa();
require_once 'includes/profile_handler.php';

$id_penyewa = $_SESSION['user_id'];
$id_gedung_selected = $_GET['id_gedung'] ?? '';

// Fetch active buildings
$gedung_list = $pdo->query("SELECT * FROM gedung WHERE status = 'Tersedia' ORDER BY id_gedung ASC")->fetchAll();

// Fetch assets for pre-selected building if available
$assets = [];
$selected_gedung_info = null;

if (!empty($id_gedung_selected)) {
    // Selected building detail
    $stmt = $pdo->prepare("SELECT * FROM gedung WHERE id_gedung = :id");
    $stmt->execute([':id' => $id_gedung_selected]);
    $selected_gedung_info = $stmt->fetch();

    // Assets of this building
    $stmt_ast = $pdo->prepare("SELECT * FROM aset WHERE id_gedung = :id");
    $stmt_ast->execute([':id' => $id_gedung_selected]);
    $assets = $stmt_ast->fetchAll();
}

// Fetch booked dates for the pre-selected building to disable them on date picker
$booked_dates = [];
if (!empty($id_gedung_selected)) {
    $stmt_booked = $pdo->prepare("SELECT tanggal_mulai, tanggal_selesai FROM transaksi 
                                  WHERE id_gedung = :id 
                                  AND status_transaksi NOT IN ('Ditolak', 'Dibatalkan')");
    $stmt_booked->execute([':id' => $id_gedung_selected]);
    $bookings = $stmt_booked->fetchAll();
    
    foreach ($bookings as $b) {
        $start = new DateTime($b['tanggal_mulai']);
        $end = new DateTime($b['tanggal_selesai']);
        $end->modify('+1 day'); // include end date
        
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        
        foreach ($period as $date) {
            $booked_dates[] = $date->format('Y-m-d');
        }
    }
}

// Sync user name with database to keep navbar and sidebar updated in real-time
$id_penyewa = $_SESSION['user_id'] ?? 0;
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
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIPAK - Pemesanan Gedung &amp; Aset</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Flatpickr CSS & JS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="dashboard.php">
                <span class="font-label-lg text-label-lg font-semibold">Dashboard</span>
            </a>
            <!-- Active Nav: Booking Baru -->
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking.php">
                <span class="font-label-lg text-label-lg font-bold">Booking Baru</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="riwayat_booking.php">
                <span class="font-label-lg text-label-lg font-semibold">Transaksi Saya</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="kalender.php">
                <span class="font-label-lg text-label-lg font-semibold">Kalender Kegiatan</span>
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

        <!-- Form & Content Section -->
        <main class="p-lg space-y-md">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary">Form Pemesanan Gedung &amp; Aset</h1>
                    <p class="text-xs text-on-surface-variant">Sistem mendeteksi bentrok jadwal secara otomatis untuk mencegah double booking.</p>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash'])): ?>
                <?php 
                $type = $_SESSION['flash']['type'] ?? 'danger';
                $msgClass = 'bg-error-container text-on-error-container border-error/20';
                $icon = 'error';
                $iconClass = 'text-error';
                
                if ($type === 'success') {
                    $msgClass = 'bg-success-green/10 text-success-green border-success-green/20';
                    $icon = 'check_circle';
                    $iconClass = 'text-success-green';
                } elseif ($type === 'warning') {
                    $msgClass = 'bg-warning-amber/10 text-secondary border-warning-amber/20';
                    $icon = 'warning';
                    $iconClass = 'text-secondary';
                }
                ?>
                <div class="<?= $msgClass ?> p-sm rounded-xl border flex items-center gap-xs shadow-sm text-xs">
                    <span class="material-symbols-outlined text-sm <?= $iconClass ?>"><?= $icon ?></span>
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <!-- Grid Layout (Main Form + Right Sidebar Summary Panel) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-md items-start">
                
                <!-- Left Main Form (8 Columns) -->
                <div class="lg:col-span-8 bg-white rounded-2xl overflow-hidden shadow-soft border border-outline-variant">
                    <!-- Form Header Banner -->
                    <div class="bg-primary p-md text-white space-y-0.5">
                        <h3 class="text-xs font-bold text-white flex items-center gap-xs">
                            <span class="material-symbols-outlined text-warning-amber text-sm">calendar_add_on</span> Detail Permohonan Sewa
                        </h3>
                        <p class="text-primary-fixed opacity-95 text-[11px]">Isi rincian kegiatan dan tanggal pelaksanaan dengan benar.</p>
                    </div>

                    <div class="p-md">
                        <form method="POST" action="booking_proses.php" enctype="multipart/form-data" class="space-y-sm" id="bookingForm">
                            <!-- Gedung Selection -->
                            <div class="space-y-base">
                                <label for="id_gedung" class="font-semibold text-primary block text-xs">Pilih Gedung Utama *</label>
                                <select name="id_gedung" id="id_gedung" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required onchange="window.location.href='booking.php?id_gedung='+this.value">
                                    <option value="">-- Pilih Gedung Kampus --</option>
                                    <?php foreach ($gedung_list as $g): ?>
                                        <option value="<?= $g['id_gedung'] ?>" <?= $id_gedung_selected == $g['id_gedung'] ? 'selected' : '' ?> data-harga="<?= $g['harga_sewa'] ?>">
                                            <?= htmlspecialchars($g['nama_gedung']) ?> — <?= format_rupiah($g['harga_sewa']) ?>/hari (Kapasitas: <?= $g['kapasitas'] ?> orang)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Optional Asset Selection (Multi-Select Card Grid) -->
                            <?php if (!empty($assets)): ?>
                                <div class="space-y-base p-sm bg-surface rounded-xl border border-outline-variant/60">
                                    <label class="font-semibold text-primary flex items-center gap-xs text-xs mb-2">
                                        <span class="material-symbols-outlined text-warning-amber text-sm">widgets</span> Pilih Aset Tambahan (Opsional, Bisa Lebih dari Satu)
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-xs">
                                        <?php foreach ($assets as $ast): ?>
                                            <div class="p-2 border border-outline-variant/80 rounded-lg bg-surface-container-lowest hover:border-primary/20 transition-all flex flex-col justify-between" id="asset_card_<?= $ast['id_aset'] ?>">
                                                <div class="flex items-start gap-2">
                                                    <input type="checkbox" name="assets[<?= $ast['id_aset'] ?>][selected]" value="1" id="asset_check_<?= $ast['id_aset'] ?>" data-harga="<?= $ast['harga_sewa_tambahan'] ?>" data-max="<?= $ast['jumlah'] ?>" class="rounded text-primary focus:ring-primary border-outline-variant mt-0.5" onchange="toggleAssetQuantity(<?= $ast['id_aset'] ?>)">
                                                    <label for="asset_check_<?= $ast['id_aset'] ?>" class="font-bold text-on-surface text-[11px] leading-tight cursor-pointer">
                                                        <?= htmlspecialchars($ast['nama_aset']) ?>
                                                    </label>
                                                </div>
                                                <div class="mt-1.5 flex justify-between items-center text-[9px] text-on-surface-variant">
                                                    <span class="font-semibold text-primary-container bg-surface-container-high px-1 rounded">+<?= format_rupiah($ast['harga_sewa_tambahan']) ?>/hari</span>
                                                    <span>Stok: <?= $ast['jumlah'] ?> Unit</span>
                                                </div>
                                                
                                                <!-- Dynamic Quantity Selector for this Asset -->
                                                <div id="qty_container_<?= $ast['id_aset'] ?>" class="mt-2 pt-1.5 border-t border-outline-variant/40 hidden">
                                                    <label for="qty_input_<?= $ast['id_aset'] ?>" class="font-semibold text-[9px] text-primary block mb-0.5">Jumlah Unit *</label>
                                                    <div class="flex items-center gap-1">
                                                        <input type="number" name="assets[<?= $ast['id_aset'] ?>][jumlah]" id="qty_input_<?= $ast['id_aset'] ?>" value="1" min="1" max="<?= $ast['jumlah'] ?>" class="w-full py-0.5 px-2 rounded border border-outline-variant text-[10px] text-on-surface" onchange="calculateEstimate()" oninput="calculateEstimate()">
                                                        <span class="text-[9px] text-on-surface-variant font-medium whitespace-nowrap">/ <?= $ast['jumlah'] ?> Unit</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Event Details -->
                            <div class="space-y-base">
                                <label for="nama_kegiatan" class="font-semibold text-primary block text-xs">Nama Acara / Kegiatan *</label>
                                <input type="text" name="nama_kegiatan" id="nama_kegiatan" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" placeholder="Contoh: Seminar Nasional AI / Resepsi Pernikahan" required>
                            </div>

                            <div class="space-y-base">
                                <label for="deskripsi_kegiatan" class="font-semibold text-primary block text-xs">Deskripsi Singkat Acara</label>
                                <textarea name="deskripsi_kegiatan" id="deskripsi_kegiatan" rows="2" class="w-full p-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" placeholder="Jelaskan kebutuhan teknis atau rincian acara."></textarea>
                            </div>
                            
                            <!-- Identity Document Upload -->
                            <div class="space-y-sm">
                                <label class="font-semibold text-primary block text-xs">Unggah Kartu Identitas (KTP/KTM/Kartu Instansi) *</label>
                                
                                <!-- Choice Tabs: Segmented Control -->
                                <div class="inline-flex p-1 bg-surface-container-low rounded-xl border border-outline-variant/30 gap-1 select-none w-fit">
                                    <button type="button" id="btn_mode_file" onclick="setIdentitasMode('file')" class="px-md py-1.5 bg-white text-primary text-xs font-bold rounded-lg shadow-sm flex items-center justify-center gap-xs transition-all duration-200">
                                        <span class="material-symbols-outlined text-[16px]">upload_file</span>
                                        <span>Unggah File</span>
                                    </button>
                                    <button type="button" id="btn_mode_camera" onclick="setIdentitasMode('camera')" class="px-md py-1.5 text-on-surface-variant text-xs font-semibold rounded-lg flex items-center justify-center gap-xs hover:bg-surface-container-high hover:text-primary transition-all duration-200">
                                        <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                                        <span>Ambil dengan Kamera</span>
                                    </button>
                                </div>

                                <!-- Mode 1: File Input (Sleek Drag & Drop Card) -->
                                <div id="container_identitas_file" class="space-y-base">
                                    <div class="relative flex flex-col items-center justify-center border border-dashed border-outline-muted hover:border-primary hover:bg-primary/5 transition-all rounded-xl p-md cursor-pointer text-center" onclick="document.getElementById('foto_identitas').click()">
                                        <span class="material-symbols-outlined text-4xl text-primary mb-xs">cloud_upload</span>
                                        <span class="text-xs font-bold text-on-surface block" id="file_upload_label">Pilih atau Seret Foto KTP / KTM</span>
                                        <span class="text-[10px] text-on-surface-variant block mt-1">Format file: JPG, PNG, atau PDF. Maksimal 2MB.</span>
                                        
                                        <!-- Hidden input for file selection -->
                                        <input type="file" name="foto_identitas" id="foto_identitas" class="hidden" required accept="image/*,application/pdf" onchange="updateFileLabel(this)">
                                    </div>
                                </div>

                                <!-- Mode 2: Camera Capture (Webcam Console) -->
                                <div id="container_identitas_camera" class="hidden space-y-md">
                                    <div class="relative w-full max-w-sm aspect-video rounded-xl border border-outline-variant bg-slate-900 overflow-hidden flex items-center justify-center shadow-inner">
                                        <video id="webcam_video" autoplay playsinline class="w-full h-full object-cover"></video>
                                        <img id="webcam_captured_preview" src="" class="absolute inset-0 w-full h-full object-cover hidden">
                                        
                                        <!-- Tech/Card frame overlay -->
                                        <div id="webcam_overlay" class="absolute inset-0 border-[24px] border-slate-950/60 pointer-events-none flex items-center justify-center">
                                            <div class="w-full h-full border border-dashed border-white/60 rounded-lg flex items-center justify-center">
                                                <span class="text-[9px] text-white/70 font-semibold uppercase tracking-wider bg-slate-950/80 px-2 py-0.5 rounded-md">Posisikan KTP / KTM Di Sini</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Loading indicator -->
                                        <div id="webcam_loading" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center text-white text-xs font-semibold gap-xs">
                                            <span class="material-symbols-outlined animate-spin text-lg text-primary-container">sync</span>
                                            <span>Mengakses Kamera...</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Controls Bar -->
                                    <div class="flex items-center gap-sm">
                                        <button type="button" id="btn_snap_camera" onclick="captureSnapshot()" class="h-9 px-lg bg-primary hover:bg-primary-container text-white text-xs font-bold rounded-lg flex items-center gap-xs shadow-md active:scale-95 transition-all">
                                            <span class="material-symbols-outlined text-[16px]">photo_camera</span>
                                            <span>Ambil Foto</span>
                                        </button>
                                        <button type="button" id="btn_retake_camera" onclick="retakeSnapshot()" class="h-9 px-lg border border-outline text-on-surface-variant text-xs font-bold rounded-lg flex items-center gap-xs hover:bg-surface-container-high transition-all hidden">
                                            <span class="material-symbols-outlined text-[16px]">replay</span>
                                            <span>Ulangi Foto</span>
                                        </button>
                                    </div>
                                    <input type="hidden" name="captured_image" id="captured_image">
                                </div>
                            </div>

                            <!-- Schedule Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-sm">
                                <div class="space-y-base">
                                    <label for="tanggal_mulai" class="font-semibold text-primary block text-xs">Tanggal Mulai Acara *</label>
                                    <input type="text" name="tanggal_mulai" id="tanggal_mulai" placeholder="Pilih Tanggal Mulai" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required readonly>
                                </div>
                                <div class="space-y-base">
                                    <label for="tanggal_selesai" class="font-semibold text-primary block text-xs">Tanggal Selesai Acara *</label>
                                    <input type="text" name="tanggal_selesai" id="tanggal_selesai" placeholder="Pilih Tanggal Selesai" class="w-full py-1.5 px-md rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary/20 bg-surface-container-lowest text-xs text-on-surface" required readonly>
                                </div>
                            </div>

                            <!-- Guard Information -->
                            <div class="bg-surface p-sm rounded-xl border border-outline-variant flex items-start gap-sm">
                                <span class="material-symbols-outlined text-warning-amber text-xl flex-shrink-0">verified_user</span>
                                <div class="space-y-xs text-[11px]">
                                    <strong class="text-primary block font-bold">Kebijakan Anti-Overbooking Real-Time</strong>
                                    <p class="text-on-surface-variant leading-relaxed">
                                        Jadwal gedung otomatis akan dikunci secara sah di sistem &amp; kalender setelah bukti pembayaran DP atau Pelunasan divalidasi oleh Admin Politeknik Aceh.
                                    </p>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Right Side Panel / Summary Sidebar (4 Columns) -->
                <div class="lg:col-span-4 space-y-md">
                    
                    <!-- Selected Gedung Details Card -->
                    <?php if ($selected_gedung_info): ?>
                        <div class="bg-white rounded-2xl overflow-hidden shadow-soft border border-outline-variant">
                            <div class="relative h-32 bg-surface-container overflow-hidden">
                                <img src="assets/uploads/<?= htmlspecialchars($selected_gedung_info['foto']) ?>" 
                                     onerror="this.src='https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=800&q=80'" 
                                     class="w-full h-full object-cover"/>
                                <div class="absolute inset-0 bg-gradient-to-t from-primary/80 to-transparent flex items-end p-sm text-white">
                                    <div>
                                        <h4 class="text-xs font-bold text-white"><?= htmlspecialchars($selected_gedung_info['nama_gedung']) ?></h4>
                                        <p class="text-[10px] text-primary-fixed-dim opacity-90">Kapasitas: <?= htmlspecialchars($selected_gedung_info['kapasitas']) ?> Orang</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-sm space-y-xs text-xs text-on-surface-variant">
                                <div class="flex justify-between items-center py-1 border-b border-outline-variant/40">
                                    <span class="font-semibold text-primary">Harga Sewa:</span>
                                    <span class="font-bold text-primary"><?= format_rupiah($selected_gedung_info['harga_sewa']) ?>/hari</span>
                                </div>
                                <?php if (!empty($selected_gedung_info['fasilitas'])): ?>
                                    <div class="pt-1">
                                        <span class="font-semibold text-primary block mb-1 text-[11px]">Fasilitas Utama:</span>
                                        <p class="leading-relaxed bg-surface p-2 rounded-lg border border-outline-variant/40 text-[10px]"><?= htmlspecialchars($selected_gedung_info['fasilitas']) ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Booked Dates List -->
                                <div class="pt-2 border-t border-outline-variant/40 mt-2">
                                    <span class="font-semibold text-primary flex items-center gap-xs text-[11px] mb-1">
                                        <span class="material-symbols-outlined text-xs text-error-red">event_busy</span> Jadwal Terisi (Tidak Tersedia):
                                    </span>
                                    <?php
                                    // Fetch detailed bookings list for display
                                    $stmt_det = $pdo->prepare("SELECT nama_kegiatan, tanggal_mulai, tanggal_selesai FROM transaksi 
                                                               WHERE id_gedung = :id 
                                                               AND status_transaksi NOT IN ('Ditolak', 'Dibatalkan') 
                                                               AND tanggal_selesai >= CURRENT_DATE()
                                                               ORDER BY tanggal_mulai ASC");
                                    $stmt_det->execute([':id' => $id_gedung_selected]);
                                    $det_bookings = $stmt_det->fetchAll();
                                    
                                    if (empty($det_bookings)):
                                    ?>
                                        <p class="text-[10px] text-success-green bg-success-green/5 p-2 rounded-lg border border-success-green/20">Semua tanggal masih tersedia untuk gedung ini.</p>
                                    <?php else: ?>
                                        <div class="space-y-1 max-h-32 overflow-y-auto pr-1">
                                            <?php foreach ($det_bookings as $db): ?>
                                                <div class="p-1.5 bg-error-container/20 rounded-lg border border-error-red/10 text-[10px]">
                                                    <strong class="text-primary block leading-none truncate mb-0.5 font-bold">"<?= htmlspecialchars($db['nama_kegiatan']) ?>"</strong>
                                                    <span class="text-error-red font-semibold"><?= format_tanggal($db['tanggal_mulai']) ?> s/d <?= format_tanggal($db['tanggal_selesai']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white p-md rounded-2xl border border-dashed border-outline-variant text-center space-y-xs shadow-soft py-6">
                            <span class="material-symbols-outlined text-3xl text-primary-fixed-dim">domain</span>
                            <h4 class="text-sm font-bold text-primary">Pilih Gedung</h4>
                            <p class="text-[11px] text-on-surface-variant">Silakan pilih gedung di formulir untuk melihat pratinjau fasilitas &amp; tarif lengkap.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Realtime Price Estimation Box -->
                    <div class="bg-gradient-to-br from-primary to-[#002068] text-white p-md rounded-2xl shadow-soft border border-primary/20 space-y-sm">
                        <div class="flex items-center gap-xs text-warning-amber">
                            <span class="material-symbols-outlined text-sm">calculate</span>
                            <h4 class="text-xs font-bold text-white">Estimasi Biaya</h4>
                        </div>
                        <div class="space-y-sm text-[11px]">
                            <!-- Durasi -->
                            <div class="flex justify-between items-center text-primary-fixed-dim border-b border-white/10 pb-1.5">
                                <span>Durasi Acara:</span>
                                <strong id="est_durasi" class="text-white text-xs">1 Hari</strong>
                            </div>
                            
                            <!-- Gedung -->
                            <div class="space-y-0.5">
                                <div class="flex justify-between items-center text-primary-fixed-dim">
                                    <span>Harga Gedung / Hari:</span>
                                    <span id="est_harga_gedung" class="text-white"><?= format_rupiah($selected_gedung_info['harga_sewa'] ?? 0) ?>/hari</span>
                                </div>
                                <div class="flex justify-between items-center font-bold text-white text-xs pl-2">
                                    <span>Subtotal Gedung:</span>
                                    <span id="est_subtotal_gedung"><?= format_rupiah($selected_gedung_info['harga_sewa'] ?? 0) ?></span>
                                </div>
                            </div>

                            <!-- Aset -->
                            <div class="space-y-1 border-t border-white/10 pt-1.5">
                                <div class="flex justify-between items-center text-primary-fixed-dim">
                                    <span>Aset Tambahan / Hari:</span>
                                    <span id="est_harga_aset" class="text-white">Rp 0/hari</span>
                                </div>
                                <div id="est_aset_details" class="pl-2 space-y-0.5 text-[9.5px] text-primary-fixed-dim hidden">
                                    <!-- Dynamic details -->
                                </div>
                                <div class="flex justify-between items-center font-bold text-white text-xs pl-2">
                                    <span>Subtotal Aset:</span>
                                    <span id="est_subtotal_aset">Rp 0</span>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="border-t border-white/20 pt-1.5 mt-1.5 flex justify-between items-center text-xs">
                                <span class="font-bold">Estimasi Total Akhir:</span>
                                <strong id="est_total" class="text-warning-amber text-sm font-black"><?= format_rupiah($selected_gedung_info['harga_sewa'] ?? 0) ?></strong>
                            </div>
                        </div>
                        <p class="text-[9px] text-primary-fixed opacity-75 italic">* Harga sewa gedung dan aset dikalikan dengan durasi hari sewa secara proporsional.</p>
                    </div>

                    <!-- Action Submit (HTML5 Form association) -->
                    <div class="bg-white p-sm rounded-2xl shadow-soft border border-outline-variant">
                        <button type="submit" form="bookingForm" class="w-full h-10 bg-primary hover:bg-primary-container text-on-primary font-bold text-xs rounded-xl flex items-center justify-center gap-xs shadow-md active:scale-95 transition-all cursor-pointer">
                            <span class="material-symbols-outlined text-sm">send</span> Kirim Permohonan Booking
                        </button>
                    </div>

                    <!-- Steps Guide Card -->
                    <div class="bg-white p-md rounded-2xl shadow-soft border border-outline-variant space-y-sm">
                        <h4 class="text-sm font-bold text-primary flex items-center gap-xs">
                            <span class="material-symbols-outlined text-warning-amber text-sm">timeline</span> Tahapan Booking
                        </h4>
                        <ol class="relative border-l border-outline-variant ml-2 space-y-sm text-[11px]">
                            <li class="ml-4">
                                <div class="absolute w-2 h-2 bg-primary rounded-full -left-[4.5px] border border-white"></div>
                                <strong class="font-bold text-primary block">1. Kirim Form Permohonan</strong>
                                <span class="text-on-surface-variant">Pilih gedung, tanggal, dan nama acara.</span>
                            </li>
                            <li class="ml-4">
                                <div class="absolute w-2 h-2 bg-warning-amber rounded-full -left-[4.5px] border border-white"></div>
                                <strong class="font-bold text-primary block">2. Transfer Pembayaran</strong>
                                <span class="text-on-surface-variant">Lakukan pembayaran DP atau Pelunasan via bank.</span>
                            </li>
                            <li class="ml-4">
                                <div class="absolute w-2 h-2 bg-success-green rounded-full -left-[4.5px] border border-white"></div>
                                <strong class="font-bold text-primary block">3. Validasi &amp; Kunci Jadwal</strong>
                                <span class="text-on-surface-variant">Admin menyetujui bukti &amp; jadwal otomatis terkunci.</span>
                            </li>
                        </ol>
                    </div>

                </div>

            </div>
        </main>
    </div>
</div>

<!-- Dynamic Estimation Script -->
<script>
    let webcamStream = null;
    let currentMode = 'file'; // 'file' or 'camera'

    function setIdentitasMode(mode) {
        currentMode = mode;
        const fileContainer = document.getElementById('container_identitas_file');
        const cameraContainer = document.getElementById('container_identitas_camera');
        const fileBtn = document.getElementById('btn_mode_file');
        const cameraBtn = document.getElementById('btn_mode_camera');
        const fileInput = document.getElementById('foto_identitas');
        
        if (mode === 'file') {
            stopCamera();
            fileContainer.classList.remove('hidden');
            cameraContainer.classList.add('hidden');
            
            fileBtn.className = "px-md py-1.5 bg-white text-primary text-xs font-bold rounded-lg shadow-sm flex items-center justify-center gap-xs transition-all duration-200";
            cameraBtn.className = "px-md py-1.5 text-on-surface-variant text-xs font-semibold rounded-lg flex items-center justify-center gap-xs hover:bg-surface-container-high hover:text-primary transition-all duration-200";
            
            fileInput.required = true;
        } else {
            fileContainer.classList.add('hidden');
            cameraContainer.classList.remove('hidden');
            
            fileBtn.className = "px-md py-1.5 text-on-surface-variant text-xs font-semibold rounded-lg flex items-center justify-center gap-xs hover:bg-surface-container-high hover:text-primary transition-all duration-200";
            cameraBtn.className = "px-md py-1.5 bg-white text-primary text-xs font-bold rounded-lg shadow-sm flex items-center justify-center gap-xs transition-all duration-200";
            
            fileInput.required = false;
            startCamera();
        }
    }

    function startCamera() {
        const video = document.getElementById('webcam_video');
        const loading = document.getElementById('webcam_loading');
        const preview = document.getElementById('webcam_captured_preview');
        const overlay = document.getElementById('webcam_overlay');
        const snapBtn = document.getElementById('btn_snap_camera');
        const retakeBtn = document.getElementById('btn_retake_camera');
        
        loading.classList.remove('hidden');
        preview.classList.add('hidden');
        video.classList.remove('hidden');
        overlay.classList.remove('hidden');
        snapBtn.classList.remove('hidden');
        retakeBtn.classList.add('hidden');
        
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                webcamStream = stream;
                video.srcObject = stream;
                loading.classList.add('hidden');
            })
            .catch(err => {
                console.error(err);
                loading.innerHTML = '<span class="material-symbols-outlined text-error-red text-sm mb-1">error</span><span class="text-error-red">Gagal mengakses kamera.</span>';
            });
    }

    function stopCamera() {
        if (webcamStream) {
            webcamStream.getTracks().forEach(track => track.stop());
            webcamStream = null;
        }
    }

    function captureSnapshot() {
        const video = document.getElementById('webcam_video');
        const preview = document.getElementById('webcam_captured_preview');
        const overlay = document.getElementById('webcam_overlay');
        const hiddenInput = document.getElementById('captured_image');
        const snapBtn = document.getElementById('btn_snap_camera');
        const retakeBtn = document.getElementById('btn_retake_camera');
        
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataUrl = canvas.toDataURL('image/png');
        hiddenInput.value = dataUrl;
        
        preview.src = dataUrl;
        preview.classList.remove('hidden');
        video.classList.add('hidden');
        overlay.classList.add('hidden');
        
        snapBtn.classList.add('hidden');
        retakeBtn.classList.remove('hidden');
        
        stopCamera();
    }

    function retakeSnapshot() {
        document.getElementById('captured_image').value = '';
        startCamera();
    }

    function updateFileLabel(input) {
        const label = document.getElementById('file_upload_label');
        if (input.files && input.files.length > 0) {
            label.innerText = 'Berkas Terpilih: ' + input.files[0].name;
            label.classList.add('text-success-green');
        } else {
            label.innerText = 'Pilih atau Seret Foto KTP / KTM';
            label.classList.remove('text-success-green');
        }
    }

    // Attach form submit validator for camera mode & Flatpickr initialization
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('bookingForm');
        if (form) {
            form.addEventListener('submit', function (event) {
                if (currentMode === 'camera') {
                    const capturedImg = document.getElementById('captured_image').value;
                    if (!capturedImg || capturedImg.trim() === '') {
                        event.preventDefault();
                        alert('Silakan ambil foto identitas Anda terlebih dahulu menggunakan kamera.');
                    }
                }
            });
        }

        // Initialize Flatpickr for booking dates
        const disabledDates = <?= json_encode($booked_dates) ?>;
        
        flatpickr("#tanggal_mulai", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: disabledDates,
            onChange: function(selectedDates, dateStr, instance) {
                const selesaiPicker = document.querySelector("#tanggal_selesai")._flatpickr;
                if (selesaiPicker) {
                    selesaiPicker.set("minDate", dateStr || "today");
                }
                calculateEstimate();
            }
        });

        flatpickr("#tanggal_selesai", {
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: disabledDates,
            onChange: function(selectedDates, dateStr, instance) {
                calculateEstimate();
            }
        });
    });

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    function toggleAssetQuantity(id) {
        const check = document.getElementById('asset_check_' + id);
        const qtyContainer = document.getElementById('qty_container_' + id);
        const qtyInput = document.getElementById('qty_input_' + id);
        const card = document.getElementById('asset_card_' + id);
        
        if (check && check.checked) {
            qtyContainer.classList.remove('hidden');
            card.classList.add('border-primary', 'bg-primary/5');
            card.classList.remove('border-outline-variant/80', 'bg-surface-container-lowest');
        } else {
            qtyContainer.classList.add('hidden');
            if (qtyInput) qtyInput.value = 1;
            card.classList.remove('border-primary', 'bg-primary/5');
            card.classList.add('border-outline-variant/80', 'bg-surface-container-lowest');
        }
        calculateEstimate();
    }

    function calculateEstimate() {
        const gedungSelect = document.getElementById('id_gedung');
        const tglMulai = document.getElementById('tanggal_mulai').value;
        const tglSelesai = document.getElementById('tanggal_selesai').value;

        let hargaGedung = 0;
        if (gedungSelect && gedungSelect.selectedIndex >= 0) {
            const opt = gedungSelect.options[gedungSelect.selectedIndex];
            hargaGedung = parseFloat(opt.getAttribute('data-harga') || 0);
        }

        let durasi = 1;
        if (tglMulai && tglSelesai) {
            const d1 = new Date(tglMulai);
            const d2 = new Date(tglSelesai);
            if (d2 >= d1) {
                const diffTime = Math.abs(d2 - d1);
                durasi = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            }
        }

        let hargaAsetTotalHari = 0;
        let detailHtml = '';
        const checkedCheckboxes = document.querySelectorAll('input[name^="assets"][name$="[selected]"]:checked');
        
        checkedCheckboxes.forEach(chk => {
            const harga = parseFloat(chk.getAttribute('data-harga') || 0);
            const maxVal = parseInt(chk.getAttribute('data-max') || 1);
            const id = chk.id.replace('asset_check_', '');
            const qtyInput = document.getElementById('qty_input_' + id);
            
            const label = document.querySelector(`label[for="asset_check_${id}"]`);
            const namaAset = label ? label.innerText.trim() : 'Aset';
            
            if (qtyInput) {
                let qty = parseInt(qtyInput.value) || 1;
                if (qty < 1) {
                    qty = 1;
                    qtyInput.value = 1;
                } else if (qty > maxVal) {
                    qty = maxVal;
                    qtyInput.value = maxVal;
                }
                const subAsetHari = harga * qty;
                hargaAsetTotalHari += subAsetHari;
                
                detailHtml += `<div class="flex justify-between pl-3 border-l-2 border-warning-amber/40 py-0.5 my-0.5">
                    <span>• ${qty}x ${namaAset} (${qty}x ${formatRupiah(harga)})</span>
                    <span>${formatRupiah(subAsetHari)}/hari</span>
                </div>`;
            }
        });

        const totalGedung = hargaGedung * durasi;
        const totalAset = hargaAsetTotalHari * durasi;
        const grandTotal = totalGedung + totalAset;

        // Set duration
        document.getElementById('est_durasi').innerText = durasi + ' Hari';
        
        // Set building estimation
        document.getElementById('est_harga_gedung').innerText = formatRupiah(hargaGedung) + '/hari';
        document.getElementById('est_subtotal_gedung').innerText = formatRupiah(totalGedung);
        
        // Set asset estimation
        document.getElementById('est_harga_aset').innerText = formatRupiah(hargaAsetTotalHari) + '/hari';
        document.getElementById('est_subtotal_aset').innerText = formatRupiah(totalAset);
        
        // Set asset details container
        const detailsContainer = document.getElementById('est_aset_details');
        if (detailHtml !== '') {
            detailsContainer.innerHTML = detailHtml;
            detailsContainer.classList.remove('hidden');
        } else {
            detailsContainer.innerHTML = '';
            detailsContainer.classList.add('hidden');
        }
        
        // Set grand total
        document.getElementById('est_total').innerText = formatRupiah(grandTotal);
    }

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
