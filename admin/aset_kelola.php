<?php
// SIIPAK - Kelola Data Aset Pendukung (Admin)
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_admin();

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Process CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_aset']) && $_POST['add_aset'] == '1') {
        $id_gedung           = (int)$_POST['id_gedung'];
        $nama_aset           = sanitize($_POST['nama_aset']);
        $jumlah              = (int)$_POST['jumlah'];
        $harga_sewa_tambahan = (float)$_POST['harga_sewa_tambahan'];
        $kondisi_aset        = $_POST['kondisi_aset'];

        $stmt = $pdo->prepare("INSERT INTO aset (id_gedung, nama_aset, jumlah, harga_sewa_tambahan, kondisi_aset) VALUES (:id_gedung, :nama, :jumlah, :harga, :kondisi)");
        $stmt->execute([
            ':id_gedung' => $id_gedung,
            ':nama'      => $nama_aset,
            ':jumlah'    => $jumlah,
            ':harga'     => $harga_sewa_tambahan,
            ':kondisi'   => $kondisi_aset
        ]);
        set_flash('success', "Aset pendukung '$nama_aset' berhasil ditambahkan.");
    }
    elseif (isset($_POST['edit_aset']) && $_POST['edit_aset'] == '1') {
        $id_aset             = (int)$_POST['id_aset'];
        $id_gedung           = (int)$_POST['id_gedung'];
        $nama_aset           = sanitize($_POST['nama_aset']);
        $jumlah              = (int)$_POST['jumlah'];
        $harga_sewa_tambahan = (float)$_POST['harga_sewa_tambahan'];
        $kondisi_aset        = $_POST['kondisi_aset'];

        $stmt = $pdo->prepare("UPDATE aset SET id_gedung = :id_gedung, nama_aset = :nama, jumlah = :jumlah, harga_sewa_tambahan = :harga, kondisi_aset = :kondisi WHERE id_aset = :id");
        $stmt->execute([
            ':id_gedung' => $id_gedung,
            ':nama'      => $nama_aset,
            ':jumlah'    => $jumlah,
            ':harga'     => $harga_sewa_tambahan,
            ':kondisi'   => $kondisi_aset,
            ':id'        => $id_aset
        ]);
        set_flash('success', "Data aset '$nama_aset' berhasil diperbarui.");
    }
    elseif (isset($_POST['delete_aset'])) {
        $id_aset = (int)$_POST['id_aset'];
        $stmt = $pdo->prepare("DELETE FROM aset WHERE id_aset = :id");
        $stmt->execute([':id' => $id_aset]);
        set_flash('success', "Aset pendukung berhasil dihapus.");
    }
    header('Location: aset_kelola.php');
    exit;
}

// Fetch all assets with filters
$search_id = (int)($_GET['id_gedung'] ?? 0);
$search = sanitize($_GET['q'] ?? '');

