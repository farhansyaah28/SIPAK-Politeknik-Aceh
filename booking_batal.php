<?php
// SIIPAK - Batalkan Pemesanan (Penyewa)
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

check_penyewa();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_transaksi = isset($_POST['id_transaksi']) ? (int)$_POST['id_transaksi'] : 0;
    $id_penyewa = $_SESSION['user_id'];

    if ($id_transaksi > 0) {
        // Fetch transaction and verify ownership & status, including email for notification
        $stmt = $pdo->prepare("SELECT t.status_transaksi, t.kode_transaksi, t.nama_kegiatan, py.email, py.nama AS nama_penyewa 
                               FROM transaksi t
                               JOIN penyewa py ON t.id_penyewa = py.id_penyewa
                               WHERE t.id_transaksi = :id AND t.id_penyewa = :id_penyewa");
        $stmt->execute([':id' => $id_transaksi, ':id_penyewa' => $id_penyewa]);
        $transaksi = $stmt->fetch();

        if ($transaksi) {
            if ($transaksi['status_transaksi'] === 'Menunggu Pembayaran') {
                // Update transaction status to Dibatalkan
                $stmt_upd = $pdo->prepare("UPDATE transaksi SET status_transaksi = 'Dibatalkan' WHERE id_transaksi = :id");
                $stmt_upd->execute([':id' => $id_transaksi]);

                // Kirim notifikasi email ke penyewa & admin
                if (!empty($transaksi['email'])) {
                    require_once 'config/email.php';
                    $email_penyewa = $transaksi['email'];
                    $nama_penyewa = $transaksi['nama_penyewa'];
                    $kode_transaksi = $transaksi['kode_transaksi'];
                    $nama_kegiatan = $transaksi['nama_kegiatan'];
                    
                    // Email untuk Penyewa
                    $subject_penyewa = "Pemesanan Gedung Dibatalkan - Kode $kode_transaksi";
                    $body_penyewa = "
                        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                            <h2 style='color: #b7791f; border-bottom: 2px solid #b7791f; padding-bottom: 10px;'>Pemesanan Dibatalkan</h2>
                            <p>Halo <strong>$nama_penyewa</strong>,</p>
                            <p>Kami mengonfirmasi bahwa pemesanan Anda untuk kegiatan <strong>\"$nama_kegiatan\"</strong> dengan Kode Sewa <strong>$kode_transaksi</strong> telah <strong>berhasil dibatalkan secara mandiri</strong>.</p>
                            <p>Status pemesanan Anda saat ini adalah: <strong>Dibatalkan</strong>.</p>
                            <p style='margin-top: 20px;'>Jika ini adalah kesalahan atau Anda ingin membuat pemesanan gedung baru, silakan kunjungi portal SIPAK Politeknik Aceh kembali.</p>
                            <hr style='border: none; border-top: 1px solid #edf2f7; margin: 30px 0;'>
                            <p style='font-size: 11px; color: #a0aec0;'>Email ini dikirim secara otomatis oleh Sistem Informasi Penyewaan Aset Kampus (SIPAK) Politeknik Aceh.</p>
                        </div>
                    ";
                    send_mail($email_penyewa, $subject_penyewa, $body_penyewa);

                    // Email untuk Admin
                    $subject_admin = "[SIPAK] Pemesanan Dibatalkan - Kode $kode_transaksi";
                    $body_admin = "
                        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                            <h2 style='color: #b7791f; border-bottom: 2px solid #b7791f; padding-bottom: 10px;'>Pemesanan Dibatalkan oleh Penyewa</h2>
                            <p>Halo Admin,</p>
                            <p>Penyewa <strong>$nama_penyewa</strong> ($email_penyewa) telah membatalkan pemesanan pending untuk kegiatan <strong>\"$nama_kegiatan\"</strong> secara mandiri.</p>
                            <p>Rincian Transaksi:</p>
                            <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold; width: 150px;'>Kode Sewa</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'>$kode_transaksi</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold;'>Nama Acara</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'>$nama_kegiatan</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold;'>Status Akhir</td>
                                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; color: #b7791f; font-weight: bold;'>Dibatalkan</td>
                                </tr>
                            </table>
                            <p style='margin-top: 20px;'>Jadwal gedung untuk rentang tanggal kegiatan tersebut kini telah dibebaskan kembali dan dapat dipesan oleh pengguna lain.</p>
                            <hr style='border: none; border-top: 1px solid #edf2f7; margin: 30px 0;'>
                            <p style='font-size: 11px; color: #a0aec0;'>Email ini dikirim secara otomatis oleh Sistem Informasi Penyewaan Aset Kampus (SIPAK) Politeknik Aceh.</p>
                        </div>
                    ";
                    send_mail(SMTP_USER, $subject_admin, $body_admin);
                }

                // Add notification to admin (Sistem internal)
                add_notification($pdo, null, 1, 'Pemesanan Dibatalkan', "Penyewa membatalkan pemesanan pending " . $transaksi['kode_transaksi'] . " secara mandiri.", "admin/booking_kelola.php");

                set_flash('warning', 'Pemesanan gedung berhasil dibatalkan.');
            } else {
                set_flash('danger', 'Hanya pemesanan dengan status Menunggu Pembayaran yang dapat dibatalkan secara mandiri.');
            }
        } else {
            set_flash('danger', 'Data transaksi tidak ditemukan.');
        }
    } else {
        set_flash('danger', 'ID Transaksi tidak valid.');
    }
} else {
    set_flash('danger', 'Metode request tidak diizinkan.');
}

header('Location: riwayat_booking.php');
exit;
