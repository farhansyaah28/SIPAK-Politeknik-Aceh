# PRD — Sistem Informasi Penyewaan Gedung dan Aset Kampus Politeknik Aceh

**Pemilik:** Risa Rahma Sari
**Program Studi:** Diploma III Teknologi Informasi — Politeknik Aceh
**Tanggal:** 1 Agustus 2026
**Status:** Draft
**Terkait dengan:** Proyek Akhir — Rancang Bangun Sistem Informasi Penyewaan Gedung dan Aset Kampus Politeknik Aceh

---

## 1. Latar Belakang & Pernyataan Masalah

Politeknik Aceh memiliki gedung utama dan aset kampus yang, selain digunakan untuk kegiatan akademik, juga disewakan kepada pihak luar untuk kegiatan seperti seminar, pelatihan, rapat, hingga resepsi pernikahan. Saat ini proses penyewaan masih dilakukan secara konvensional (pencatatan manual dan Microsoft Excel), sehingga menimbulkan beberapa masalah:

- Calon penyewa harus datang langsung atau menghubungi pengelola untuk mengetahui ketersediaan jadwal gedung.
- Tidak ada sinkronisasi data jadwal secara real-time, sehingga berisiko terjadi **overbooking**.
- Pencatatan transaksi manual meningkatkan risiko **kehilangan data** dan **kesalahan administrasi**.
- Pemantauan status pembayaran (DP, cicilan, lunas) tidak terpusat, sehingga pembuatan laporan keuangan menjadi lambat dan kurang akurat.
- Pimpinan institusi tidak memiliki akses cepat ke laporan rekapitulasi penggunaan aset dan aliran dana untuk pengambilan keputusan.

**Solusi yang diusulkan:** sistem informasi penyewaan gedung dan aset kampus berbasis web yang menyediakan kalender ketersediaan real-time, penguncian jadwal otomatis, validasi pembayaran bertahap (termin), dan pelaporan rekapitulasi otomatis.

---

## 2. Tujuan

1. Mempercepat proses administrasi penyewaan secara digital tanpa mengharuskan penyewa berkunjung langsung ke kampus.
2. Meminimalkan *human error* dalam penjadwalan sehingga *double booking* dapat dicegah secara sistematis.
3. Menghasilkan pencatatan keuangan yang terstruktur dan akuntabel untuk mendukung skema pembayaran bertahap (termin).
4. Menyediakan laporan rekapitulasi otomatis sebagai instrumen pertanggungjawaban dan transparansi dana bulanan kepada pimpinan Politeknik Aceh.

**Manfaat utama:**
- **Pimpinan** — transparansi penuh atas frekuensi pemakaian aset dan aliran dana bulanan.
- **Admin/Pengelola** — beban kerja pemantauan pembayaran dan jadwal berkurang; laporan keuangan dibuat otomatis.
- **Penyewa** — bisa cek jadwal, booking, dan bayar bertahap (unggah bukti transfer) tanpa datang ke kampus.

---

## 3. Ruang Lingkup

### 3.1 Peran Pengguna (Hak Akses)

| Peran | Hak Akses |
|---|---|
| **Pimpinan** | Hanya dapat mengakses laporan rekapitulasi keuangan (read-only) |
| **Admin/Pengelola** | Mengelola data gedung, aset, penyewa; memvalidasi pembayaran; mengelola jadwal; mencetak laporan |
| **Penyewa** | Registrasi/login, melihat kalender ketersediaan, melakukan booking, mengunggah bukti pembayaran, memantau status transaksi sendiri |

### 3.2 Termasuk dalam Ruang Lingkup (In-Scope)

