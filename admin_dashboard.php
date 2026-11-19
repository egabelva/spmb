<?php
require 'koneksi.php';
require_role('Admin');
$errors = []; $buka_manual = false; $in = null;

// --- Aksi cepat: Terima / Tolak / Kembalikan ke Pending (GET + token CSRF) ---
if (isset($_GET['aksi'], $_GET['id'])) {
    csrf_check($_GET['t'] ?? '');
    $peta = ['terima' => 'Diterima', 'tolak' => 'Ditolak', 'pending' => 'Pending'];
    if (isset($peta[$_GET['aksi']])) {
        $lama = db_one('SELECT status FROM pendaftar WHERE id=?', 'i', [(int)$_GET['id']]);
        if ($lama && $lama['status'] !== 'Draft' && $lama['status'] !== $peta[$_GET['aksi']]) {
            db_exec("UPDATE pendaftar SET status=? WHERE id=? AND status<>'Draft'", 'si', [$peta[$_GET['aksi']], (int)$_GET['id']]);
            catat_status((int)$_GET['id'], $lama['status'], $peta[$_GET['aksi']], 'Admin');
            catat_log('ubah_status', '#' . (int)$_GET['id'] . ' ' . $lama['status'] . ' -> ' . $peta[$_GET['aksi']]);
        }
        flash('ok', 'Status pendaftar diubah menjadi ' . $peta[$_GET['aksi']] . '.');
    }
    redirect('admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'simpan_pengaturan') {
        $kuota = max(0, (int)($_POST['kuota'] ?? 0));
        $dl = trim($_POST['deadline'] ?? '');
        $dl = $dl !== '' ? str_replace('T', ' ', $dl) . (strlen($dl) === 16 ? ':00' : '') : '';
        $jl = [];
        foreach (preg_split('/\r\n|\r|\n/', (string)($_POST['jalur'] ?? '')) as $j) { $j = trim(mb_substr($j, 0, 50)); if ($j !== '') $jl[] = $j; }
        set_setting('kuota', (string)$kuota); set_setting('deadline', $dl); set_setting('jalur', implode("\n", $jl));
        set_setting('hasil_diumumkan', isset($_POST['hasil_diumumkan']) ? '1' : '0');
        flash('ok', 'Pengaturan PPDB disimpan.'); redirect('admin_dashboard.php');
    }
    if ($aksi === 'isi_pendaftar') {
        $in = kumpul_input();
        $errors = validasi_pendaftar($in, true);
        if (nik_terdaftar($in['nik_nisn'])) $errors[] = 'NIK/NISN ini sudah terdaftar.';
        list($uerr, $unama, $utipe, $udata) = baca_upload('berkas', ['application/pdf', 'image/jpeg', 'image/png']);
        if ($uerr) $errors[] = $uerr;
        if (!$errors) {
            $id = simpan_pendaftar(0, null, $in, 'Pending', 'Offline');
            if ($id > 0) {
                beri_nomor($id);
                if ($udata !== null) simpan_berkas($id, $unama, $utipe, $udata);
                flash('ok', 'Pendaftar offline berhasil ditambahkan.'); redirect('admin_dashboard.php');
            }
            $errors[] = 'Gagal menyimpan data.';
        }
        $buka_manual = true;
    }
}

$daftar = db_all('SELECT ' . KOLOM_PENDAFTAR . " FROM v_pendaftar WHERE status<>'Draft' ORDER BY waktu_daftar DESC");
$cnt = ['Pending' => 0, 'Diterima' => 0, 'Ditolak' => 0]; $perJalur = [];
foreach ($daftar as $r) { $cnt[$r['status']] = ($cnt[$r['status']] ?? 0) + 1; $perJalur[$r['jalur'] ?: '-'] = ($perJalur[$r['jalur'] ?: '-'] ?? 0) + 1; }
$draft = (int)(db_one("SELECT COUNT(*) AS n FROM pendaftar WHERE status='Draft'")['n'] ?? 0);
$total = count($daftar); $kuota = (int)setting('kuota', '0');
$dlVal = setting('deadline') ? date('Y-m-d\TH:i', strtotime(setting('deadline'))) : '';
$d = $in ?? array_fill_keys(FIELD_PENDAFTAR, '');

html_head('Dashboard Admin');
?>
<header class="nav-abu backdrop-blur shadow-sm sticky top-0 z-30 no-print">
  <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="index.php"><?= logo_html(true) ?></a>
    <div class="flex items-center gap-2"><span class="hidden sm:inline rounded-full bg-orange-100 text-orange-700 text-xs font-bold px-3 py-1">ADMIN PPDB</span><?= theme_btn() ?>
      <a href="index.php" class="btn btn-or btn-sm"><i class="fa-solid fa-house"></i> Beranda</a>
      <a href="logout.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a></div>
  </div>
