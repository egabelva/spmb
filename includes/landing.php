<?php
// Halaman publik PPDB. render_landing(false) = tampilan siswa, render_landing(true) = mode edit operator.
$BAGIAN = [
 'banner'    => ['Banner Utama','fa-image','Judul = headline utama, Isi = kalimat pendukung, Gambar = foto latar banner'],
 'profil'    => ['Profil Sekolah','fa-school','Judul = judul (opsional), Isi = deskripsi profil sekolah'],
 'visi'      => ['Visi','fa-eye','Isi = pernyataan visi sekolah'],
 'misi'      => ['Misi','fa-bullseye','Isi = satu butir misi per item'],
 'sejarah'   => ['Sejarah','fa-landmark','Isi = sejarah singkat sekolah'],
 'akreditasi'=> ['Akreditasi','fa-award','Judul = peringkat (mis. A), Isi = keterangan SK akreditasi'],
 'fasilitas' => ['Fasilitas','fa-building','Judul = nama fasilitas, Isi = deskripsi, Gambar = foto'],
 'prestasi'  => ['Prestasi','fa-trophy','Judul = nama prestasi, Isi = tingkat/tahun, Gambar = foto'],
 'ekskul'    => ['Ekstrakurikuler','fa-futbol','Judul = nama ekskul, Isi = keterangan kegiatan'],
 'organisasi'=> ['Organisasi','fa-people-group','Judul = nama organisasi, Isi = keterangan'],
 'guru'      => ['Profil Guru','fa-chalkboard-user','Judul = nama guru, Isi = mata pelajaran/jabatan, Gambar = foto'],
 'galeri'    => ['Galeri','fa-images','Judul = keterangan, Gambar = foto, Tautan = link video (mis. YouTube) bila berupa video'],
 'agenda'    => ['Agenda & Event','fa-calendar-days','Judul = nama event, Isi = waktu/keterangan'],
 'berita'    => ['Berita','fa-newspaper','Judul = judul berita, Isi = ringkasan, Gambar = foto'],
 'jadwal'    => ['Jadwal PPDB','fa-calendar-check','Judul = tahapan, Isi = tanggal pelaksanaan'],
 'syarat'    => ['Syarat Pendaftaran','fa-clipboard-check','Judul = satu syarat per item'],
 'biaya'     => ['Biaya','fa-money-bill-wave','Judul = komponen biaya, Isi = nominal/keterangan'],
 'faq'       => ['FAQ','fa-circle-question','Judul = pertanyaan, Isi = jawaban'],
];

function konten_semua() {
    static $k = null;
    if ($k === null) {
        $k = [];
        // Satu tabel per menu -> digabung dengan UNION ALL (nama tabel berasal dari konstanta TABEL_BAGIAN, bukan input pengguna)
        $parts = [];
        foreach (TABEL_BAGIAN as $bagian => $tabel) {
            $parts[] = "SELECT '" . $bagian . "' AS bagian, id, judul, isi, tautan, urutan, UNIX_TIMESTAMP(diubah) AS v, (gambar_tipe IS NOT NULL AND gambar_tipe<>'') AS ada_gambar FROM " . $tabel;
        }
        $q = implode(' UNION ALL ', $parts) . ' ORDER BY urutan, id';
        foreach (db_all($q) as $r) $k[$r['bagian']][] = $r;
    }
    return $k;
}
function konten($b) { $k = konten_semua(); return $k[$b] ?? []; }

