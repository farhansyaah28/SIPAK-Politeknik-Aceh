<?php
// SIIPAK - Dashboard Pimpinan
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_pimpinan();

$user_name = $_SESSION['user_name'] ?? 'Pimpinan';

// Fetch totals
$total_kegiatan = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_transaksi IN ('DP', 'Cicilan', 'Lunas')")->fetchColumn();
$total_pendapatan = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran WHERE status_validasi = 'Valid'")->fetchColumn();
$total_gedung = $pdo->query("SELECT COUNT(*) FROM gedung WHERE status = 'Tersedia'")->fetchColumn();

// Fetch building utilization stats
$stmt_usage = $pdo->query("SELECT g.nama_gedung, COUNT(t.id_transaksi) AS frekuensi
                           FROM gedung g
                           LEFT JOIN transaksi t ON g.id_gedung = t.id_gedung AND t.status_transaksi IN ('DP', 'Cicilan', 'Lunas')
                           GROUP BY g.id_gedung
                           ORDER BY frekuensi DESC");
$usage_list = $stmt_usage->fetchAll();

// User Initials for Avatar
$names = explode(' ', $user_name);
$initials = '';
if (count($names) > 0) {
    $initials .= strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
} else {
    $initials = 'P';
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard Pimpinan | SIPAK Politeknik Aceh</title>
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
    <!-- SideNavBar - Pimpinan Dashboard Nav -->
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
            <div class="px-md py-xs text-primary-fixed-dim uppercase tracking-wider text-[9px] font-bold opacity-60">Pimpinan Menu</div>
            
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="index.php">
                <span class="font-label-lg text-label-lg font-bold">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="laporan.php">
                <span class="font-label-lg text-label-lg font-semibold">Laporan Rekap</span>
            </a>
        </nav>

        <!-- User Profile & Logout at bottom -->
        <div class="mt-auto px-md space-y-0.5 border-t border-outline/10 pt-md">
            <div class="flex items-center gap-xs px-xs py-1 mb-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-xs shadow-md">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <p class="text-xs font-semibold text-white truncate leading-none"><?= htmlspecialchars($user_name) ?></p>
                    <p class="text-[9px] text-primary-fixed-dim opacity-70 mt-0.5 truncate font-medium">Pimpinan</p>
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
                <h2 class="text-base font-bold text-primary leading-tight"><?= htmlspecialchars($user_name) ?></h2>
            </div>
            <div class="flex items-center gap-xs">
                <div class="bg-surface-container-low px-2.5 py-1 rounded-full border border-outline-variant flex items-center gap-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-error-red inline-block shadow-sm"></span>
                    <span class="text-[10px] font-semibold text-primary">Role: <b>Pimpinan Kampus</b></span>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="p-lg space-y-md">
            <!-- Header Section -->
            <div class="space-y-0.5">
                <div class="flex items-center gap-xs text-primary">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">security</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Akses Khusus Pimpinan (Read-Only)</span>
                </div>
                <h1 class="font-display-md text-display-md text-primary font-bold">Eksekutif Dashboard Pimpinan</h1>
                <p class="text-xs text-on-surface-variant">Transparansi frekuensi okupansi penggunaan gedung &amp; aliran dana masuk kampus Politeknik Aceh.</p>
            </div>

            <!-- Summary Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <!-- Stat Card 1 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-primary-container/5 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Total Pendapatan Valid</p>
                        <h3 class="text-base text-primary font-black mt-0.5"><?= format_rupiah($total_pendapatan) ?></h3>
                    </div>
                </div>
                <!-- Stat Card 2 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-success-green/10 flex items-center justify-center text-success-green">
                        <span class="material-symbols-outlined text-xl">calendar_month</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Kegiatan Terlaksana</p>
                        <h3 class="text-base text-success-green font-black mt-0.5"><?= $total_kegiatan ?> Acara</h3>
                    </div>
                </div>
                <!-- Stat Card 3 -->
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-warning-amber/10 flex items-center justify-center text-secondary">
                        <span class="material-symbols-outlined text-xl">domain</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Gedung Siap Sewa</p>
                        <h3 class="text-base text-warning-amber font-black mt-0.5"><?= $total_gedung ?> Fasilitas</h3>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid Section -->
            <div class="grid grid-cols-12 gap-md">
                <!-- Column Left: Building Usage Utilization -->
                <div class="col-span-12 lg:col-span-7 bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden bento-card">
                    <div class="px-lg py-3 bg-surface border-b border-outline-variant flex items-center gap-xs">
                        <span class="material-symbols-outlined text-primary text-sm">bar_chart</span>
                        <h4 class="text-xs text-primary font-bold">Frekuensi Pemakaian Gedung Kampus</h4>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse align-middle">
                            <thead>
                                <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                    <th class="px-lg py-2">Nama Gedung</th>
                                    <th class="px-lg py-2 text-center w-36">Total Pemakaian</th>
                                    <th class="px-lg py-2 pr-lg">Tingkat Okupansi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                                <?php foreach ($usage_list as $u): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                        <td class="px-lg py-2.5 font-bold text-on-surface"><?= htmlspecialchars($u['nama_gedung']) ?></td>
                                        <td class="px-lg py-2.5 text-center font-bold text-primary"><?= $u['frekuensi'] ?> Kali</td>
                                        <td class="px-lg py-2.5 pr-lg">
                                            <div class="w-full bg-surface-container-high rounded-full h-2">
                                                <div class="bg-primary h-2 rounded-full transition-all" style="width: <?= min(100, $u['frekuensi'] * 20) ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Column Right: Executive Privileges Information & Quick Links -->
                <div class="col-span-12 lg:col-span-5 bg-white rounded-2xl p-md border border-outline-variant shadow-soft flex flex-col justify-between bento-card">
                    <div class="space-y-sm">
                        <div class="flex items-center gap-xs text-primary">
                            <span class="material-symbols-outlined text-sm">info</span>
                            <h4 class="text-xs font-bold">Akses Pertanggungjawaban</h4>
                        </div>
                        <p class="text-on-surface-variant leading-relaxed text-xs">
                            Sebagai Pimpinan Politeknik Aceh, Anda memiliki hak akses <strong>read-only</strong> untuk memantau rekapitulasi penggunaan fasilitas dan aliran dana masuk tanpa mengubah data operasional.
                        </p>
                    </div>

                    <div class="space-y-sm pt-md">
                        <a href="../admin/laporan.php" class="w-full h-9 bg-warning-amber hover:bg-warning-amber/90 text-primary font-bold text-xs rounded-lg flex items-center justify-center gap-xs shadow-md active:scale-95 transition-all decoration-none">
                            <span class="material-symbols-outlined text-sm">print</span> Cetak Laporan Rekapitulasi
                        </a>
                        <a href="../kalender.php" class="w-full h-9 border border-primary text-primary hover:bg-surface-container-low font-bold text-xs rounded-lg flex items-center justify-center gap-xs active:scale-95 transition-all decoration-none">
                            <span class="material-symbols-outlined text-sm">calendar_today</span> Lihat Kalender Ketersediaan
                        </a>
                    </div>
                </div>
            </div>
        </main>

    </div>
</div>

<script>
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
