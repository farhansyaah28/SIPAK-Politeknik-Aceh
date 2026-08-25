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
        // Fetch transaction and verify ownership & status
        $stmt = $pdo->prepare("SELECT status_transaksi, kode_transaksi FROM transaksi WHERE id_transaksi = :id AND id_penyewa = :id_penyewa");
        $stmt->execute([':id' => $id_transaksi, ':id_penyewa' => $id_penyewa]);
        $transaksi = $stmt->fetch();

        if ($transaksi) {
            if ($transaksi['status_transaksi'] === 'Menunggu Pembayaran') {
                // Update transaction status to Dibatalkan
                $stmt_upd = $pdo->prepare("UPDATE transaksi SET status_transaksi = 'Dibatalkan' WHERE id_transaksi = :id");
                $stmt_upd->execute([':id' => $id_transaksi]);

                // Add notification to admin
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
