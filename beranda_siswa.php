<?php
require 'koneksi.php';
require_role('Pendaftar');
$akun_id = (int)$_SESSION['akun_id'];
$jalur = jalur_list();
$row = db_one('SELECT ' . KOLOM_PENDAFTAR . ' FROM v_pendaftar WHERE akun_id=?', 'i', [$akun_id]);
$umum = setting('hasil_diumumkan') === '1';
$errors = []; $in = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($row && $row['status'] !== 'Draft') { flash('error', 'Pendaftaran sudah dikirim dan tidak dapat diubah.'); redirect('beranda_siswa.php'); }
    if (in_array($aksi, ['simpan', 'kirim'], true)) {
        $kirim = $aksi === 'kirim';
        $in = kumpul_input();
        $errors = validasi_pendaftar($in, $kirim);
        list($uerr, $unama, $utipe, $udata) = baca_upload('berkas', ['application/pdf', 'image/jpeg', 'image/png']);
        if ($uerr) $errors[] = $uerr;
        if ($kirim) {
            if (deadline_lewat()) $errors[] = 'Pendaftaran sudah ditutup.';
            $adaBerkas = $udata !== null || ($row && $row['nama_berkas'] !== '');
            if (!$adaBerkas) $errors[] = 'Dokumen persyaratan wajib diunggah (PDF/JPG/PNG).';
            if ($in['nik_nisn'] !== '' && nik_terdaftar($in['nik_nisn'])) $errors[] = 'NIK/NISN ini sudah terdaftar.';
        }
        if (!$errors) {
            $id = simpan_pendaftar($row ? (int)$row['id'] : 0, $akun_id, $in, 'Draft', 'Online');
            if ($id <= 0) { $errors[] = 'Gagal menyimpan data. Coba lagi.'; }
            else {
                if ($udata !== null && !simpan_berkas($id, $unama, $utipe, $udata)) $errors[] = 'Berkas gagal disimpan ke database. Pastikan max_allowed_packet MySQL cukup besar.';
                if (!$errors && $kirim) {
                    db_exec("UPDATE pendaftar SET status='Pending', waktu_daftar=NOW() WHERE id=?", 'i', [$id]);
                    catat_status($id, 'Draft', 'Pending', 'Pendaftar');
                    catat_log('kirim_pendaftaran', '#' . $id);
                    beri_nomor($id);
                    flash('ok', 'Pendaftaran berhasil dikirim! Simpan bukti pendaftaran Anda.');
                    redirect('beranda_siswa.php');
                }
                if (!$errors) { flash('ok', 'Draft tersimpan. Anda dapat melanjutkan kapan saja.'); redirect('beranda_siswa.php'); }
            }
        }
    }
}
$d = $in ?? ($row ?: array_fill_keys(FIELD_PENDAFTAR, ''));
if (!$row && $in === null) $d['nama_lengkap'] = $_SESSION['nama'] ?? '';
$ro = $row && $row['status'] !== 'Draft';
$status = $row['status'] ?? 'Draft';
$adaBerkas = $row && $row['nama_berkas'] !== '';
$dis = $ro ? 'disabled' : '';

html_head('Beranda Pendaftar');
?>
<header class="bg-white/90 dark:bg-gray-900/90 backdrop-blur shadow-sm sticky top-0 z-30 no-print">
  <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="index.php"><?= logo_html(true) ?></a>
    <div class="flex items-center gap-2"><span class="hidden sm:inline text-sm font-medium"><i class="fa-solid fa-user mr-1 text-orange-500"></i><?= e($_SESSION['nama'] ?? '') ?></span><?= theme_btn() ?>
      <a href="logout.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a></div>
  </div>