- Autentikasi & manajemen tiga peran pengguna (Pimpinan, Admin, Penyewa).
- Kalender ketersediaan jadwal gedung secara real-time, dengan penguncian tanggal otomatis saat pembayaran lunas.
- Deteksi bentrok jadwal berbasis tanggal mulai–selesai dari data booking di database.
- Pemesanan (booking) gedung dan aset oleh penyewa.
- Modul pembayaran manual bertahap (DP, cicilan, pelunasan) dengan unggah bukti transfer dan validasi oleh Admin.
- Dashboard monitoring Admin: data penyewa, jadwal kegiatan, status fasilitas.
- Manajemen data master: Gedung, Aset, Penyewa.
- Laporan rekapitulasi bulanan otomatis (jumlah kegiatan, total dana masuk), dapat dicetak/ekspor.
- Notifikasi status booking/pembayaran ke penyewa dan admin (dalam sistem).

### 3.3 Tidak Termasuk dalam Ruang Lingkup (Out-of-Scope)

- Integrasi *payment gateway* otomatis — pembayaran dilakukan manual via transfer bank, divalidasi manual oleh Admin dari bukti transfer yang diunggah.
- Modul di luar penyewaan gedung/aset untuk kegiatan umum dan resepsi pernikahan (mis. penyewaan kendaraan, ruang kelas reguler akademik).
- Aplikasi mobile native (fokus web-based).

### 3.4 Asumsi & Batasan

- Dibangun dengan bahasa pemrograman **PHP**, basis data **MySQL**, dan kerangka antarmuka **Bootstrap**.
- Arsitektur **client-server**: Browser (client) → PHP (server) → MySQL (database).
- Validasi bukti pembayaran dilakukan **manual** oleh Admin berdasarkan foto/file bukti transfer yang diunggah penyewa.
- Metode pengembangan: **Waterfall** (analisis kebutuhan → perancangan → implementasi → pengujian → pemeliharaan).
- Pengujian menggunakan **Black Box Testing** (fungsional) dan **UAT/kuesioner pengguna**.

---

## 4. Metrik Keberhasilan

| Metrik | Target | Cara Mengukur |
|---|---|---|
| Insiden overbooking / bentrok jadwal | 0 kasus setelah sistem berjalan | Audit data booking vs. keluhan jadwal bentrok |
| Waktu proses administrasi booking | Berkurang signifikan dibanding proses manual (tanpa kunjungan langsung) | Perbandingan alur sebelum vs. sesudah sistem |
| Akurasi status pembayaran (DP/cicilan/lunas) | 100% status transaksi tercatat sesuai bukti transfer tervalidasi | Rekonsiliasi data transaksi vs. mutasi rekening |
| Waktu pembuatan laporan rekapitulasi bulanan | Otomatis, tersedia real-time (bukan manual rekap Excel) | Uji fungsional fitur laporan |
| Hasil pengujian fungsional (Black Box) | Semua fitur inti (login, booking, validasi, laporan) lulus tanpa bug kritis | Hasil pengujian Black Box Testing |
| Kepuasan pengguna (UAT) | Skor positif pada kuesioner UAT (Admin & Penyewa) | Kuesioner User Acceptance Test |

---

## 5. Alur Proses Bisnis (Ringkas)

1. Penyewa login/registrasi → melihat kalender ketersediaan gedung.
2. Jika jadwal tersedia → penyewa mengisi form booking → sistem menyimpan dengan status **"Menunggu Pembayaran"**.
3. Penyewa transfer manual → mengunggah bukti bayar.
4. Sistem mengirim notifikasi ke Admin → Admin memverifikasi bukti transfer.
   - Bukti **valid** → status diubah menjadi **"Lunas"/sesuai termin** → jadwal dikunci otomatis.
   - Bukti **tidak valid** → status **"Ditolak"** → penyewa diminta unggah ulang.
5. Admin dapat mencetak/mengekspor laporan rekapitulasi bulanan (jumlah kegiatan & total dana masuk) untuk Pimpinan.

---

## 6. Backend

### 6.1 Arsitektur Sistem

Arsitektur **client-server 3-tier**:

