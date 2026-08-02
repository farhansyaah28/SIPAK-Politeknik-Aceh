<?php
// SIIPAK - Kuitansi Resmi Penyewaan Aset Politeknik Aceh
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

// Proteksi: Harus login
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$id_transaksi = isset($_GET['id_transaksi']) ? (int)$_GET['id_transaksi'] : 0;
$user_role = $_SESSION['user_role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Query Transaksi Utama beserta data gedung & penyewa
$sql = "SELECT t.*, g.nama_gedung, g.harga_sewa AS harga_gedung, py.nama AS nama_penyewa, py.no_telepon, py.instansi, py.email
        FROM transaksi t
        JOIN gedung g ON t.id_gedung = g.id_gedung
        JOIN penyewa py ON t.id_penyewa = py.id_penyewa
        WHERE t.id_transaksi = :id_trx";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id_trx' => $id_transaksi]);
$transaksi = $stmt->fetch();

// Validasi Keberadaan Transaksi
if (!$transaksi) {
    die("Kuitansi Error: Data transaksi tidak ditemukan.");
}

// Validasi Hak Akses: Hanya admin, pimpinan, atau penyewa yang bersangkutan
if ($user_role === 'Penyewa' && (int)$transaksi['id_penyewa'] !== (int)$user_id) {
    die("Kuitansi Error: Anda tidak memiliki akses untuk melihat kuitansi ini.");
}

// Validasi Status: Kuitansi resmi hanya dicetak jika sudah Lunas / Selesai
if (!in_array($transaksi['status_transaksi'], ['Lunas', 'Selesai'])) {
    die("Kuitansi Error: Kuitansi resmi belum diterbitkan karena status pemesanan saat ini masih: " . htmlspecialchars($transaksi['status_transaksi']) . ".");
}

// Fetch all assets selected for this transaction
$stmt_assets = $pdo->prepare("SELECT ta.*, a.nama_aset, a.harga_sewa_tambahan 
                              FROM transaksi_aset ta
                              JOIN aset a ON ta.id_aset = a.id_aset
                              WHERE ta.id_transaksi = :id_trx");
$stmt_assets->execute([':id_trx' => $id_transaksi]);
$selected_assets = $stmt_assets->fetchAll();

// Fetch last validated payment date to set as receipt date
$stmt_payment = $pdo->prepare("SELECT MAX(tanggal_bayar) AS tgl_lunas 
                               FROM pembayaran 
                               WHERE id_transaksi = :id_trx AND status_validasi = 'Valid'");
$stmt_payment->execute([':id_trx' => $id_transaksi]);
$tgl_lunas_raw = $stmt_payment->fetchColumn();
$tgl_lunas = $tgl_lunas_raw ? date('Y-m-d', strtotime($tgl_lunas_raw)) : date('Y-m-d');

// Hitung durasi hari
$d1 = new DateTime($transaksi['tanggal_mulai']);
$d2 = new DateTime($transaksi['tanggal_selesai']);
$durasi = $d2->diff($d1)->days + 1;

// Spelling helper functions for Indonesian numbers
function penyebut($nilai) {
    $nilai = abs($nilai);
    $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $temp = penyebut($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $temp = penyebut($nilai / 10) . " Puluh" . penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " Seratus" . penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebut($nilai / 100) . " Ratus" . penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " Seribu" . penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebut($nilai / 1000) . " Ribu" . penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebut($nilai / 1000000) . " Juta" . penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = penyebut($nilai / 1000000000) . " Milyar" . penyebut(fmod($nilai, 1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = penyebut($nilai / 1000000000000) . " Trilyun" . penyebut(fmod($nilai, 1000000000000));
    }     
    return $temp;
}

function terbilang($nilai) {
    if($nilai < 0) {
        $hasil = "Minus " . trim(penyebut($nilai));
    } else {
        $hasil = trim(penyebut($nilai));
    }     
    return $hasil . " Rupiah";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Kuitansi Resmi #<?= htmlspecialchars($transaksi['kode_transaksi']) ?> | Politeknik Aceh</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
        }
        @media print {
            body {
                background-color: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .print-border {
                border: 1px solid #000000 !important;
            }
        }
    </style>
</head>
<body class="p-4 md:p-8">

    <!-- Control Actions Banner (Hidden when printing) -->
    <div class="max-w-3xl mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm no-print">
        <a href="<?= $user_role === 'Admin' ? 'admin/booking_kelola.php' : 'riwayat_booking.php' ?>" class="flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
        <button onclick="window.print()" class="flex items-center gap-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-3a2 2 0 00-2-2H5a2 2 0 00-2 2v3a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Kuitansi / Simpan PDF
        </button>
    </div>

    <!-- Official Receipt Sheet -->
    <div class="max-w-3xl mx-auto bg-white p-8 md:p-12 border border-slate-300 shadow-md relative overflow-hidden print-border rounded-xl">
        
        <!-- Official Letterhead Section -->
        <div class="flex items-center gap-md pb-4 mb-1">
            <div class="flex-shrink-0">
                <div class="w-14 h-14 bg-slate-900 rounded-xl flex items-center justify-center text-white font-extrabold text-sm tracking-wider">
                    SIPAK
                </div>
            </div>
            <div class="flex-grow text-center">
                <h1 class="text-base font-extrabold text-slate-900 uppercase leading-none">POLITEKNIK ACEH</h1>
                <p class="text-[10px] text-slate-700 font-bold uppercase tracking-wider mt-1.5">Unit Pelaksana Teknis Sarana &amp; Prasarana Kampus</p>
                <p class="text-[9px] text-slate-500 font-medium mt-0.5">Jalan Pango Raya No. 8, Lueng Bata, Banda Aceh | Telp: (0651) 123456 | Web: www.politeknikaceh.ac.id</p>
            </div>
        </div>
        
        <!-- Double Border Line representing formal document standard -->
        <div class="border-b-[3px] border-slate-900 mb-[1px]"></div>
        <div class="border-b border-slate-900 mb-6"></div>

        <!-- Receipt Header Title & Serial -->
        <div class="text-center mb-8">
            <h2 class="text-base font-extrabold text-slate-900 tracking-widest underline underline-offset-4 uppercase">KUITANSI PEMBAYARAN</h2>
            <p class="text-xs text-slate-600 font-semibold mt-1.5">Nomor: KUT/TRX/<?= htmlspecialchars($transaksi['kode_transaksi']) ?></p>
        </div>

        <!-- Traditional Indonesian Kuitansi Body Layout -->
        <div class="space-y-sm text-xs text-slate-800">
            <!-- Telah Terima Dari -->
            <div class="grid grid-cols-12 gap-xs items-start border-b border-slate-100 py-2">
                <div class="col-span-3 font-semibold text-slate-600 uppercase tracking-wider text-[9px] pt-0.5">Telah Diterima Dari</div>
                <div class="col-span-1 text-center text-slate-400">:</div>
                <div class="col-span-8 font-bold text-slate-900 text-sm">
                    <?= htmlspecialchars($transaksi['nama_penyewa']) ?> 
                    <?php if (!empty($transaksi['instansi'])): ?>
                        <span class="font-semibold text-slate-500 text-xs">(<?= htmlspecialchars($transaksi['instansi']) ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Uang Sejumlah (Terbilang) -->
            <div class="grid grid-cols-12 gap-xs items-start border-b border-slate-100 py-2">
                <div class="col-span-3 font-semibold text-slate-600 uppercase tracking-wider text-[9px] pt-1">Uang Sejumlah</div>
                <div class="col-span-1 text-center text-slate-400">:</div>
                <div class="col-span-8 bg-slate-50 border border-slate-200/75 rounded-lg px-3 py-2 font-bold italic text-slate-800 text-xs">
                    "<?= terbilang($transaksi['total_pembayaran']) ?>"
                </div>
            </div>
            
            <!-- Untuk Pembayaran -->
            <div class="grid grid-cols-12 gap-xs items-start border-b border-slate-100 py-2">
                <div class="col-span-3 font-semibold text-slate-600 uppercase tracking-wider text-[9px] pt-0.5">Untuk Pembayaran</div>
                <div class="col-span-1 text-center text-slate-400">:</div>
                <div class="col-span-8 text-slate-900 space-y-1">
                    <p class="leading-relaxed">Biaya sewa gedung <strong class="font-bold text-slate-900"><?= htmlspecialchars($transaksi['nama_gedung']) ?></strong> untuk penyelenggaraan acara <strong class="font-semibold">"<?= htmlspecialchars($transaksi['nama_kegiatan']) ?>"</strong>.</p>
                    <p class="text-slate-500 text-[10px] font-medium">Tanggal Pelaksanaan: <?= format_tanggal($transaksi['tanggal_mulai']) ?> s/d <?= format_tanggal($transaksi['tanggal_selesai']) ?> (<?= $durasi ?> Hari).</p>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown Invoice Table -->
        <div class="mt-8 mb-6">
            <h3 class="text-slate-700 font-bold uppercase tracking-wider text-[9px] mb-2">Rincian Perhitungan Sewa</h3>
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-900 text-slate-600 font-bold uppercase text-[9px]">
                        <th class="py-2 text-left">Item Penyewaan</th>
                        <th class="py-2 text-center w-28">Durasi / Jumlah</th>
                        <th class="py-2 text-right w-32">Tarif Satuan</th>
                        <th class="py-2 text-right w-32">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <!-- Gedung Row -->
                    <tr>
                        <td class="py-2.5 font-bold text-slate-900">Sewa Gedung: <?= htmlspecialchars($transaksi['nama_gedung']) ?></td>
                        <td class="py-2.5 text-center"><?= $durasi ?> Hari</td>
                        <td class="py-2.5 text-right"><?= format_rupiah($transaksi['harga_gedung']) ?></td>
                        <td class="py-2.5 text-right font-bold text-slate-900"><?= format_rupiah($transaksi['harga_gedung'] * $durasi) ?></td>
                    </tr>
                    <!-- Assets -->
                    <?php if (!empty($selected_assets)): ?>
                        <?php foreach ($selected_assets as $ast): ?>
                            <tr>
                                <td class="py-2.5 pl-4 text-slate-600">+ Aset: <?= htmlspecialchars($ast['nama_aset']) ?></td>
                                <td class="py-2.5 text-center text-slate-600"><?= $ast['jumlah_aset'] ?> Unit</td>
                                <td class="py-2.5 text-right text-slate-600"><?= format_rupiah($ast['harga_sewa_tambahan']) ?></td>
                                <td class="py-2.5 text-right text-slate-600 font-semibold"><?= format_rupiah($ast['harga_sewa_tambahan'] * $ast['jumlah_aset'] * $durasi) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Total Box & Status -->
        <div class="flex justify-between items-center bg-slate-100 border border-slate-200 rounded-xl p-4 my-6">
            <div>
                <span class="text-[9px] uppercase tracking-wider font-bold text-slate-500 block">Jumlah Pembayaran / Total Paid</span>
                <span class="text-lg font-black text-slate-900"><?= format_rupiah($transaksi['total_pembayaran']) ?></span>
            </div>
            <div class="text-right">
                <span class="text-[9px] uppercase tracking-wider font-bold text-slate-500 block">Status Verifikasi</span>
                <span class="text-[10px] font-extrabold uppercase text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-200 tracking-wider">LUNAS / PAID</span>
            </div>
        </div>

        <!-- Footer Signatures -->
        <div class="grid grid-cols-2 gap-4 mt-12 text-xs relative">
            
            <div class="space-y-1">
                <p class="text-slate-500">Pihak Penyewa / Penerima Kuitansi,</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-800 underline underline-offset-2"><?= htmlspecialchars($transaksi['nama_penyewa']) ?></p>
                <p class="text-[10px] text-slate-400">Tanda Tangan &amp; Nama Terang</p>
            </div>
            
            <div class="space-y-1 text-right relative">
                <!-- Large PAID Stamp Overlay in signature space -->
                <div class="absolute right-14 -top-6 border-2 border-dashed border-emerald-500/60 text-emerald-500/60 font-bold tracking-widest text-[10px] uppercase px-3 py-1 rounded rotate-12 opacity-80 select-none pointer-events-none">
                    LUNAS / VERIFIED
                </div>

                <p class="text-slate-500">Banda Aceh, <?= format_tanggal($tgl_lunas) ?></p>
                <p class="text-slate-500 font-semibold">Bendahara UPT Sarana &amp; Prasarana,</p>
                <div class="h-20 flex justify-end items-center">
                    <div class="text-[9px] text-indigo-900 border border-indigo-900/30 px-2 py-0.5 rounded opacity-65 italic scale-90 origin-right">
                        Signed Digitally (SIPAK Auth)
                    </div>
                </div>
                <p class="font-bold text-slate-900 underline underline-offset-2">Admin Pengelola</p>
                <p class="text-[10px] text-slate-500">UPT Sarana &amp; Prasarana Politeknik Aceh</p>
            </div>
        </div>

    </div>

</body>
</html>