if ($search_id > 0) {
    $stmt = $pdo->prepare("SELECT a.*, g.nama_gedung 
                           FROM aset a 
                           JOIN gedung g ON a.id_gedung = g.id_gedung 
                           WHERE a.id_gedung = :id_gedung 
                           ORDER BY a.id_aset DESC");
    $stmt->execute([':id_gedung' => $search_id]);
    
    // Pre-fill the search input with the building name so the user knows what they are viewing
    $stmt_g = $pdo->prepare("SELECT nama_gedung FROM gedung WHERE id_gedung = :id");
    $stmt_g->execute([':id' => $search_id]);
    $search = trim($stmt_g->fetchColumn() ?? '');
} elseif (!empty($search)) {
    $stmt = $pdo->prepare("SELECT a.*, g.nama_gedung 
                           FROM aset a 
                           JOIN gedung g ON a.id_gedung = g.id_gedung 
                           WHERE a.nama_aset LIKE :q1 OR g.nama_gedung LIKE :q2 
                           ORDER BY a.id_aset DESC");
    $stmt->execute([':q1' => "%$search%", ':q2' => "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT a.*, g.nama_gedung FROM aset a JOIN gedung g ON a.id_gedung = g.id_gedung ORDER BY a.id_aset DESC");
}
$aset_list = $stmt->fetchAll();

// Fetch building options for select dropdown
$gedung_list = $pdo->query("SELECT * FROM gedung ORDER BY nama_gedung ASC")->fetchAll();

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
    <title>Master Aset | SIPAK Politeknik Aceh</title>
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
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="aset_kelola.php">
                <span class="font-label-lg text-label-lg font-bold">Data Aset</span>
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-sm">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary font-bold">Kelola Data Aset Pendukung</h1>
                    <p class="text-xs text-on-surface-variant">Tambahkan &amp; atur stok fasilitas opsional seperti kursi, proyektor, sound system, panggung dll.</p>
                </div>
                
                <button onclick="openAddAsetModal()" class="h-9 bg-primary hover:bg-primary-container text-white font-bold text-xs px-md rounded-lg flex items-center gap-xs shadow-md active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-sm">add</span> Tambah Aset Baru
                </button>
            </div>

            <!-- Flash Session Messaging -->
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="bg-success-green/10 text-success-green p-sm rounded-xl border border-success-green/20 flex items-center gap-xs text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    <span><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <!-- Search Card -->
            <div class="bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                <form method="GET" action="aset_kelola.php" class="flex gap-sm items-center">
                    <div class="relative flex-1">
                        <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                        <input type="text" name="q" class="w-full pl-9 pr-md py-1.5 bg-surface-container-low border-none rounded-lg text-xs focus:ring-1 focus:ring-primary/20 text-on-surface" placeholder="Cari nama aset atau gedung..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="h-9 bg-surface-container-high text-primary hover:bg-outline-variant font-bold text-xs px-md rounded-lg flex items-center gap-xs active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-sm">filter_alt</span> Cari
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="aset_kelola.php" class="h-9 w-9 bg-surface-container-high hover:bg-outline-variant text-on-surface-variant rounded-lg flex items-center justify-center hover:text-primary active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table List -->
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse align-middle">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-lg py-2">No</th>
                                <th class="px-lg py-2">Nama Aset</th>
                                <th class="px-lg py-2">Terhubung ke Gedung</th>
                                <th class="px-lg py-2 text-center">Jumlah Stok</th>
                                <th class="px-lg py-2 text-right">Harga Tambahan / Hari</th>
                                <th class="px-lg py-2 text-center">Kondisi Fisik</th>
                                <th class="px-lg py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                            <?php if (empty($aset_list)): ?>
                                <tr>
                                    <td colspan="7" class="px-lg py-6 text-center text-on-surface-variant">Belum ada data aset pendukung terdaftar.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($aset_list as $a): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                        <td class="px-lg py-2.5 text-on-surface-variant font-bold"><?= $no++ ?></td>
                                        <td class="px-lg py-2.5">
                                            <strong class="text-primary font-bold text-xs"><?= htmlspecialchars($a['nama_aset']) ?></strong>
                                        </td>
                                        <td class="px-lg py-2.5">
                                            <span class="inline-block whitespace-nowrap bg-surface-container text-primary text-[10px] px-2.5 py-0.5 rounded-full font-bold border border-outline-variant/40"><?= htmlspecialchars($a['nama_gedung']) ?></span>
                                        </td>
                                        <td class="px-lg py-2.5 text-center font-semibold text-on-surface"><?= number_format($a['jumlah']) ?> Unit</td>
                                        <td class="px-lg py-2.5 text-right font-bold text-primary"><?= format_rupiah($a['harga_sewa_tambahan']) ?></td>
                                        <td class="px-lg py-2.5 text-center">
                                            <?php if ($a['kondisi_aset'] === 'Bagus'): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-success-green/10 text-success-green border border-success-green/20">Bagus</span>
                                            <?php elseif ($a['kondisi_aset'] === 'Rusak Ringan'): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-warning-amber/10 text-secondary border border-warning-amber/20">Rusak Ringan</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-on-error-container border border-error/20">Rusak Berat</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-lg py-2.5 text-center">
                                            <div class="flex justify-center gap-sm">
                                                <button type="button" class="bg-primary text-white text-[10px] font-bold px-2 py-1 rounded-lg hover:shadow-md active:scale-95 transition-all flex items-center gap-xs" onclick="openEditAsetModal(<?= htmlspecialchars(json_encode($a)) ?>)">
                                                    <span class="material-symbols-outlined text-[12px]">edit</span> Edit
                                                </button>
                                                <form method="POST" action="aset_kelola.php" onsubmit="showConfirmModal(event, 'Apakah Anda yakin ingin menghapus aset ini?')">
                                                    <input type="hidden" name="delete_aset" value="1">
                                                    <input type="hidden" name="id_aset" value="<?= $a['id_aset'] ?>">
                                                    <button type="submit" class="bg-error-red text-white text-[10px] font-bold px-2 py-1 rounded-lg hover:shadow-md active:scale-95 transition-all flex items-center gap-xs">
                                                        <span class="material-symbols-outlined text-[12px]">delete</span> Hapus
                                                    </button>
                                                </form>
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

<!-- Modal Add/Edit Aset -->
<div id="asetModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-md">
    <div class="bg-white rounded-2xl max-w-sm w-full overflow-hidden border border-outline-variant shadow-lg animate-scale-up">
        <div class="bg-primary text-white p-md flex items-center justify-between">
            <h5 class="font-headline-md text-headline-md font-bold text-white" id="asetModalTitle">Tambah Aset Baru</h5>
            <button type="button" onclick="closeAsetModal()" class="text-white hover:text-warning-amber transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <form method="POST" action="aset_kelola.php">
            <input type="hidden" name="id_aset" id="m_id_aset">
            <input type="hidden" name="add_aset" id="m_aset_action_add" value="1">
            <input type="hidden" name="edit_aset" id="m_aset_action_edit" value="0">
            
            <div class="p-md space-y-sm text-xs">
                <div class="space-y-base">
                    <label for="id_gedung" class="font-semibold text-primary block">Pilih Gedung Induk *</label>
                    <select name="id_gedung" id="m_id_gedung" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required>
                        <option value="">-- Pilih Gedung --</option>
                        <?php foreach ($gedung_list as $g): ?>
                            <option value="<?= $g['id_gedung'] ?>"><?= htmlspecialchars($g['nama_gedung']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-base">
                    <label for="nama_aset" class="font-semibold text-primary block">Nama Aset Pendukung *</label>
                    <input type="text" name="nama_aset" id="m_nama_aset" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required placeholder="Contoh: Kursi Futura Cover Busa">
                </div>
                
                <div class="grid grid-cols-2 gap-sm">
                    <div class="space-y-base">
                        <label for="jumlah" class="font-semibold text-primary block">Stok Unit Tersedia *</label>
                        <input type="number" name="jumlah" id="m_jumlah" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required min="1">
                    </div>
                    <div class="space-y-base">
                        <label for="kondisi_aset" class="font-semibold text-primary block">Kondisi Fisik *</label>
                        <select name="kondisi_aset" id="m_kondisi" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required>
                            <option value="Bagus">Bagus (Siap Pakai)</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-base">
                    <label for="harga_sewa_tambahan" class="font-semibold text-primary block">Harga Sewa / Unit Per Hari (Rp) *</label>
                    <input type="number" name="harga_sewa_tambahan" id="m_harga_sewa_aset" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface" required>
                </div>
            </div>
            <div class="px-md py-2.5 bg-surface-container-low border-t border-outline-variant/40 flex justify-end gap-sm">
                <button type="button" onclick="closeAsetModal()" class="px-md py-1.5 border border-outline text-on-surface-variant text-[11px] rounded-lg hover:bg-surface-container-high transition-colors">Batal</button>
                <button type="submit" class="px-md py-1.5 bg-primary text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all">Simpan Aset</button>
            </div>
        </form>
    </div>
</div>
<!-- Custom Confirmation Modal HTML -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-[100] p-md">
    <div class="bg-white rounded-2xl max-w-sm w-full overflow-hidden border border-outline-variant shadow-lg animate-scale-up">
        <div class="p-md text-center space-y-md">
            <div class="w-12 h-12 rounded-full bg-error-container text-error flex items-center justify-center mx-auto shadow-inner">
                <span class="material-symbols-outlined text-lg">warning</span>
            </div>
            <div class="space-y-xs">
                <h4 class="font-headline-md text-headline-md font-bold text-primary" id="confirmModalTitle">Konfirmasi Hapus</h4>
                <p class="text-xs text-on-surface-variant leading-relaxed" id="confirmModalMessage">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="flex gap-sm justify-center pt-2">
                <button type="button" id="confirmCancelBtn" class="px-md py-1.5 border border-outline text-on-surface-variant text-[11px] rounded-lg hover:bg-surface-container-high transition-colors">Batal</button>
                <button type="button" id="confirmOkBtn" class="px-md py-1.5 bg-error-red text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all">Hapus</button>
            </div>
        </div>
    </div>
</div>

<script>
function openAddAsetModal() {
    document.getElementById('asetModalTitle').innerText = 'Tambah Aset Baru';
    document.getElementById('m_id_aset').value = '';
    document.getElementById('m_id_gedung').value = '';
    document.getElementById('m_nama_aset').value = '';
    document.getElementById('m_jumlah').value = 1;
    document.getElementById('m_harga_sewa_aset').value = 0;
    document.getElementById('m_kondisi').value = 'Bagus';
    
    document.getElementById('m_aset_action_add').value = '1';
    document.getElementById('m_aset_action_edit').value = '0';
    
    const modal = document.getElementById('asetModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function openEditAsetModal(a) {
    document.getElementById('asetModalTitle').innerText = 'Edit Data Aset';
    document.getElementById('m_id_aset').value = a.id_aset;
    document.getElementById('m_id_gedung').value = a.id_gedung;
    document.getElementById('m_nama_aset').value = a.nama_aset;
    document.getElementById('m_jumlah').value = a.jumlah;
    document.getElementById('m_harga_sewa_aset').value = a.harga_sewa_tambahan;
    document.getElementById('m_kondisi').value = a.kondisi_aset;
    
    document.getElementById('m_aset_action_add').value = '0';
    document.getElementById('m_aset_action_edit').value = '1';
    
    const modal = document.getElementById('asetModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAsetModal() {
    const modal = document.getElementById('asetModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

// Custom Confirm Modal Logic
let formToSubmit = null;
function showConfirmModal(event, message, title = 'Konfirmasi Hapus', buttonText = 'Hapus') {
    event.preventDefault();
    formToSubmit = event.target;
    
    document.getElementById('confirmModalTitle').innerText = title;
    document.getElementById('confirmModalMessage').innerText = message;
    
    const okBtn = document.getElementById('confirmOkBtn');
    okBtn.innerText = buttonText;
    
    if (buttonText === 'Hapus') {
        okBtn.className = "px-md py-1.5 bg-error-red text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all";
    } else {
        okBtn.className = "px-md py-1.5 bg-primary text-white font-bold text-[11px] rounded-lg hover:shadow-md active:scale-95 transition-all";
    }

    const modal = document.getElementById('confirmModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

document.getElementById('confirmCancelBtn').addEventListener('click', () => {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    formToSubmit = null;
});

document.getElementById('confirmOkBtn').addEventListener('click', () => {
    if (formToSubmit) {
        formToSubmit.submit();
    }
});

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
