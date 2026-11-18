<?php
require 'koneksi.php';
if (isset($_SESSION['role'])) redirect(dashboard_url($_SESSION['role']));

$pesan = ''; $tab = ($_GET['tab'] ?? '') === 'daftar' ? 'daftar' : 'masuk';
$v = ['role' => 'Pendaftar', 'ident' => '', 'nama' => '', 'ident_d' => ''];

function normal_ident($s) {
    $s = trim($s);
    if (filter_var($s, FILTER_VALIDATE_EMAIL)) return strtolower($s);
    $d = preg_replace('/[^\d+]/', '', $s);
    if (preg_match('/^(\+62|62|0)8\d{7,12}$/', $d)) return '0' . preg_replace('/^(\+62|62|0)/', '', $d);
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'login') {
        $role = $_POST['role'] ?? '';
        $v['role'] = $role; $v['ident'] = trim($_POST['ident'] ?? '');
        $ok = false;
        if ($role === 'Pendaftar') {
            $id = normal_ident($_POST['ident'] ?? '');
            $akun = $id !== '' ? db_one('SELECT id,nama,password_hash FROM akun WHERE email_hp=?', 's', [$id]) : null;
            if ($akun && password_verify($_POST['password'] ?? '', $akun['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['role'] = 'Pendaftar'; $_SESSION['akun_id'] = (int)$akun['id']; $_SESSION['nama'] = $akun['nama'];
                $ok = true;
            }
            $gagal = 'Email/nomor HP atau password salah.';
        } elseif ($role === 'Admin' || $role === 'Operator') {
            $kode = (string)($_POST['kode'] ?? '');
            $benar = $role === 'Admin' ? setting('kode_universal', 'SMAGM') : setting('kode_operator', '');
            if ($kode !== '' && hash_equals((string)$benar, $kode)) {
                session_regenerate_id(true);
                $_SESSION['role'] = $role; $_SESSION['nama'] = $role;
                $ok = true;
            }
            $gagal = 'Kode masuk salah. Akses ditolak.';
        } else { $gagal = 'Pilih peran terlebih dahulu.'; }
        if ($ok) redirect(dashboard_url($_SESSION['role']));
        usleep(400000);
        $pesan = $gagal; $tab = 'masuk';
    } elseif ($aksi === 'daftar') {
        $tab = 'daftar';
        $nama = trim($_POST['nama'] ?? ''); $v['nama'] = $nama; $v['ident_d'] = trim($_POST['ident_d'] ?? '');
        $id = normal_ident($_POST['ident_d'] ?? ''); $pw = (string)($_POST['pw1'] ?? '');
        if (mb_strlen($nama) < 3) $pesan = 'Nama lengkap minimal 3 karakter.';
        elseif ($id === '') $pesan = 'Masukkan email atau nomor HP (08xxxxxxxxxx) yang valid.';
        elseif (strlen($pw) < 6) $pesan = 'Password minimal 6 karakter.';
        elseif ($pw !== (string)($_POST['pw2'] ?? '')) $pesan = 'Konfirmasi password tidak sama.';
        elseif (db_one('SELECT id FROM akun WHERE email_hp=?', 's', [$id])) $pesan = 'Email/nomor HP sudah terdaftar. Silakan masuk.';
        else {
            $aid = db_exec('INSERT INTO akun (nama,email_hp,password_hash) VALUES (?,?,?)', 'sss', [mb_substr($nama, 0, 150), $id, password_hash($pw, PASSWORD_DEFAULT)]);
            if (is_int($aid)) {
                session_regenerate_id(true);
                $_SESSION['role'] = 'Pendaftar'; $_SESSION['akun_id'] = $aid; $_SESSION['nama'] = $nama;
                redirect('beranda_siswa.php');
            }
            $pesan = 'Gagal membuat akun. Coba lagi.';
        }
    }
}
html_head('Masuk', 'bg-tebu min-h-screen flex items-center justify-center p-4');
?>
<div class="absolute top-4 right-4"><?= theme_btn() ?></div>
<div class="w-full max-w-md">
  <div class="text-center mb-6"><a href="index.php"><?= logo_html() ?></a></div>
  <div class="card p-7">
    <div class="flex gap-2 rounded-full bg-gray-100 dark:bg-gray-900 p-1 mb-6 text-sm font-semibold">
      <button type="button" data-tabbtn="masuk" onclick="pilihTab('masuk')" class="flex-1 rounded-full py-2"><i class="fa-solid fa-right-to-bracket mr-1"></i>Masuk</button>
      <button type="button" data-tabbtn="daftar" onclick="pilihTab('daftar')" class="flex-1 rounded-full py-2"><i class="fa-solid fa-user-plus mr-1"></i>Buat Akun</button>
    </div>
    <?php if ($pesan): ?><div class="rounded-3xl border-2 border-red-300 bg-red-100 text-red-800 px-4 py-3 mb-4 text-sm font-medium"><i class="fa-solid fa-circle-exclamation mr-2"></i><?= e($pesan) ?></div><?php endif; ?>

    <form method="post" data-tab="masuk" class="space-y-4">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="login">
      <div><label class="lbl" for="role">Masuk sebagai</label>
        <select name="role" id="role" class="inp" onchange="pilihPeran(this)">
          <?php foreach (['Pendaftar', 'Admin', 'Operator'] as $r): ?><option value="<?= $r ?>" <?= $v['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
        </select></div>
      <div data-peran="pendaftar"><label class="lbl" for="ident">Email / Nomor HP</label><input class="inp" id="ident" name="ident" value="<?= e($v['ident']) ?>" placeholder="nama@email.com atau 08xxxxxxxxxx" required></div>
      <div data-peran="pendaftar"><label class="lbl" for="pw">Password</label><input class="inp" id="pw" type="password" name="password" required></div>
      <div data-peran="kode" class="hidden"><label class="lbl" for="kode">Kode Masuk</label><input class="inp" id="kode" type="password" name="kode" autocomplete="off" placeholder="Masukkan kode rahasia"></div>
      <button class="btn btn-or w-full">Masuk <i class="fa-solid fa-arrow-right"></i></button>
    </form>

    <form method="post" data-tab="daftar" class="space-y-4 hidden">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="daftar">
      <div><label class="lbl">Nama lengkap calon siswa</label><input class="inp" name="nama" value="<?= e($v['nama']) ?>" required maxlength="150"></div>
      <div><label class="lbl">Email / Nomor HP</label><input class="inp" name="ident_d" value="<?= e($v['ident_d']) ?>" placeholder="nama@email.com atau 08xxxxxxxxxx" required></div>
      <div><label class="lbl">Password (min. 6 karakter)</label><input class="inp" type="password" name="pw1" minlength="6" required></div>
      <div><label class="lbl">Ulangi password</label><input class="inp" type="password" name="pw2" minlength="6" required></div>
      <button class="btn btn-or w-full"><i class="fa-solid fa-user-plus"></i> Buat Akun & Mulai Daftar</button>
    </form>
  </div>
  <p class="text-center mt-5 text-sm"><a href="index.php" class="text-orange-600 font-semibold"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke beranda</a></p>
</div>
<?php html_foot('<script>pilihTab(' . json_encode($tab) . ');var s=document.getElementById("role");s.dispatchEvent(new Event("change"));pilihPeran(s);</script>'); ?>
