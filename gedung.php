<?php
// SIIPAK - Katalog Gedung & Aset Kampus (Informasi)
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

$role = get_user_role();
$user_name = $_SESSION['user_name'] ?? '';

// Fetch all active buildings
$stmt = $pdo->query("SELECT * FROM gedung WHERE status != 'Tidak Aktif' ORDER BY id_gedung ASC");
$gedung_list = $stmt->fetchAll();
?>
<?php
require_once 'includes/header_tailwind.php';
require_once 'includes/navbar_tailwind.php';
?>

<main class="py-xl bg-[#eef2f6]">
    <div class="max-w-[1140px] mx-auto px-lg">
        <!-- Title Header -->
        <div class="mb-xl text-center md:text-left space-y-xs">
            <span class="text-primary font-bold uppercase tracking-widest text-label-md">Informasi Gedung &amp; Aset</span>
            <h2 class="text-display-md font-display-md text-primary">Katalog Fasilitas Politeknik Aceh</h2>
            <p class="text-on-surface-variant max-w-2xl text-xs md:text-sm">
                Cari dan pelajari ruangan yang sesuai untuk acara Anda. Dilengkapi data kapasitas, tarif dasar, dan aset tambahan penunjang kegiatan.
            </p>
        </div>

        <!-- Catalog Grid -->
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
</main>

<?php require_once 'includes/footer_tailwind.php'; ?>
