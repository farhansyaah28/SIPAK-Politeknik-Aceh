<?php
// SIIPAK - Database Connection Configuration
// Politeknik Aceh Building & Asset Rental System

// Auto-detect environment: Localhost (XAMPP) vs Remote Host (InfinityFree)
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['SERVER_NAME'] === 'localhost';

if ($is_localhost) {
    // Localhost XAMPP Settings
    $host = '127.0.0.1';
    $db   = 'siipak';
    $user = 'root';
    $pass = '';
} else {
    // InfinityFree Remote Database Settings
    // TODO: Sesuaikan dengan kredensial dari Control Panel InfinityFree Anda
    $host = 'sql207.infinityfree.com'; // Cari "MySQL Hostname" di InfinityFree Client Area
    $db   = 'if0_42559580_siipak';     // Cari "MySQL Database Name" yang Anda buat
    $user = 'if0_42559580';            // Cari "MySQL Username" di InfinityFree
    $pass = 'E8f217mX8bW5I6';  // Cari "MySQL Password" di InfinityFree (bisa dilihat di Client Area)
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Set Timezone globally to Asia/Jakarta (UTC+7)
    date_default_timezone_set('Asia/Jakarta');
    $pdo->exec("SET time_zone = '+07:00'");
    
    // Self-healing database migration for jumlah_aset column
    try {
        $pdo->query("SELECT jumlah_aset FROM transaksi LIMIT 1");
    } catch (\PDOException $ex) {
        $pdo->exec("ALTER TABLE transaksi ADD COLUMN jumlah_aset INT DEFAULT 1");
    }

    // Self-healing database migration for transaksi_aset table (Multi-Aset support)
    try {
        $pdo->query("SELECT id_transaksi_aset FROM transaksi_aset LIMIT 1");
    } catch (\PDOException $ex) {
        // Create transaksi_aset table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi_aset` (
          `id_transaksi_aset` INT AUTO_INCREMENT PRIMARY KEY,
          `id_transaksi` INT NOT NULL,
          `id_aset` INT NOT NULL,
          `jumlah_aset` INT DEFAULT 1,
          FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
          FOREIGN KEY (`id_aset`) REFERENCES `aset` (`id_aset`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Migrate existing asset selections to the new table
        try {
            $pdo->exec("INSERT INTO `transaksi_aset` (id_transaksi, id_aset, jumlah_aset)
                        SELECT id_transaksi, id_aset, jumlah_aset 
                        FROM transaksi 
                        WHERE id_aset IS NOT NULL");
        } catch (\PDOException $mig_ex) {
            // Ignore if columns do not exist
        }
    }
    
    // Self-healing database migration for foto_identitas column
    try {
        $pdo->query("SELECT foto_identitas FROM transaksi LIMIT 1");
    } catch (\PDOException $ex) {
        $pdo->exec("ALTER TABLE transaksi ADD COLUMN foto_identitas VARCHAR(255) DEFAULT NULL");
    }

    // Self-healing database migration for token_kuitansi column
    try {
        $pdo->query("SELECT token_kuitansi FROM transaksi LIMIT 1");
    } catch (\PDOException $ex) {
        $pdo->exec("ALTER TABLE transaksi ADD COLUMN token_kuitansi VARCHAR(64) UNIQUE DEFAULT NULL");
        // Populate existing rows with random tokens
        $stmt_all = $pdo->query("SELECT id_transaksi FROM transaksi");
        $rows = $stmt_all->fetchAll();
        $stmt_upd = $pdo->prepare("UPDATE transaksi SET token_kuitansi = :token WHERE id_transaksi = :id");
        foreach ($rows as $row) {
            $random_token = bin2hex(random_bytes(16));
            $stmt_upd->execute([':token' => $random_token, ':id' => $row['id_transaksi']]);
        }
    }

    // Self-healing database migration to update Admin full name
    try {
        $pdo->exec("UPDATE `admin` SET nama_lengkap = 'Administrator' WHERE username = 'admin' AND nama_lengkap = 'Administrator Pengelola'");
    } catch (\PDOException $ex) {
        // Ignore if fails
    }

    // Self-healing database migration to update Pimpinan full name
    try {
        $pdo->exec("UPDATE `admin` SET nama_lengkap = 'Direktur Poltek Aceh' WHERE username = 'pimpinan' AND nama_lengkap = 'Direktur Politeknik Aceh'");
    } catch (\PDOException $ex) {
        // Ignore if fails
    }
} catch (\PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
