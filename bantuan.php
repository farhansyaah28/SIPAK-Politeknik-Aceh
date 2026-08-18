<?php
// SIIPAK - Pusat Bantuan & Panduan Penyewaan (Bantuan)
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
    <title>SIPAK - Bantuan &amp; Pengaturan</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
            <a class="flex items-center px-md py-2 text-primary-fixed-dim hover:bg-surface-container-low/10 hover:text-white rounded-r-lg mr-2 my-0.5 transition-all decoration-none" href="kalender.php">
                <span class="material-symbols-outlined mr-2">calendar_month</span>
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
            <!-- Header Title -->
            <div class="space-y-base">
                <span class="text-primary font-bold uppercase tracking-widest text-[10px]">Pusat Bantuan &amp; FAQ</span>
                <h2 class="font-display-md text-display-md text-primary">Panduan Penyewaan Gedung</h2>
                <p class="text-xs text-on-surface-variant">Temukan jawaban atas pertanyaan umum serta alur kerja prosedur penyewaan gedung dan aset di Politeknik Aceh.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-md">
                <!-- FAQ Section -->
                <div class="lg:col-span-8 space-y-md">
                    <h3 class="text-sm font-bold text-primary flex items-center gap-xs"><span class="material-symbols-outlined text-warning-amber text-sm">help_center</span> Pertanyaan Sering Diajukan (FAQ)</h3>
                    
                    <div class="space-y-sm bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                        <!-- FAQ 1 -->
                        <details class="group py-2 border-b border-outline-variant/60">
                            <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                                <span>Bagaimana cara mengetahui ketersediaan tanggal gedung?</span>
                                <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                            </summary>
                            <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                                Anda dapat masuk ke halaman <a href="kalender.php" class="text-primary font-semibold underline">Peta Ruangan / Kalender Jadwal</a> untuk melihat kalender secara real-time. Tanggal yang sudah terisi kegiatan lain akan ditandai dengan warna hijau (Lunas/Kunci) atau biru/kuning. Jika kosong, berarti tanggal tersebut tersedia.
                            </p>
                        </details>

                        <!-- FAQ 2 -->
                        <details class="group py-2 border-b border-outline-variant/60">
                            <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                                <span>Apakah pembayaran harus langsung lunas?</span>
                                <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                            </summary>
                            <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                                Tidak, sistem mendukung pembayaran bertahap (termin). Penyewa dapat membayar uang muka (DP) terlebih dahulu sesuai kesepakatan untuk mengamankan dan mengunci jadwal gedung, kemudian membayar cicilan tambahan hingga pelunasan dilakukan sebelum hari H pelaksanaan acara.
                            </p>
                        </details>

                        <!-- FAQ 3 -->
                        <details class="group py-2 border-b border-outline-variant/60">
                            <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                                <span>Bagaimana cara memvalidasi bukti pembayaran transfer saya?</span>
                                <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                            </summary>
                            <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                                Setelah melakukan transfer bank, login ke akun Anda dan buka <a href="riwayat_booking.php" class="text-primary font-semibold underline">Dashboard / Pemesanan Saya</a>. Pilih transaksi terkait, klik tombol "Unggah Bukti", lalu unggah berkas bukti transfer Anda. Admin Pengelola Kampus akan memverifikasi bukti tersebut dalam waktu maksimal 24 jam.
                            </p>
                        </details>

                        <!-- FAQ 4 -->
                        <details class="group py-2 border-b border-outline-variant/60">
                            <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                                <span>Bagaimana jika bukti transfer saya ditolak oleh Admin?</span>
                                <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                            </summary>
                            <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                                Apabila bukti ditolak (misalnya karena nominal tidak sesuai atau gambar buram), status transaksi akan berubah menjadi "Ditolak". Anda dapat mengunggah kembali foto/berkas bukti transfer yang benar melalui halaman rincian transaksi Anda.
                            </p>
                        </details>

                        <!-- FAQ 5 -->
                        <details class="group py-2">
                            <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                                <span>Apakah saya bisa membatalkan penyewaan?</span>
                                <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                            </summary>
                            <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                                Pembatalan penyewaan dapat diajukan dengan menghubungi Bagian Pengelola Aset Politeknik Aceh via telepon atau langsung datang ke bagian Sekretariat Pengelola Gedung Kampus.
                            </p>
                        </details>
                    </div>
                </div>

                <!-- Contact & Support Section -->
                <div class="lg:col-span-4 space-y-md">
                    <h3 class="text-sm font-bold text-primary flex items-center gap-xs"><span class="material-symbols-outlined text-warning-amber text-sm">support_agent</span> Kontak Pengelola</h3>

                    <div class="bg-gradient-to-br from-primary to-[#002068] text-white rounded-2xl p-md border border-primary/20 space-y-sm shadow-soft">
                        <div class="space-y-base">
                            <h4 class="text-xs font-bold text-white">Bagian Pengelola Gedung</h4>
                            <p class="text-primary-fixed-dim text-[11px]">Politeknik Aceh — Kampus Utama</p>
                        </div>

                        <div class="space-y-sm text-[11px]">
                            <div class="flex items-start gap-xs">
                                <span class="material-symbols-outlined text-warning-amber text-sm flex-shrink-0">location_on</span>
                                <div>
                                    <strong class="block font-bold text-white">Alamat Kampus:</strong>
                                    <span class="text-primary-fixed-dim leading-relaxed">Jl. Politeknik Aceh No.1, Pango Raya, Kec. Ulee Kareng, Kota Banda Aceh</span>
                                </div>
                            </div>

                            <div class="flex items-start gap-xs">
                                <span class="material-symbols-outlined text-warning-amber text-sm flex-shrink-0">call</span>
                                <div>
                                    <strong class="block font-bold text-white">Telepon / WhatsApp:</strong>
                                    <span class="text-primary-fixed-dim">+62 812-6900-1234 (Jam Kerja)</span>
                                </div>
                            </div>

                            <div class="flex items-start gap-xs">
                                <span class="material-symbols-outlined text-warning-amber text-sm flex-shrink-0">mail</span>
                                <div>
                                    <strong class="block font-bold text-white">Email Resmi:</strong>
                                    <span class="text-primary-fixed-dim">pengelola.gedung@politeknikaceh.ac.id</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-sm border-t border-white/20">
                            <a href="https://wa.me/6281269001234" target="_blank" class="w-full bg-warning-amber hover:bg-warning-amber/90 text-primary font-bold py-1.5 rounded-lg flex items-center justify-center gap-xs shadow-md transition-all decoration-none active:scale-95 text-xs">
                                <span class="material-symbols-outlined text-sm">chat</span> Hubungi via WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
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
</body>
</html>

