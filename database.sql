-- SIIPAK - Database Schema & Initial Seed Data
-- Sistem Informasi Penyewaan Gedung dan Aset Kampus Politeknik Aceh

CREATE DATABASE IF NOT EXISTS `siipak` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `siipak`;

-- --------------------------------------------------------
-- 1. Tabel Admin & Pimpinan
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id_admin` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('admin', 'pimpinan') NOT NULL DEFAULT 'admin',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Hash for 'admin123' and 'pimpinan123'
INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_lengkap`, `email`, `role`) VALUES
(1, 'admin', '$2y$10$zRTX6cAYzowG7Ob/6fRDYu1IAhn1Yq70n9YWcTBzPI6IGXVbgqUoG', 'Administrator', 'admin@politeknikaceh.ac.id', 'admin'),
(2, 'pimpinan', '$2y$10$Qxi48qrgkn5wr1IdyWHCrOfBS/UEL3GM/sJmaB6P/fu7DU2p3422e', 'Direktur Poltek Aceh', 'pimpinan@politeknikaceh.ac.id', 'pimpinan');

-- --------------------------------------------------------
-- 2. Tabel Penyewa
-- --------------------------------------------------------
DROP TABLE IF EXISTS `penyewa`;
CREATE TABLE `penyewa` (
  `id_penyewa` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `instansi` VARCHAR(100) DEFAULT 'Perorangan',
  `alamat` TEXT NOT NULL,
  `no_telepon` VARCHAR(20) NOT NULL,
  `status_akun` ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `penyewa` (`id_penyewa`, `email`, `password`, `nama`, `instansi`, `alamat`, `no_telepon`, `status_akun`) VALUES
(1, 'penyewa@gmail.com', '$2y$10$8vFJR0ssNaZ5aZAbBc4HBOemtymL9S6nurg96VmBPh0NyOWsB7NTO', 'Budi Santoso', 'PT Aceh Multimedia', 'Jl. T. Nyak Arief No. 12, Banda Aceh', '081269001122', 'Aktif'),
(2, 'sarah@gmail.com', '$2y$10$8vFJR0ssNaZ5aZAbBc4HBOemtymL9S6nurg96VmBPh0NyOWsB7NTO', 'Sarah Amalia', 'EO Sahabat Events', 'Jl. Sultan Iskandar Muda No. 45, Lhoknga', '085277889900', 'Aktif');

-- --------------------------------------------------------
-- 3. Tabel Gedung
-- --------------------------------------------------------
DROP TABLE IF EXISTS `gedung`;
CREATE TABLE `gedung` (
  `id_gedung` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_gedung` VARCHAR(100) NOT NULL,
  `harga_sewa` DECIMAL(12,2) NOT NULL,
  `fasilitas` TEXT NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `kapasitas` INT DEFAULT 100,
  `foto` VARCHAR(255) DEFAULT 'default_gedung.jpg',
  `status` ENUM('Tersedia', 'Perbaikan', 'Tidak Aktif') DEFAULT 'Tersedia',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `gedung` (`id_gedung`, `nama_gedung`, `harga_sewa`, `fasilitas`, `deskripsi`, `kapasitas`, `foto`, `status`) VALUES
(1, 'Auditorium Utama Politeknik Aceh', 3500000.00, 'AC Central, Sound System Professional, panggung 10x4m, 300 Kursi Futura, Projector 5000 Lumens, Ruang Transit VIP', 'Gedung serbaguna termewah cocok untuk acara resepsi pernikahan, seminar internasional, wisuda, dan pameran.', 500, 'auditorium.jpg', 'Tersedia'),
(2, 'Hall Gedung Utama', 2000000.00, 'Sound System Standard, 150 Kursi, Lighting Panggung, AC Split 4 Unit', 'Ruangan aula luas ideal untuk seminar nasional, workshop, expo produk, dan rapat umum.', 250, 'hall_utama.jpg', 'Tersedia'),
(3, 'Ruang Rapat Amphi Theater', 1200000.00, 'Kursi Amphi bertingkat, Full AC, Smart TV 85 inch, Sound System, Wi-Fi High-Speed', 'Ruangan model amphitheater bertingkat sangat representative untuk presentasi bisnis, rapat instansi, dan pelatihan executive.', 80, 'amphi_theater.jpg', 'Tersedia'),
(4, 'Laboratorium Komputer Multi-Purpose', 1500000.00, '40 Unit PC Core i7, Full AC, High Speed LAN/Wi-Fi, Projector Dual Screen', 'Laboratorium komputer canggih cocok untuk ujian online, sertifikasi profesi, dan bimbingan teknis.', 45, 'lab_komputer.jpg', 'Tersedia');

-- --------------------------------------------------------
-- 4. Tabel Aset
-- --------------------------------------------------------
DROP TABLE IF EXISTS `aset`;
CREATE TABLE `aset` (
  `id_aset` INT AUTO_INCREMENT PRIMARY KEY,
  `id_gedung` INT NOT NULL,
  `nama_aset` VARCHAR(100) NOT NULL,
  `kondisi_aset` ENUM('Bagus', 'Rusak Ringan', 'Rusak Berat') DEFAULT 'Bagus',
  `jumlah` INT NOT NULL DEFAULT 1,
  `harga_sewa_tambahan` DECIMAL(12,2) DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_gedung`) REFERENCES `gedung` (`id_gedung`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `aset` (`id_aset`, `id_gedung`, `nama_aset`, `kondisi_aset`, `jumlah`, `harga_sewa_tambahan`) VALUES
(1, 1, 'Kursi Futura Cover Busa', 'Bagus', 300, 5000.00),
(2, 1, 'Mixer Wireless Digital & Mic Clip-on', 'Bagus', 4, 150000.00),
(3, 1, 'Projector High Lumens 5000 ANSI', 'Bagus', 2, 250000.00),
(4, 2, 'Sound System Portable Active 15 inch', 'Bagus', 2, 100000.00),
(5, 2, 'Meja Registrasi & Taplak', 'Bagus', 10, 15000.00),
(6, 3, 'Smart Board Interactive 75 Inch', 'Bagus', 1, 300000.00);

-- --------------------------------------------------------
-- 5. Tabel Transaksi
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE `transaksi` (
  `id_transaksi` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_transaksi` VARCHAR(30) NOT NULL UNIQUE,
  `token_kuitansi` VARCHAR(64) UNIQUE DEFAULT NULL,
  `id_penyewa` INT NOT NULL,
  `id_gedung` INT NOT NULL,
  `id_aset` INT DEFAULT NULL,
  `jumlah_aset` INT DEFAULT 1,
  `id_admin` INT DEFAULT NULL,
  `nama_kegiatan` VARCHAR(150) NOT NULL,
  `deskripsi_kegiatan` TEXT DEFAULT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `total_pembayaran` DECIMAL(12,2) NOT NULL,
  `status_transaksi` ENUM('Menunggu Pembayaran', 'DP', 'Cicilan', 'Lunas', 'Ditolak', 'Dibatalkan') DEFAULT 'Menunggu Pembayaran',
  `catatan` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`) ON DELETE CASCADE,
  FOREIGN KEY (`id_gedung`) REFERENCES `gedung` (`id_gedung`) ON DELETE CASCADE,
  FOREIGN KEY (`id_aset`) REFERENCES `aset` (`id_aset`) ON DELETE SET NULL,
  FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `transaksi` (`id_transaksi`, `kode_transaksi`, `token_kuitansi`, `id_penyewa`, `id_gedung`, `id_aset`, `id_admin`, `nama_kegiatan`, `deskripsi_kegiatan`, `tanggal_mulai`, `tanggal_selesai`, `total_pembayaran`, `status_transaksi`) VALUES
(1, 'TRX-20260801-001', 'a8f9c12b4e5f6d7c8b9a0123456789ab', 1, 1, 1, 1, 'Seminar Nasional Teknologi AI & IoT 2026', 'Seminar dan pameran hasil karya inovasi mahasiswa dan industri.', '2026-08-15', '2026-08-16', 3500000.00, 'Lunas'),
(2, 'TRX-20260801-002', 'b9f0d23c5f6g7h8d9c0b1234567890cd', 2, 2, 4, 1, 'Resepsi Pernikahan Sarah & Rizky', 'Acara resepsi pernikahan dengan undangan 200 orang.', '2026-08-20', '2026-08-20', 2000000.00, 'DP');

-- --------------------------------------------------------
-- 6. Tabel Pembayaran (Rincian Termin / Bertahap)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pembayaran`;
CREATE TABLE `pembayaran` (
  `id_pembayaran` INT AUTO_INCREMENT PRIMARY KEY,
  `id_transaksi` INT NOT NULL,
  `jumlah_bayar` DECIMAL(12,2) NOT NULL,
  `jenis_pembayaran` ENUM('DP', 'Cicilan', 'Pelunasan') NOT NULL DEFAULT 'DP',
  `bukti_transfer` VARCHAR(255) NOT NULL,
  `tanggal_bayar` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status_validasi` ENUM('Menunggu', 'Valid', 'Ditolak') DEFAULT 'Menunggu',
  `catatan_admin` TEXT DEFAULT NULL,
  `id_admin_validator` INT DEFAULT NULL,
  FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  FOREIGN KEY (`id_admin_validator`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pembayaran` (`id_pembayaran`, `id_transaksi`, `jumlah_bayar`, `jenis_pembayaran`, `bukti_transfer`, `tanggal_bayar`, `status_validasi`, `catatan_admin`, `id_admin_validator`) VALUES
(1, 1, 3500000.00, 'Pelunasan', 'bukti_trx1.jpg', '2026-08-01 09:30:00', 'Valid', 'Pembayaran lunas terverifikasi sesuai mutasi bank.', 1),
(2, 2, 1000000.00, 'DP', 'bukti_trx2.jpg', '2026-08-01 10:00:00', 'Valid', 'DP 50% berhasil diterima.', 1);

-- --------------------------------------------------------
-- 7. Tabel Notifikasi
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id_notifikasi` INT AUTO_INCREMENT PRIMARY KEY,
  `id_penyewa` INT DEFAULT NULL,
  `id_admin` INT DEFAULT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `status_baca` ENUM('Belum Dibaca', 'Sudah Dibaca') DEFAULT 'Belum Dibaca',
  `link_url` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `notifikasi` (`id_notifikasi`, `id_penyewa`, `judul`, `pesan`, `link_url`) VALUES
(1, 1, 'Pembayaran Diverifikasi', 'Pembayaran transaksi TRX-20260801-001 senilai Rp 3.500.000 telah disetujui (Lunas). Jadwal gedung telah dikunci.', 'riwayat_booking.php'),
(2, 2, 'DP Diterima', 'DP sebesar Rp 1.000.000 untuk transaksi TRX-20260801-002 telah dikonfirmasi. Sisa pembayaran Rp 1.000.000.', 'riwayat_booking.php');

-- --------------------------------------------------------
-- 8. Tabel Penghubung Transaksi & Aset Tambahan (Multi-Aset)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transaksi_aset`;
CREATE TABLE `transaksi_aset` (
  `id_transaksi_aset` INT AUTO_INCREMENT PRIMARY KEY,
  `id_transaksi` INT NOT NULL,
  `id_aset` INT NOT NULL,
  `jumlah_aset` INT DEFAULT 1,
  FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  FOREIGN KEY (`id_aset`) REFERENCES `aset` (`id_aset`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default asset assignments based on migrated transactions
INSERT INTO `transaksi_aset` (`id_transaksi`, `id_aset`, `jumlah_aset`) VALUES
(1, 1, 1),
(2, 4, 1);

