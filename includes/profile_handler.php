<?php
// SIIPAK - Profile Edit Handler for Renter Pop-up
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_modal'])) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/functions.php';
    
    $id_penyewa = $_SESSION['user_id'] ?? 0;
    $nama       = sanitize($_POST['nama'] ?? '');
    $email      = sanitize($_POST['email'] ?? '');
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $instansi   = sanitize($_POST['instansi'] ?? 'Perorangan');
    $alamat     = sanitize($_POST['alamat'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($nama) || empty($email) || empty($no_telepon) || empty($alamat)) {
        set_flash('danger', 'Nama, Email, No. Telepon, dan Alamat wajib diisi.');
    } else {
        // Cek apakah email sudah terdaftar oleh pengguna lain
        $stmt_email = $pdo->prepare("SELECT COUNT(*) FROM penyewa WHERE email = :email AND id_penyewa != :id");
        $stmt_email->execute([':email' => $email, ':id' => $id_penyewa]);
        if ($stmt_email->fetchColumn() > 0) {
            set_flash('danger', 'Email ini sudah terdaftar oleh pengguna lain.');
        } else {
            // Update profile
            $stmt_upd = $pdo->prepare("UPDATE penyewa SET nama = :nama, email = :email, no_telepon = :telp, instansi = :instansi, alamat = :alamat WHERE id_penyewa = :id");
            $stmt_upd->execute([
                ':nama'     => $nama,
                ':email'    => $email,
                ':telp'     => $no_telepon,
                ':instansi' => $instansi,
                ':alamat'   => $alamat,
                ':id'       => $id_penyewa
            ]);

            // Update password if filled
            if (!empty($password)) {
                $hash_pass = password_hash($password, PASSWORD_BCRYPT);
                $stmt_pass = $pdo->prepare("UPDATE penyewa SET password = :pass WHERE id_penyewa = :id");
                $stmt_pass->execute([':pass' => $hash_pass, ':id' => $id_penyewa]);
            }

            $_SESSION['user_name'] = $nama;
            set_flash('success', 'Profil Anda berhasil diperbarui.');
            
            // Redirect back to current URI
            $redirect_uri = $_SERVER['REQUEST_URI'];
            header("Location: $redirect_uri");
            exit;
        }
    }
}
?>
