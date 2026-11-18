<?php
// Menyajikan logo sekolah dari database (diunggah lewat Dashboard Operator -> Pengaturan).
require 'koneksi.php';
$r = db_one('SELECT logo_tipe, logo FROM logo_sekolah WHERE id=1');
if (!$r || $r['logo'] === null || $r['logo'] === '') { http_response_code(404); exit; }
header('Content-Type: ' . $r['logo_tipe']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
echo $r['logo'];
