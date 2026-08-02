<?php
// SIIPAK - Functions & Utility Helper

function format_rupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function format_tanggal($date_str, $with_time = false) {
    if (empty($date_str) || $date_str == '0000-00-00') return '-';
    $timestamp = strtotime($date_str);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', $timestamp);
    $m = $bulan[(int)date('m', $timestamp)];
    $y = date('Y', $timestamp);
    
    $result = "$d $m $y";
    if ($with_time) {
        $result .= ' pukul ' . date('H:i', $timestamp) . ' WIB';
    }
    return $result;
}

/**
 * Logika Deteksi Bentrok Jadwal Berdasarkan Tanggal Mulai dan Tanggal Selesai
 * Mengembalikan true jika bentrok (terjadi overbooking), false jika jadwal tersedia.
 */
function cek_bentrok_jadwal($pdo, $id_gedung, $tanggal_mulai, $tanggal_selesai, $exclude_transaksi_id = null) {
    $sql = "SELECT COUNT(*) FROM transaksi 
            WHERE id_gedung = :id_gedung 
            AND status_transaksi NOT IN ('Ditolak', 'Dibatalkan')
            AND (:tanggal_mulai <= tanggal_selesai AND :tanggal_selesai >= tanggal_mulai)";
    
    if ($exclude_transaksi_id) {
        $sql .= " AND id_transaksi != :exclude_id";
    }

    $stmt = $pdo->prepare($sql);
    $params = [
        ':id_gedung' => $id_gedung,
        ':tanggal_mulai' => $tanggal_mulai,
        ':tanggal_selesai' => $tanggal_selesai
    ];
    
    if ($exclude_transaksi_id) {
        $params[':exclude_id'] = $exclude_transaksi_id;
    }

    $stmt->execute($params);
    return ($stmt->fetchColumn() > 0);
}

function generate_kode_transaksi($pdo) {
    $date_prefix = 'TRX-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE kode_transaksi LIKE :prefix");
    $stmt->execute([':prefix' => $date_prefix . '%']);
    $count = $stmt->fetchColumn() + 1;
    return $date_prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
}

function add_notification($pdo, $id_penyewa, $id_admin, $judul, $pesan, $link_url = '') {
    $stmt = $pdo->prepare("INSERT INTO notifikasi (id_penyewa, id_admin, judul, pesan, link_url) VALUES (:id_penyewa, :id_admin, :judul, :pesan, :link_url)");
    $stmt->execute([
        ':id_penyewa' => $id_penyewa,
        ':id_admin'   => $id_admin,
        ':judul'      => $judul,
        ':pesan'      => $pesan,
        ':link_url'   => $link_url
    ]);
}

function get_status_badge($status) {
    switch ($status) {
        case 'Menunggu Pembayaran':
            return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu Pembayaran</span>';
        case 'DP':
            return '<span class="badge bg-info text-dark"><i class="bi bi-pie-chart-fill me-1"></i>DP (Tervalidasi)</span>';
        case 'Cicilan':
            return '<span class="badge bg-primary"><i class="bi bi-wallet2 me-1"></i>Cicilan (Tervalidasi)</span>';
        case 'Lunas':
            return '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Lunas & Jadwal Terkunci</span>';
        case 'Ditolak':
            return '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Ditolak</span>';
        case 'Dibatalkan':
            return '<span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Dibatalkan</span>';
        default:
            return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Memproses pembatalan pemesanan secara otomatis jika sisa tagihan belum lunas pada H-1.
 * Dibebaskan secara otomatis di kalender dan notifikasi dikirim ke admin & penyewa.
 */
function proses_pembatalan_otomatis($pdo) {
    try {
        $stmt = $pdo->query("SELECT t.*, g.nama_gedung 
                             FROM transaksi t 
                             JOIN gedung g ON t.id_gedung = g.id_gedung 
                             WHERE t.status_transaksi NOT IN ('Lunas', 'Ditolak', 'Dibatalkan') 
                               AND t.tanggal_mulai <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                               AND t.created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
        $to_cancel = $stmt->fetchAll();
        
        if (!empty($to_cancel)) {
            foreach ($to_cancel as $trx) {
                // Update status transaksi menjadi Dibatalkan
                $stmt_upd = $pdo->prepare("UPDATE transaksi 
                                           SET status_transaksi = 'Dibatalkan', 
                                               catatan = CONCAT(COALESCE(catatan, ''), '\n[Sistem] Dibatalkan otomatis pada H-1 karena sisa tagihan belum terlunasi.') 
                                           WHERE id_transaksi = :id");
                $stmt_upd->execute([':id' => $trx['id_transaksi']]);
                
                // Kirim notifikasi ke penyewa
                add_notification(
                    $pdo, 
                    $trx['id_penyewa'], 
                    null, 
                    'Pemesanan Dibatalkan Otomatis', 
                    "Pemesanan Anda untuk " . $trx['nama_gedung'] . " (" . $trx['kode_transaksi'] . ") dibatalkan otomatis oleh sistem karena sisa tagihan belum dilunasi pada H-1 sebelum pelaksanaan.", 
                    'riwayat_booking.php'
                );
                
                // Kirim notifikasi ke admin (id_admin = 1)
                add_notification(
                    $pdo, 
                    null, 
                    1, 
                    'Pemesanan Dibatalkan (H-1)', 
                    "Sistem membatalkan sewa " . $trx['kode_transaksi'] . " (" . $trx['nama_gedung'] . ") secara otomatis karena melewati batas pelunasan H-1.", 
                    'admin/booking_kelola.php'
                );
            }
        }
    } catch (\Exception $e) {
        // Gagal secara aman tanpa menghentikan aplikasi
    }
}

// Jalankan pembatalan otomatis H-1 setiap kali halaman diakses
if (isset($pdo)) {
    proses_pembatalan_otomatis($pdo);
}
