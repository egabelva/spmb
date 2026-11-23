<?php
// Halaman publik PPDB. render_landing(false) = tampilan siswa, render_landing(true) = mode edit operator.
// Semua informasi tampil sebagai kartu seragam; isinya terbuka di jendela (modal) saat kartu diklik.
$BAGIAN = [
 'banner'    => ['Banner Utama','fa-image','Judul = headline utama, Isi = kalimat pendukung, Gambar = foto latar banner'],
 'profil'    => ['Profil Sekolah','fa-school','Judul = judul (opsional), Isi = deskripsi profil sekolah'],
 'visi'      => ['Visi','fa-eye','Isi = pernyataan visi sekolah'],
 'misi'      => ['Misi','fa-bullseye','Isi = satu butir misi per item'],
 'sejarah'   => ['Sejarah','fa-landmark','Isi = sejarah singkat sekolah'],
 'akreditasi'=> ['Akreditasi','fa-award','Judul = peringkat (mis. A), Isi = keterangan SK akreditasi'],
 'fasilitas' => ['Fasilitas','fa-building','Judul = nama fasilitas, Isi = deskripsi, Gambar = foto'],
 'prestasi'  => ['Prestasi','fa-trophy','Judul = nama prestasi, Isi = tingkat/tahun, Gambar = foto'],
 'ekskul'    => ['Ekstrakurikuler','fa-futbol','Judul = nama ekskul, Isi = keterangan kegiatan, Gambar = foto kegiatan'],
 'organisasi'=> ['Organisasi','fa-people-group','Judul = nama organisasi, Isi = keterangan, Gambar = foto kegiatan'],
 'guru'      => ['Profil Guru','fa-chalkboard-user','Judul = nama guru, Isi = mata pelajaran/jabatan, Gambar = foto'],
 'galeri'    => ['Galeri','fa-images','Judul = keterangan, Gambar = foto, Tautan = link video (mis. YouTube) bila berupa video'],
 'agenda'    => ['Agenda & Event','fa-calendar-days','Judul = nama event, Isi = waktu/keterangan, Gambar = foto event'],
 'jadwal'    => ['Jadwal PPDB','fa-calendar-check','Judul = tahapan, Isi = tanggal pelaksanaan'],
 'syarat'    => ['Syarat Pendaftaran','fa-clipboard-check','Judul = satu syarat per item'],
 'biaya'     => ['Biaya','fa-money-bill-wave','Judul = komponen biaya, Isi = nominal/keterangan'],
];

// Kartu informasi: kunci => [ikon, judul, deskripsi singkat]. Kunci sama dengan nama bagian konten.
$KARTU = [
 'jadwal'    => ['fa-calendar-check','Jadwal PPDB','Tahapan dan tanggal pelaksanaan'],
 'jalur'     => ['fa-route','Jalur Penerimaan','Pilihan jalur pendaftaran'],
 'syarat'    => ['fa-clipboard-check','Syarat Pendaftaran','Dokumen yang perlu disiapkan'],
 'biaya'     => ['fa-money-bill-wave','Biaya','Rincian biaya pendaftaran'],
 'profil'    => ['fa-school','Profil Sekolah','Mengenal SMA Gunung Madu'],
 'visi'      => ['fa-eye','Visi','Arah dan cita-cita sekolah'],
 'misi'      => ['fa-bullseye','Misi','Langkah mewujudkan visi'],
 'sejarah'   => ['fa-landmark','Sejarah','Perjalanan sekolah kami'],
 'akreditasi'=> ['fa-award','Akreditasi','Peringkat dan SK akreditasi'],
 'fasilitas' => ['fa-building','Fasilitas','Sarana dan prasarana'],
 'prestasi'  => ['fa-trophy','Prestasi','Capaian warga sekolah'],
 'ekskul'    => ['fa-futbol','Ekstrakurikuler','Kembangkan bakatmu'],
 'organisasi'=> ['fa-people-group','Organisasi','Latih jiwa kepemimpinan'],
 'guru'      => ['fa-chalkboard-user','Profil Guru','Tenaga pendidik kami'],
 'galeri'    => ['fa-images','Galeri','Foto dan video sekolah'],
 'agenda'    => ['fa-calendar-days','Agenda & Event','Kegiatan sepanjang tahun'],
 'kontak'    => ['fa-location-dot','Kontak & Lokasi','Alamat, telepon, dan email'],
];
$KARTU_DAFTAR = ['jadwal', 'jalur', 'syarat', 'biaya'];
$KARTU_SEKOLAH = ['profil', 'visi', 'misi', 'sejarah', 'akreditasi', 'fasilitas', 'prestasi', 'ekskul', 'organisasi', 'guru', 'galeri', 'agenda', 'kontak'];

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
function sec_label($ikon, $judul, $sub = '') {
    echo '<div class="sec-head flex items-center gap-3 mb-5"><span class="w-10 h-10 shrink-0 rounded-full bg-gray-700 text-white flex items-center justify-center"><i class="fa-solid ' . $ikon . '"></i></span>'
       . '<div><h2 class="text-xl sm:text-2xl font-extrabold leading-tight">' . e($judul) . '</h2>'
       . ($sub ? '<p class="text-sm text-gray-500 dark:text-gray-400">' . e($sub) . '</p>' : '') . '</div>'
       . '<span class="flex-1 h-px bg-gray-300 dark:bg-gray-600 ml-2"></span></div>';
}

