<?php
require 'koneksi.php';
$r = ambil_pendaftar_akses($_GET['id'] ?? 0);
if ($r['status'] === 'Draft') die('Pendaftaran belum dikirim.');
html_head('Bukti Pendaftaran', 'bg-gray-100 min-h-screen p-4');
?>
<div class="max-w-2xl mx-auto">
  <div class="no-print flex gap-3 mb-4 justify-center">
    <button onclick="window.print()" class="btn btn-or"><i class="fa-solid fa-print"></i> Cetak / Simpan sebagai PDF</button>
    <button onclick="history.length>1?history.back():window.close()" class="btn btn-ghost">Kembali</button>
  </div>
  <div class="card p-8 text-gray-800">
    <div class="text-center border-b-2 border-orange-500 pb-4 mb-5">
      <h1 class="text-xl font-extrabold">BUKTI PENDAFTARAN PESERTA DIDIK BARU</h1>
      <p class="font-semibold">SMA Gunung Madu - Tahun Pelajaran 2027/2028</p>
      <p class="text-xs tracking-widest text-orange-600">SMART • HEBAT • BERMARTABAT</p>
    </div>
    <p class="text-center text-sm text-gray-500">Nomor Pendaftaran</p>
    <p class="text-center text-3xl font-extrabold text-orange-600 mb-5"><?= e($r['no_pendaftaran']) ?></p>
    <table class="w-full text-sm">
      <?php $baris = [['Nama lengkap', $r['nama_lengkap']], ['NIK / NISN', $r['nik_nisn']], ['Jenis kelamin', $r['jenis_kelamin']],
        ['Tempat, tanggal lahir', $r['tempat_lahir'] . ', ' . tgl_indo($r['tanggal_lahir'])], ['Alamat', $r['alamat']], ['Asal sekolah', $r['asal_sekolah']],
        ['Jalur pendaftaran', $r['jalur']], ['Orang tua/wali', $r['tipe_batih'] . ' - ' . $r['nama_batih']], ['Pekerjaan', $r['pekerjaan_batih']],
        ['Kontak', $r['kontak_batih']], ['Berkas diunggah', $r['nama_berkas'] ?: '-'], ['Waktu pendaftaran', tgl_indo($r['waktu_daftar'], true)]];
      foreach ($baris as $b): ?><tr class="border-b border-gray-200"><td class="py-2 w-48 font-semibold align-top"><?= e($b[0]) ?></td><td class="py-2 align-top">: <?= e($b[1]) ?></td></tr><?php endforeach; ?>
    </table>
    <p class="mt-5 text-xs text-gray-500">Dokumen ini adalah bukti sah pendaftaran online. Simpan dan tunjukkan saat diperlukan. Status seleksi dapat dilihat melalui akun pendaftar setelah diumumkan panitia.</p>
    <p class="mt-6 text-right text-sm">Dicetak: <?= e(tgl_indo(date('Y-m-d H:i:s'), true)) ?></p>
  </div>
</div>
<?php html_foot(); ?>