</header>
<main class="max-w-4xl mx-auto px-4 py-8 space-y-6">
  <?= flash_html() ?>

  <section class="card p-6">
    <h2 class="font-extrabold text-xl mb-4"><i class="fa-solid fa-flag-checkered text-orange-500 mr-2"></i>Status Pendaftaran</h2>
    <?php if (!$ro): ?>
      <p class="rounded-3xl bg-gray-100 dark:bg-gray-900 p-4"><b>Belum dikirim.</b> Lengkapi formulir di bawah, lalu tekan <b>Kirim Pendaftaran</b>. Anda bisa menyimpan draft dan melanjutkan nanti.</p>
      <?php if (setting('deadline')): ?><p class="text-sm mt-2 text-gray-500">Batas pendaftaran: <?= e(tgl_indo(setting('deadline'), true)) ?></p><?php endif; ?>
    <?php else: ?>
      <p class="text-sm text-gray-500">No. Pendaftaran</p><p class="text-2xl font-extrabold text-orange-600 mb-3"><?= e($row['no_pendaftaran']) ?></p>
      <?php if ($status === 'Pending' || !$umum): ?>
        <p class="rounded-3xl bg-yellow-100 text-yellow-900 p-4"><i class="fa-solid fa-hourglass-half mr-2"></i><b>Sedang diproses.</b> Data Anda sudah diterima panitia dan menunggu verifikasi/pengumuman hasil seleksi.</p>
      <?php elseif ($status === 'Diterima'): ?>
        <p class="rounded-3xl bg-green-100 text-green-900 p-4"><i class="fa-solid fa-circle-check mr-2"></i><b>Selamat! Anda dinyatakan DITERIMA</b> sebagai peserta didik baru SMA Gunung Madu. Unduh surat kelulusan untuk daftar ulang.</p>
      <?php else: ?>
        <p class="rounded-3xl bg-red-100 text-red-900 p-4"><i class="fa-solid fa-circle-info mr-2"></i><b>Mohon maaf, Anda belum dapat diterima</b> pada seleksi kali ini. Hubungi Customer Service untuk informasi lebih lanjut.</p>
      <?php endif; ?>
      <div class="flex flex-wrap gap-3 mt-4">
        <a href="bukti_pendaftaran.php?id=<?= (int)$row['id'] ?>" target="_blank" class="btn btn-or"><i class="fa-solid fa-file-pdf"></i> Unduh Bukti Pendaftaran</a>
        <?php if ($status === 'Diterima' && $umum): ?><a href="surat_kelulusan.php?id=<?= (int)$row['id'] ?>" target="_blank" class="btn btn-green"><i class="fa-solid fa-file-circle-check"></i> Unduh Surat Kelulusan</a><?php endif; ?>
        <?php if ($adaBerkas): ?><a href="lihat_berkas.php?id=<?= (int)$row['id'] ?>" target="_blank" class="btn btn-ghost"><i class="fa-solid fa-paperclip"></i> Lihat Berkas Saya</a><?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card p-6">
    <h2 class="font-extrabold text-xl mb-1"><i class="fa-solid fa-pen-to-square text-orange-500 mr-2"></i>Formulir Pendaftaran</h2>
    <div class="mb-5">
      <div class="h-4 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden"><div id="bar-progres" class="h-full bg-orange-500 rounded-full transition-all" style="width:0%"></div></div>
      <p id="txt-progres" class="text-xs text-gray-500 mt-1 ml-2"></p>
    </div>
    <?php if ($errors): ?><div class="rounded-3xl border-2 border-red-300 bg-red-100 text-red-800 p-4 mb-4 text-sm"><b><i class="fa-solid fa-circle-exclamation mr-1"></i>Periksa kembali:</b><ul class="list-disc ml-6 mt-1"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <form id="form-daftar" method="post" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-4">
      <?= csrf_field() ?>
      <h3 class="sm:col-span-2 font-bold text-orange-600"><i class="fa-solid fa-id-card mr-1"></i>Biodata Calon Siswa</h3>
      <div><label class="lbl">NIK / NISN</label><input class="inp" name="nik_nisn" data-req value="<?= e($d['nik_nisn']) ?>" inputmode="numeric" maxlength="16" <?= $dis ?>></div>
      <div><label class="lbl">Nama lengkap</label><input class="inp" name="nama_lengkap" data-req value="<?= e($d['nama_lengkap']) ?>" maxlength="150" <?= $dis ?>></div>
      <div><label class="lbl">Jenis kelamin</label><select class="inp" name="jenis_kelamin" data-req <?= $dis ?>><option value="">-- Pilih --</option>
        <?php foreach (['Laki-laki', 'Perempuan'] as $o): ?><option value="<?= e($o) ?>" <?= $d['jenis_kelamin'] === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
      <div><label class="lbl">Tempat lahir</label><input class="inp" name="tempat_lahir" data-req value="<?= e($d['tempat_lahir']) ?>" maxlength="50" <?= $dis ?>></div>
      <div><label class="lbl">Tanggal lahir</label><input class="inp" type="date" name="tanggal_lahir" data-req value="<?= e($d['tanggal_lahir']) ?>" <?= $dis ?>></div>
      <div><label class="lbl">Asal sekolah (SMP/MTs)</label><input class="inp" name="asal_sekolah" data-req value="<?= e($d['asal_sekolah']) ?>" maxlength="150" <?= $dis ?>></div>
      <div class="sm:col-span-2"><label class="lbl">Alamat lengkap</label><textarea class="inp" name="alamat" data-req rows="3" maxlength="1000" <?= $dis ?>><?= e($d['alamat']) ?></textarea></div>
      <div class="sm:col-span-2"><label class="lbl">Jalur pendaftaran</label><select class="inp" name="jalur" data-req <?= $dis ?>><option value="">-- Pilih jalur --</option>
        <?php foreach ($jalur as $j): ?><option value="<?= e($j) ?>" <?= $d['jalur'] === $j ? 'selected' : '' ?>><?= e($j) ?></option><?php endforeach; ?></select></div>
      <h3 class="sm:col-span-2 font-bold text-orange-600 mt-2"><i class="fa-solid fa-people-roof mr-1"></i>Data Orang Tua / Wali</h3>
      <div><label class="lbl">Hubungan</label><select class="inp" name="tipe_batih" data-req <?= $dis ?>><option value="">-- Pilih --</option>
        <?php foreach (['Ayah', 'Ibu', 'Wali'] as $o): ?><option value="<?= e($o) ?>" <?= $d['tipe_batih'] === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
      <div><label class="lbl">Nama lengkap</label><input class="inp" name="nama_batih" data-req value="<?= e($d['nama_batih']) ?>" maxlength="150" <?= $dis ?>></div>
      <div><label class="lbl">Pekerjaan</label><input class="inp" name="pekerjaan_batih" data-req value="<?= e($d['pekerjaan_batih']) ?>" maxlength="100" <?= $dis ?>></div>
      <div><label class="lbl">Nomor HP / WhatsApp aktif</label><input class="inp" name="kontak_batih" data-req value="<?= e($d['kontak_batih']) ?>" maxlength="25" <?= $dis ?>></div>
      <h3 class="sm:col-span-2 font-bold text-orange-600 mt-2"><i class="fa-solid fa-paperclip mr-1"></i>Dokumen Persyaratan</h3>
      <div class="sm:col-span-2">
        <?php if ($adaBerkas): ?><p class="text-sm mb-2 ml-2"><i class="fa-solid fa-file-circle-check text-green-600 mr-1"></i>Berkas tersimpan: <b><?= e($row['nama_berkas']) ?></b><?= $ro ? '' : ' (unggah lagi untuk mengganti)' ?></p><?php endif; ?>
        <?php if (!$ro): ?><input class="inp" type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png" data-req data-ada="<?= $adaBerkas ? '1' : '0' ?>">
        <p class="text-xs text-gray-500 mt-1 ml-3">Gabungkan semua dokumen (ijazah/SKL, KK, akta, dsb.) dalam satu berkas PDF/JPG/PNG, maks. <?= round(max_upload_bytes() / 1048576, 1) ?> MB.</p><?php endif; ?>
      </div>
      <?php if (!$ro): ?>
      <div class="sm:col-span-2 flex flex-wrap gap-3 mt-2">
        <button name="aksi" value="simpan" class="btn btn-ghost"><i class="fa-solid fa-floppy-disk"></i> Simpan Draft</button>
        <button name="aksi" value="kirim" class="btn btn-or" onclick="return confirm('Kirim pendaftaran? Data tidak dapat diubah setelah dikirim.')"><i class="fa-solid fa-paper-plane"></i> Kirim Pendaftaran</button>
      </div>
      <?php endif; ?>
    </form>
  </section>
</main>
<?php html_foot(); ?>
