<?php
require 'koneksi.php';
require_role('Operator');
require 'includes/landing.php';
$tab = ($_GET['tab'] ?? '') === 'pengaturan' ? 'pengaturan' : 'live';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan_konten') {
        $id = (int)($_POST['id'] ?? 0); $b = $_POST['bagian'] ?? '';
        if (!isset($BAGIAN[$b])) { flash('error', 'Bagian tidak valid.'); redirect('operator_dashboard.php'); }
        $judul = mb_substr(trim($_POST['judul'] ?? ''), 0, 200); $isi = trim($_POST['isi'] ?? '');
        $tautan = mb_substr(trim($_POST['tautan'] ?? ''), 0, 255); $urut = (int)($_POST['urutan'] ?? 0);
        if ($tautan !== '' && !preg_match('#^https?://#i', $tautan)) { flash('error', 'Tautan harus diawali http:// atau https://'); redirect('operator_dashboard.php'); }
        list($uerr, $unama, $utipe, $udata) = baca_upload('gambar', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        if ($uerr) { flash('error', $uerr); redirect('operator_dashboard.php'); }
        if ($id > 0) {
            db_exec('UPDATE ' . tabel_bagian($b) . ' SET judul=?, isi=?, tautan=?, urutan=? WHERE id=?', 'sssii', [$judul, $isi, $tautan, $urut, $id]);
        } else {
            $id = db_exec('INSERT INTO ' . tabel_bagian($b) . ' (judul,isi,tautan,urutan) VALUES (?,?,?,?)', 'sssi', [$judul, $isi, $tautan, $urut]);
            $id = is_int($id) ? $id : 0;
        }
        if ($id > 0) {
            if ($udata !== null) db_exec('UPDATE ' . tabel_bagian($b) . ' SET gambar_tipe=?, gambar=? WHERE id=?', 'ssi', [$utipe, $udata, $id]);
            elseif (isset($_POST['hapus_gambar'])) db_exec('UPDATE ' . tabel_bagian($b) . ' SET gambar_tipe=NULL, gambar=NULL WHERE id=?', 'i', [$id]);
            catat_log('simpan_konten', $b . ' #' . $id);
            flash('ok', 'Konten tersimpan.');
        } else flash('error', 'Gagal menyimpan konten.');
        redirect('operator_dashboard.php#m-' . $b);
    }
    if ($aksi === 'hapus_konten') {
        $tb = tabel_bagian($_POST['bagian'] ?? '');
        if (!$tb) { flash('error', 'Bagian tidak valid.'); redirect('operator_dashboard.php'); }
        db_exec('DELETE FROM ' . $tb . ' WHERE id=?', 'i', [(int)($_POST['id'] ?? 0)]);
        catat_log('hapus_konten', ($_POST['bagian'] ?? '') . ' #' . (int)($_POST['id'] ?? 0));
        flash('ok', 'Konten dihapus.'); redirect('operator_dashboard.php' . (isset(TABEL_BAGIAN[$_POST['bagian'] ?? '']) ? '#m-' . $_POST['bagian'] : ''));
    }
    if ($aksi === 'simpan_kontak') {
        foreach (['alamat', 'telepon', 'email', 'wa', 'maps_embed', 'instagram', 'facebook', 'youtube', 'tiktok'] as $k) set_setting($k, trim($_POST[$k] ?? ''));
        set_setting('siswa_aktif', (string)max(0, (int)($_POST['siswa_aktif'] ?? 0)));
        flash('ok', 'Kontak & identitas sekolah disimpan.'); redirect('operator_dashboard.php?tab=pengaturan');
    }
    if ($aksi === 'simpan_logo') {
        list($uerr, $unama, $utipe, $udata) = baca_upload('logo', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        if ($uerr) flash('error', $uerr);
        elseif ($udata !== null) {
            if (db_exec('INSERT INTO logo_sekolah (id, logo_tipe, logo) VALUES (1,?,?) ON DUPLICATE KEY UPDATE logo_tipe=VALUES(logo_tipe), logo=VALUES(logo)', 'ss', [$utipe, $udata])) { catat_log('ganti_logo'); flash('ok', 'Logo sekolah diperbarui.'); }
            else flash('error', 'Gagal menyimpan logo. Pastikan tabel logo_sekolah sudah ada (impor ulang database.sql).');
        } elseif (isset($_POST['hapus_logo'])) {
            db_exec('DELETE FROM logo_sekolah WHERE id=1'); catat_log('hapus_logo');
            flash('ok', 'Logo dihapus. Tampilan kembali memakai lambang bawaan.');
        } else flash('error', 'Pilih berkas logo terlebih dahulu.');
        redirect('operator_dashboard.php?tab=pengaturan#logo');
    }
    if ($aksi === 'simpan_gedung') {
        pastikan_tabel_gedung();
        list($uerr, $unama, $utipe, $udata) = baca_upload('gedung', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
        if ($uerr) flash('error', $uerr);
        elseif ($udata !== null) {
            if (db_exec('INSERT INTO foto_gedung (id, gedung_tipe, gedung) VALUES (1,?,?) ON DUPLICATE KEY UPDATE gedung_tipe=VALUES(gedung_tipe), gedung=VALUES(gedung)', 'ss', [$utipe, $udata])) { catat_log('ganti_foto_gedung'); flash('ok', 'Foto gedung sekolah diperbarui dan tampil di hero beranda.'); }
            else flash('error', 'Gagal menyimpan foto gedung.');
        } elseif (isset($_POST['hapus_gedung'])) {
            db_exec('DELETE FROM foto_gedung WHERE id=1'); catat_log('hapus_foto_gedung');
            flash('ok', 'Foto gedung dihapus dari hero beranda.');
        } else flash('error', 'Pilih berkas foto gedung terlebih dahulu.');
        redirect('operator_dashboard.php?tab=pengaturan#gedung');
    }
    if ($aksi === 'ganti_kode') {
        $ka = (string)($_POST['kode_admin'] ?? ''); $ko = (string)($_POST['kode_operator'] ?? '');
        $akhir_a = $ka !== '' ? $ka : setting('kode_universal', 'SMAGM'); $akhir_o = $ko !== '' ? $ko : setting('kode_operator', '');
        if (($ka !== '' && strlen($ka) < 4) || ($ko !== '' && strlen($ko) < 4)) flash('error', 'Kode minimal 4 karakter.');
        elseif ($akhir_a === $akhir_o) flash('error', 'Kode Admin dan kode Operator tidak boleh sama.');
        else {
            if ($ka !== '') set_setting('kode_universal', $ka);
            if ($ko !== '') set_setting('kode_operator', $ko);
            flash('ok', 'Kode masuk diperbarui dan langsung berlaku pada login berikutnya.');
        }
        redirect('operator_dashboard.php?tab=pengaturan');
    }
}

html_head('Dashboard Operator', 'bg-tebu min-h-screen');
?>
<header class="sm:sticky sm:top-0 z-50 min-h-14 bg-gray-900 text-white shadow">
  <div class="max-w-7xl mx-auto px-4 py-2 min-h-14 flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
    <span class="font-extrabold text-sm sm:text-base"><i class="fa-solid fa-screwdriver-wrench text-orange-400 mr-2"></i>Dashboard Operator</span>
    <nav class="flex gap-2 text-sm font-semibold order-3 sm:order-none w-full sm:w-auto justify-center">
      <a href="operator_dashboard.php" class="rounded-full px-4 py-1.5 <?= $tab === 'live' ? 'bg-orange-500' : 'bg-white/10' ?>"><i class="fa-solid fa-pen-ruler mr-1"></i>Tampilan Live</a>
      <a href="operator_dashboard.php?tab=pengaturan" class="rounded-full px-4 py-1.5 <?= $tab === 'pengaturan' ? 'bg-orange-500' : 'bg-white/10' ?>"><i class="fa-solid fa-gear mr-1"></i>Pengaturan</a>
    </nav>
    <div class="flex items-center gap-2"><?= theme_btn() ?><a href="index.php" target="_blank" class="rounded-full bg-orange-500 px-4 py-1.5 text-sm font-semibold"><i class="fa-solid fa-house sm:mr-1"></i><span class="hidden sm:inline">Lihat Website</span></a><a href="logout.php" class="rounded-full bg-white/10 px-4 py-1.5 text-sm font-semibold"><i class="fa-solid fa-right-from-bracket sm:mr-1"></i><span class="hidden sm:inline">Keluar</span></a></div>
  </div>
</header>
<?php if ($tab === 'live'): ?>
  <div class="max-w-3xl mx-auto px-4 pt-4"><?= flash_html() ?></div>
  <div class="bg-orange-100 text-orange-800 text-center text-sm py-2 font-medium">Mode edit: arahkan kursor ke elemen lalu klik tombol <b>Edit</b> (oranye). Gunakan tombol <b>+ Tambah</b> untuk menambah item.</div>
  <?php render_landing(true); ?>

  <div id="modal-konten" class="modal"><div class="card p-6 w-full max-w-lg">
    <div class="flex justify-between items-center mb-1"><h3 id="konten-judul" class="font-extrabold text-lg"></h3><button type="button" data-close class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700"><i class="fa-solid fa-xmark"></i></button></div>
    <p id="konten-hint" class="text-xs text-gray-500 mb-4"></p>
    <form id="form-konten" method="post" enctype="multipart/form-data" class="space-y-3">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_konten"><input type="hidden" name="id" value="0"><input type="hidden" name="bagian" value="">
      <div><label class="lbl">Judul / Nama</label><input class="inp" name="judul" maxlength="200"></div>
      <div><label class="lbl">Isi / Deskripsi</label><textarea class="inp" name="isi" rows="4"></textarea></div>
      <div><label class="lbl">Tautan (opsional)</label><input class="inp" name="tautan" maxlength="255" placeholder="https://..."></div>
      <div><label class="lbl">Foto (JPG/PNG/WEBP, maks. <?= round(max_upload_bytes() / 1048576, 1) ?> MB)</label><input class="inp" type="file" name="gambar" accept="image/*"></div>
      <label id="konten-hapusgbr" class="hidden flex items-center gap-2 text-sm ml-2"><input type="checkbox" name="hapus_gambar" class="accent-orange-500"> Hapus foto yang ada</label>
      <div><label class="lbl">Urutan tampil (angka kecil tampil lebih dulu)</label><input class="inp" type="number" name="urutan" value="0"></div>
      <div class="flex gap-2 pt-2"><button class="btn btn-or flex-1"><i class="fa-solid fa-floppy-disk"></i> Simpan</button></div>
    </form>
    <form id="konten-hapus" method="post" class="hidden mt-3" onsubmit="return confirm('Hapus item ini secara permanen?')">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus_konten"><input type="hidden" name="id" id="hapus-id" value="0"><input type="hidden" name="bagian" id="hapus-bagian" value="">
      <button class="btn btn-red w-full"><i class="fa-solid fa-trash"></i> Hapus Item</button>
    </form>
  </div></div>
<?php else:
  $f = fn($k) => e(setting($k)); ?>
  <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">
    <?= flash_html() ?>
    <section id="kontak" class="card p-6">
      <h2 class="font-extrabold text-xl mb-4"><i class="fa-solid fa-address-book text-orange-500 mr-2"></i>Kontak & Identitas Sekolah</h2>
      <form method="post" class="grid sm:grid-cols-2 gap-4">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_kontak">
        <div class="sm:col-span-2"><label class="lbl">Alamat sekolah</label><textarea class="inp" name="alamat" rows="2"><?= $f('alamat') ?></textarea></div>
        <div><label class="lbl">Telepon</label><input class="inp" name="telepon" value="<?= $f('telepon') ?>"></div>
        <div><label class="lbl">Email</label><input class="inp" name="email" value="<?= $f('email') ?>"></div>
        <div><label class="lbl">WhatsApp Customer Service (628xxx)</label><input class="inp" name="wa" value="<?= $f('wa') ?>"></div>
        <div><label class="lbl">Jumlah siswa aktif</label><input class="inp" type="number" min="0" name="siswa_aktif" value="<?= $f('siswa_aktif') ?>"></div>
        <div class="sm:col-span-2"><label class="lbl">Tautan peta (URL embed Google Maps)</label><input class="inp" name="maps_embed" value="<?= $f('maps_embed') ?>" placeholder="https://www.google.com/maps?q=...&output=embed"></div>
        <div><label class="lbl">Instagram (URL)</label><input class="inp" name="instagram" value="<?= $f('instagram') ?>"></div>
        <div><label class="lbl">Facebook (URL)</label><input class="inp" name="facebook" value="<?= $f('facebook') ?>"></div>
        <div><label class="lbl">YouTube (URL)</label><input class="inp" name="youtube" value="<?= $f('youtube') ?>"></div>
        <div><label class="lbl">TikTok (URL)</label><input class="inp" name="tiktok" value="<?= $f('tiktok') ?>"></div>
        <div class="sm:col-span-2"><button class="btn btn-or"><i class="fa-solid fa-floppy-disk"></i> Simpan</button></div>
      </form>
    </section>
    <section id="logo" class="card p-6">
      <h2 class="font-extrabold text-xl mb-1"><i class="fa-solid fa-image text-orange-500 mr-2"></i>Logo Sekolah</h2>
      <p class="text-sm text-gray-500 mb-4">Logo tampil di header semua halaman (beranda, login, dashboard). Disarankan gambar persegi dengan latar transparan (PNG/WEBP).</p>
      <form method="post" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_logo">
        <div class="tile p-4 inline-block"><?= logo_html() ?></div>
        <div><label class="lbl">Unggah logo baru (JPG/PNG/WEBP/GIF, maks. <?= round(max_upload_bytes() / 1048576, 1) ?> MB)</label><input class="inp" type="file" name="logo" accept="image/*"></div>
        <?php if (logo_ada()): ?><label class="flex items-center gap-2 text-sm ml-2"><input type="checkbox" name="hapus_logo" class="accent-orange-500"> Hapus logo (kembali ke lambang bawaan &quot;GM&quot;)</label><?php endif; ?>
        <button class="btn btn-or"><i class="fa-solid fa-floppy-disk"></i> Simpan Logo</button>
      </form>
    </section>
    <section id="gedung" class="card p-6">
      <h2 class="font-extrabold text-xl mb-1"><i class="fa-solid fa-building-columns text-orange-500 mr-2"></i>Foto Gedung Sekolah</h2>
      <p class="text-sm text-gray-500 mb-4">Foto tampil di bagian bawah hero beranda, di depan motif daun tebu, dengan bagian atas memudar. Disarankan foto gedung lebar (landscape) dengan langit/latar bersih; PNG transparan (gedung saja) hasilnya paling bagus.</p>
      <form method="post" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_gedung">
        <?php if (gedung_ada()): ?><div class="tile p-3 inline-block"><img src="gedung.php?v=<?= gedung_ada() ?>" alt="Foto gedung sekolah" class="max-h-40 rounded-xl"></div>
        <?php else: ?><p class="text-sm text-gray-500 ml-2">Belum ada foto gedung.</p><?php endif; ?>
        <div><label class="lbl">Unggah foto gedung (JPG/PNG/WEBP/GIF, maks. <?= round(max_upload_bytes() / 1048576, 1) ?> MB)</label><input class="inp" type="file" name="gedung" accept="image/*"></div>
        <?php if (gedung_ada()): ?><label class="flex items-center gap-2 text-sm ml-2"><input type="checkbox" name="hapus_gedung" class="accent-orange-500"> Hapus foto gedung dari hero</label><?php endif; ?>
        <button class="btn btn-or"><i class="fa-solid fa-floppy-disk"></i> Simpan Foto Gedung</button>
      </form>
    </section>
    <section class="card p-6">
      <h2 class="font-extrabold text-xl mb-1"><i class="fa-solid fa-key text-orange-500 mr-2"></i>Kode Masuk</h2>
      <p class="text-sm text-gray-500 mb-4">Kosongkan kolom yang tidak ingin diganti. Kode baru langsung berlaku pada percobaan login berikutnya. Kode Admin dan Operator harus berbeda.</p>
      <form method="post" class="grid sm:grid-cols-2 gap-4">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="ganti_kode">
        <div><label class="lbl">Kode universal / Admin baru</label><input class="inp" name="kode_admin" autocomplete="off" minlength="4" placeholder="mis. SMAGM"></div>
        <div><label class="lbl">Kode Operator baru</label><input class="inp" name="kode_operator" autocomplete="off" minlength="4"></div>
        <div class="sm:col-span-2"><button class="btn btn-or"><i class="fa-solid fa-lock"></i> Perbarui Kode</button></div>
      </form>
    </section>
  </main>
<?php endif; ?>
<?php html_foot(); ?>
