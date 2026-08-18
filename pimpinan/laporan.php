<?php
// SIIPAK - Laporan Rekapitulasi Keuangan & Aset
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/functions.php';

check_pimpinan();

$user_name = $_SESSION['user_name'] ?? 'Pengguna';
$role = get_user_role();

// Filter Tahun & Bulan
$selected_year  = (int)($_GET['tahun'] ?? date('Y'));
$selected_month = $_GET['bulan'] ?? '';

// Query Rekapitulasi Bulanan / Tahunan
$sql = "SELECT t.*, g.nama_gedung, py.nama AS nama_penyewa, py.instansi,
        (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran p WHERE p.id_transaksi = t.id_transaksi AND p.status_validasi = 'Valid') AS total_terbayar
        FROM transaksi t
        JOIN gedung g ON t.id_gedung = g.id_gedung
        JOIN penyewa py ON t.id_penyewa = py.id_penyewa
        WHERE t.status_transaksi IN ('DP', 'Cicilan', 'Lunas')
        AND YEAR(t.tanggal_mulai) = :year";

$params = [':year' => $selected_year];

if (!empty($selected_month)) {
    $sql .= " AND MONTH(t.tanggal_mulai) = :month";
    $params[':month'] = (int)$selected_month;
}

$sql .= " ORDER BY t.tanggal_mulai ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$report_list = $stmt->fetchAll();

// Calculate Summary Totals
$total_kegiatan = count($report_list);
$total_pendapatan = 0;
foreach ($report_list as $r) {
    $total_pendapatan += $r['total_terbayar'];
}

// Data Pendapatan Per Bulan untuk Chart.js
$monthly_income = array_fill(1, 12, 0);
$sql_chart = "SELECT MONTH(p.tanggal_bayar) AS bulan, SUM(p.jumlah_bayar) AS total 
              FROM pembayaran p 
              WHERE p.status_validasi = 'Valid' AND YEAR(p.tanggal_bayar) = :year
              GROUP BY MONTH(p.tanggal_bayar)";
$stmt_chart = $pdo->prepare($sql_chart);
$stmt_chart->execute([':year' => $selected_year]);
while ($c = $stmt_chart->fetch()) {
    $monthly_income[(int)$c['bulan']] = (float)$c['total'];
}

// Sidebar count for Admin (pimpinan doesn't show pending payments)
$pending_val = 0;

// User Initials for Avatar
$names = explode(' ', $user_name);
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
    <title>Laporan Rekapitulasi | SIPAK Politeknik Aceh</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        @media print {
            .no-print {
                display: none !important;
            }
            body, .flex-1, main {
                background: white !important;
                color: black !important;
                overflow: visible !important;
                height: auto !important;
                }
            aside {
                display: none !important;
            }
            .print-layout {
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-table {
                border-collapse: collapse !important;
                width: 100% !important;
                margin-top: 15px !important;
            }
            .print-table th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                border-bottom: 2px solid #cbd5e1 !important;
                padding: 8px 10px !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }
            .print-table th.text-center, .print-table td.text-center {
                text-align: center !important;
            }
            .print-table th.text-right, .print-table td.text-right {
                text-align: right !important;
            }
            .print-table td {
                border-bottom: 1px solid #e2e8f0 !important;
                padding: 8px 10px !important;
                font-size: 10px !important;
                color: #334155 !important;
            }
            .print-table tfoot td {
                border-top: 2px solid #cbd5e1 !important;
                border-bottom: none !important;
                background-color: #f8fafc !important;
                font-weight: 700 !important;
                font-size: 11px !important;
            }
        }
    </style>