</header>
<main class="max-w-7xl mx-auto px-4 py-8 space-y-6">
  <?= flash_html() ?>
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 no-print">
    <?php foreach ([['fa-users','Total pendaftar',$total,'text-orange-500'],['fa-hourglass-half','Diproses (Pending)',$cnt['Pending'],'text-yellow-500'],['fa-circle-check','Diterima',$cnt['Diterima'],'text-green-600'],['fa-circle-xmark','Ditolak',$cnt['Ditolak'],'text-red-500'],['fa-file-pen','Draft (belum kirim)',$draft,'text-gray-500']] as $s): ?>
    <div class="card p-5"><div class="text-2xl <?= $s[3] ?>"><i class="fa-solid <?= $s[0] ?>"></i></div><div class="text-3xl font-extrabold"><?= $s[2] ?></div><div class="text-xs text-gray-500 font-medium"><?= $s[1] ?></div></div>
    <?php endforeach; ?>
  </div>
  <p class="text-sm text-gray-500 no-print">Kuota: <b><?= $kuota ?></b> &bull; Terisi (diterima): <b><?= $cnt['Diterima'] ?></b> &bull; Sisa: <b><?= max(0, $kuota - $cnt['Diterima']) ?></b> &bull; Hasil seleksi: <b><?= setting('hasil_diumumkan') === '1' ? 'sudah diumumkan' : 'belum diumumkan' ?></b></p>

  <div class="grid lg:grid-cols-2 gap-6 no-print">
    <div class="card p-6"><h3 class="font-bold mb-3"><i class="fa-solid fa-chart-pie text-orange-500 mr-2"></i>Status Pendaftaran</h3><div class="h-64"><canvas id="chStatus"></canvas></div></div>
    <div class="card p-6"><h3 class="font-bold mb-3"><i class="fa-solid fa-chart-column text-orange-500 mr-2"></i>Pendaftar per Jalur</h3><div class="h-64"><canvas id="chJalur"></canvas></div></div>
  </div>

  <section class="card p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 no-print">
      <h2 class="font-extrabold text-xl"><i class="fa-solid fa-table-list text-orange-500 mr-2"></i>Data Pendaftar <span class="text-sm font-medium text-gray-500">(<span id="jml-tampil"><?= $total ?></span> ditampilkan)</span></h2>
      <div class="flex flex-wrap gap-2">
        <button data-open="modal-manual" class="btn btn-or btn-sm"><i class="fa-solid fa-user-plus"></i> Isi Pendaftar</button>
        <button data-open="modal-setting" class="btn btn-ghost btn-sm"><i class="fa-solid fa-gear"></i> Pengaturan PPDB</button>
        <a id="link-export" href="admin_export.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-file-excel"></i> Excel (CSV)</a>
        <button onclick="window.print()" class="btn btn-ghost btn-sm"><i class="fa-solid fa-file-pdf"></i> Cetak / PDF</button>
      </div>
    </div>
    <div class="flex flex-wrap gap-3 mb-4 no-print">
      <div class="relative flex-1 min-w-[14rem]"><i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i><input id="cari" oninput="filterTabel()" class="inp" style="padding-left:2.6rem" placeholder="Cari nama, NIK/NISN, no. pendaftaran, kontak..."></div>
      <select id="filter-status" onchange="filterTabel()" class="inp" style="width:auto"><option value="">Semua status</option><option value="Pending">Pending</option><option value="Diterima">Diterima</option><option value="Ditolak">Ditolak</option></select>
    </div>
    <div class="overflow-x-auto">
      <table id="tabel-pendaftar" class="w-full text-sm">
        <thead><tr class="text-left text-xs uppercase text-gray-500 border-b-2 border-orange-200"><th class="p-3">No. Daftar</th><th class="p-3">Nama</th><th class="p-3">Jalur</th><th class="p-3">Kontak</th><th class="p-3">Status</th><th class="p-3 no-print">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $r):
            $det = ['id' => (int)$r['id'], 'no_pendaftaran' => $r['no_pendaftaran'], 'nik_nisn' => $r['nik_nisn'], 'nama_lengkap' => $r['nama_lengkap'], 'jenis_kelamin' => $r['jenis_kelamin'],
                'tempat_lahir' => $r['tempat_lahir'], 'tgl_lahir' => tgl_indo($r['tanggal_lahir']), 'alamat' => $r['alamat'], 'asal_sekolah' => $r['asal_sekolah'], 'jalur' => $r['jalur'],
                'tipe_batih' => $r['tipe_batih'], 'nama_batih' => $r['nama_batih'], 'pekerjaan_batih' => $r['pekerjaan_batih'], 'kontak_batih' => $r['kontak_batih'],
                'nama_berkas' => $r['nama_berkas'], 'sumber' => $r['sumber'], 'waktu' => tgl_indo($r['waktu_daftar'], true), 'status' => $r['status']];
            $cari = mb_strtolower($r['nama_lengkap'] . ' ' . $r['nik_nisn'] . ' ' . $r['no_pendaftaran'] . ' ' . $r['kontak_batih'] . ' ' . $r['jalur']); ?>
          <tr data-status="<?= e($r['status']) ?>" data-cari="<?= e($cari) ?>" data-detail="<?= e(json_encode($det, JSON_UNESCAPED_UNICODE)) ?>" class="border-b border-gray-100 dark:border-gray-700 hover:bg-orange-50/60 dark:hover:bg-gray-700/40">
            <td class="p-3 font-semibold"><?= e($r['no_pendaftaran']) ?></td>
            <td class="p-3"><?= e($r['nama_lengkap']) ?><div class="text-xs text-gray-500"><?= e($r['nik_nisn']) ?> &bull; <?= e($r['sumber']) ?></div></td>
            <td class="p-3"><?= e($r['jalur']) ?></td><td class="p-3"><?= e($r['kontak_batih']) ?></td><td class="p-3"><?= status_badge($r['status']) ?></td>
            <td class="p-3 no-print whitespace-nowrap">
              <button type="button" onclick="bukaDetail(this.closest('tr'))" class="btn btn-ghost btn-sm" title="Detail"><i class="fa-solid fa-eye"></i></button>
              <a href="admin_dashboard.php?aksi=terima&id=<?= (int)$r['id'] ?>&t=<?= e(csrf_token()) ?>" class="btn btn-green btn-sm">Terima</a>
              <a href="admin_dashboard.php?aksi=tolak&id=<?= (int)$r['id'] ?>&t=<?= e(csrf_token()) ?>" data-confirm="Yakin menolak pendaftaran <?= e($r['nama_lengkap']) ?>?" class="btn btn-red btn-sm">Tolak</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><tr><td colspan="6" class="p-8 text-center text-gray-500">Belum ada pendaftar.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<!-- Modal detail -->
