<?php
// SIIPAK - Booking Processor & Overbooking Prevention
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

check_penyewa();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

$id_penyewa        = $_SESSION['user_id'];
$id_gedung         = (int)($_POST['id_gedung'] ?? 0);
$nama_kegiatan     = sanitize($_POST['nama_kegiatan'] ?? '');
$deskripsi         = sanitize($_POST['deskripsi_kegiatan'] ?? '');
$tanggal_mulai     = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai   = $_POST['tanggal_selesai'] ?? '';

if (empty($id_gedung) || empty($nama_kegiatan) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
    set_flash('danger', 'Semua bidang bertanda bintang (*) wajib diisi.');
    header("Location: booking.php?id_gedung=$id_gedung");
    exit;
}

if ($tanggal_selesai < $tanggal_mulai) {
    set_flash('danger', 'Tanggal selesai acara tidak boleh lebih awal dari tanggal mulai.');
    header("Location: booking.php?id_gedung=$id_gedung");
    exit;
}

if ($tanggal_mulai < date('Y-m-d')) {
    set_flash('danger', 'Tanggal mulai acara tidak boleh di masa lalu (sebelum hari ini).');
    header("Location: booking.php?id_gedung=$id_gedung");
    exit;
}

// 1. Algoritma Penguncian & Pengecekan Bentrok Jadwal Real-Time
if (cek_bentrok_jadwal($pdo, $id_gedung, $tanggal_mulai, $tanggal_selesai)) {
    set_flash('danger', 'OVERBOOKING PREVENTION: Tanggal ' . format_tanggal($tanggal_mulai) . ' s/d ' . format_tanggal($tanggal_selesai) . ' sudah terisi atau dikunci untuk kegiatan lain di gedung ini. Silakan pilih rentang tanggal lain.');
    header("Location: booking.php?id_gedung=$id_gedung");
    exit;
}

// 2. Hitung Total Pembayaran Berdasarkan Durasi Hari & Sewa Aset Tambahan
$stmt_gedung = $pdo->prepare("SELECT * FROM gedung WHERE id_gedung = :id");
$stmt_gedung->execute([':id' => $id_gedung]);
$gedung = $stmt_gedung->fetch();

if (!$gedung) {
    set_flash('danger', 'Gedung tidak ditemukan.');
    header('Location: gedung.php');
    exit;
}

$d1 = new DateTime($tanggal_mulai);
$d2 = new DateTime($tanggal_selesai);
$durasi_hari = $d2->diff($d1)->days + 1; // Minimal 1 hari

$total = $gedung['harga_sewa'] * $durasi_hari;

// Tambahkan harga sewa aset jika dipilih
$selected_assets = [];
if (!empty($_POST['assets']) && is_array($_POST['assets'])) {
    foreach ($_POST['assets'] as $asset_id => $asset_data) {
        if (!empty($asset_data['selected'])) {
            $qty = !empty($asset_data['jumlah']) ? (int)$asset_data['jumlah'] : 1;
            $selected_assets[(int)$asset_id] = $qty;
        }
    }
}

if (!empty($selected_assets)) {
    $stmt_aset = $pdo->prepare("SELECT * FROM aset WHERE id_aset = :id");
    foreach ($selected_assets as $asset_id => $qty) {
        $stmt_aset->execute([':id' => $asset_id]);
        $aset = $stmt_aset->fetch();
        if ($aset) {
            // Validasi jumlah unit agar tidak melebihi stok yang tersedia
            if ($qty < 1) {
                $qty = 1;
                $selected_assets[$asset_id] = 1;
            } elseif ($qty > $aset['jumlah']) {
                $qty = $aset['jumlah'];
                $selected_assets[$asset_id] = $aset['jumlah'];
            }
            $total += ($aset['harga_sewa_tambahan'] * $qty * $durasi_hari);
        }
    }
}

// 3. Upload Kartu Identitas (KTP/KTM/Instansi)
$foto_identitas = '';

