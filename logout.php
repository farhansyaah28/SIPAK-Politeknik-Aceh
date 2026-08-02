<?php
// SIIPAK - Logout Controller
require_once 'config/session.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash('info', 'Anda telah berhasil keluar dari sistem.');
header('Location: login.php');
exit;
