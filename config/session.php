<?php
// SIIPAK - Session & Auth Helper
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Immediately update session username to match new name
if (isset($_SESSION['user_name'])) {
    if ($_SESSION['user_name'] === 'Administrator Pengelola') {
        $_SESSION['user_name'] = 'Administrator';
    } elseif ($_SESSION['user_name'] === 'Direktur Politeknik Aceh') {
        $_SESSION['user_name'] = 'Direktur Poltek Aceh';
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function check_admin() {
    if (!is_logged_in() || $_SESSION['user_role'] !== 'admin') {
        set_flash('danger', 'Akses ditolak. Anda harus login sebagai Admin Pengelola.');
        header('Location: ../login.php');
        exit;
    }
}

function check_pimpinan() {
    if (!is_logged_in() || $_SESSION['user_role'] !== 'pimpinan') {
        set_flash('danger', 'Akses ditolak. Halaman ini khusus untuk Pimpinan.');
        header('Location: ../login.php');
        exit;
    }
}

function check_penyewa() {
    if (!is_logged_in() || $_SESSION['user_role'] !== 'penyewa') {
        set_flash('danger', 'Silakan login terlebih dahulu untuk melakukan pemesanan.');
        header('Location: login.php');
        exit;
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show role="alert">
                    ' . htmlspecialchars($flash['message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}
