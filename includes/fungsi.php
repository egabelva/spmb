<?php
// ===== Fungsi bantu umum =====
const FIELD_PENDAFTAR = ['nik_nisn','nama_lengkap','jenis_kelamin','tempat_lahir','tanggal_lahir','alamat','asal_sekolah','jalur','tipe_batih','nama_batih','pekerjaan_batih','kontak_batih'];
const LABEL_PENDAFTAR = [
    'nik_nisn'=>'NIK / NISN','nama_lengkap'=>'Nama lengkap','jenis_kelamin'=>'Jenis kelamin','tempat_lahir'=>'Tempat lahir',
    'tanggal_lahir'=>'Tanggal lahir','alamat'=>'Alamat','asal_sekolah'=>'Asal sekolah (SMP/MTs)','jalur'=>'Jalur pendaftaran',
    'tipe_batih'=>'Hubungan wali','nama_batih'=>'Nama orang tua/wali','pekerjaan_batih'=>'Pekerjaan orang tua/wali','kontak_batih'=>'Kontak orang tua/wali',
];
const PANJANG_PENDAFTAR = [
    'nik_nisn'=>20,'nama_lengkap'=>150,'jenis_kelamin'=>20,'tempat_lahir'=>50,'tanggal_lahir'=>10,'alamat'=>1000,'asal_sekolah'=>150,
    'jalur'=>50,'tipe_batih'=>20,'nama_batih'=>150,'pekerjaan_batih'=>100,'kontak_batih'=>25,
];
const KOLOM_PENDAFTAR = 'id,akun_id,no_pendaftaran,nik_nisn,nama_lengkap,jenis_kelamin,tempat_lahir,tanggal_lahir,alamat,asal_sekolah,jalur,tipe_batih,nama_batih,pekerjaan_batih,kontak_batih,nama_berkas,tipe_berkas,status,sumber,waktu_daftar';

// Peta bagian konten website -> nama tabel (satu tabel per menu)
const TABEL_BAGIAN = [
    'banner'=>'banner','profil'=>'profil_sekolah','visi'=>'visi','misi'=>'misi','sejarah'=>'sejarah','akreditasi'=>'akreditasi',
    'fasilitas'=>'fasilitas','prestasi'=>'prestasi','ekskul'=>'ekstrakurikuler','organisasi'=>'organisasi','guru'=>'guru','galeri'=>'galeri',
    'agenda'=>'agenda','berita'=>'berita','jadwal'=>'jadwal_ppdb','syarat'=>'syarat_pendaftaran','biaya'=>'biaya_pendaftaran','faq'=>'faq',
];
function tabel_bagian($b) { return TABEL_BAGIAN[$b] ?? null; }

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($u) { header('Location: ' . $u); exit; }

// ----- Database (prepared statement) -----
function db_all($sql, $types = '', $params = []) {
    global $koneksi;
    $st = mysqli_prepare($koneksi, $sql);
    if (!$st) return [];
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($st);
    return $rows;
}
function db_one($sql, $types = '', $params = []) { $r = db_all($sql, $types, $params); return $r ? $r[0] : null; }
function db_exec($sql, $types = '', $params = []) {
    global $koneksi;
    $st = mysqli_prepare($koneksi, $sql);
    if (!$st) return false;
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    $ok = mysqli_stmt_execute($st);
    $id = mysqli_insert_id($koneksi);
    mysqli_stmt_close($st);
    return $ok ? ($id ?: true) : false;
}

