<?php
// Konfigurasi koneksi database. Sesuaikan jika MySQL Anda memakai password.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_OFF);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'db_spmb_gunungmadu';

$koneksi = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$koneksi) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;max-width:520px;margin:15vh auto;padding:24px;border-radius:24px;background:#fff7ed;border:2px solid #fb923c">'
      . '<h2 style="margin:0 0 8px">Database belum siap</h2>'
      . '<p>Pastikan MySQL di XAMPP sudah berjalan dan file <b>database.sql</b> sudah diimpor lewat phpMyAdmin. '
      . 'Jika MySQL memakai password, ubah pengaturan di <b>koneksi.php</b>.</p></div>');
}
mysqli_set_charset($koneksi, 'utf8mb4');

require_once __DIR__ . '/includes/fungsi.php';
