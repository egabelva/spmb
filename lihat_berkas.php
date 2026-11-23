<?php
// Pratinjau berkas pendaftar (Admin: semua, Pendaftar: miliknya).
// ?id=ID_PENDAFTAR&berkas=ID_BERKAS membuka berkas tertentu; tanpa parameter berkas = berkas pertama.
require 'koneksi.php';
$row = ambil_pendaftar_akses($_GET['id'] ?? 0);
$bid = (int)($_GET['berkas'] ?? 0);
if ($bid > 0) {
    $b = db_one('SELECT nama_berkas, tipe_berkas, data_berkas FROM berkas_pendaftar WHERE id=? AND pendaftar_id=?', 'ii', [$bid, (int)$row['id']]);
} else {
    $b = db_one('SELECT nama_berkas, tipe_berkas, data_berkas FROM berkas_pendaftar WHERE pendaftar_id=? ORDER BY id LIMIT 1', 'i', [(int)$row['id']]);
}
if (!$b || $b['nama_berkas'] === '' || $b['data_berkas'] === null) { http_response_code(404); die('Berkas belum diunggah.'); }
header('Content-Type: ' . $b['tipe_berkas']);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . (isset($_GET['unduh']) ? 'attachment' : 'inline') . '; filename="' . rawurlencode($b['nama_berkas']) . '"');
echo $b['data_berkas'];
