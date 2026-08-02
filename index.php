<?php
// SIIPAK - Sistem Informasi Penyewaan Gedung & Aset Kampus Politeknik Aceh
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

// Fetch statistics
$total_gedung = $pdo->query("SELECT COUNT(*) FROM gedung")->fetchColumn();
$total_aset = $pdo->query("SELECT COUNT(*) FROM aset")->fetchColumn();
$total_booking = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status_transaksi IN ('DP', 'Cicilan', 'Lunas')")->fetchColumn();
$total_aset_kampus = $total_gedung + $total_aset;

// Fetch all active buildings for the catalog
$stmt_gedung = $pdo->query("SELECT * FROM gedung WHERE status != 'Tidak Aktif' ORDER BY id_gedung ASC");
$gedung_list = $stmt_gedung->fetchAll();

$role = get_user_role();
$user_name = $_SESSION['user_name'] ?? '';
?>
<?php
require_once 'includes/header_tailwind.php';
require_once 'includes/navbar_tailwind.php';
?>

<style>
    html {
        scroll-behavior: smooth;
        scroll-padding-top: 70px;
    }
</style>

<main class="bg-[#eef2f6] pb-xl">
    <!-- Hero Section -->
    <section id="beranda" class="relative hero-gradient py-16 md:py-18 lg:py-20 overflow-hidden">
        <!-- Ambient Glow Accent Left & Right -->
        <div class="absolute -right-32 -top-32 w-[600px] h-[600px] bg-primary/20 rounded-full blur-3xl pointer-events-none z-0"></div>
        <div class="absolute -left-32 -bottom-32 w-[600px] h-[600px] bg-warning-amber/5 rounded-full blur-3xl pointer-events-none z-0"></div>

        <div class="container mx-auto px-lg relative z-10">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-lg">
                <!-- Left text content -->
                <div class="w-full lg:w-1/2 text-left space-y-md">
                    <div class="inline-flex items-center bg-warning-amber text-primary px-3.5 py-1 rounded-full text-xs font-extrabold tracking-wider">
                        Sistem Resmi Kampus
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-[56px] font-extrabold text-white leading-tight">
                        Penyewaan Aset<br>
                        Kampus <span class="text-warning-amber">Politeknik</span><br>
                        Aceh
                    </h1>
                    <p class="text-white/80 text-sm md:text-base lg:text-[17px] leading-relaxed max-w-md">
                        Booking auditorium, aula, dan ruang praktikum secara online. Pembayaran DP &amp; pelunasan transparan, jadwal aset real-time, validasi cepat oleh admin kampus.
                    </p>
                    <div class="flex flex-wrap gap-sm pt-sm">
                        <a href="#aset-tersedia" class="bg-warning-amber text-primary px-5 py-2.5 rounded-xl font-bold text-xs md:text-sm flex items-center gap-xs hover:scale-105 transition-transform shadow-lg decoration-none">
                            Lihat Aset Tersedia <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                        <a href="kalender.php" class="border border-white/30 text-white px-5 py-2.5 rounded-xl font-bold text-xs md:text-sm hover:bg-white/10 transition-colors decoration-none flex items-center gap-xs">
                            <span class="material-symbols-outlined text-sm">calendar_month</span> Cek Jadwal Aset
                        </a>
                    </div>
                </div>

                <!-- Right image content -->
                <div class="w-full lg:w-5/12 relative mt-6 lg:mt-0">
                    <div class="rounded-[20px] overflow-hidden shadow-2xl relative">
                        <img class="w-full h-[260px] md:h-[300px] lg:h-[320px] object-cover" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=600&q=80" alt="Students working"/>
                    </div>
                    <!-- Floating Card -->
                    <div class="absolute -bottom-4 -left-4 bg-white p-2.5 rounded-xl shadow-xl flex items-center gap-2.5 border border-outline-variant animate-bounce-subtle z-20">
                        <div class="bg-success-green/10 p-1.5 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-success-green text-base" style="font-variation-settings: 'FILL' 1;">calendar_today</span>
                        </div>
                        <div>
                            <p class="text-[9px] font-semibold text-on-surface-variant leading-none">Booking bulan ini</p>
                            <p class="text-xs font-extrabold text-primary mt-0.5">Rp 42.8 juta</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Row at the bottom of the blue box -->
            <div class="grid grid-cols-3 gap-md pt-6 mt-6 border-t border-white/10">
                <div class="text-center">
                    <p class="text-lg md:text-xl lg:text-2xl font-extrabold text-white leading-none"><?= $total_aset_kampus ?>+</p>
                    <p class="text-[9px] md:text-[10px] text-white/60 mt-1 uppercase tracking-wider">Aset kampus</p>
                </div>
                <div class="text-center">
                    <p class="text-lg md:text-xl lg:text-2xl font-extrabold text-white leading-none"><?= $total_booking ?>+</p>
                    <p class="text-[9px] md:text-[10px] text-white/60 mt-1 uppercase tracking-wider">Booking sukses</p>
                </div>
                <div class="text-center">
                    <p class="text-lg md:text-xl lg:text-2xl font-extrabold text-white leading-none">24/7</p>
                    <p class="text-[9px] md:text-[10px] text-white/60 mt-1 uppercase tracking-wider">Booking online</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works / Workflow Section -->
    <section id="cara-sewa" class="min-h-[calc(100vh-70px)] pt-12 md:pt-16 pb-xl bg-[#00164e] border-y border-white/10 relative overflow-hidden">
        <!-- Ambient decorative blur -->
        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-80 h-80 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-0 bottom-0 w-80 h-80 bg-warning-amber/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="container mx-auto px-lg relative z-10">
            <div class="text-center max-w-2xl mx-auto mb-xl space-y-xs">
                <span class="text-warning-amber font-bold uppercase tracking-widest text-label-md">Bagaimana SIPAK Bekerja?</span>
                <h2 class="text-display-md font-display-md text-white">Alur Booking Mudah &amp; Cepat</h2>
                <p class="text-body-lg text-white/80 leading-relaxed">
                    Kami menyederhanakan proses peminjaman dan penyewaan aset Politeknik Aceh dalam 4 langkah terintegrasi.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-lg relative">
                <!-- Step 1 -->
                <div class="bg-white/5 p-lg rounded-2xl border border-white/15 hover:border-warning-amber transition-all duration-300 group hover:shadow-md relative">
                    <div class="absolute top-3 right-4 text-3xl font-extrabold text-white/20 group-hover:text-warning-amber/70 transition-colors select-none">01</div>
                    <div class="w-12 h-12 rounded-xl bg-white/10 text-white flex items-center justify-center mb-md group-hover:bg-warning-amber group-hover:text-primary transition-all shadow-md">
                        <span class="material-symbols-outlined text-2xl">search_insights</span>
                    </div>
                    <h3 class="text-headline-md font-headline-md text-white mb-xs">1. Cari Aset</h3>
                    <p class="text-body-md text-white/70 leading-relaxed">
                        Cari gedung, ruang auditorium, aula, atau ruang rapat di daftar aset kampus secara real-time.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="bg-white/5 p-lg rounded-2xl border border-white/15 hover:border-warning-amber transition-all duration-300 group hover:shadow-md relative">
                    <div class="absolute top-3 right-4 text-3xl font-extrabold text-white/20 group-hover:text-warning-amber/70 transition-colors select-none">02</div>
                    <div class="w-12 h-12 rounded-xl bg-white/10 text-white flex items-center justify-center mb-md group-hover:bg-warning-amber group-hover:text-primary transition-all shadow-md">
                        <span class="material-symbols-outlined text-2xl">edit_calendar</span>
                    </div>
                    <h3 class="text-headline-md font-headline-md text-white mb-xs">2. Reservasi</h3>
                    <p class="text-body-md text-white/70 leading-relaxed">
                        Pilih tanggal kegiatan, isi detail acara, serta tentukan opsi cicilan/DP (50%) atau bayar lunas langsung.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="bg-white/5 p-lg rounded-2xl border border-white/15 hover:border-warning-amber transition-all duration-300 group hover:shadow-md relative">
                    <div class="absolute top-3 right-4 text-3xl font-extrabold text-white/20 group-hover:text-warning-amber/70 transition-colors select-none">03</div>
                    <div class="w-12 h-12 rounded-xl bg-white/10 text-white flex items-center justify-center mb-md group-hover:bg-warning-amber group-hover:text-primary transition-all shadow-md">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                    <h3 class="text-headline-md font-headline-md text-white mb-xs">3. Unggah Bukti</h3>
                    <p class="text-body-md text-white/70 leading-relaxed">
                        Unggah bukti transfer pembayaran langsung dari sistem. Transaksi Anda tercatat dan terlindungi dengan aman.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="bg-white/5 p-lg rounded-2xl border border-white/15 hover:border-warning-amber transition-all duration-300 group hover:shadow-md relative">
                    <div class="absolute top-3 right-4 text-3xl font-extrabold text-white/20 group-hover:text-warning-amber/70 transition-colors select-none">04</div>
                    <div class="w-12 h-12 rounded-xl bg-white/10 text-white flex items-center justify-center mb-md group-hover:bg-warning-amber group-hover:text-primary transition-all shadow-md">
                        <span class="material-symbols-outlined text-2xl">workspace_premium</span>
                    </div>
                    <h3 class="text-headline-md font-headline-md text-white mb-xs">4. Verifikasi &amp; Siap</h3>
                    <p class="text-body-md text-white/70 leading-relaxed">
                        Persetujuan dan invoice digital divalidasi oleh Admin kampus dalam kurun waktu kurang dari 24 jam.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Catalog Section -->
    <section id="aset-tersedia" class="py-xl bg-surface">
        <div class="container mx-auto px-lg">
            <div class="text-center max-w-2xl mx-auto mb-xl space-y-xs">
                <span class="text-primary font-bold uppercase tracking-widest text-label-md">Katalog Fasilitas</span>
                <h2 class="text-display-md font-display-md text-primary">Aset &amp; Ruangan Tersedia</h2>
                <p class="text-body-lg text-on-surface-variant leading-relaxed">
                    Pilih dan sewa fasilitas kampus terbaik Politeknik Aceh untuk menunjang kelancaran kegiatan Anda.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-md lg:gap-lg">
                <?php foreach ($gedung_list as $g): ?>
                    <?php
                    // Fetch associated assets for this building
                    $stmt_aset = $pdo->prepare("SELECT * FROM aset WHERE id_gedung = :id_gedung");
                    $stmt_aset->execute([':id_gedung' => $g['id_gedung']]);
                    $assets = $stmt_aset->fetchAll();
                    ?>
                    <div class="bg-white rounded-[24px] overflow-hidden shadow-soft border border-outline-variant/65 hover:shadow-xl transition-all duration-300 flex flex-col hover:border-primary">
                        <!-- Photo Header -->
                        <div class="relative h-[160px] bg-slate-200">
                            <img class="w-full h-full object-cover" src="assets/uploads/<?= htmlspecialchars($g['foto']) ?>" alt="<?= htmlspecialchars($g['nama_gedung']) ?>" onerror="this.src='https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=800&q=80'">
                            <div class="absolute bottom-3 right-3 bg-primary/95 backdrop-blur-md text-white font-bold px-3 py-1 rounded-full shadow-lg border border-white/20 text-[10px]">
                                <?= format_rupiah($g['harga_sewa']) ?> / Hari
                            </div>
                        </div>
                        
                        <!-- Details Body -->
                        <div class="p-4 flex-grow flex flex-col justify-between space-y-sm">
                           <div class="space-y-sm">
                               <div class="flex justify-between items-start gap-xs">
                                   <h3 class="text-sm font-extrabold text-primary leading-tight"><?= htmlspecialchars($g['nama_gedung']) ?></h3>
                                   <span class="bg-success-green/10 text-success-green border border-success-green/20 px-2 py-0.5 rounded-full text-[9px] font-bold flex items-center gap-xs shrink-0">
                                       <span class="material-symbols-outlined text-[10px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                       <?= $g['status'] ?>
                                   </span>
                               </div>
                               
                               <p class="text-[11px] text-on-surface-variant leading-relaxed">
                                   <?= htmlspecialchars($g['deskripsi']) ?>
                               </p>
                               
                               <div class="grid grid-cols-2 gap-sm">
                                   <div class="bg-surface p-2 rounded-xl border border-outline-variant/40">
                                       <span class="text-on-surface-variant text-[9px] block font-semibold leading-none">Kapasitas</span>
                                       <strong class="text-primary text-[11px] font-bold flex items-center gap-xs mt-1">
                                           <span class="material-symbols-outlined text-xs">groups</span> <?= $g['kapasitas'] ?> Orang
                                       </strong>
                                   </div>
                                   <div class="bg-surface p-2 rounded-xl border border-outline-variant/40">
                                       <span class="text-on-surface-variant text-[9px] block font-semibold leading-none">Fasilitas Bawaan</span>
                                       <span class="text-primary text-[11px] font-medium truncate block mt-1" title="<?= htmlspecialchars($g['fasilitas']) ?>"><?= htmlspecialchars($g['fasilitas']) ?></span>
                                   </div>
                                </div>

                               <!-- Additional Assets -->
                               <?php if (!empty($assets)): ?>
                                   <div class="space-y-xs">
                                       <span class="text-[11px] font-bold text-primary flex items-center gap-xs">
                                           <span class="material-symbols-outlined text-xs">widgets</span> Aset Tambahan:
                                       </span>
                                       <div class="border border-outline-variant/40 rounded-xl divide-y divide-outline-variant/30 overflow-hidden bg-surface-container-low/30">
                                           <?php foreach ($assets as $ast): ?>
                                               <div class="flex justify-between items-center p-2 text-[10px]">
                                                   <div>
                                                       <span class="font-semibold text-primary"><?= htmlspecialchars($ast['nama_aset']) ?></span>
                                                       <div class="flex gap-xs mt-0.5">
                                                           <span class="bg-outline-variant/40 text-on-surface-variant text-[8px] px-1.5 py-0.5 rounded-full"><?= $ast['jumlah'] ?> Unit</span>
                                                       </div>
                                                   </div>
                                                   <span class="text-primary font-bold">+<?= format_rupiah($ast['harga_sewa_tambahan']) ?></span>
                                               </div>
                                           <?php endforeach; ?>
                                       </div>
                                   </div>
                               <?php endif; ?>
                           </div>

                           <div class="flex gap-sm pt-sm border-t border-outline-variant/40">
                               <a href="booking.php?id_gedung=<?= $g['id_gedung'] ?>" class="flex-grow bg-primary text-white text-center py-1.5 rounded-xl font-bold hover:shadow-lg transition-all decoration-none text-[11px]">
                                   Booking
                               </a>
                               <a href="kalender.php?id_gedung=<?= $g['id_gedung'] ?>" class="border border-outline hover:bg-surface-container px-3 py-1.5 rounded-xl font-bold text-primary decoration-none flex items-center gap-xs text-[11px]">
                                   <span class="material-symbols-outlined text-xs">calendar_month</span> Jadwal
                               </a>
                           </div>
                       </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-xl">
        <div class="container mx-auto px-lg">
            <div class="bg-gradient-to-br from-primary-container to-[#001035] rounded-[40px] p-xl flex flex-col lg:flex-row items-center gap-xl relative overflow-hidden shadow-xl border border-white/5">
                <!-- Subtle pattern background -->
                <div class="absolute inset-0 opacity-10 pointer-events-none">
                    <div class="w-full h-full" style="background-image: radial-gradient(circle at 2px 2px, #ffffff 1px, transparent 0); background-size: 24px 24px;"></div>
                </div>
                <div class="w-full lg:w-2/3 space-y-md relative z-10">
                    <h2 class="text-display-md font-display-md text-white">Siap Memulai Acara Anda?</h2>
                    <p class="text-body-lg font-body-lg text-primary-fixed/80 max-w-xl">
                        Lakukan reservasi sekarang untuk menjamin ketersediaan ruangan di tanggal yang Anda inginkan. Proses verifikasi administrasi kurang dari 24 jam.
                    </p>
                    <div class="flex flex-wrap gap-md">
                        <a href="booking.php" class="bg-warning-amber text-primary px-xl py-md rounded-full font-headline-md text-headline-md hover:bg-white hover:scale-105 transition-all shadow-md decoration-none">
                            Buat Reservasi Sekarang
                        </a>
                        <a href="#cara-sewa" class="text-white border border-white/30 px-xl py-md rounded-full font-headline-md text-headline-md hover:bg-white/10 transition-colors decoration-none">
                            Pelajari Prosedur
                        </a>
                    </div>
                </div>
                <div class="w-full lg:w-1/3 flex justify-center lg:justify-end relative z-10">
                    <a href="kalender.php" class="bg-white/10 backdrop-blur-xl p-xl rounded-[32px] border border-white/20 hover:bg-white/20 transition-all decoration-none group">
                        <span class="material-symbols-outlined text-white text-6xl group-hover:scale-105 transition-transform" style="font-variation-settings: 'FILL' 0, 'wght' 200;">event_available</span>
                        <div class="mt-md">
                            <p class="text-white font-bold text-lg">Cek Ketersediaan</p>
                            <p class="text-primary-fixed/60 text-sm">Update Real-time 15 menit lalu</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer_tailwind.php'; ?>