// Check if image is captured via webcam (base64)
if (isset($_POST['captured_image']) && !empty($_POST['captured_image'])) {
    $base64_img = $_POST['captured_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_img, $type)) {
        $data = substr($base64_img, strpos($base64_img, ',') + 1);
        $type = strtolower($type[1]); // png, jpg, jpeg

        if (!in_array($type, ['png', 'jpg', 'jpeg'])) {
            set_flash('danger', 'Format foto kamera tidak didukung.');
            header("Location: booking.php?id_gedung=$id_gedung");
            exit;
        }

        $data = base64_decode($data);
        if ($data === false) {
            set_flash('danger', 'Gagal memproses foto kamera.');
            header("Location: booking.php?id_gedung=$id_gedung");
            exit;
        }

        $new_name = 'identitas_' . time() . '_' . rand(1000, 9999) . '.' . $type;
        if (file_put_contents('assets/uploads/' . $new_name, $data) === false) {
            set_flash('danger', 'Gagal menyimpan foto kamera ke server.');
            header("Location: booking.php?id_gedung=$id_gedung");
            exit;
        }
        $foto_identitas = $new_name;
    } else {
        set_flash('danger', 'Data foto kamera tidak valid.');
        header("Location: booking.php?id_gedung=$id_gedung");
        exit;
    }
} else {
    // Normal file upload
    if (!isset($_FILES['foto_identitas']) || $_FILES['foto_identitas']['error'] !== UPLOAD_ERR_OK) {
        set_flash('danger', 'Anda wajib mengunggah foto kartu identitas (KTP/KTM/Instansi) untuk verifikasi legalitas.');
        header("Location: booking.php?id_gedung=$id_gedung");
        exit;
    }

    $file = $_FILES['foto_identitas'];
    $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_exts)) {
        set_flash('danger', 'Format file identitas tidak didukung. Silakan gunakan format JPG, PNG, atau PDF.');
        header("Location: booking.php?id_gedung=$id_gedung");
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        set_flash('danger', 'Ukuran file identitas terlalu besar. Maksimal 2MB.');
        header("Location: booking.php?id_gedung=$id_gedung");
        exit;
    }

    $new_name = 'identitas_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
    if (!move_uploaded_file($file['tmp_name'], 'assets/uploads/' . $new_name)) {
        set_flash('danger', 'Gagal mengunggah file identitas ke server. Silakan periksa izin folder assets/uploads.');
        header("Location: booking.php?id_gedung=$id_gedung");
        exit;
    }
    $foto_identitas = $new_name;
}

// 4. Simpan Transaksi Baru dengan Status 'Menunggu Pembayaran'
$kode_transaksi = generate_kode_transaksi($pdo);
$token_kuitansi = bin2hex(random_bytes(16));

// Setup legacy fallback columns
$first_asset_id = !empty($selected_assets) ? array_key_first($selected_assets) : null;
$first_asset_qty = $first_asset_id ? $selected_assets[$first_asset_id] : 1;