- **Client (Browser):** antarmuka pengguna, mengirim request (booking, upload bukti bayar, dsb).
- **Server (PHP):** pusat logika aplikasi — menerima request, memproses bisnis rule (cek bentrok jadwal, validasi status pembayaran), berinteraksi dengan database.
- **Database (MySQL):** menyimpan seluruh data (gedung, aset, penyewa, transaksi).

Alur umum: Browser → request ke server PHP → server memproses & query ke MySQL → hasil dikembalikan ke client untuk ditampilkan.

### 6.2 Struktur Database (berdasarkan ERD proposal)

**Entitas utama:** Admin, Penyewa, Gedung, Aset, Transaksi.

| Tabel | Atribut Kunci | Keterangan |
|---|---|---|
| `admin` | id_admin (PK), username, password | Data pengelola sistem |
| `penyewa` | id_penyewa (PK), email, password, nama, alamat, no_telepon | Data pengguna yang melakukan penyewaan |
| `gedung` | id_gedung (PK), nama_gedung, harga_sewa, fasilitas | Data gedung yang tersedia disewa |
| `aset` | id_aset (PK), id_gedung (FK), nama_aset, kondisi_aset | Aset/fasilitas tambahan; satu gedung punya banyak aset, satu aset milik satu gedung |
| `transaksi` | id_transaksi (PK), id_penyewa (FK), id_gedung (FK), id_aset (FK), id_admin (FK), tanggal_mulai, tanggal_selesai, total_pembayaran, status_transaksi | Tabel utama pencatatan penyewaan |

**Relasi:**
- Satu Admin → banyak Transaksi (mengelola).
- Satu Penyewa → banyak Transaksi.
- Satu Transaksi → satu Penyewa, satu Admin, satu Gedung, satu Aset.
- Satu Gedung → banyak Aset & banyak Transaksi.

> Catatan pengembangan: perlu tabel tambahan untuk **riwayat pembayaran/termin** (mis. `pembayaran`: id_pembayaran, id_transaksi (FK), jumlah_bayar, bukti_transfer, tanggal_bayar, status_validasi) agar skema pembayaran bertahap (DP → cicilan → lunas) tercatat sebagai beberapa entri, bukan satu status tunggal di tabel transaksi.

### 6.3 Modul & Fungsi Backend

| Modul | Fungsi |
|---|---|
| **Autentikasi** | Login/registrasi Penyewa, login Admin, login Pimpinan; manajemen sesi & hak akses per peran |
| **Manajemen Gedung & Aset** | CRUD data gedung dan aset (Admin) |
| **Manajemen Penyewa** | CRUD/lihat data penyewa (Admin) |
| **Booking & Kalender** | Cek ketersediaan jadwal; simpan booking; deteksi bentrok jadwal berdasarkan tanggal_mulai & tanggal_selesai; kunci jadwal otomatis setelah pembayaran valid |
| **Pembayaran** | Unggah bukti transfer (Penyewa); validasi manual oleh Admin; update status transaksi (Menunggu Pembayaran → Ditolak/Diperbarui → Lunas); dukung pembayaran bertahap (termin) |
| **Notifikasi** | Notifikasi status booking/pembayaran antara sistem, Admin, dan Penyewa |
| **Laporan** | Rekapitulasi otomatis bulanan (jumlah kegiatan, total dana), cetak/ekspor (PDF), grafik tren pendapatan |
| **Dashboard Admin** | Ringkasan data booking masuk, status fasilitas, metrik keuangan |

### 6.4 Struktur Alur Halaman & Proses PHP (bukan REST API)

Sistem dibangun dengan pendekatan **PHP tradisional (server-side rendering)** — sesuai batasan teknologi di proposal (PHP + MySQL + Bootstrap), tanpa REST API terpisah. Setiap halaman PHP langsung memproses request, mengakses database, lalu merender HTML sebagai respons. Interaksi dinamis ringan (mis. kalender jadwal, notifikasi) dapat memakai AJAX sederhana bila diperlukan, tanpa perlu API penuh.