// ----- Pengaturan (tersebar di pengaturan_ppdb, kontak_sekolah, media_sosial, kode_akses, jalur_pendaftaran) -----
function muat_setting() {
    $c = [];
    $r = db_one('SELECT kuota, deadline, hasil_diumumkan, siswa_aktif FROM pengaturan_ppdb WHERE id=1');
    if ($r) {
        $c['kuota'] = (string)$r['kuota']; $c['deadline'] = (string)($r['deadline'] ?? '');
        $c['hasil_diumumkan'] = (string)$r['hasil_diumumkan']; $c['siswa_aktif'] = (string)$r['siswa_aktif'];
    }
    $r = db_one('SELECT alamat, telepon, email, wa, maps_embed FROM kontak_sekolah WHERE id=1');
    if ($r) foreach ($r as $k => $v) $c[$k] = (string)$v;
    foreach (db_all('SELECT platform, url FROM media_sosial') as $m) $c[$m['platform']] = (string)$m['url'];
    foreach (db_all('SELECT peran, kode FROM kode_akses') as $m) $c[$m['peran'] === 'Admin' ? 'kode_universal' : 'kode_operator'] = (string)$m['kode'];
    $j = [];
    foreach (db_all('SELECT nama FROM jalur_pendaftaran WHERE aktif=1 ORDER BY urutan, id') as $x) $j[] = $x['nama'];
    $c['jalur'] = implode(chr(10), $j);
    return $c;
}
function simpan_setting($k, $v) {
    switch ($k) {
        case 'kuota': case 'siswa_aktif':
            return db_exec('INSERT INTO pengaturan_ppdb (id, ' . $k . ') VALUES (1, ?) ON DUPLICATE KEY UPDATE ' . $k . ' = VALUES(' . $k . ')', 'i', [(int)$v]);
        case 'deadline':
            return db_exec('INSERT INTO pengaturan_ppdb (id, deadline) VALUES (1, ?) ON DUPLICATE KEY UPDATE deadline = VALUES(deadline)', 's', [$v === '' ? null : $v]);
        case 'hasil_diumumkan':
            return db_exec('INSERT INTO pengaturan_ppdb (id, hasil_diumumkan) VALUES (1, ?) ON DUPLICATE KEY UPDATE hasil_diumumkan = VALUES(hasil_diumumkan)', 'i', [$v === '1' ? 1 : 0]);
        case 'alamat': case 'telepon': case 'email': case 'wa': case 'maps_embed':
            return db_exec('INSERT INTO kontak_sekolah (id, ' . $k . ') VALUES (1, ?) ON DUPLICATE KEY UPDATE ' . $k . ' = VALUES(' . $k . ')', 's', [$v]);
        case 'instagram': case 'facebook': case 'youtube': case 'tiktok':
            return db_exec('INSERT INTO media_sosial (platform, url) VALUES (?, ?) ON DUPLICATE KEY UPDATE url = VALUES(url)', 'ss', [$k, $v]);
        case 'kode_universal': case 'kode_operator':
            return db_exec('INSERT INTO kode_akses (peran, kode) VALUES (?, ?) ON DUPLICATE KEY UPDATE kode = VALUES(kode)', 'ss', [$k === 'kode_universal' ? 'Admin' : 'Operator', $v]);
        case 'jalur':
            db_exec('UPDATE jalur_pendaftaran SET aktif=0');
            $i = 0;
            foreach (explode(chr(10), str_replace(chr(13), '', (string)$v)) as $j) {
                $j = trim($j); if ($j === '') continue; $i++;
                db_exec('INSERT INTO jalur_pendaftaran (nama, urutan, aktif) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE urutan = VALUES(urutan), aktif = 1', 'si', [$j, $i]);
            }
            return true;
    }
    return false;
}
// ----- Riwayat status & log aktivitas -----
function catat_status($id, $lama, $baru, $oleh = 'Sistem') {
    db_exec('INSERT INTO riwayat_status (pendaftar_id, status_lama, status_baru, diubah_oleh) VALUES (?,?,?,?)', 'isss', [(int)$id, $lama, $baru, $oleh]);
}
function catat_log($aksi, $ket = '') {
    db_exec('INSERT INTO log_aktivitas (akun_id, peran, aksi, keterangan, ip) VALUES (?,?,?,?,?)', 'issss',
            [isset($_SESSION['akun_id']) ? (int)$_SESSION['akun_id'] : null, $_SESSION['role'] ?? 'Tamu', $aksi, mb_substr((string)$ket, 0, 255), $_SERVER['REMOTE_ADDR'] ?? '']);
}
function nik_terdaftar($nik) {
    return (bool)db_one('SELECT p.id FROM pendaftar p JOIN data_siswa s ON s.pendaftar_id = p.id WHERE s.nik_nisn=? AND p.status<>?', 'ss', [$nik, 'Draft']);
}
function setting($k, $d = '') {
    static $c = null;
    if ($c === null) {
        $c = [];
        $c = muat_setting();
    }
    return array_key_exists($k, $c) ? $c[$k] : $d;
}
function set_setting($k, $v) {
    return simpan_setting($k, $v);
}
function jalur_list() {
    $o = [];
    foreach (preg_split('/\r\n|\r|\n/', setting('jalur', '')) as $j) { $j = trim($j); if ($j !== '') $o[] = $j; }
    return $o;
}