// ----- Kartu (semua kartu informasi memakai komponen yang sama) -----
function kartu_info($key, $edit) {
    global $KARTU;
    $k = $KARTU[$key];
    if ($key === 'kontak') $badge = '<span class="badge-gray">Info</span>';
    else $badge = '<span class="badge-gray">' . ($key === 'jalur' ? count(jalur_list()) : count(konten($key))) . ' item</span>';
    return '<button type="button" data-open="info-' . e($key) . '" class="info-card"><span class="chip"><i class="fa-solid ' . $k[0] . '"></i></span>'
         . '<span class="block font-bold mt-3">' . e($k[1]) . '</span>'
         . '<span class="block text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">' . e($k[2]) . '</span>'
         . '<span class="mt-auto flex items-center justify-between w-full text-xs font-semibold">' . $badge . '<span class="text-orange-600">' . ($edit ? 'Kelola' : 'Lihat') . ' <i class="fa-solid fa-arrow-right ml-1"></i></span></span></button>';
}
function kartu_daftar($edit) {
    $href = $edit ? '#' : 'login.php?tab=daftar';
    return '<a href="' . $href . '" class="info-card info-card-cta"><span class="chip chip-or"><i class="fa-solid fa-rocket"></i></span>'
         . '<span class="block font-bold mt-3">Daftar Sekarang</span>'
         . '<span class="block text-xs text-white/90 mt-1 mb-3">Buat akun dan isi formulir secara online</span>'
         . '<span class="mt-auto flex items-center justify-end w-full text-xs font-bold">Mulai <i class="fa-solid fa-arrow-right ml-1"></i></span></a>';
}

