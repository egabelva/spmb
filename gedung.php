<?php
// Menyajikan foto gedung sekolah untuk hero beranda (diunggah lewat Dashboard Operator -> Pengaturan).
require 'koneksi.php';
$r = db_one('SELECT gedung_tipe, gedung FROM foto_gedung WHERE id=1');
if (!$r || $r['gedung'] === null || $r['gedung'] === '') { http_response_code(404); exit; }
header('Content-Type: ' . $r['gedung_tipe']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
echo $r['gedung'];