<?php else: ?>

<?php
$page_title = "Pusat Bantuan - SIPAK Politeknik Aceh";
require_once 'includes/header_tailwind.php';
require_once 'includes/navbar_tailwind.php';
?>

<main class="py-md text-xs">
    <div class="container mx-auto px-md">
        <!-- Banner Title -->
        <div class="text-center space-y-base mb-md">
            <span class="text-primary font-bold uppercase tracking-widest text-[10px]">Pusat Bantuan &amp; FAQ</span>
            <h2 class="text-base font-bold text-primary">Panduan Penyewaan Gedung</h2>
            <p class="text-on-surface-variant max-w-xl mx-auto">Temukan jawaban atas pertanyaan umum serta alur kerja prosedur penyewaan gedung dan aset di Politeknik Aceh.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-md">
            <!-- FAQ Section -->
            <div class="lg:col-span-8 space-y-md">
                <h3 class="text-sm font-bold text-primary flex items-center gap-xs"><span class="material-symbols-outlined text-warning-amber text-sm">help_center</span> Pertanyaan Sering Diajukan (FAQ)</h3>
                
                <div class="space-y-sm bg-white rounded-2xl p-md border border-outline-variant shadow-soft">
                    <!-- FAQ 1 -->
                    <details class="group py-2 border-b border-outline-variant/60">
                        <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                            <span>Bagaimana cara mengetahui ketersediaan tanggal gedung?</span>
                            <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                        </summary>
                        <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                            Anda dapat masuk ke halaman <a href="kalender.php" class="text-primary font-semibold underline">Peta Ruangan / Kalender Jadwal</a> untuk melihat kalender secara real-time. Tanggal yang sudah terisi kegiatan lain akan ditandai dengan warna hijau (Lunas/Kunci) atau biru/kuning. Jika kosong, berarti tanggal tersebut tersedia.
                        </p>
                    </details>

                    <!-- FAQ 2 -->
                    <details class="group py-2 border-b border-outline-variant/60">
                        <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                            <span>Apakah pembayaran harus langsung lunas?</span>
                            <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                        </summary>
                        <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                            Tidak, sistem mendukung pembayaran bertahap (termin). Penyewa dapat membayar uang muka (DP) terlebih dahulu sesuai kesepakatan untuk mengamankan dan mengunci jadwal gedung, kemudian membayar cicilan tambahan hingga pelunasan dilakukan sebelum hari H pelaksanaan acara.
                        </p>
                    </details>

                    <!-- FAQ 3 -->
                    <details class="group py-2 border-b border-outline-variant/60">
                        <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                            <span>Bagaimana cara memvalidasi bukti pembayaran transfer saya?</span>
                            <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                        </summary>
                        <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                            Setelah melakukan transfer bank, login ke akun Anda dan buka <a href="riwayat_booking.php" class="text-primary font-semibold underline">Akun Saya / Pemesanan Saya</a>. Pilih transaksi terkait, klik tombol "Unggah Bukti", lalu unggah berkas bukti transfer Anda. Admin Pengelola Kampus akan memverifikasi bukti tersebut dalam waktu maksimal 24 jam.
                        </p>
                    </details>

                    <!-- FAQ 4 -->
                    <details class="group py-2 border-b border-outline-variant/60">
                        <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                            <span>Bagaimana jika bukti transfer saya ditolak oleh Admin?</span>
                            <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                        </summary>
                        <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                            Apabila bukti ditolak (misalnya karena nominal tidak sesuai atau gambar buram), status transaksi akan berubah menjadi "Ditolak". Anda dapat mengunggah kembali foto/berkas bukti transfer yang benar melalui halaman rincian transaksi Anda.
                        </p>
                    </details>

                    <!-- FAQ 5 -->
                    <details class="group py-2">
                        <summary class="flex justify-between items-center font-bold text-primary cursor-pointer list-none text-xs">
                            <span>Apakah saya bisa membatalkan penyewaan?</span>
                            <span class="transition group-open:rotate-180"><span class="material-symbols-outlined text-xs">expand_more</span></span>
                        </summary>
                        <p class="mt-2 text-on-surface-variant text-[11px] leading-relaxed">
                            Pembatalan penyewaan dapat diajukan dengan menghubungi Bagian Pengelola Aset Politeknik Aceh via telepon atau langsung datang ke bagian Sekretariat Pengelola Gedung Kampus.
                        </p>
                    </details>
                </div>
            </div>

            <!-- Contact & Support Section -->
            <div class="lg:col-span-4 space-y-md">
                <h3 class="text-sm font-bold text-primary flex items-center gap-xs"><span class="material-symbols-outlined text-warning-amber text-sm">support_agent</span> Kontak Pengelola</h3>

                <div class="bg-primary-container text-white rounded-2xl p-md border border-primary-fixed/30 space-y-sm shadow-soft">
                    <div class="space-y-base">
                        <h4 class="text-xs font-bold text-white">Bagian Pengelola Gedung</h4>
                        <p class="text-on-primary-container text-[11px]">Politeknik Aceh — Kampus Utama</p>
                    </div>

                    <div class="space-y-sm text-[11px]">
                        <div class="flex items-start gap-xs">
                            <span class="material-symbols-outlined text-warning-amber text-sm flex-shrink-0">location_on</span>
                            <div>
                                <strong class="block font-bold text-white">Alamat Kampus:</strong>
                                <span class="text-on-primary-container leading-relaxed">Jl. Politeknik Aceh No.1, Pango Raya, Kec. Ulee Kareng, Kota Banda Aceh</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-xs">
                            <span class="material-symbols-outlined text-warning-amber text-sm flex-shrink-0">call</span>
                            <div>
                                <strong class="block font-bold text-white">Telepon / WhatsApp:</strong>
                                <span class="text-on-primary-container">+62 812-6900-1234 (Jam Kerja)</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-xs">
                            <span class="material-symbols-outlined text-warning-amber text-sm flex-shrink-0">mail</span>
                            <div>
                                <strong class="block font-bold text-white">Email Resmi:</strong>
                                <span class="text-on-primary-container">pengelola.gedung@politeknikaceh.ac.id</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-sm border-t border-white/10">
                        <a href="https://wa.me/6281269001234" target="_blank" class="w-full bg-warning-amber text-primary font-bold py-1.5 rounded-lg flex items-center justify-center gap-xs shadow-md hover:shadow-lg transition-all decoration-none active:scale-95 text-xs">
                            <span class="material-symbols-outlined text-sm">chat</span> Hubungi via WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer_tailwind.php'; ?>
<?php include 'includes/profile_modal.php'; ?>
<?php endif; ?>