<div id="modal-detail" class="modal"><div class="card p-6 w-full max-w-2xl">
  <div class="flex justify-between items-center mb-3"><h3 class="font-extrabold text-lg"><i class="fa-solid fa-id-card text-orange-500 mr-2"></i>Detail Pendaftar</h3><button data-close class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700"><i class="fa-solid fa-xmark"></i></button></div>
  <dl id="detail-isi" class="text-sm"></dl>
  <div class="flex flex-wrap gap-2 mt-5">
    <a id="d-berkas" href="#" target="_blank" class="btn btn-ghost btn-sm"><i class="fa-solid fa-paperclip"></i> Lihat Berkas</a>
    <a id="d-bukti" href="#" target="_blank" class="btn btn-ghost btn-sm"><i class="fa-solid fa-print"></i> Bukti</a>
    <a id="d-terima" href="#" class="btn btn-green btn-sm"><i class="fa-solid fa-check"></i> Terima (Verifikasi)</a>
    <a id="d-pending" href="#" class="btn btn-ghost btn-sm"><i class="fa-solid fa-rotate-left"></i> Kembalikan ke Pending</a>
    <a id="d-tolak" href="#" data-confirm="" class="btn btn-red btn-sm"><i class="fa-solid fa-xmark"></i> Tolak</a>
  </div>
</div></div>

