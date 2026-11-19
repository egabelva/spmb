<?php
require 'koneksi.php';
$r = ambil_pendaftar_akses($_GET['id'] ?? 0);
if ($r['status'] !== 'Diterima') die('Surat kelulusan hanya tersedia untuk pendaftar yang diterima.');
if (($_SESSION['role'] ?? '') === 'Pendaftar' && setting('hasil_diumumkan') !== '1') die('Hasil seleksi belum diumumkan.');
html_head('Surat Kelulusan', 'bg-gray-100 min-h-screen p-4');
?>
<div class="max-w-2xl mx-auto">
  <div class="no-print flex flex-wrap gap-3 mb-4 justify-center">
    <button onclick="window.print()" class="btn btn-or"><i class="fa-solid fa-print"></i> Cetak / Simpan sebagai PDF</button>
    <button onclick="history.length>1?history.back():window.close()" class="btn btn-ghost">Kembali</button>
  </div>
  <div class="card p-5 sm:p-10 text-gray-800 leading-relaxed">
    <div class="text-center border-b-4 double border-orange-500 pb-4 mb-6">
      <h1 class="text-2xl font-extrabold">SMA GUNUNG MADU</h1>
      <p class="text-xs tracking-widest text-orange-600">SMART • HEBAT • BERMARTABAT</p>
      <p class="text-sm"><?= e(setting('alamat')) ?></p>
    </div>
    <h2 class="text-center font-extrabold text-lg underline mb-1">SURAT KETERANGAN DITERIMA</h2>
    <p class="text-center text-sm mb-6">Nomor: <?= e($r['no_pendaftaran']) ?>/SPMB/2027</p>
    <p class="mb-3">Panitia Penerimaan Murid Baru SMA Gunung Madu menerangkan bahwa:</p>
    <table class="text-sm mb-4 sm:ml-4 w-full sm:w-auto" style="overflow-wrap:anywhere">
      <tr><td class="pr-4 sm:pr-6 py-1 align-top whitespace-nowrap">Nama</td><td>: <b><?= e($r['nama_lengkap']) ?></b></td></tr>
      <tr><td class="pr-4 sm:pr-6 py-1 align-top whitespace-nowrap">NIK / NISN</td><td>: <?= e($r['nik_nisn']) ?></td></tr>
      <tr><td class="pr-4 sm:pr-6 py-1 align-top whitespace-nowrap">Tempat, tgl lahir</td><td>: <?= e($r['tempat_lahir']) ?>, <?= e(tgl_indo($r['tanggal_lahir'])) ?></td></tr>
      <tr><td class="pr-4 sm:pr-6 py-1 align-top whitespace-nowrap">Asal sekolah</td><td>: <?= e($r['asal_sekolah']) ?></td></tr>
      <tr><td class="pr-4 sm:pr-6 py-1 align-top whitespace-nowrap">Jalur</td><td>: <?= e($r['jalur']) ?></td></tr>
    </table>
    <p class="mb-3">dinyatakan <b>DITERIMA</b> sebagai peserta didik baru SMA Gunung Madu Tahun Pelajaran 2027/2028. Surat ini digunakan sebagai syarat untuk melakukan daftar ulang sesuai jadwal yang ditetapkan panitia.</p>
    <p class="mt-10 text-right text-sm">Ditetapkan pada <?= e(tgl_indo(date('Y-m-d'))) ?><br>Panitia SPMB SMA Gunung Madu<br><br><br><br>(................................)</p>
  </div>
</div>
<?php html_foot(); ?>
