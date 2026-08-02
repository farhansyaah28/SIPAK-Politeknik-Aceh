<?php
// SIIPAK - Global Footer Include
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/pimpinan/') !== false);
$base_path = $is_subfolder ? '../' : '';
?>
    <footer class="footer-custom no-print mt-auto">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5 col-md-6">
                    <h5 class="d-flex align-items-center">
                        <i class="bi bi-building-gear text-warning me-2"></i> SIIPAK Politeknik Aceh
                    </h5>
                    <p class="small text-slate-300 mb-2">
                        Sistem Informasi Penyewaan Gedung dan Aset Kampus berbasis web. Menyediakan kalender ketersediaan real-time, penguncian jadwal otomatis, dan validasi pembayaran bertahap.
                    </p>
                    <p class="small text-muted mb-0">
                        &copy; 2026 Politeknik Aceh. Program Studi D3 Teknologi Informasi — Risa Rahma Sari.
                    </p>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Navigasi Cepat</h5>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><a href="<?= $base_path ?>index.php"><i class="bi bi-chevron-right text-warning me-1"></i>Beranda Utama</a></li>
                        <li class="mb-2"><a href="<?= $base_path ?>gedung.php"><i class="bi bi-chevron-right text-warning me-1"></i>Daftar Gedung & Aset</a></li>
                        <li class="mb-2"><a href="<?= $base_path ?>kalender.php"><i class="bi bi-chevron-right text-warning me-1"></i>Cek Kalender Jadwal</a></li>
                        <li class="mb-2"><a href="<?= $base_path ?>login.php"><i class="bi bi-chevron-right text-warning me-1"></i>Portal Masuk / Login</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-12">
                    <h5>Kontak Pengelola Kampus</h5>
                    <ul class="list-unstyled small text-slate-300">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill text-warning me-2"></i>Jl. Sultan Malikul Saleh, Lhong Raya, Banda Aceh</li>
                        <li class="mb-2"><i class="bi bi-telephone-fill text-warning me-2"></i>(0651) 7555566 / WhatsApp: 0812-6900-1122</li>
                        <li class="mb-2"><i class="bi bi-envelope-fill text-warning me-2"></i>penyewaan@politeknikaceh.ac.id</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