function btn_edit($r, $edit) {
    if (!$edit) return '';
    global $BAGIAN; $b = $BAGIAN[$r['bagian']];
    $j = ['id'=>(int)$r['id'],'bagian'=>$r['bagian'],'label'=>$b[0],'hint'=>$b[2],'judul'=>$r['judul'],'isi'=>$r['isi'],'tautan'=>$r['tautan'],'urutan'=>(int)$r['urutan'],'ada_gambar'=>!empty($r['ada_gambar'])];
    return '<button type="button" class="edit-btn" data-item="' . e(json_encode($j, JSON_UNESCAPED_UNICODE)) . '" onclick="bukaEdit(this)"><i class="fa-solid fa-pen"></i> Edit</button>';
}
function btn_tambah($b, $edit) {
    if (!$edit) return '';
    global $BAGIAN; $x = $BAGIAN[$b];
    $j = ['id'=>0,'bagian'=>$b,'label'=>$x[0],'hint'=>$x[2],'judul'=>'','isi'=>'','tautan'=>'','urutan'=>0,'ada_gambar'=>false];
    return '<button type="button" class="add-btn" data-item="' . e(json_encode($j, JSON_UNESCAPED_UNICODE)) . '" onclick="bukaEdit(this)"><i class="fa-solid fa-plus"></i> Tambah ' . e($x[0]) . '</button>';
}
function gambar_atau($r, $ikon, $cls) {
    if (!empty($r['ada_gambar'])) return '<img src="gambar.php?b=' . e($r['bagian']) . '&id=' . (int)$r['id'] . '&v=' . (int)$r['v'] . '" alt="' . e($r['judul']) . '" loading="lazy" class="' . $cls . ' object-cover">';
    return '<div class="' . $cls . ' ph flex items-center justify-center"><i class="fa-solid ' . $ikon . ' text-4xl text-white/80"></i></div>';
}
function sec_head($ikon, $judul, $sub = '') {
    echo '<div class="text-center mb-10"><div class="w-14 h-14 mx-auto mb-3 rounded-full bg-orange-500 text-white flex items-center justify-center text-xl shadow"><i class="fa-solid ' . $ikon . '"></i></div>'
       . '<h2 class="text-2xl sm:text-3xl font-extrabold">' . e($judul) . '</h2>'
       . ($sub ? '<p class="text-gray-500 dark:text-gray-400 mt-1">' . e($sub) . '</p>' : '') . '</div>';
}
// Grid kartu generik. $gambar=true -> tampilkan foto/placeholder di atas kartu.
function kartu_grid($b, $ikon, $edit, $gambar, $kolom = 'sm:grid-cols-2 lg:grid-cols-3') {
    $ew = $edit ? ' edit-wrap' : '';
    echo '<div class="grid ' . $kolom . ' gap-6">';
    foreach (konten($b) as $r) {
        echo '<div class="card overflow-hidden' . $ew . '">' . btn_edit($r, $edit);
        if ($gambar) echo gambar_atau($r, $ikon, 'w-full h-44');
        echo '<div class="p-5">';
        if (!$gambar) echo '<div class="w-11 h-11 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mb-3"><i class="fa-solid ' . $ikon . '"></i></div>';
        echo '<h3 class="font-bold text-lg">' . e($r['judul']) . '</h3>';
        if ($r['isi'] !== '') echo '<p class="text-sm text-gray-600 dark:text-gray-300 mt-1">' . nl2br(e($r['isi'])) . '</p>';
        echo '</div></div>';
    }
    echo '</div>' . btn_tambah($b, $edit);
}
function blok_teks($b, $ikon, $edit) {
    global $BAGIAN;
    $ew = $edit ? ' edit-wrap' : '';
    echo '<div class="card p-6"><h3 class="font-bold text-lg mb-3 flex items-center gap-2 text-orange-600"><i class="fa-solid ' . $ikon . '"></i>' . e($BAGIAN[$b][0]) . '</h3>';
    foreach (konten($b) as $r) {
        echo '<div class="mb-3 rounded-2xl p-2' . $ew . '">' . btn_edit($r, $edit);
        if ($b === 'misi') echo '<p class="flex gap-2"><i class="fa-solid fa-circle-check text-orange-500 mt-1"></i><span>' . e($r['isi']) . '</span></p>';
        else {
            if ($b === 'akreditasi') echo '<p class="text-4xl font-extrabold text-orange-500">' . e($r['judul']) . '</p>';
            elseif ($r['judul'] !== '') echo '<p class="font-semibold">' . e($r['judul']) . '</p>';
            echo '<p class="text-gray-600 dark:text-gray-300">' . nl2br(e($r['isi'])) . '</p>';
        }
        echo '</div>';
    }
    echo btn_tambah($b, $edit) . '</div>';
}

