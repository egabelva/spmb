<?php
// Menyajikan gambar konten website dari database (satu tabel per menu).
require 'koneksi.php';
$id = (int)($_GET['id'] ?? 0);
$tabel = tabel_bagian($_GET['b'] ?? '');   // daftar putih: hanya nama tabel yang dikenal
if (!$tabel) { http_response_code(404); exit; }
$r = db_one('SELECT gambar_tipe, gambar FROM ' . $tabel . ' WHERE id=? AND gambar_tipe IS NOT NULL AND gambar_tipe<>""', 'i', [$id]);
if (!$r) { http_response_code(404); exit; }
header('Content-Type: ' . $r['gambar_tipe']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
echo $r['gambar'];