<!-- Modal isi pendaftar manual -->
<div id="modal-manual" class="modal <?= $buka_manual ? 'open' : '' ?>"><div class="card p-6 w-full max-w-2xl">
  <div class="flex justify-between items-center mb-3"><h3 class="font-extrabold text-lg"><i class="fa-solid fa-user-plus text-orange-500 mr-2"></i>Isi Pendaftar (Offline)</h3><button data-close class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700"><i class="fa-solid fa-xmark"></i></button></div>
  <?php if ($errors): ?><div class="rounded-3xl border-2 border-red-300 bg-red-100 text-red-800 p-4 mb-3 text-sm"><ul class="list-disc ml-5"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-3">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="isi_pendaftar">
    <div><label class="lbl">NIK / NISN</label><input class="inp" name="nik_nisn" value="<?= e($d['nik_nisn']) ?>" required maxlength="16"></div>
    <div><label class="lbl">Nama lengkap</label><input class="inp" name="nama_lengkap" value="<?= e($d['nama_lengkap']) ?>" required></div>
    <div><label class="lbl">Jenis kelamin</label><select class="inp" name="jenis_kelamin" required><option value="">-- Pilih --</option><?php foreach (['Laki-laki', 'Perempuan'] as $o): ?><option value="<?= e($o) ?>" <?= $d['jenis_kelamin'] === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
    <div><label class="lbl">Tempat lahir</label><input class="inp" name="tempat_lahir" value="<?= e($d['tempat_lahir']) ?>" required></div>
    <div><label class="lbl">Tanggal lahir</label><input class="inp" type="date" name="tanggal_lahir" value="<?= e($d['tanggal_lahir']) ?>" required></div>
    <div><label class="lbl">Asal sekolah</label><input class="inp" name="asal_sekolah" value="<?= e($d['asal_sekolah']) ?>" required></div>
    <div class="sm:col-span-2"><label class="lbl">Alamat</label><textarea class="inp" name="alamat" rows="2" required><?= e($d['alamat']) ?></textarea></div>
    <div class="sm:col-span-2"><label class="lbl">Jalur</label><select class="inp" name="jalur" required><option value="">-- Pilih jalur --</option><?php foreach (jalur_list() as $j): ?><option value="<?= e($j) ?>" <?= $d['jalur'] === $j ? 'selected' : '' ?>><?= e($j) ?></option><?php endforeach; ?></select></div>
    <div><label class="lbl">Hubungan</label><select class="inp" name="tipe_batih" required><option value="">-- Pilih --</option><?php foreach (['Ayah', 'Ibu', 'Wali'] as $o): ?><option value="<?= e($o) ?>" <?= $d['tipe_batih'] === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
    <div><label class="lbl">Nama orang tua/wali</label><input class="inp" name="nama_batih" value="<?= e($d['nama_batih']) ?>" required></div>
    <div><label class="lbl">Pekerjaan</label><input class="inp" name="pekerjaan_batih" value="<?= e($d['pekerjaan_batih']) ?>" required></div>
    <div><label class="lbl">Kontak</label><input class="inp" name="kontak_batih" value="<?= e($d['kontak_batih']) ?>" required></div>
    <div class="sm:col-span-2"><label class="lbl">Berkas (opsional, PDF/JPG/PNG)</label><input class="inp" type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png"></div>
    <div class="sm:col-span-2"><button class="btn btn-or w-full"><i class="fa-solid fa-floppy-disk"></i> Simpan Pendaftar</button></div>
  </form>
</div></div>

<!-- Modal pengaturan PPDB -->
<div id="modal-setting" class="modal"><div class="card p-6 w-full max-w-lg">
  <div class="flex justify-between items-center mb-3"><h3 class="font-extrabold text-lg"><i class="fa-solid fa-gear text-orange-500 mr-2"></i>Pengaturan PPDB</h3><button data-close class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700"><i class="fa-solid fa-xmark"></i></button></div>
  <form method="post" class="space-y-3">
    <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan_pengaturan">
    <div><label class="lbl">Kuota penerimaan</label><input class="inp" type="number" min="0" name="kuota" value="<?= $kuota ?>"></div>
    <div><label class="lbl">Batas akhir pendaftaran</label><input class="inp" type="datetime-local" name="deadline" value="<?= e($dlVal) ?>"></div>
    <div><label class="lbl">Jalur penerimaan (satu jalur per baris)</label><textarea class="inp" name="jalur" rows="4"><?= e(implode("\n", jalur_list())) ?></textarea></div>
    <label class="flex items-center gap-3 rounded-3xl bg-orange-50 dark:bg-gray-900 p-4 text-sm font-medium"><input type="checkbox" name="hasil_diumumkan" value="1" class="w-5 h-5 accent-orange-500" <?= setting('hasil_diumumkan') === '1' ? 'checked' : '' ?>><span><b>Umumkan hasil seleksi</b> - jika dicentang, pendaftar dapat melihat status Diterima/Ditolak di akun mereka.</span></label>
    <button class="btn btn-or w-full"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
  </form>
</div></div>
<?php
$js = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script><script>'
    . 'window.CSRF=' . json_encode(csrf_token()) . ';'
    . 'if(window.Chart){'
    . 'new Chart(document.getElementById("chStatus"),{type:"doughnut",data:{labels:["Pending","Diterima","Ditolak"],datasets:[{data:' . json_encode([$cnt['Pending'], $cnt['Diterima'], $cnt['Ditolak']]) . ',backgroundColor:["#facc15","#22c55e","#ef4444"]}]},options:{maintainAspectRatio:false}});'
    . 'new Chart(document.getElementById("chJalur"),{type:"bar",data:{labels:' . json_encode(array_keys($perJalur), JSON_UNESCAPED_UNICODE) . ',datasets:[{label:"Pendaftar",data:' . json_encode(array_values($perJalur)) . ',backgroundColor:"#F97316",borderRadius:12}]},options:{maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}},plugins:{legend:{display:false}}}});'
    . '}</script>';
html_foot($js); ?>