function render_landing($edit = false) {
    $ew = $edit ? ' edit-wrap' : '';
    $top = $edit ? 'top-14' : 'top-0';
    $st = db_one("SELECT COUNT(*) AS total, SUM(status='Diterima') AS dit FROM pendaftar WHERE status<>'Draft'");
    $total = (int)($st['total'] ?? 0); $dit = (int)($st['dit'] ?? 0);
    $umum = setting('hasil_diumumkan') === '1';
    $kuota = (int)setting('kuota', '0'); $aktif = (int)setting('siswa_aktif', '0');
    $deadline = setting('deadline', '');
    $banner = konten('banner'); $b0 = $banner[0] ?? null;
    $daftar = $edit ? '#' : 'login.php?tab=daftar';
    $masuk = $edit ? '#' : 'login.php';
    $wa = preg_replace('/\D/', '', setting('wa', ''));
    $nav = [['beranda','fa-house','Beranda'],['ppdb','fa-clipboard-list','Info PPDB'],['tentang','fa-school','Tentang'],['fasilitas','fa-building','Fasilitas'],
            ['prestasi','fa-trophy','Prestasi'],['kegiatan','fa-people-group','Kegiatan'],['guru','fa-chalkboard-user','Guru'],['galeri','fa-images','Galeri'],
            ['agenda','fa-calendar-days','Agenda'],['faq','fa-circle-question','FAQ'],['kontak','fa-location-dot','Kontak']];
?>
<nav class="sticky <?= $top ?> z-40 bg-white/90 dark:bg-gray-900/90 backdrop-blur shadow-sm no-print">
  <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
    <a href="#beranda"><?= logo_html(true) ?></a>
    <div class="hidden xl:flex items-center gap-1 text-sm font-medium">
      <?php foreach ($nav as $n): ?><a href="#<?= $n[0] ?>" class="px-3 py-2 rounded-full hover:bg-orange-100 dark:hover:bg-gray-700 hover:text-orange-600"><i class="fa-solid <?= $n[1] ?> mr-1 text-orange-500"></i><?= $n[2] ?></a><?php endforeach; ?>
    </div>
    <div class="flex items-center gap-2">
      <?= theme_btn() ?>
      <?php if (!$edit): ?><a href="<?= $masuk ?>" class="btn btn-ghost btn-sm hidden sm:inline-flex"><i class="fa-solid fa-right-to-bracket"></i> Masuk</a><?php endif; ?>
      <?php if (!$edit): ?><a href="<?= $daftar ?>" class="btn btn-or btn-sm"><i class="fa-solid fa-pen-to-square"></i> Daftar</a><?php endif; ?>
      <button id="navBtn" type="button" class="xl:hidden w-10 h-10 rounded-full bg-orange-100 text-orange-600"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
  <div id="navMenu" class="hidden xl:hidden px-4 pb-4 grid grid-cols-2 gap-2 text-sm font-medium">
    <?php foreach ($nav as $n): ?><a href="#<?= $n[0] ?>" class="px-3 py-2 rounded-full bg-orange-50 dark:bg-gray-800"><i class="fa-solid <?= $n[1] ?> mr-1 text-orange-500"></i><?= $n[2] ?></a><?php endforeach; ?>
  </div>
</nav>

<header id="beranda" class="relative overflow-hidden text-white<?= $ew ?>">
  <?php if ($b0 && !empty($b0['ada_gambar'])): ?>
    <img src="gambar.php?b=banner&id=<?= (int)$b0['id'] ?>&v=<?= (int)$b0['v'] ?>" alt="" class="absolute inset-0 w-full h-full object-cover"><div class="absolute inset-0 bg-orange-700/70"></div>
  <?php else: ?><div class="absolute inset-0 hero-grad"></div><?php endif; ?>
  <?= $b0 ? btn_edit($b0, $edit) : btn_tambah('banner', $edit) ?>
  <div class="relative max-w-6xl mx-auto px-4 py-16 sm:py-24 text-center">
    <span class="inline-block rounded-full bg-white/20 px-4 py-1 text-xs font-semibold tracking-widest mb-5">SMART • HEBAT • BERMARTABAT</span>
    <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight"><?= e($b0['judul'] ?? 'Penerimaan Murid Baru SMA Gunung Madu 2027') ?></h1>
    <p class="mt-4 text-lg max-w-2xl mx-auto text-white/90"><?= e($b0['isi'] ?? 'Daftarkan dirimu secara online dan jadilah bagian dari keluarga besar kami.') ?></p>
    <div class="mt-8 flex flex-wrap gap-3 justify-center">
      <?php if (!$edit): ?><a href="<?= $daftar ?>" class="btn bg-white text-orange-600 hover:bg-orange-50 text-lg px-8"><i class="fa-solid fa-rocket"></i> Daftar Sekarang</a><?php endif; ?>
      <?php if (!$edit): ?><a href="<?= $masuk ?>" class="btn border-white text-white hover:bg-white/10"><i class="fa-solid fa-right-to-bracket"></i> Masuk / Cek Hasil</a><?php endif; ?>
    </div>
    <div class="mt-10">
      <p class="text-sm mb-3 font-semibold"><i class="fa-solid fa-hourglass-half mr-1"></i> Pendaftaran ditutup dalam</p>
      <div id="countdown" data-deadline="<?= e($deadline) ?>" class="flex justify-center gap-3">
        <div class="cd-box"><b id="cd-d">--</b><span>Hari</span></div><div class="cd-box"><b id="cd-h">--</b><span>Jam</span></div>
        <div class="cd-box"><b id="cd-m">--</b><span>Menit</span></div><div class="cd-box"><b id="cd-s">--</b><span>Detik</span></div>
      </div>
    </div>
  </div>
</header>

<section class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php $stat = [['fa-user-graduate','Siswa aktif', $aktif],['fa-users','Pendaftar saat ini', $total],['fa-door-open','Kuota penerimaan', $kuota],['fa-circle-check','Siswa diterima', $umum ? $dit : '-']];
    foreach ($stat as $s): ?>
    <div class="card p-5 text-center"><div class="text-2xl text-orange-500 mb-1"><i class="fa-solid <?= $s[0] ?>"></i></div>
      <div class="text-3xl font-extrabold"><?= e($s[2]) ?></div><div class="text-xs text-gray-500 dark:text-gray-400 font-medium"><?= $s[1] ?></div></div>
    <?php endforeach; ?>
  </div>
  <?php if ($edit): ?><p class="text-xs text-center mt-2 text-gray-500">Angka pendaftar & diterima dihitung otomatis. Jumlah siswa aktif diubah di tab <a class="underline text-orange-600" href="operator_dashboard.php?tab=pengaturan">Pengaturan</a>.</p><?php endif; ?>
</section>

<section id="ppdb" class="py-16"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-clipboard-list', 'Informasi PPDB 2027', 'Jadwal, jalur, syarat, dan biaya pendaftaran'); ?>
  <div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-6"><h3 class="font-bold text-lg mb-4 text-orange-600"><i class="fa-solid fa-calendar-check mr-2"></i>Jadwal PPDB</h3>
      <ol class="space-y-3">
      <?php $i = 0; foreach (konten('jadwal') as $r): $i++; ?>
        <li class="flex gap-3 rounded-2xl p-2<?= $ew ?>"><?= btn_edit($r, $edit) ?><span class="w-8 h-8 shrink-0 rounded-full bg-orange-500 text-white flex items-center justify-center text-sm font-bold"><?= $i ?></span>
          <div><p class="font-semibold"><?= e($r['judul']) ?></p><p class="text-sm text-gray-500 dark:text-gray-400"><?= e($r['isi']) ?></p></div></li>
      <?php endforeach; ?></ol><?= btn_tambah('jadwal', $edit) ?></div>
    <div class="card p-6"><h3 class="font-bold text-lg mb-4 text-orange-600"><i class="fa-solid fa-route mr-2"></i>Jalur Penerimaan</h3>
      <div class="flex flex-wrap gap-2 mb-6"><?php foreach (jalur_list() as $j): ?><span class="rounded-full bg-orange-100 text-orange-700 px-4 py-2 text-sm font-semibold"><?= e($j) ?></span><?php endforeach; ?></div>
      <h3 class="font-bold text-lg mb-3 text-orange-600"><i class="fa-solid fa-clipboard-check mr-2"></i>Syarat Pendaftaran</h3>
      <ul class="space-y-2"><?php foreach (konten('syarat') as $r): ?><li class="flex gap-2 rounded-2xl p-1<?= $ew ?>"><?= btn_edit($r, $edit) ?><i class="fa-solid fa-circle-check text-green-500 mt-1"></i><span><?= e($r['judul']) ?></span></li><?php endforeach; ?></ul>
      <?= btn_tambah('syarat', $edit) ?>
      <h3 class="font-bold text-lg mt-6 mb-3 text-orange-600"><i class="fa-solid fa-money-bill-wave mr-2"></i>Biaya</h3>
      <ul class="space-y-2"><?php foreach (konten('biaya') as $r): ?><li class="flex justify-between gap-3 rounded-2xl p-1<?= $ew ?>"><?= btn_edit($r, $edit) ?><span><?= e($r['judul']) ?></span><b class="text-orange-600"><?= e($r['isi']) ?></b></li><?php endforeach; ?></ul>
      <?= btn_tambah('biaya', $edit) ?>
    </div>
  </div>
</div></section>

<section id="tentang" class="py-16 bg-white/60 dark:bg-gray-800/40"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-school', 'Tentang SMA Gunung Madu', 'Mengenal sekolah kami lebih dekat'); ?>
  <div class="grid md:grid-cols-2 gap-6">
    <?php blok_teks('profil', 'fa-school', $edit); blok_teks('akreditasi', 'fa-award', $edit); blok_teks('visi', 'fa-eye', $edit); blok_teks('misi', 'fa-bullseye', $edit); ?>
    <div class="md:col-span-2"><?php blok_teks('sejarah', 'fa-landmark', $edit); ?></div>
  </div>
</div></section>

<section id="fasilitas" class="py-16"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-building', 'Fasilitas Sekolah', 'Laboratorium, perpustakaan, ruang kelas, masjid, lapangan olahraga, dan lainnya'); kartu_grid('fasilitas', 'fa-building', $edit, true); ?>
</div></section>

<section id="prestasi" class="py-16 bg-white/60 dark:bg-gray-800/40"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-trophy', 'Prestasi Sekolah', 'Bukti semangat dan kerja keras warga sekolah'); kartu_grid('prestasi', 'fa-trophy', $edit, true); ?>
</div></section>

<section id="kegiatan" class="py-16"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-people-group', 'Ekstrakurikuler & Organisasi', 'Kembangkan bakat dan jiwa kepemimpinanmu'); ?>
  <h3 class="font-bold text-xl mb-4"><i class="fa-solid fa-futbol text-orange-500 mr-2"></i>Ekstrakurikuler</h3>
  <?php kartu_grid('ekskul', 'fa-futbol', $edit, false, 'sm:grid-cols-2 lg:grid-cols-4'); ?>
  <h3 class="font-bold text-xl mt-10 mb-4"><i class="fa-solid fa-people-group text-orange-500 mr-2"></i>Organisasi Sekolah</h3>
  <?php kartu_grid('organisasi', 'fa-people-group', $edit, false, 'sm:grid-cols-2 lg:grid-cols-4'); ?>
</div></section>

<section id="guru" class="py-16 bg-white/60 dark:bg-gray-800/40"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-chalkboard-user', 'Profil Guru', 'Tenaga pendidik yang siap membimbing'); ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
  <?php foreach (konten('guru') as $r): ?>
    <div class="card p-5 text-center<?= $ew ?>"><?= btn_edit($r, $edit) ?>
      <?= gambar_atau($r, 'fa-user', 'w-24 h-24 rounded-full mx-auto mb-3') ?>
      <h3 class="font-bold"><?= e($r['judul']) ?></h3><p class="text-sm text-gray-500 dark:text-gray-400"><?= e($r['isi']) ?></p></div>
  <?php endforeach; ?></div><?= btn_tambah('guru', $edit) ?>
</div></section>

<section id="galeri" class="py-16"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-images', 'Galeri Sekolah', 'Foto dan video suasana sekolah'); ?>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
  <?php foreach (konten('galeri') as $r): ?>
    <figure class="card overflow-hidden relative<?= $ew ?>"><?= btn_edit($r, $edit) ?>
      <?= gambar_atau($r, 'fa-image', 'w-full h-40') ?>
      <?php if ($r['tautan'] !== ''): ?><a href="<?= e($r['tautan']) ?>" target="_blank" rel="noopener" class="absolute inset-0 flex items-center justify-center bg-black/30 text-white text-4xl"><i class="fa-solid fa-circle-play"></i></a><?php endif; ?>
      <figcaption class="p-3 text-sm font-medium"><?= e($r['judul']) ?></figcaption></figure>
  <?php endforeach; ?></div><?= btn_tambah('galeri', $edit) ?>
</div></section>

<section id="agenda" class="py-16 bg-white/60 dark:bg-gray-800/40"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-calendar-days', 'Agenda & Event Tahunan', 'Kegiatan rutin yang menanti kamu'); kartu_grid('agenda', 'fa-calendar-days', $edit, false); ?>
  <h3 class="font-bold text-xl mt-12 mb-4"><i class="fa-solid fa-newspaper text-orange-500 mr-2"></i>Berita Terbaru</h3>
  <?php kartu_grid('berita', 'fa-newspaper', $edit, true); ?>
</div></section>

<section id="faq" class="py-16"><div class="max-w-3xl mx-auto px-4">
  <?php sec_head('fa-circle-question', 'Pertanyaan yang Sering Diajukan'); ?>
  <div class="space-y-3">
  <?php foreach (konten('faq') as $r): ?>
    <details class="faq card px-5 py-4<?= $ew ?>"><?= btn_edit($r, $edit) ?>
      <summary class="flex justify-between items-center gap-3"><span><?= e($r['judul']) ?></span><i class="fa-solid fa-chevron-down text-orange-500 transition"></i></summary>
      <p class="mt-3 text-gray-600 dark:text-gray-300"><?= nl2br(e($r['isi'])) ?></p></details>
  <?php endforeach; ?></div><?= btn_tambah('faq', $edit) ?>
</div></section>

<section id="kontak" class="py-16 bg-white/60 dark:bg-gray-800/40"><div class="max-w-6xl mx-auto px-4">
  <?php sec_head('fa-location-dot', 'Kontak & Lokasi', 'Hubungi kami jika mengalami kendala'); ?>
  <div class="grid lg:grid-cols-2 gap-6">
    <div class="card p-6 space-y-4 <?= $ew ?>">
      <?php if ($edit): ?><a href="operator_dashboard.php?tab=pengaturan#kontak" class="edit-btn"><i class="fa-solid fa-pen"></i> Edit</a><?php endif; ?>
      <p class="flex gap-3"><i class="fa-solid fa-location-dot text-orange-500 mt-1"></i><span><?= nl2br(e(setting('alamat'))) ?></span></p>
      <p class="flex gap-3"><i class="fa-solid fa-phone text-orange-500 mt-1"></i><span><?= e(setting('telepon')) ?></span></p>
      <p class="flex gap-3"><i class="fa-solid fa-envelope text-orange-500 mt-1"></i><span><?= e(setting('email')) ?></span></p>
      <?php if ($wa): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" class="btn btn-green"><i class="fa-brands fa-whatsapp text-lg"></i> Hubungi Customer Service</a><?php endif; ?>
      <div class="flex gap-3 pt-2 text-xl">
        <?php foreach ([['instagram','fa-instagram'],['facebook','fa-facebook'],['youtube','fa-youtube'],['tiktok','fa-tiktok']] as $s): if (setting($s[0]) !== ''): ?>
          <a href="<?= e(setting($s[0])) ?>" target="_blank" rel="noopener" title="<?= ucfirst($s[0]) ?>" class="w-11 h-11 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center hover:bg-orange-500 hover:text-white"><i class="fa-brands <?= $s[1] ?>"></i></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
    <div class="card overflow-hidden min-h-[18rem]">
      <?php if (setting('maps_embed') !== ''): ?><iframe src="<?= e(setting('maps_embed')) ?>" class="w-full h-full min-h-[18rem]" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Lokasi sekolah"></iframe>
      <?php else: ?><div class="p-8 text-gray-500">Peta lokasi belum diatur.</div><?php endif; ?>
    </div>
  </div>
</div></section>

<footer class="bg-gray-900 text-gray-300 py-8 text-center text-sm">
  <p class="font-bold text-white">SMA Gunung Madu</p><p class="text-orange-400 tracking-widest text-xs mb-2">SMART • HEBAT • BERMARTABAT</p>
  <p>&copy; <?= date('Y') ?> Panitia SPMB SMA Gunung Madu. Hak cipta dilindungi.</p>
  <?php if (!$edit): ?><p class="mt-2 no-print"><a href="login.php" class="text-gray-500 hover:text-orange-400">Masuk Admin / Operator</a></p><?php endif; ?>
</footer>
<?php if ($wa && !$edit): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" title="Chat Customer Service" class="no-print fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full bg-green-500 text-white text-3xl flex items-center justify-center shadow-lg hover:bg-green-600"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
<?php }