</head>
<body class="bg-surface-blue text-on-surface font-body-md overflow-hidden text-xs md:text-sm">
<!-- Main Wrapper -->
<div class="flex h-screen w-full">
    <!-- SideNavBar (Hidden during print) -->
    <aside class="no-print hidden md:flex flex-col h-full py-md bg-primary dark:bg-on-primary w-56 left-0 top-0 border-r border-outline-variant dark:border-outline shadow-soft z-50">
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
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="index.php">
                <span class="font-label-lg text-label-lg">Dashboard</span>
            </a>
            <a class="flex items-center px-md py-2 active-nav rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="laporan.php">
                <span class="font-label-lg text-label-lg font-bold">Laporan Rekap</span>
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
    <div class="flex-1 flex flex-col min-w-0 bg-surface-blue relative overflow-y-auto print-layout">
        <!-- TopNavBar (Hidden during print) -->
        <header class="no-print sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-outline-variant flex items-center justify-between px-lg py-2 min-h-[50px] shadow-sm">
            <div class="flex flex-col justify-center">
                <p class="text-[9px] font-medium text-on-surface-variant/80 uppercase tracking-wider">Selamat datang,</p>
                <h2 class="text-base font-bold text-primary leading-tight"><?= htmlspecialchars($user_name) ?></h2>
            </div>
            <div class="flex items-center gap-xs">
                <div class="bg-surface-container-low px-2.5 py-1 rounded-full border border-outline-variant flex items-center gap-1.5 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-error-red inline-block shadow-sm"></span>
                    <span class="text-[10px] font-semibold text-primary">Role: <b class="capitalize"><?= $role ?></b></span>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="p-lg space-y-md">
            <!-- Kop Surat Politeknik Aceh (Only visible during print) -->
            <div class="hidden print:block text-center mb-lg">
                <h1 class="text-xl font-extrabold uppercase tracking-wider text-slate-900">POLITEKNIK ACEH</h1>
                <h2 class="text-sm font-bold uppercase mt-0.5 text-slate-800">LAPORAN REKAPITULASI PENYEWAAN GEDUNG &amp; ASET KAMPUS</h2>
                <p class="text-[10px] text-slate-600 mt-1">Jl. Sultan Malikul Saleh, Lhong Raya, Banda Aceh | Telepon: (0651) 7555566 | Email: info@politeknikaceh.ac.id</p>
                <p class="text-[10px] text-slate-700 font-bold mt-1 font-mono">Periode Rekap: <?= !empty($selected_month) ? 'Bulan ' . $selected_month . ' ' : '' ?>Tahun <?= $selected_year ?></p>
                <div class="border-t-2 border-slate-900 mt-2"></div>
                <div class="border-t border-slate-900 mt-0.5 mb-md"></div>
            </div>

            <!-- Header Section (Hidden during print) -->
            <div class="no-print flex flex-col md:flex-row justify-between items-start md:items-center gap-sm">
                <div class="space-y-0.5">
                    <h1 class="font-display-md text-display-md text-primary font-bold">Laporan Rekapitulasi Keuangan</h1>
                    <p class="text-xs text-on-surface-variant">Dokumen pertanggungjawaban dana bulanan &amp; aktivitas okupansi sewa gedung.</p>
                </div>
                
                <button onclick="window.print()" class="h-9 bg-success-green hover:bg-success-green/90 text-white font-bold text-xs px-md rounded-lg flex items-center justify-center gap-xs shadow-md active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-sm">print</span> Cetak / Ekspor PDF
                </button>
            </div>

            <!-- Filter Card (Hidden during print) -->
            <div class="no-print bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                <form method="GET" action="laporan.php" class="grid grid-cols-1 md:grid-cols-3 gap-sm items-end text-xs">
                    <div class="space-y-base">
                        <label for="tahun" class="font-semibold text-primary block">Pilih Tahun</label>
                        <select name="tahun" id="tahun" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface">
                            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="space-y-base">
                        <label for="bulan" class="font-semibold text-primary block">Pilih Bulan (Opsional)</label>
                        <select name="bulan" id="bulan" class="w-full py-1.5 px-md rounded-lg border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-surface-container-lowest text-xs text-on-surface">
                            <option value="">-- Semua Bulan --</option>
                            <?php 
                            $nama_bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                            foreach ($nama_bulan as $m_num => $m_name): ?>
                                <option value="<?= $m_num ?>" <?= $selected_month == $m_num ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full h-9 bg-primary hover:bg-primary-container text-white font-bold text-xs rounded-lg flex items-center justify-center gap-xs shadow-md active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-sm">filter_alt</span> Tampilkan Laporan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Financial Summary Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-success-green/10 flex items-center justify-center text-success-green">
                        <span class="material-symbols-outlined text-xl">event_available</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Aktivitas Terlaksana</p>
                        <h3 class="text-base text-success-green font-black mt-0.5"><?= $total_kegiatan ?> Acara</h3>
                    </div>
                </div>
                <div class="bg-white p-md rounded-2xl border border-outline-variant shadow-soft flex items-center gap-md bento-card">
                    <div class="w-10 h-10 rounded-xl bg-[#2563eb]/10 flex items-center justify-center text-[#2563eb]">
                        <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                    </div>
                    <div>
                        <p class="text-[10px] text-on-surface-variant uppercase font-semibold tracking-wider">Dana Masuk Terverifikasi</p>
                        <h3 class="text-base text-primary font-black mt-0.5"><?= format_rupiah($total_pendapatan) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Monthly Income Bar Chart Card (Hidden during print) -->
            <div class="no-print bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                <div class="flex items-center gap-xs mb-md">
                    <span class="material-symbols-outlined text-primary text-sm">bar_chart</span>
                    <h4 class="text-xs text-primary font-bold">Grafik Tren Pendapatan Sewa Gedung Tahun <?= $selected_year ?></h4>
                </div>
                <div class="w-full relative h-[180px]">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl border border-outline-variant shadow-soft overflow-hidden">
                <div class="px-lg py-2.5 bg-surface border-b border-outline-variant">
                    <h4 class="text-xs text-primary font-bold">Rincian Rekapitulasi Kegiatan &amp; Transaksi</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse align-middle print-table">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-md py-2 text-center w-12">No</th>
                                <th class="px-md py-2">Kode Transaksi</th>
                                <th class="px-md py-2">Penyewa / Instansi</th>
                                <th class="px-md py-2">Gedung Dipakai</th>
                                <th class="px-md py-2">Nama Kegiatan</th>
                                <th class="px-md py-2">Tanggal Pelaksanaan</th>
                                <th class="px-md py-2 text-right">Total Sewa</th>
                                <th class="px-md py-2 text-right">Dana Masuk (Valid)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60 text-xs text-on-surface">
                            <?php if (empty($report_list)): ?>
                                <tr>
                                    <td colspan="8" class="px-md py-6 text-center text-on-surface-variant">Tidak ada data transaksi terverifikasi pada periode yang dipilih.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($report_list as $r): ?>
                                    <tr class="hover:bg-surface-container-lowest transition-colors">
                                        <td class="px-md py-2 text-center font-bold text-on-surface-variant"><?= $no++ ?></td>
                                        <td class="px-md py-2 font-bold text-primary"><?= htmlspecialchars($r['kode_transaksi']) ?></td>
                                        <td class="px-md py-2">
                                            <strong class="text-on-surface block font-bold"><?= htmlspecialchars($r['nama_penyewa']) ?></strong>
                                            <span class="text-[10px] text-on-surface-variant block mt-0.5"><?= htmlspecialchars($r['instansi']) ?></span>
                                        </td>
                                        <td class="px-md py-2 font-semibold text-on-surface-variant"><?= htmlspecialchars($r['nama_gedung']) ?></td>
                                        <td class="px-md py-2 text-[11px] italic">"<?= htmlspecialchars($r['nama_kegiatan']) ?>"</td>
                                        <td class="px-md py-2 text-[10px]"><?= format_tanggal($r['tanggal_mulai']) ?> s/d <?= format_tanggal($r['tanggal_selesai']) ?></td>
                                        <td class="px-md py-2 text-right font-semibold text-on-surface"><?= format_rupiah($r['total_pembayaran']) ?></td>
                                        <td class="px-md py-2 text-right font-bold text-success-green"><?= format_rupiah($r['total_terbayar']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-surface-container-low font-bold text-primary border-t border-outline-variant">
                            <tr>
                                <td colspan="7" class="px-md py-3 text-right font-bold text-xs uppercase">Total Keseluruhan Dana Masuk:</td>
                                <td class="px-md py-3 text-right font-black text-sm text-success-green"><?= format_rupiah($total_pendapatan) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Signatures (Only displayed during print) -->
            <div class="hidden print:block mt-12 w-full">
                <div class="grid grid-cols-2 text-center text-xs">
                    <div>
                        <p class="mb-20 text-slate-700">Pengelola Gedung &amp; Aset,</p>
                        <p class="font-bold text-slate-900 underline">___________________________</p>
                        <span class="text-[10px] text-slate-500 block mt-1">Staf Aset Politeknik Aceh</span>
                    </div>
                    <div>
                        <p class="mb-20 text-slate-700">Mengetahui,<br/>Direktur Poltek Aceh</p>
                        <p class="font-bold text-slate-900 underline">Direktur Poltek Aceh</p>
                        <span class="text-[10px] text-slate-500 block mt-1">Pimpinan Kampus</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('incomeChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?= json_encode(array_values($monthly_income)) ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderColor: '#2563eb',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
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