| Berkas/Proses (usulan) | Fungsi | Akses |
|---|---|---|
| `login.php`, `register.php` | Autentikasi & registrasi | Publik |
| `logout.php` | Keluar sesi | Semua peran |
| `gedung.php` | Menampilkan daftar gedung & ketersediaan | Publik/Penyewa |
| `admin/gedung_kelola.php` | Tambah/ubah/hapus data gedung | Admin |
| `admin/aset_kelola.php` | Tambah/ubah/hapus data aset | Admin |
| `kalender.php` | Menampilkan ketersediaan jadwal per gedung | Publik/Penyewa |
| `booking_proses.php` | Menyimpan pemesanan baru & cek bentrok jadwal | Penyewa |
| `admin/booking_kelola.php` | Melihat & mengubah status booking | Admin |
| `pembayaran_upload.php` | Unggah bukti transfer | Penyewa |
| `admin/pembayaran_validasi.php` | Validasi/tolak bukti pembayaran, update status | Admin |
| `admin/laporan.php` | Rekapitulasi laporan bulanan, cetak/ekspor | Admin/Pimpinan |
| `admin/penyewa_kelola.php` | Kelola/lihat data penyewa | Admin |

*(Nama berkas dan pembagian modul akan disesuaikan lebih lanjut saat implementasi detail per fitur.)*

### 6.5 Tech Stack

| Layer | Teknologi |
|---|---|
| Bahasa pemrograman (server-side) | PHP |
| Basis data | MySQL |
| Framework antarmuka | Bootstrap |
| Markup & styling dasar | HTML, CSS |
| Local server / dev environment | XAMPP |
| Metodologi pengembangan | Waterfall |
| Pengujian | Black Box Testing, User Acceptance Test (UAT) |

---

## 7. Frontend

Bagian ini akan **disesuaikan dengan desain UI** yang akan diberikan terpisah. Berikut kerangka halaman yang perlu didukung backend, berdasarkan hasil rancangan pada proposal (Bab IV):

| Halaman | Peran | Fungsi Utama |
|---|---|---|
| Landing Page | Publik | Informasi umum sistem penyewaan |
| Gedung Tersedia | Publik/Penyewa | Menampilkan daftar gedung & ketersediaan |
| Login | Semua peran | Autentikasi masuk sistem |
| Dashboard Admin | Admin | Ringkasan booking, status fasilitas, metrik keuangan |
| Data Gedung (Admin) | Admin | CRUD data gedung |
| Data Aset (Admin) | Admin | CRUD data aset |
| Data Penyewa (Admin) | Admin | Kelola data penyewa |
| Transaksi (Admin) | Admin | Kelola & pantau seluruh transaksi |
| Jadwal Gedung (Admin) | Admin | Kalender interaktif filter per gedung, detail booking per tanggal |
| Pembayaran (Admin) | Admin | Ringkasan status finansial, tabel transaksi dengan badge status warna |
| Laporan (Admin) | Admin/Pimpinan | Rekap tahunan, grafik tren pendapatan bulanan, cetak/ekspor PDF |

> Status: **menunggu file UI** — struktur halaman di atas akan dipetakan ke desain final begitu diterima.

---

## 8. Pengujian

- **Black Box Testing** — memastikan fungsionalitas login, booking (anti-bentrok jadwal), validasi pembayaran, dan laporan berjalan sesuai spesifikasi tanpa memeriksa kode internal.
- **User Acceptance Test (UAT)** — kuesioner ke pengguna (Admin/Penyewa) untuk menilai kelayakan dan kemudahan penggunaan sistem.

---

## 9. Referensi

Disusun berdasarkan proposal penelitian "Rancang Bangun Sistem Informasi Penyewaan Gedung dan Aset Kampus Politeknik Aceh" oleh Risa Rahma Sari (2023302003), Program Studi Diploma III Teknologi Informasi, Politeknik Aceh.