// ----- Keamanan & sesi -----
function csrf_token() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check($t = null) {
    $t = $t ?? ($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), (string)$t)) { http_response_code(419); die('Sesi formulir kedaluwarsa. Silakan muat ulang halaman.'); }
}
function require_role($r) { if (($_SESSION['role'] ?? '') !== $r) redirect('login.php'); }
function dashboard_url($role) {
    return ['Pendaftar' => 'beranda_siswa.php', 'Admin' => 'admin_dashboard.php', 'Operator' => 'operator_dashboard.php'][$role] ?? 'login.php';
}
function flash($tipe, $pesan) { $_SESSION['flash'] = ['t' => $tipe, 'm' => $pesan]; }
function flash_html() {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash']; unset($_SESSION['flash']);
    $ok = $f['t'] === 'ok';
    $cls = $ok ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300';
    $ic = $ok ? 'fa-circle-check' : 'fa-circle-exclamation';
    return '<div class="rounded-3xl border-2 px-5 py-3 mb-4 text-sm font-medium ' . $cls . '"><i class="fa-solid ' . $ic . ' mr-2"></i>' . e($f['m']) . '</div>';
}

// ----- Format -----
function tgl_indo($d, $jam = false) {
    if (!$d) return '-';
    $t = strtotime($d); if (!$t) return '-';
    $b = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $s = date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $jam ? $s . ', ' . date('H:i', $t) . ' WIB' : $s;
}
function status_badge($s) {
    $m = ['Pending' => ['bg-yellow-100 text-yellow-800', 'Pending'], 'Diterima' => ['bg-green-100 text-green-800', 'Diterima'],
          'Ditolak' => ['bg-red-100 text-red-800', 'Ditolak'], 'Draft' => ['bg-gray-200 text-gray-700', 'Draft']];
    $x = $m[$s] ?? $m['Draft'];
    return '<span class="inline-block rounded-full px-3 py-1 text-xs font-bold ' . $x[0] . '">' . $x[1] . '</span>';
}

// ----- Unggah berkas -----
function max_upload_bytes() {
    $p = db_one("SHOW VARIABLES LIKE 'max_allowed_packet'");
    $pk = $p ? (int)$p['Value'] : 1048576;
    return max(100000, min(2 * 1024 * 1024, $pk - 65536));
}
// Mengembalikan [pesan_error|null, nama, tipe, data]
function baca_upload($field, $diizinkan) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return [null, null, null, null];
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return ['Unggah berkas gagal (kode ' . $f['error'] . '). Periksa batas upload_max_filesize pada php.ini.', null, null, null];
    $maks = max_upload_bytes();
    if ($f['size'] > $maks) return ['Ukuran berkas maksimal ' . round($maks / 1048576, 1) . ' MB.', null, null, null];
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($f['tmp_name']);
    if (!in_array($mime, $diizinkan, true)) return ['Format berkas tidak diizinkan.', null, null, null];
    return [null, mb_substr(basename($f['name']), 0, 255), $mime, file_get_contents($f['tmp_name'])];
}