// ----- Isi jendela (modal) tiap kartu -----
function isi_modal($b, $edit) {
    global $KARTU;
    $ew = $edit ? ' edit-wrap' : '';
    $pr = $edit ? ' pr-20' : '';
    $ikon = $KARTU[$b][0];
    if ($b === 'jalur') {
        $jl = jalur_list();
        if (!$jl) echo '<p class="text-center text-gray-500 py-8">Belum ada jalur penerimaan.</p>';
        echo '<div class="flex flex-wrap gap-2">';
        foreach ($jl as $j) echo '<span class="rounded-full bg-orange-100 text-orange-700 px-4 py-2 text-sm font-semibold">' . e($j) . '</span>';
        echo '</div>';
        if ($edit) echo '<p class="text-xs text-gray-500 mt-5"><i class="fa-solid fa-circle-info mr-1"></i>Jalur penerimaan diatur oleh Admin di Dashboard Admin &rarr; Pengaturan PPDB.</p>';
        return;
    }
    if ($b === 'kontak') {
        $wa = preg_replace('/\D/', '', setting('wa', ''));
        echo '<div><div class="tile p-5 space-y-4' . $ew . '">';
        if ($edit) echo '<a href="operator_dashboard.php?tab=pengaturan#kontak" class="edit-btn"><i class="fa-solid fa-pen"></i> Edit</a>';
        echo '<p class="flex gap-3"><i class="fa-solid fa-location-dot text-orange-500 mt-1"></i><span>' . nl2br(e(setting('alamat'))) . '</span></p>'
           . '<p class="flex gap-3"><i class="fa-solid fa-phone text-orange-500 mt-1"></i><span>' . e(setting('telepon')) . '</span></p>'
           . '<p class="flex gap-3"><i class="fa-solid fa-envelope text-orange-500 mt-1"></i><span>' . e(setting('email')) . '</span></p>';
        if ($wa) echo '<a href="https://wa.me/' . $wa . '" target="_blank" rel="noopener" class="btn btn-green"><i class="fa-brands fa-whatsapp text-lg"></i> Hubungi Customer Service</a>';
        echo '<div class="flex gap-3 pt-2 text-xl">';
        foreach ([['instagram','fa-instagram'],['facebook','fa-facebook'],['youtube','fa-youtube'],['tiktok','fa-tiktok']] as $s) {
            if (setting($s[0]) !== '') echo '<a href="' . e(setting($s[0])) . '" target="_blank" rel="noopener" title="' . ucfirst($s[0]) . '" class="w-11 h-11 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center hover:bg-orange-500 hover:text-white"><i class="fa-brands ' . $s[1] . '"></i></a>';
        }
        echo '</div></div>';
        return;
    }
    $items = konten($b);
    if (!$items) { echo '<p class="text-center text-gray-500 py-8">Belum ada data.' . ($edit ? ' Klik <b>+ Tambah</b> di atas.' : '') . '</p>'; return; }

    if (in_array($b, ['profil', 'sejarah', 'visi', 'akreditasi'], true)) {
        foreach ($items as $r) {
            echo '<div class="tile p-5 mb-3' . $ew . $pr . '">' . btn_edit($r, $edit);
            if ($b === 'akreditasi') echo '<p class="text-5xl font-extrabold text-orange-500">' . e($r['judul']) . '</p>';
            elseif ($r['judul'] !== '') echo '<p class="font-bold mb-1">' . e($r['judul']) . '</p>';
            echo '<p class="text-gray-600 dark:text-gray-300">' . nl2br(e($r['isi'])) . '</p></div>';
        }
        return;
    }
    if ($b === 'misi') {
        foreach ($items as $r) echo '<div class="tile p-4 mb-3 flex gap-3' . $ew . $pr . '">' . btn_edit($r, $edit) . '<i class="fa-solid fa-circle-check text-orange-500 mt-1"></i><span>' . e($r['isi']) . '</span></div>';
        return;
    }
    if (in_array($b, ['fasilitas', 'prestasi', 'agenda', 'ekskul', 'organisasi'], true)) {
        echo '<div class="grid sm:grid-cols-2 gap-4">';
        foreach ($items as $r) {
            echo '<div class="tile overflow-hidden' . $ew . '">' . btn_edit($r, $edit) . gambar_atau($r, $ikon, 'w-full h-40') . '<div class="p-4"><h4 class="font-bold">' . e($r['judul']) . '</h4>';
            if ($r['isi'] !== '') echo '<p class="text-sm text-gray-600 dark:text-gray-300 mt-1">' . nl2br(e($r['isi'])) . '</p>';
            echo '</div></div>';
        }
        echo '</div>';
        return;
    }
    if ($b === 'guru') {
        echo '<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">';
        foreach ($items as $r) echo '<div class="tile p-4 text-center' . $ew . '">' . btn_edit($r, $edit) . gambar_atau($r, 'fa-user', 'w-20 h-20 rounded-full mx-auto mb-3') . '<h4 class="font-bold">' . e($r['judul']) . '</h4><p class="text-sm text-gray-500 dark:text-gray-400">' . e($r['isi']) . '</p></div>';
        echo '</div>';
        return;
    }
    if ($b === 'galeri') {
        echo '<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">';
        foreach ($items as $r) {
            echo '<figure class="tile overflow-hidden relative' . $ew . '">' . btn_edit($r, $edit) . gambar_atau($r, 'fa-image', 'w-full h-32');
            if ($r['tautan'] !== '') echo '<a href="' . e($r['tautan']) . '" target="_blank" rel="noopener" class="absolute inset-x-0 top-0 h-32 flex items-center justify-center bg-black/30 text-white text-4xl"><i class="fa-solid fa-circle-play"></i></a>';
            echo '<figcaption class="p-3 text-sm font-medium">' . e($r['judul']) . '</figcaption></figure>';
        }
        echo '</div>';
        return;
    }
    if ($b === 'jadwal') {
        $i = 0;
        foreach ($items as $r) { $i++; echo '<div class="tile p-4 mb-3 flex gap-3' . $ew . $pr . '">' . btn_edit($r, $edit) . '<span class="w-8 h-8 shrink-0 rounded-full bg-gray-700 text-white flex items-center justify-center text-sm font-bold">' . $i . '</span><div><p class="font-semibold">' . e($r['judul']) . '</p><p class="text-sm text-gray-500 dark:text-gray-400">' . e($r['isi']) . '</p></div></div>'; }
        return;
    }
    if ($b === 'syarat') {
        foreach ($items as $r) echo '<div class="tile p-4 mb-3 flex gap-3' . $ew . $pr . '">' . btn_edit($r, $edit) . '<i class="fa-solid fa-circle-check text-green-500 mt-1"></i><span>' . e($r['judul']) . '</span></div>';
        return;
    }
    if ($b === 'biaya') {
        foreach ($items as $r) echo '<div class="tile p-4 mb-3 flex justify-between gap-3' . $ew . $pr . '">' . btn_edit($r, $edit) . '<span>' . e($r['judul']) . '</span><b class="text-orange-600">' . e($r['isi']) . '</b></div>';
        return;
    }
    // ekskul, organisasi, agenda
    echo '<div class="grid sm:grid-cols-2 gap-4">';
    foreach ($items as $r) {
        echo '<div class="tile p-4 flex gap-3' . $ew . $pr . '">' . btn_edit($r, $edit) . '<span class="chip shrink-0"><i class="fa-solid ' . $ikon . '"></i></span><div><h4 class="font-bold">' . e($r['judul']) . '</h4>';
        if ($r['isi'] !== '') echo '<p class="text-sm text-gray-600 dark:text-gray-300 mt-1">' . nl2br(e($r['isi'])) . '</p>';
        echo '</div></div>';
    }
    echo '</div>';
}
function modal_info($key, $edit) {
    global $KARTU, $BAGIAN;
    $k = $KARTU[$key];
    echo '<div id="info-' . e($key) . '" class="modal"><div class="card w-full max-w-3xl">'
       . '<div class="modal-head"><h3 class="font-extrabold text-lg flex items-center gap-3"><span class="chip" style="width:2.4rem;height:2.4rem;font-size:1rem"><i class="fa-solid ' . $k[0] . '"></i></span>' . e($k[1]) . '</h3>'
       . '<button type="button" data-close class="w-9 h-9 rounded-full bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300"><i class="fa-solid fa-xmark"></i></button></div><div class="p-6">';
    if ($edit && isset($BAGIAN[$key])) echo '<div class="mb-4">' . btn_tambah($key, true) . '</div>';
    isi_modal($key, $edit);
    echo '</div></div></div>';
}

