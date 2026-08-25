<?php
// SIIPAK - Email SMTP Helper
// Konfigurasi Akun Pengirim Resmi SIPAK Politeknik Aceh

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'sipakpoliteknikaceh@gmail.com');
define('SMTP_PASS', 'dnszkxhrtdjanizy'); // Sandi Aplikasi (App Password) Gmail Anda
define('SMTP_FROM', 'sipakpoliteknikaceh@gmail.com');
define('SMTP_FROM_NAME', 'SIPAK Politeknik Aceh');

/**
 * Mengirim email menggunakan protokol SMTP (SSL Port 465) langsung menggunakan Socket PHP
 * Tanpa memerlukan dependensi eksternal (PHPMailer/Composer).
 *
 * @param string $to Penerima email
 * @param string $subject Subjek email
 * @param string $message_html Konten email (Format HTML)
 * @return bool True jika berhasil terkirim, False jika gagal.
 */
function send_mail($to, $subject, $message_html) {
    $host      = SMTP_HOST;
    $port      = SMTP_PORT;
    $username  = SMTP_USER;
    $password  = SMTP_PASS;
    $from      = SMTP_FROM;
    $from_name = SMTP_FROM_NAME;

    // Jika sandi SMTP masih berupa placeholder, lewati pengiriman untuk mencegah error
    if ($password === 'YOUR_APP_PASSWORD_HERE' || empty($password)) {
        error_log("Email Warning: Sandi SMTP (App Password) belum dikonfigurasi.");
        return false;
    }

    $timeout = 10;
    // Buka koneksi socket ke server SMTP Gmail menggunakan SSL
    $socket = @fsockopen("ssl://" . $host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        error_log("SMTP Socket Connection Error: $errstr ($errno)");
        return false;
    }

    // Fungsi pembantu untuk membaca respon server SMTP
    function read_response($socket, $expected_code) {
        $response = "";
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === " ") {
                break;
            }
        }
        $code = substr($response, 0, 3);
        if ($code !== $expected_code) {
            error_log("SMTP Error: Mengharapkan respon '$expected_code', mendapatkan: " . trim($response));
            return false;
        }
        return true;
    }

    // 1. Baca Greeting awal dari server
    if (!read_response($socket, "220")) { fclose($socket); return false; }

    // 2. Kirim EHLO
    fwrite($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
    if (!read_response($socket, "250")) { fclose($socket); return false; }

    // 3. Kirim AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    if (!read_response($socket, "334")) { fclose($socket); return false; }

    // 4. Kirim Username Base64
    fwrite($socket, base64_encode($username) . "\r\n");
    if (!read_response($socket, "334")) { fclose($socket); return false; }

    // 5. Kirim Password/App Password Base64
    fwrite($socket, base64_encode($password) . "\r\n");
    if (!read_response($socket, "235")) { fclose($socket); return false; }

    // 6. Kirim MAIL FROM
    fwrite($socket, "MAIL FROM:<" . $from . ">\r\n");
    if (!read_response($socket, "250")) { fclose($socket); return false; }

    // 7. Kirim RCPT TO
    fwrite($socket, "RCPT TO:<" . $to . ">\r\n");
    if (!read_response($socket, "250")) { fclose($socket); return false; }

    // 8. Kirim DATA
    fwrite($socket, "DATA\r\n");
    if (!read_response($socket, "354")) { fclose($socket); return false; }

    // Susun Header dan Konten Email HTML
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "To: <" . $to . ">\r\n";
    $headers .= "From: \"" . $from_name . "\" <" . $from . ">\r\n";
    $headers .= "Subject: " . $subject . "\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "Message-ID: <" . time() . "-" . md5($to) . "@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";

    $email_body = $headers . "\r\n" . $message_html . "\r\n.\r\n";
    
    // Kirim isi email
    fwrite($socket, $email_body);
    if (!read_response($socket, "250")) { fclose($socket); return false; }

    // 9. Kirim QUIT
    fwrite($socket, "QUIT\r\n");
    read_response($socket, "221");

    fclose($socket);
    return true;
}