// ----- Data pendaftar -----
function kumpul_input($src = null) {
    $src = $src ?? $_POST; $o = [];
    foreach (FIELD_PENDAFTAR as $f) $o[$f] = mb_substr(trim((string)($src[$f] ?? '')), 0, PANJANG_PENDAFTAR[$f]);
    return $o;
}
function validasi_pendaftar($in, $wajib) {
    $err = [];
    if ($wajib) foreach (FIELD_PENDAFTAR as $f) if ($in[$f] === '') $err[] = LABEL_PENDAFTAR[$f] . ' wajib diisi.';
    if ($in['nik_nisn'] !== '' && !preg_match('/^\d{10,16}$/', $in['nik_nisn'])) $err[] = 'NIK/NISN harus 10-16 digit angka.';
    if ($in['jenis_kelamin'] !== '' && !in_array($in['jenis_kelamin'], ['Laki-laki', 'Perempuan'], true)) $err[] = 'Jenis kelamin tidak valid.';
    if ($in['tipe_batih'] !== '' && !in_array($in['tipe_batih'], ['Ayah', 'Ibu', 'Wali'], true)) $err[] = 'Hubungan wali tidak valid.';
    if ($in['tanggal_lahir'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $in['tanggal_lahir']);
        if (!$d || $d->format('Y-m-d') !== $in['tanggal_lahir'] || (int)$d->format('Y') < 1990 || $d > new DateTime('-10 years')) $err[] = 'Tanggal lahir tidak valid.';
    }
    if ($in['kontak_batih'] !== '' && !preg_match('/^[0-9+\-\s]{8,25}$/', $in['kontak_batih'])) $err[] = 'Kontak orang tua/wali tidak valid.';
    $jl = jalur_list();
    if ($in['jalur'] !== '' && $jl && !in_array($in['jalur'], $jl, true)) $err[] = 'Jalur pendaftaran tidak tersedia.';
    return $err;
}
// Data pendaftar tersebar di 3 tabel: pendaftar (inti), data_siswa, orang_tua_wali.
function simpan_pendaftar($id, $akun_id, $in, $status = 'Draft', $sumber = 'Online') {
    $jalur_id = null;
    if ($in['jalur'] !== '') {
        $j = db_one('SELECT id FROM jalur_pendaftaran WHERE nama=?', 's', [$in['jalur']]);
        $jalur_id = $j ? (int)$j['id'] : null;
    }
    if ($id > 0) {
        db_exec('UPDATE pendaftar SET jalur_id=? WHERE id=?', 'ii', [$jalur_id, (int)$id]);
    } else {
        $r = db_exec('INSERT INTO pendaftar (akun_id, jalur_id, status, sumber) VALUES (?,?,?,?)', 'iiss', [$akun_id, $jalur_id, $status, $sumber]);
        $id = is_int($r) ? $r : 0;
        if ($id <= 0) return 0;
        catat_status($id, null, $status, $sumber === 'Offline' ? 'Admin' : 'Pendaftar');
    }
    $tgl = $in['tanggal_lahir'] === '' ? null : $in['tanggal_lahir'];
    db_exec('INSERT INTO data_siswa (pendaftar_id, nik_nisn, nama_lengkap, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, asal_sekolah) VALUES (?,?,?,?,?,?,?,?) '
          . 'ON DUPLICATE KEY UPDATE nik_nisn=VALUES(nik_nisn), nama_lengkap=VALUES(nama_lengkap), jenis_kelamin=VALUES(jenis_kelamin), tempat_lahir=VALUES(tempat_lahir), tanggal_lahir=VALUES(tanggal_lahir), alamat=VALUES(alamat), asal_sekolah=VALUES(asal_sekolah)',
            'isssssss', [$id, $in['nik_nisn'], $in['nama_lengkap'], $in['jenis_kelamin'], $in['tempat_lahir'], $tgl, $in['alamat'], $in['asal_sekolah']]);
    db_exec('INSERT INTO orang_tua_wali (pendaftar_id, tipe, nama, pekerjaan, kontak) VALUES (?,?,?,?,?) '
          . 'ON DUPLICATE KEY UPDATE tipe=VALUES(tipe), nama=VALUES(nama), pekerjaan=VALUES(pekerjaan), kontak=VALUES(kontak)',
            'issss', [$id, $in['tipe_batih'], $in['nama_batih'], $in['pekerjaan_batih'], $in['kontak_batih']]);
    return (int)$id;
}
function simpan_berkas($id, $nama, $tipe, $data) {
    return db_exec('INSERT INTO berkas_pendaftar (pendaftar_id, nama_berkas, tipe_berkas, data_berkas) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nama_berkas=VALUES(nama_berkas), tipe_berkas=VALUES(tipe_berkas), data_berkas=VALUES(data_berkas)', 'isss', [(int)$id, $nama, $tipe, $data]);
}
function beri_nomor($id) {
    $no = 'GM27-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    db_exec('UPDATE pendaftar SET no_pendaftaran=? WHERE id=?', 'si', [$no, $id]);
    return $no;
}
function deadline_lewat() {
    $d = setting('deadline', '');
    return $d !== '' && strtotime($d) !== false && time() > strtotime($d);
}
// Akses pendaftar untuk halaman cetak/berkas: Admin = semua, Pendaftar = miliknya saja.
function ambil_pendaftar_akses($id) {
    $row = db_one('SELECT ' . KOLOM_PENDAFTAR . ' FROM v_pendaftar WHERE id=?', 'i', [(int)$id]);
    if (!$row) { http_response_code(404); die('Data tidak ditemukan.'); }
    $role = $_SESSION['role'] ?? '';
    if ($role === 'Admin') return $row;
    if ($role === 'Pendaftar' && (int)$row['akun_id'] === (int)($_SESSION['akun_id'] ?? 0)) return $row;
    http_response_code(403); die('Anda tidak memiliki akses.');
}

