<?php
// SIIPAK - Multi-Role Login Portal
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
    $identity = sanitize($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = 'Username/Email dan Password wajib diisi.';
    } else {
        // 1. Cek Admin & Pimpinan
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :uname OR email = :email");
        $stmt->execute([':uname' => $identity, ':email' => $identity]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id_admin'];
            $_SESSION['user_name'] = $admin['nama_lengkap'];
            $_SESSION['user_role'] = $admin['role']; // 'admin' atau 'pimpinan'

            set_flash('success', 'Selamat datang kembali, ' . htmlspecialchars($admin['nama_lengkap']));
            if ($admin['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: pimpinan/index.php');
            }
            exit;
        } else {
            // 2. Cek Penyewa
            $stmt = $pdo->prepare("SELECT * FROM penyewa WHERE email = :email");
            $stmt->execute([':email' => $identity]);
            $penyewa = $stmt->fetch();

            if ($penyewa && password_verify($password, $penyewa['password'])) {
                if ($penyewa['status_akun'] === 'Nonaktif') {
                    $error = 'Akun Anda dinonaktifkan oleh administrator.';
                } else {
                    $_SESSION['user_id'] = $penyewa['id_penyewa'];
                    $_SESSION['user_name'] = $penyewa['nama'];
                    $_SESSION['user_role'] = 'penyewa';

                    set_flash('success', 'Selamat datang, ' . htmlspecialchars($penyewa['nama']));
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Kombinasi Username/Email atau Password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login | SIPAK Politeknik Aceh</title>
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
<body class="bg-surface-blue font-body-md text-on-surface overflow-hidden">
<!-- Shell Filter: Suppression of TopNavBar and SideNavBar for Transactional/Login screen -->
<main class="flex h-screen w-full">
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
        <div class="max-w-lg z-10">
            <h2 class="font-display-lg text-display-lg text-white mb-md leading-tight">
                Kelola sewa aset kampus dalam satu sistem.
            </h2>
            <p class="font-body-lg text-body-lg text-primary-fixed/90 leading-relaxed">
                Booking, pembayaran dua termin, validasi bukti transfer, hingga laporan bulanan untuk pimpinan.
            </p>
        </div>
        <!-- Footer -->
        <div class="z-10">
            <p class="font-label-md text-label-md text-primary-fixed/60">
                © 2026 Politeknik Aceh
            </p>
        </div>
    </section>
    
    <!-- Right Login Panel -->
    <section class="w-full lg:w-1/2 flex items-center justify-center p-md bg-surface-container-low">
        <div class="w-full max-w-[400px] glass-card rounded-xl p-8 custom-shadow border border-outline-variant">
            <a href="index.php" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border border-outline-variant hover:border-primary text-[10px] font-bold text-on-surface-variant hover:text-primary bg-white/50 hover:bg-white shadow-xs hover:shadow-sm transition-all duration-300 decoration-none mb-8 w-fit group">
                <span class="material-symbols-outlined text-xs transition-transform group-hover:-translate-x-0.5">arrow_back</span> 
                <span>Kembali ke Beranda</span>
            </a>
            <div class="mb-xl">
                <h3 class="font-display-md text-display-md text-primary mb-xs">Masuk ke akun</h3>
                <p class="font-body-md text-body-md text-on-surface-variant">Masukkan email dan password untuk masuk ke sistem.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-error-container text-on-error-container p-md rounded-xl border border-error/20 mb-md flex items-center gap-xs">
                    <span class="material-symbols-outlined text-error">error</span>
                    <span class="text-body-md"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="bg-surface-container-high text-primary p-md rounded-xl border border-outline-variant mb-md flex items-center gap-xs">
                    <span class="material-symbols-outlined">info</span>
                    <span class="text-body-md"><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <form class="space-y-4" method="POST" action="login.php">
                <!-- Email Input -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-primary block" for="email">Email / Username</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">alternate_email</span>
                        <input class="w-full h-10 pl-9 pr-3 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="email" name="identity" placeholder="Masukkan email atau username" type="text" required value="<?= htmlspecialchars($_POST['identity'] ?? '') ?>"/>
                    </div>
                </div>
                <!-- Password Input -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-primary block" for="password">Password</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-muted group-focus-within:text-primary text-[16px] transition-colors pointer-events-none">lock</span>
                        <input class="w-full h-10 pl-9 pr-9 text-sm rounded-lg border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary transition-all bg-surface-container-lowest text-on-surface" id="password" name="password" placeholder="Masukkan password" type="password" required/>
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-muted hover:text-primary transition-colors flex items-center" type="button">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                        </button>
                    </div>
                </div>
                <!-- Submit Button -->
                <button class="w-full h-10 bg-gradient-to-r from-primary to-[#002068] hover:shadow-md text-white font-bold text-sm rounded-lg flex items-center justify-center gap-xs active:scale-95 transition-all group" type="submit">
                    <span class="material-symbols-outlined text-sm group-hover:translate-x-0.5 transition-transform">login</span>
                    Masuk ke Akun
                </button>
                <!-- Secondary Action -->
                <div class="text-center pt-2">
                    <p class="text-xs text-on-surface-variant">
                        Belum punya akun? 
                        <a class="text-primary font-bold hover:underline transition-all" href="register.php">Daftar sekarang</a>
                    </p>
                </div>
            </form>
            <!-- Help link -->
            <div onclick="window.location.href='bantuan.php'" class="mt-4 flex justify-center items-center gap-xs text-outline cursor-pointer hover:text-primary transition-colors text-xs">
                <span class="material-symbols-outlined text-sm">help_outline</span>
                <span class="font-semibold text-[11px]">Butuh bantuan login?</span>
            </div>
        </div>
    </section>
</main>
<script>


    // Toggle Password visibility
    const toggleBtn = document.querySelector('button[type="button"]');
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