$stmt = $pdo->prepare("INSERT INTO transaksi 
    (kode_transaksi, token_kuitansi, id_penyewa, id_gedung, id_aset, jumlah_aset, nama_kegiatan, deskripsi_kegiatan, tanggal_mulai, tanggal_selesai, total_pembayaran, status_transaksi, foto_identitas) 
    VALUES (:kode, :token_kuitansi, :id_penyewa, :id_gedung, :id_aset, :jumlah_aset, :nama_kegiatan, :deskripsi, :tgl_mulai, :tgl_selesai, :total, 'Menunggu Pembayaran', :foto_id)");

$stmt->execute([
    ':kode'           => $kode_transaksi,
    ':token_kuitansi' => $token_kuitansi,
    ':id_penyewa'      => $id_penyewa,
    ':id_gedung'       => $id_gedung,
    ':id_aset'         => $first_asset_id,
    ':jumlah_aset'     => $first_asset_qty,
    ':nama_kegiatan'   => $nama_kegiatan,
    ':deskripsi'       => $deskripsi,
    ':tgl_mulai'       => $tanggal_mulai,
    ':tgl_selesai'     => $tanggal_selesai,
    ':total'           => $total,
    ':foto_id'         => $foto_identitas
]);

$id_transaksi = $pdo->lastInsertId();

// Simpan detail semua aset tambahan terpilih ke tabel transaksi_aset
if (!empty($selected_assets)) {
    $stmt_ins_link = $pdo->prepare("INSERT INTO transaksi_aset (id_transaksi, id_aset, jumlah_aset) VALUES (:id_trx, :id_aset, :jumlah)");
    foreach ($selected_assets as $asset_id => $qty) {
        $stmt_ins_link->execute([
            ':id_trx'  => $id_transaksi,
            ':id_aset' => $asset_id,
            ':jumlah'  => $qty
        ]);
    }
}

// Ambil email & nama penyewa untuk notifikasi
$stmt_user = $pdo->prepare("SELECT email, nama FROM penyewa WHERE id_penyewa = :id");
$stmt_user->execute([':id' => $id_penyewa]);
$user_info = $stmt_user->fetch();
$email_penyewa = $user_info['email'] ?? '';
$nama_penyewa = $user_info['nama'] ?? '';

// Kirim email notifikasi ke penyewa & admin
if (!empty($email_penyewa)) {
    require_once 'config/email.php';
    
    // Email untuk Penyewa
    $subject_penyewa = "Pemesanan Gedung Berhasil Dibuat - Kode $kode_transaksi";
    $body_penyewa = "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
            <h2 style='color: #000e3a; border-bottom: 2px solid #fecb00; padding-bottom: 10px;'>Pemesanan Gedung Berhasil</h2>
            <p>Halo <strong>$nama_penyewa</strong>,</p>
            <p>Permohonan pemesanan Anda untuk kegiatan <strong>\"$nama_kegiatan\"</strong> telah berhasil dibuat dalam sistem SIPAK.</p>
            <p>Rincian Pemesanan:</p>
            <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold; width: 150px;'>Kode Sewa</td>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'>$kode_transaksi</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold;'>Pelaksanaan</td>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'>$tanggal_mulai s/d $tanggal_selesai</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold;'>Total Tagihan</td>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'><strong>" . format_rupiah($total) . "</strong></td>
                </tr>
            </table>
            <p style='margin-top: 20px;'>Silakan lakukan pengunggahan bukti transfer DP atau Pelunasan pada portal pembayaran berikut untuk mengonfirmasi pesanan Anda:</p>
            <p><a href='" . ($_SERVER['REQUEST_SCHEME'] ?? 'http') . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['PHP_SELF']) . "/pembayaran_upload.php?id_transaksi=$id_transaksi' style='background-color: #000e3a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Unggah Bukti Transfer</a></p>
            <hr style='border: none; border-top: 1px solid #edf2f7; margin: 30px 0;'>
            <p style='font-size: 11px; color: #a0aec0;'>Email ini dikirim secara otomatis oleh Sistem Informasi Penyewaan Aset Kampus (SIPAK) Politeknik Aceh.</p>
        </div>
    ";
    send_mail($email_penyewa, $subject_penyewa, $body_penyewa);

    // Email untuk Admin
    $subject_admin = "[SIPAK] Pemesanan Baru Masuk - Kode $kode_transaksi";
    $body_admin = "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
            <h2 style='color: #000e3a; border-bottom: 2px solid #fecb00; padding-bottom: 10px;'>Pemesanan Baru Masuk</h2>
            <p>Halo Admin,</p>
            <p>Penyewa <strong>$nama_penyewa</strong> ($email_penyewa) telah membuat permohonan pemesanan baru di sistem SIPAK.</p>
            <p>Rincian Kegiatan:</p>
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
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold;'>Pelaksanaan</td>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'>$tanggal_mulai s/d $tanggal_selesai</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7; font-weight: bold;'>Total Tagihan</td>
                    <td style='padding: 8px; border-bottom: 1px solid #edf2f7;'><strong>" . format_rupiah($total) . "</strong></td>
                </tr>
            </table>
            <p style='margin-top: 20px;'>Silakan pantau dan validasi pembayaran setelah bukti transfer diunggah oleh penyewa.</p>
            <hr style='border: none; border-top: 1px solid #edf2f7; margin: 30px 0;'>
            <p style='font-size: 11px; color: #a0aec0;'>Email ini dikirim secara otomatis oleh Sistem Informasi Penyewaan Aset Kampus (SIPAK) Politeknik Aceh.</p>
        </div>
    ";
    send_mail(SMTP_USER, $subject_admin, $body_admin);
}

// Notifikasi ke Admin (Sistem internal)
add_notification($pdo, null, 1, 'Pemesanan Baru Masuk', "Penyewa " . $_SESSION['user_name'] . " melakukan booking untuk $nama_kegiatan ($kode_transaksi).", "admin/pembayaran_validasi.php");

set_flash('success', 'Permohonan booking berhasil dibuat! Silakan lakukan pengunggahan bukti transfer DP atau Pelunasan.');
header("Location: pembayaran_upload.php?id_transaksi=$id_transaksi");
exit;