// ----- Kerangka HTML -----
// Versi logo (timestamp unggah); 0 = belum ada logo. Tabel logo_sekolah belum ada -> aman, dianggap 0.
function logo_ada() {
    static $v = null;
    if ($v === null) {
        $r = db_one('SELECT UNIX_TIMESTAMP(diubah) AS v FROM logo_sekolah WHERE id=1');
        $v = $r ? max(1, (int)$r['v']) : 0;
    }
    return $v;
}
function logo_html($kecil = false) {
    $s = $kecil ? 'w-9 h-9 text-sm' : 'w-11 h-11';
    $v = logo_ada();
    $ikon = $v ? '<img src="logo.php?v=' . $v . '" alt="Logo SMA Gunung Madu" class="' . $s . ' object-contain shrink-0">'
                : '<span class="' . $s . ' rounded-full bg-orange-500 text-white font-extrabold flex items-center justify-center shadow">GM</span>';
    return '<span class="inline-flex items-center gap-3">' . $ikon
         . '<span class="leading-tight"><span class="block font-extrabold">SPMB SMA Gunung Madu</span><span class="block text-[10px] tracking-widest text-orange-600 font-semibold">SMART • HEBAT • BERMARTABAT</span></span></span>';
}
function theme_btn($cls = '') {
    return '<button type="button" onclick="toggleTheme()" title="Mode terang/gelap" class="w-10 h-10 rounded-full bg-white/90 dark:bg-gray-700 text-orange-500 shadow flex items-center justify-center ' . $cls . '">'
         . '<i class="fa-solid fa-moon dark:hidden"></i><i class="fa-solid fa-sun hidden dark:inline"></i></button>';
}
function html_head($judul, $bodyClass = 'bg-tebu min-h-screen') { ?>
<!DOCTYPE html>
<html lang="id" translate="no">
<head>
<meta charset="UTF-8">
<meta name="google" content="notranslate">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | SPMB SMA Gunung Madu</title>
<script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark');}catch(e){}})();</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Poppins','ui-sans-serif','system-ui','sans-serif']}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?> font-sans text-gray-800 dark:text-gray-100">
<?php }
function html_foot($js = '') { ?>
<div id="modal-confirm" class="modal">
  <div class="card p-6 max-w-sm w-full text-center">
    <div class="text-4xl mb-2 text-red-500"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <p id="confirm-text" class="mb-5 font-medium"></p>
    <div class="flex gap-3 justify-center">
      <button type="button" class="btn btn-ghost" data-close>Batal</button>
      <a id="confirm-ok" href="#" class="btn btn-red">Ya, lanjutkan</a>
    </div>
  </div>
</div>
<script src="assets/js/script.js"></script>
<?= $js ?>
</body>
</html>
<?php }
