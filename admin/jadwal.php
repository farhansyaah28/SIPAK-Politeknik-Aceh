<?php
// SIIPAK - Jadwal Terpadu Sewa Gedung (Admin)
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_admin();

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch summary metrics (for pending notifications count in sidebar)
$pending_val = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_validasi = 'Menunggu'")->fetchColumn();

// Fetch building options
$gedung_list = $pdo->query("SELECT * FROM gedung ORDER BY nama_gedung ASC")->fetchAll();
$selected_gedung = $_GET['id_gedung'] ?? '';

// Build calendar events
$sql = "SELECT t.*, g.nama_gedung, py.nama AS nama_penyewa 
        FROM transaksi t
        JOIN gedung g ON t.id_gedung = g.id_gedung
        JOIN penyewa py ON t.id_penyewa = py.id_penyewa
        WHERE t.status_transaksi IN ('DP', 'Cicilan', 'Lunas')";

$params = [];
if (!empty($selected_gedung)) {
    $sql .= " AND t.id_gedung = :id_gedung";
    $params[':id_gedung'] = $selected_gedung;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = [];
while ($row = $stmt->fetch()) {
    // Color codes based on transaction status
    $color = '#2563eb'; // DP / Cicilan: Blue
    if ($row['status_transaksi'] === 'Lunas') {
        $color = '#45bf59'; // Lunas: Green
    }

    $events[] = [
        'title' => '[' . htmlspecialchars($row['nama_gedung']) . '] ' . htmlspecialchars($row['nama_penyewa']) . ' - ' . htmlspecialchars($row['nama_kegiatan']),
        'start' => $row['tanggal_mulai'] . 'T08:00:00',
        'end'   => $row['tanggal_selesai'] . 'T18:00:00',
        'color' => $color,
        'allDay'=> true,
        'extendedProps' => [
            'kode_transaksi' => htmlspecialchars($row['kode_transaksi']),
            'nama_gedung' => htmlspecialchars($row['nama_gedung']),
            'nama_penyewa' => htmlspecialchars($row['nama_penyewa']),
            'nama_kegiatan' => htmlspecialchars($row['nama_kegiatan']),
            'status' => htmlspecialchars($row['status_transaksi']),
            'tanggal_mulai' => format_tanggal($row['tanggal_mulai']),
            'tanggal_selesai' => format_tanggal($row['tanggal_selesai'])
        ]
    ];
}

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
    <title>Jadwal Terpadu | SIPAK Politeknik Aceh</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- FullCalendar CDN -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
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
        /* Clean & Premium Minimalist Calendar Design */
        .fc {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif !important;
            --fc-border-color: #f1f5f9 !important; /* Extremely soft slate-100 border */
            --fc-today-bg-color: rgba(0, 45, 149, 0.04) !important; /* Soft blue highlight for today */
            --fc-button-bg-color: #ffffff !important;
            --fc-button-border-color: #e2e8f0 !important;
            --fc-button-text-color: #334155 !important;
            --fc-button-hover-bg-color: #f8fafc !important;
            --fc-button-hover-border-color: #cbd5e1 !important;
            --fc-button-active-bg-color: #002d95 !important;
            --fc-button-active-border-color: #002d95 !important;
        }

        /* Modernized Toolbar */
        .fc .fc-toolbar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 20px !important;
            padding: 8px 12px !important;
            background: #ffffff !important;
            border-radius: 12px !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
        }

        .fc-toolbar-title {
            font-size: 1.1rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            letter-spacing: -0.02em !important;
        }

        /* Clean Flat Buttons */
        .fc-button-primary {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            color: #475569 !important;
            font-weight: 600 !important;
            font-size: 0.75rem !important;
            padding: 6px 12px !important;
            border-radius: 10px !important;
            text-transform: capitalize !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03) !important;
            transition: all 0.15s ease-in-out !important;
        }

        .fc-button-primary:hover {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }

        .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #002d95 !important;
            border-color: #002d95 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        /* Premium Calendar Header th */
        .fc-theme-standard th {
            border: none !important;
            background-color: #f8fafc !important;
            color: #64748b !important;
            font-weight: 700 !important;
            font-size: 0.7rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            padding: 10px 0 !important;
        }

        /* Clean Calendar Cell Grid */
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #f1f5f9 !important;
        }

        .fc-daygrid-day-number {
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #475569 !important;
            padding: 8px 10px !important;
            transition: color 0.15s ease !important;
        }

        /* Active highlight for today number */
        .fc-day-today .fc-daygrid-day-number {
            color: #002d95 !important;
            font-weight: 800 !important;
            background-color: rgba(0, 45, 149, 0.1) !important;
            border-radius: 9999px !important;
            display: inline-block !important;
            padding: 4px 8px !important;
            margin: 4px 6px !important;
        }

        /* Modern Event Cards */
        .fc-v-event, .fc-h-event {
            border: none !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03) !important;
            padding: 4px 8px !important;
            border-radius: 8px !important;
            font-size: 0.7rem !important;
            font-weight: 600 !important;
            margin-top: 2px !important;
            transition: transform 0.15s ease, box-shadow 0.15s ease !important;
        }

        .fc-v-event:hover, .fc-h-event:hover {
            transform: translateY(-1px) scale(1.01) !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06) !important;
        }

        /* Grid lines clean background */
        .fc-daygrid-day {
            transition: background-color 0.15s ease !important;
        }

        .fc-daygrid-day:hover {
            background-color: #f8fafc !important;
        }

        /* Mobile specific styles */
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column !important;
                align-items: center !important;
                gap: 10px !important;
                padding: 10px !important;
            }
            .fc-toolbar-chunk {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
            }
            .fc-toolbar-title {
                font-size: 1rem !important;
                text-align: center !important;
            }
            .fc-button-primary {
                padding: 6px 12px !important;
                font-size: 0.7rem !important;
            }
            .fc-daygrid-day-number {
                font-size: 0.65rem !important;
                padding: 4px !important;
            }
            .fc-day-today .fc-daygrid-day-number {
                margin: 2px !important;
                padding: 2px 6px !important;
            }
            .fc-theme-standard th {
                padding: 6px 0 !important;
                font-size: 0.65rem !important;
            }
            .fc-daygrid-event {
                font-size: 0.6rem !important;
                padding: 2px 4px !important;
                border-radius: 4px !important;
            }
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
                <span class="material-symbols-outlined mr-2">dashboard</span>
                <span class="font-label-lg text-label-lg font-semibold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking_kelola.php">
                <span class="material-symbols-outlined mr-2">receipt_long</span>
                <span class="font-label-lg text-label-lg">Kelola Booking</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none relative" href="pembayaran_validasi.php">
                <span class="material-symbols-outlined mr-2">price_check</span>
                <span class="font-label-lg text-label-lg">Validasi Bayar</span>
                <?php if ($pending_val > 0): ?>
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 bg-error-red text-white text-[9px] rounded-full flex items-center justify-center font-bold">
                        <?= $pending_val ?>
                    </span>
                <?php endif; ?>
            </a>
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="jadwal.php">
                <span class="material-symbols-outlined mr-2" style="font-variation-settings: 'FILL' 1;">calendar_month</span>
                <span class="font-label-lg text-label-lg font-bold">Jadwal Terpadu</span>
            </a>
            
            <div class="h-[1px] bg-outline-muted/5 my-1.5 mx-md"></div>
            <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Data Master</div>

            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="gedung_kelola.php">
                <span class="material-symbols-outlined mr-2">domain</span>
                <span class="font-label-lg text-label-lg">Data Gedung</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="aset_kelola.php">
                <span class="material-symbols-outlined mr-2">inventory_2</span>
                <span class="font-label-lg text-label-lg">Data Aset</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="penyewa_kelola.php">
                <span class="material-symbols-outlined mr-2">groups</span>
                <span class="font-label-lg text-label-lg">Data Penyewa</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="laporan.php">
                <span class="material-symbols-outlined mr-2">analytics</span>
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
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-sm">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary font-bold">Jadwal Terpadu Penyewaan</h1>
                    <p class="text-xs text-on-surface-variant">Pantau seluruh agenda pemakaian fasilitas gedung &amp; aula yang telah disetujui (DP/Lunas).</p>
                </div>
                
                <!-- Filter Dropdown -->
                <form method="GET" action="jadwal.php" class="flex gap-xs items-center bg-white p-1 rounded-xl border border-outline-variant shadow-sm w-full md:w-auto">
                    <span class="material-symbols-outlined text-primary text-xs pl-2">filter_list</span>
                    <select name="id_gedung" onchange="this.form.submit()" class="py-1 px-md bg-transparent border-none rounded-lg text-xs focus:ring-0 text-on-surface font-semibold">
                        <option value="">Semua Gedung</option>
                        <?php foreach ($gedung_list as $g): ?>
                            <option value="<?= $g['id_gedung'] ?>" <?= $selected_gedung == $g['id_gedung'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['nama_gedung']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Calendar Card -->
            <div class="bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                <div id="calendar" class="w-full"></div>
            </div>
        </main>

    </div>
</div>

<!-- Details Modal -->
<div id="eventModal" class="fixed inset-0 z-50 flex items-center justify-center p-md bg-black/60 opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-white rounded-2xl overflow-hidden shadow-2xl max-w-sm w-full border border-outline-variant transform scale-95 transition-transform duration-300">
        <!-- Header -->
        <div class="bg-primary p-md text-white flex justify-between items-center">
            <h5 class="text-xs font-bold flex items-center gap-xs"><span class="material-symbols-outlined text-warning-amber text-sm">event_available</span> Detail Kegiatan</h5>
            <button onclick="closeModal()" class="material-symbols-outlined text-white/80 hover:text-white cursor-pointer text-sm">close</button>
        </div>
        <!-- Body -->
        <div class="p-md space-y-sm text-xs text-primary">
            <div>
                <span class="text-on-surface-variant text-[10px] block">Nama Kegiatan:</span>
                <strong class="text-xs text-primary font-bold block" id="modalTitle"></strong>
            </div>
            <div>
                <span class="text-on-surface-variant text-[10px] block">Penyewa / Instansi:</span>
                <strong class="text-primary font-semibold block" id="modalPenyewa"></strong>
            </div>
            <div>
                <span class="text-on-surface-variant text-[10px] block">Kode Transaksi:</span>
                <strong class="text-primary font-semibold" id="modalKode"></strong>
            </div>
            <div>
                <span class="text-on-surface-variant text-[10px] block">Gedung / Aula:</span>
                <strong class="text-primary font-semibold" id="modalGedung"></strong>
            </div>
            <div>
                <span class="text-on-surface-variant text-[10px] block">Status Sewa:</span>
                <span id="modalStatus"></span>
            </div>
            <div class="grid grid-cols-2 gap-sm pt-xs">
                <div class="bg-surface p-2 rounded-lg border border-outline-variant">
                    <span class="text-on-surface-variant text-[9px] block">Mulai</span>
                    <strong class="text-primary font-bold text-[10px]" id="modalMulai"></strong>
                </div>
                <div class="bg-surface p-2 rounded-lg border border-outline-variant">
                    <span class="text-on-surface-variant text-[9px] block">Selesai</span>
                    <strong class="text-primary font-bold text-[10px]" id="modalSelesai"></strong>
                </div>
            </div>
        </div>
        <!-- Footer -->
        <div class="p-md border-t border-outline-variant bg-surface flex justify-end">
            <button onclick="closeModal()" class="bg-primary text-on-primary px-md py-1 rounded-lg font-bold text-[10px] hover:shadow-md transition-all active:scale-95">Tutup</button>
        </div>
    </div>
</div>

<script>
var eventModal = document.getElementById('eventModal');

function closeModal() {
    eventModal.classList.add('opacity-0', 'pointer-events-none');
    eventModal.children[0].classList.add('scale-95');
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        locale: 'id',
        events: <?= json_encode($events) ?>,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan'
        },
        eventClick: function(info) {
            document.getElementById('modalTitle').innerText = info.event.extendedProps.nama_kegiatan;
            document.getElementById('modalPenyewa').innerText = info.event.extendedProps.nama_penyewa;
            document.getElementById('modalKode').innerText = info.event.extendedProps.kode_transaksi;
            document.getElementById('modalGedung').innerText = info.event.extendedProps.nama_gedung;
            document.getElementById('modalMulai').innerText = info.event.extendedProps.tanggal_mulai;
            document.getElementById('modalSelesai').innerText = info.event.extendedProps.tanggal_selesai;
            
            var status = info.event.extendedProps.status;
            var badgeClass = 'bg-[#f59e0b] text-white';
            if (status === 'Lunas') {
                badgeClass = 'bg-[#10b981] text-white';
            } else if (status === 'DP' || status === 'Cicilan') {
                badgeClass = 'bg-[#2563eb] text-white';
            }
            document.getElementById('modalStatus').innerHTML = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ' + badgeClass + '">' + status + '</span>';
            
            // Show modal with animation
            eventModal.classList.remove('opacity-0', 'pointer-events-none');
            eventModal.children[0].classList.remove('scale-95');
        }
    });
    calendar.render();
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