function render_landing($edit = false) {
    global $KARTU_DAFTAR, $KARTU_SEKOLAH;
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
    $role = $_SESSION['role'] ?? ''; $dash = $role ? dashboard_url($role) : '';
    $wa = preg_replace('/\D/', '', setting('wa', ''));
?>
<nav class="nav-abu sticky <?= $top ?> z-40 backdrop-blur border-b border-gray-300 dark:border-gray-600 no-print">
  <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
    <a href="index.php"><?= logo_html(true) ?></a>
    <div class="flex items-center gap-2">
      <?= theme_btn() ?>
      <?php if ($edit): ?><a href="operator_dashboard.php?tab=pengaturan#logo" class="btn btn-ghost btn-sm"><i class="fa-solid fa-image"></i> Ganti Logo</a><?php endif; ?>
      <?php if (!$edit && $role): ?><a href="<?= $dash ?>" class="btn btn-or btn-sm"><i class="fa-solid fa-gauge"></i> Dashboard <?= e($role) ?></a><a href="logout.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a><?php endif; ?>
      <?php if (!$edit && !$role): ?><a href="<?= $masuk ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Masuk</a><?php endif; ?>
      <?php if (!$edit && !$role): ?><a href="<?= $daftar ?>" class="btn btn-or btn-sm"><i class="fa-solid fa-pen-to-square"></i> Daftar</a><?php endif; ?>
    </div>
  </div>
</nav>

<header id="beranda" class="relative overflow-hidden text-white<?= $ew ?>">
  <?php if ($b0 && !empty($b0['ada_gambar'])): ?>
    <img src="gambar.php?b=banner&id=<?= (int)$b0['id'] ?>&v=<?= (int)$b0['v'] ?>" alt="" class="absolute inset-0 w-full h-full object-cover"><div class="absolute inset-0 hero-mesh opacity-80"></div>
  <?php else: ?><div class="absolute inset-0 hero-mesh"></div><?php endif; ?>
  <?php if (gedung_ada()): ?>
    <img src="gedung.php?v=<?= gedung_ada() ?>" alt="Gedung SMA Gunung Madu" class="absolute inset-0 w-full h-full object-cover object-bottom pointer-events-none select-none"><div class="absolute inset-0 bg-orange-900/55"></div>
  <?php endif; ?>
  <div class="absolute inset-0 hero-scrim"></div>
  <?php if ($edit): ?><a href="operator_dashboard.php?tab=pengaturan#gedung" class="edit-btn" style="right:auto;left:.5rem"><i class="fa-solid fa-building-columns"></i> Foto Gedung</a><?php endif; ?>
  <?= $b0 ? btn_edit($b0, $edit) : btn_tambah('banner', $edit) ?>
  <div class="relative max-w-6xl mx-auto px-4 py-16 sm:py-20 text-center hero-teks">
    <span class="inline-block rounded-full bg-white/20 px-4 py-1 text-xs font-semibold tracking-widest mb-5">SMART &bull; HEBAT &bull; BERMARTABAT</span>
    <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight"><?= e($b0['judul'] ?? 'Penerimaan Murid Baru SMA Gunung Madu 2027') ?></h1>
    <p class="mt-4 text-lg max-w-2xl mx-auto text-white/90"><?= e($b0['isi'] ?? 'Daftarkan dirimu secara online dan jadilah bagian dari keluarga besar kami.') ?></p>
    <div class="mt-8 flex flex-wrap gap-3 justify-center">
      <?php if (!$edit && $role): ?><a href="<?= $dash ?>" class="btn bg-white text-orange-600 hover:bg-orange-50 text-lg px-8"><i class="fa-solid fa-gauge"></i> Buka Dashboard</a><?php endif; ?>
      <?php if (!$edit && !$role): ?><a href="<?= $daftar ?>" class="btn bg-white text-orange-600 hover:bg-orange-50 text-lg px-8"><i class="fa-solid fa-rocket"></i> Daftar Sekarang</a><?php endif; ?>
      <?php if (!$edit && !$role): ?><a href="<?= $masuk ?>" class="btn border-white text-white hover:bg-white/10"><i class="fa-solid fa-right-to-bracket"></i> Masuk / Cek Hasil</a><?php endif; ?>
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
    <div class="card p-5 text-center border border-gray-200 dark:border-gray-700"><div class="text-2xl text-gray-600 dark:text-gray-300 mb-1"><i class="fa-solid <?= $s[0] ?>"></i></div>
      <div class="text-3xl font-extrabold"><?= e($s[2]) ?></div><div class="text-xs text-gray-500 dark:text-gray-400 font-medium"><?= $s[1] ?></div></div>
    <?php endforeach; ?>
  </div>
  <?php if ($edit): ?><p class="text-xs text-center mt-2 text-gray-500">Angka pendaftar & diterima dihitung otomatis. Jumlah siswa aktif diubah di tab <a class="underline text-orange-600" href="operator_dashboard.php?tab=pengaturan">Pengaturan</a>.</p><?php endif; ?>
</section>

<main class="max-w-6xl mx-auto px-4 pt-14 pb-16 space-y-12">
  <section>
    <?php sec_label('fa-clipboard-list', 'Informasi Pendaftaran', 'Mulai dari sini, lalu pelajari jadwal, jalur, syarat, dan biaya'); ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <?= kartu_daftar($edit) ?>
      <?php foreach ($KARTU_DAFTAR as $k) echo kartu_info($k, $edit); ?>
    </div>
  </section>
  <section>
    <?php sec_label('fa-school', 'Informasi Sekolah', 'Klik kartu untuk melihat selengkapnya'); ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <?php foreach ($KARTU_SEKOLAH as $k) echo kartu_info($k, $edit); ?>
    </div>
  </section>
</main>

<footer class="bg-gray-800 text-gray-300 py-8 text-center text-sm">
  <p class="font-bold text-white">SMA Gunung Madu</p><p class="text-orange-400 tracking-widest text-xs mb-2">SMART &bull; HEBAT &bull; BERMARTABAT</p>
  <p>&copy; <?= date('Y') ?> BETA Team, Project Manager = Ega Belva Sofyana</p>
  <?php if (!$edit): ?><p class="mt-2 no-print"><a href="login.php" class="text-gray-500 hover:text-orange-400">Masuk Admin / Operator</a></p><?php endif; ?>
</footer>
<?php if ($wa && !$edit): ?><a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" title="Chat Customer Service" class="no-print fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full bg-green-500 text-white text-3xl flex items-center justify-center shadow-lg hover:bg-green-600"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
<?php foreach (array_merge($KARTU_DAFTAR, $KARTU_SEKOLAH) as $k) modal_info($k, $edit); ?>
<?php }
