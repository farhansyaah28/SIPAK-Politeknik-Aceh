<?php
// SIIPAK - Penyewa Registration Portal
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/functions.php';

if (is_logged_in()) {
    $role = get_user_role();
    if ($role === 'admin') header('Location: admin/index.php');
    elseif ($role === 'pimpinan') header('Location: pimpinan/index.php');
    else header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = sanitize($_POST['nama'] ?? '');
    $email      = sanitize($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $instansi   = sanitize($_POST['instansi'] ?? 'Perorangan');
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $alamat     = sanitize($_POST['alamat'] ?? '');

    if (empty($nama) || empty($email) || empty($password) || empty($no_telepon) || empty($alamat)) {
        $error = 'Semua bidang bertanda bintang (*) wajib diisi.';
    } else {
        // Cek apakah email sudah terdaftar
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM penyewa WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Email ini sudah terdaftar. Silakan gunakan email lain atau login.';
        } else {
            $hash_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO penyewa (nama, email, password, instansi, no_telepon, alamat) VALUES (:nama, :email, :password, :instansi, :no_telepon, :alamat)");
            $stmt->execute([
                ':nama'       => $nama,
                ':email'      => $email,
                ':password'   => $hash_password,
                ':instansi'   => $instansi,
                ':no_telepon' => $no_telepon,
                ':alamat'     => $alamat
            ]);

            set_flash('success', 'Pendaftaran akun berhasil! Silakan login untuk melakukan penyewaan gedung.');
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Daftar Akun | SIPAK Politeknik Aceh</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100..900&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "outline": "#757682",
                        "primary-fixed": "#dce1ff",
                        "on-tertiary-fixed-variant": "#0f521e",
                        "surface-container-high": "#dde9ff",
                        "on-tertiary": "#ffffff",
                        "inverse-on-surface": "#ebf1ff",
                        "outline-variant": "#c5c5d2",
                        "surface-container-low": "#eff4ff",
                        "error-red": "#ba1a1a",
                        "surface-container-highest": "#d3e3ff",
                        "surface-bright": "#f8f9ff",
                        "secondary": "#745b00",
                        "on-primary-fixed-variant": "#2b4289",
                        "surface-blue": "#f8f9ff",
                        "secondary-container": "#fdd979",
                        "on-secondary-fixed-variant": "#584400",
                        "error": "#ba1a1a",
                        "background": "#f8f9ff",
                        "primary": "#000e3a",
                        "on-secondary-container": "#775e03",
                        "inverse-primary": "#b5c4ff",
                        "on-background": "#0b1c30",
                        "secondary-fixed-dim": "#e5c366",
                        "inverse-surface": "#213146",
                        "error-container": "#ffdad6",
                        "on-surface-variant": "#444651",
                        "tertiary": "#001803",
                        "surface-tint": "#455aa2",
                        "on-tertiary-fixed": "#002106",
                        "on-tertiary-container": "#5d9d5f",
                        "on-secondary-fixed": "#241a00",
                        "on-secondary": "#ffffff",
                        "surface-variant": "#d3e3ff",
                        "tertiary-fixed": "#aff3ad",
                        "primary-fixed-dim": "#b5c4ff",
                        "on-surface": "#0b1c30",
                        "warning-amber": "#fecb00",
                        "surface-container": "#e6eeff",
                        "on-primary-container": "#758bd6",
                        "surface-container-lowest": "#ffffff",
                        "on-error-container": "#93000a",
                        "success-green": "#45bf59",
                        "on-primary-fixed": "#00164e",
                        "surface-dim": "#cbdbf6",
                        "tertiary-container": "#002f0b",
                        "secondary-fixed": "#ffe08d",
                        "primary-container": "#002068",
                        "on-error": "#ffffff",
                        "outline-muted": "#c4c5d5",
                        "tertiary-fixed-dim": "#93d693",
                        "surface": "#f8f9ff",
                        "on-primary": "#ffffff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "md": "12px",
                        "touch-target": "36px",
                        "sm": "10px",
                        "base": "4px",
                        "xs": "6px",
                        "xl": "20px",
                        "gutter": "12px",
                        "lg": "16px",
                        "container-margin": "12px"
                    },
                    "fontFamily": {
                        "display-md": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "display-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "label-lg": ["Plus Jakarta Sans"],
                        "headline-md": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "display-md": ["18px", {"lineHeight": "24px", "letterSpacing": "-0.01em", "fontWeight": "800"}],
                        "headline-lg-mobile": ["18px", {"lineHeight": "24px", "fontWeight": "800"}],
                        "body-lg": ["13px", {"lineHeight": "18px", "fontWeight": "400"}],
                        "label-md": ["11px", {"lineHeight": "14px", "fontWeight": "600"}],
                        "headline-lg": ["15px", {"lineHeight": "20px", "fontWeight": "700"}],
                        "display-lg": ["22px", {"lineHeight": "28px", "letterSpacing": "-0.02em", "fontWeight": "800"}],
                        "body-md": ["12px", {"lineHeight": "16px", "fontWeight": "400"}],
                        "label-lg": ["12px", {"lineHeight": "16px", "fontWeight": "600"}],
                        "headline-md": ["14px", {"lineHeight": "20px", "fontWeight": "700"}]
                    }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }
        .brand-gradient {
            background: linear-gradient(135deg, #00164e 0%, #000e3a 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px);
        }
        .custom-shadow {
            box-shadow: 0px 4px 32px rgba(0, 14, 58, 0.08);
        }
    </style>
</head>
<body class="bg-surface-blue font-body-md text-on-surface">

<main class="flex min-h-screen w-full">
    <!-- Left Brand Panel -->
    <section class="hidden lg:flex flex-col justify-between w-1/2 p-xl brand-gradient relative overflow-hidden">
        <!-- Ambient Glow Accent -->
        <div class="absolute -left-16 -top-16 w-64 h-64 bg-warning-amber/10 rounded-full blur-3xl pointer-events-none z-0"></div>
        <!-- Subtle Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10 pointer-events-none z-0">
            <svg height="100%" width="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern height="40" id="grid" patternunits="userSpaceOnUse" width="40">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"></path>
                    </pattern>
                </defs>
                <rect fill="url(#grid)" height="100%" width="100%"></rect>
            </svg>
        </div>
        <!-- Top Brand Identity -->
        <div class="flex items-center gap-md z-10">
            <div class="w-12 h-12 bg-warning-amber rounded-xl flex items-center justify-center shadow-lg transform rotate-3">
                <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings: 'FILL' 1;">apartment</span>
            </div>
            <div>
                <a href="index.php" class="decoration-none"><h1 class="font-headline-lg text-headline-lg text-white leading-tight">SIPAK</h1></a>
                <p class="font-label-md text-label-md text-primary-fixed opacity-80">Politeknik Aceh</p>
            </div>
        </div>
        <!-- Hero Message -->
        <div class="max-w-lg z-10 my-auto">
            <h2 class="font-display-lg text-display-lg text-white mb-md leading-tight">
                Kelola sewa aset kampus dalam satu sistem.
            </h2>
            <p class="font-body-lg text-body-lg text-primary-fixed/90 leading-relaxed">
                Booking auditorium, aula, dan ruang praktikum secara online. Pembayaran DP &amp; pelunasan transparan, jadwal aset real-time.
            </p>
        </div>
        <!-- Footer -->
        <div class="z-10">
            <p class="font-label-md text-label-md text-primary-fixed/60">
                © 2026 Politeknik Aceh
            </p>
        </div>
    </section>
    
    <!-- Right Register Panel -->
    <section class="w-full lg:w-1/2 flex items-center justify-center p-md bg-surface-container-low py-xl">
        <div class="w-full max-w-[540px] glass-card rounded-xl p-8 custom-shadow border border-outline-variant">
            <a href="index.php" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border border-outline-variant hover:border-primary text-[10px] font-bold text-on-surface-variant hover:text-primary bg-white/50 hover:bg-white shadow-xs hover:shadow-sm transition-all duration-300 decoration-none mb-8 w-fit group">
                <span class="material-symbols-outlined text-xs transition-transform group-hover:-translate-x-0.5">arrow_back</span> 
                <span>Kembali ke Beranda</span>
            </a>
            <div class="mb-lg">
                <h3 class="font-display-md text-display-md text-primary mb-xs">Daftar Akun Penyewa</h3>
                <p class="font-body-md text-body-md text-on-surface-variant">Lengkapi formulir di bawah untuk membuat akun penyewa baru.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-error-container text-on-error-container p-md rounded-xl border border-error/20 mb-md flex items-center gap-xs">
                    <span class="material-symbols-outlined text-error">error</span>
                    <span class="text-body-md"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form class="space-y-4" method="POST" action="register.php">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-sm">
                    <!-- Nama Lengkap -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-primary block" for="nama">Nama Lengkap *</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">person</span>
                            <input class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="nama" name="nama" placeholder="Contoh: Budi Santoso" type="text" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"/>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-primary block" for="email">Alamat Email *</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">alternate_email</span>
                            <input class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="email" name="email" placeholder="contoh@email.com" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-sm">
                    <!-- Password -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-primary block" for="password">Password *</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">lock</span>
                            <input class="w-full h-10 pl-9 pr-9 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="password" name="password" placeholder="Minimal 6 karakter" type="password" required/>
                            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-muted hover:text-primary transition-colors flex items-center" type="button" id="togglePassword">
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                            </button>
                        </div>
                    </div>
                    <!-- No. Telepon -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-primary block" for="no_telepon">No. WhatsApp / Telepon *</label>
                        <div class="relative group">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">phone</span>
                            <input class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="no_telepon" name="no_telepon" placeholder="Contoh: 0812xxxxxxxx" type="text" required value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>"/>
                        </div>
                    </div>
                </div>

                <!-- Instansi -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-primary block" for="instansi">Nama Instansi / Perusahaan (Opsional)</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">corporate_fare</span>
                        <input class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="instansi" name="instansi" placeholder="Contoh: PT Aceh Multimedia" type="text" value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>"/>
                    </div>
                </div>

                <!-- Alamat -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-primary block" for="alamat">Alamat Lengkap *</label>
                    <textarea class="w-full p-3 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface resize-none" id="alamat" name="alamat" rows="2" placeholder="Masukkan alamat lengkap instansi atau domisili" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>

                <!-- Submit Button -->
                <button class="w-full h-10 bg-gradient-to-r from-primary to-[#002068] hover:shadow-md text-white font-bold text-sm rounded-lg flex items-center justify-center gap-xs active:scale-95 transition-all group" type="submit">
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">person_add</span>
                    Daftarkan Akun Baru
                </button>

                <!-- Secondary Action -->
                <div class="text-center pt-2">
                    <p class="text-xs text-on-surface-variant">
                        Sudah punya akun? 
                        <a class="text-primary font-bold hover:underline transition-all" href="login.php">Masuk sekarang</a>
                    </p>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
    // Toggle Password visibility
    const toggleBtn = document.getElementById('togglePassword');
    const passInput = document.getElementById('password');
    let isVisible = false;

    toggleBtn.addEventListener('click', () => {
        isVisible = !isVisible;
        passInput.type = isVisible ? 'text' : 'password';
        toggleBtn.children[0].textContent = isVisible ? 'visibility_off' : 'visibility';
    });
</script>
</body>
</html>
