<?php
// SIIPAK - Kalender Ketersediaan Gedung & Aset (Peta Ruangan)
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

$role = get_user_role();
if ($role === 'penyewa') {
    require_once 'includes/profile_handler.php';
    $id_penyewa = $_SESSION['user_id'] ?? 0;
    $stmt_sync = $pdo->prepare("SELECT nama FROM penyewa WHERE id_penyewa = :id");
    $stmt_sync->execute([':id' => $id_penyewa]);
    $renter_db = $stmt_sync->fetch();
    if ($renter_db) {
        $_SESSION['user_name'] = $renter_db['nama'];
    }
}
$user_name = $_SESSION['user_name'] ?? '';

$selected_gedung = $_GET['id_gedung'] ?? '';
$gedung_list = $pdo->query("SELECT * FROM gedung WHERE status = 'Tersedia' ORDER BY id_gedung ASC")->fetchAll();

// User Initials for Avatar (if penyewa)
$initials = 'U';
if (!empty($user_name)) {
    $names = explode(' ', $user_name);
    $initials = '';
    if (count($names) > 0) {
        $initials .= strtoupper(substr($names[0], 0, 1));
        if (count($names) > 1) {
            $initials .= strtoupper(substr($names[1], 0, 1));
        }
    }
}
?>
<?php if ($role === 'penyewa'): ?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIPAK - Kalender Jadwal Aset</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
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
                <span class="material-symbols-outlined mr-2">dashboard</span>
                <span class="font-label-lg text-label-lg font-semibold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="booking.php">
                <span class="material-symbols-outlined mr-2">add_box</span>
                <span class="font-label-lg text-label-lg font-semibold">Booking Baru</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="riwayat_booking.php">
                <span class="material-symbols-outlined mr-2">receipt_long</span>
                <span class="font-label-lg text-label-lg font-semibold">Transaksi Saya</span>
            </a>
            <!-- Active State: Jadwal Aset -->
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="kalender.php">
                <span class="material-symbols-outlined mr-2" style="font-variation-settings: 'FILL' 1;">calendar_month</span>
                <span class="font-label-lg text-label-lg font-bold">Jadwal Aset</span>
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
                <h2 class="text-base font-bold text-primary leading-tight"><?= htmlspecialchars($user_name) ?></h2>
            </div>
            <div class="flex items-center gap-xs">
                <div class="bg-surface-container-low px-2.5 py-1 rounded-full border border-outline-variant flex items-center gap-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-success-green inline-block shadow-sm"></span>
                    <span class="text-[10px] font-semibold text-primary">Role: <b>Penyewa</b></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-lg space-y-md">
            <!-- Top Header Card -->
            <div class="bg-white rounded-2xl p-sm border border-outline-variant shadow-soft">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-sm">
                    <div class="space-y-base">
                        <h2 class="text-sm font-bold text-primary flex items-center gap-xs">
                            <span class="material-symbols-outlined text-warning-amber text-sm">calendar_month</span> Kalender Jadwal &amp; Peta Ruangan
                        </h2>
                        <p class="text-on-surface-variant text-[11px]">Pantau ketersediaan jadwal gedung Politeknik Aceh secara real-time demi mencegah double booking.</p>
                    </div>
                    <div>
                        <form method="GET" action="kalender.php" class="flex gap-xs items-center">
                            <label for="id_gedung" class="text-[11px] font-semibold text-primary text-nowrap self-center">Filter Ruangan:</label>
                            <select name="id_gedung" id="id_gedung" class="bg-surface border border-outline rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-primary/20 text-on-surface" onchange="this.form.submit()">
                                <option value="">-- Semua Gedung --</option>
                                <?php foreach ($gedung_list as $g): ?>
                                    <option value="<?= $g['id_gedung'] ?>" <?= $selected_gedung == $g['id_gedung'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['nama_gedung']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($selected_gedung)): ?>
                                <a href="kalender.php" class="border border-outline hover:bg-surface-container-low px-2 py-1 rounded-lg text-[10px] font-bold text-primary text-center flex items-center justify-center gap-xs decoration-none">
                                    <span class="material-symbols-outlined text-xs">close</span> Reset
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Grid Layout (Legend & Callout + Full Calendar) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-md items-start">
                <!-- Left Sidebars: Legend & Callout (3 cols) -->
                <div class="lg:col-span-3 space-y-md">
                    <!-- Color Legend Card -->
                    <div class="bg-white rounded-2xl p-sm border border-outline-variant shadow-soft">
                        <h4 class="text-[11px] font-bold text-primary mb-sm flex items-center gap-xs">
                            <span class="material-symbols-outlined text-xs text-warning-amber">color_lens</span> Keterangan Status
                        </h4>
                        <ul class="space-y-sm text-[11px]">
                            <li class="flex items-center gap-xs">
                                <span class="w-3 h-3 rounded-full bg-[#10b981] flex-shrink-0"></span>
                                <div class="flex flex-col">
                                    <strong class="font-semibold text-primary">Lunas / Terkunci</strong>
                                    <span class="text-on-surface-variant text-[10px]">Jadwal resmi aman terkunci.</span>
                                </div>
                            </li>
                            <li class="flex items-center gap-xs">
                                <span class="w-3 h-3 rounded-full bg-[#2563eb] flex-shrink-0"></span>
                                <div class="flex flex-col">
                                    <strong class="font-semibold text-primary">DP / Cicilan Valid</strong>
                                    <span class="text-on-surface-variant text-[10px]">Pembayaran termin disetujui.</span>
                                </div>
                            </li>
                            <li class="flex items-center gap-xs">
                                <span class="w-3 h-3 rounded-full bg-[#f59e0b] flex-shrink-0"></span>
                                <div class="flex flex-col">
                                    <strong class="font-semibold text-primary">Menunggu Pembayaran</strong>
                                    <span class="text-on-surface-variant text-[10px]">Booking tersimpan sementara.</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Callout Card -->
                    <div class="bg-gradient-to-br from-primary to-[#002068] rounded-2xl p-sm border border-primary/20 text-white space-y-sm shadow-soft">
                        <span class="material-symbols-outlined text-warning-amber text-2xl">domain_add</span>
                        <div>
                            <h4 class="text-xs font-bold text-white mb-0.5">Ingin Sewa?</h4>
                            <p class="text-primary-fixed-dim text-[11px] leading-relaxed">
                                Cari tanggal kosong pada kalender di samping, lalu buat permohonan sewa baru.
                            </p>
                        </div>
                        <a href="booking.php<?= !empty($selected_gedung) ? '?id_gedung='.$selected_gedung : '' ?>" class="block w-full bg-warning-amber text-primary text-center py-1 rounded-lg font-bold text-[11px] hover:shadow-md transition-all decoration-none active:scale-95">
                            Booking Sekarang
                        </a>
                    </div>
                </div>

                <!-- Calendar Container (9 cols) -->
                <div class="lg:col-span-9">
                    <div class="bg-white rounded-2xl p-sm border border-outline-variant shadow-soft">
                        <div id="calendar"></div>
                    </div>
                </div>
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

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
    var eventModal = document.getElementById('eventModal');
    
    function closeModal() {
        eventModal.classList.add('opacity-0', 'pointer-events-none');
        eventModal.children[0].classList.add('scale-95');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var selectedGedung = '<?= htmlspecialchars($selected_gedung) ?>';
        
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            dayMaxEvents: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            locale: 'id',
            events: 'api/events.php' + (selectedGedung ? '?id_gedung=' + selectedGedung : ''),
            eventClick: function(info) {
                document.getElementById('modalTitle').innerText = info.event.title;
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

<?php else: ?>

<?php
$page_title = "Kalender Jadwal Aset - SIPAK Politeknik Aceh";
require_once 'includes/header_tailwind.php';
echo '<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">';
require_once 'includes/navbar_tailwind.php';
?>
<style>
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

<main class="py-md text-xs">
    <div class="container mx-auto px-md">
        <!-- Dashboard Top Header Card -->
        <div class="bg-white rounded-2xl p-sm border border-outline-variant shadow-soft mb-md">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-sm">
                <div class="space-y-base">
                    <h2 class="text-sm font-bold text-primary flex items-center gap-xs"><span class="material-symbols-outlined text-warning-amber text-sm">calendar_month</span> Kalender Jadwal &amp; Peta Ruangan</h2>
                    <p class="text-on-surface-variant text-[11px]">Pantau ketersediaan jadwal gedung Politeknik Aceh secara real-time demi mencegah double booking.</p>
                </div>
                <div>
                    <form method="GET" action="kalender.php" class="flex gap-xs items-center">
                        <label for="id_gedung" class="text-[11px] font-semibold text-primary text-nowrap self-center">Filter Ruangan:</label>
                        <select name="id_gedung" id="id_gedung" class="bg-surface border border-outline rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-primary-container text-on-surface" onchange="this.form.submit()">
                            <option value="">-- Semua Gedung --</option>
                            <?php foreach ($gedung_list as $g): ?>
                                <option value="<?= $g['id_gedung'] ?>" <?= $selected_gedung == $g['id_gedung'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama_gedung']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($selected_gedung)): ?>
                            <a href="kalender.php" class="border border-outline hover:bg-surface-container-low px-2 py-1 rounded-lg text-[10px] font-bold text-primary text-center flex items-center justify-center gap-xs decoration-none">
                                <span class="material-symbols-outlined text-xs">close</span> Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-md items-start">
            <!-- Left Sidebars: Legend & Callout -->
            <div class="lg:col-span-3 space-y-md">
                <!-- Color Legend Card -->
                <div class="bg-white rounded-2xl p-sm border border-outline-variant shadow-soft">
                    <h4 class="text-[11px] font-bold text-primary mb-sm flex items-center gap-xs"><span class="material-symbols-outlined text-xs text-warning-amber">color_lens</span> Keterangan Status</h4>
                    <ul class="space-y-sm text-[11px]">
                        <li class="flex items-center gap-xs">
                            <span class="w-3 h-3 rounded-full bg-[#10b981] flex-shrink-0"></span>
                            <div class="flex flex-col">
                                <strong class="font-semibold text-primary">Lunas / Terkunci</strong>
                                <span class="text-on-surface-variant text-[10px]">Jadwal resmi aman terkunci.</span>
                            </div>
                        </li>
                        <li class="flex items-center gap-xs">
                            <span class="w-3 h-3 rounded-full bg-[#2563eb] flex-shrink-0"></span>
                            <div class="flex flex-col">
                                <strong class="font-semibold text-primary">DP / Cicilan Valid</strong>
                                <span class="text-on-surface-variant text-[10px]">Pembayaran termin disetujui.</span>
                            </div>
                        </li>
                        <li class="flex items-center gap-xs">
                            <span class="w-3 h-3 rounded-full bg-[#f59e0b] flex-shrink-0"></span>
                            <div class="flex flex-col">
                                <strong class="font-semibold text-primary">Menunggu Pembayaran</strong>
                                <span class="text-on-surface-variant text-[10px]">Booking tersimpan sementara.</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Callout Card -->
                <div class="bg-primary-container rounded-2xl p-sm border border-primary-fixed/30 text-white space-y-sm shadow-soft">
                    <span class="material-symbols-outlined text-warning-amber text-2xl">domain_add</span>
                    <div>
                        <h4 class="text-xs font-bold text-white mb-0.5">Ingin Sewa?</h4>
                        <p class="text-on-primary-container text-[11px] leading-relaxed">
                            Cari tanggal kosong pada kalender di samping, lalu klik tombol di bawah untuk membuat permohonan sewa.
                        </p>
                    </div>
                    <a href="booking.php<?= !empty($selected_gedung) ? '?id_gedung='.$selected_gedung : '' ?>" class="block w-full bg-warning-amber text-primary text-center py-1 rounded-lg font-bold text-[11px] hover:shadow-md transition-all decoration-none active:scale-95">
                        Booking Sekarang
                    </a>
                </div>
            </div>

            <!-- Calendar Container -->
            <div class="lg:col-span-9">
                <div class="bg-white rounded-2xl p-sm border border-outline-variant shadow-soft">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</main>

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

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
    var eventModal = document.getElementById('eventModal');
    
    function closeModal() {
        eventModal.classList.add('opacity-0', 'pointer-events-none');
        eventModal.children[0].classList.add('scale-95');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var selectedGedung = '<?= htmlspecialchars($selected_gedung) ?>';
        
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            dayMaxEvents: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth'
            },
            locale: 'id',
            events: 'api/events.php' + (selectedGedung ? '?id_gedung=' + selectedGedung : ''),
            eventClick: function(info) {
                document.getElementById('modalTitle').innerText = info.event.title;
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
</script>

<?php require_once 'includes/footer_tailwind.php'; ?>
<?php include 'includes/profile_modal.php'; ?>
<?php endif; ?>
