<?php
// Ekspor data pendaftar ke CSV (dibuka langsung di Excel).
require 'koneksi.php';
require_role('Admin');
$status = $_GET['status'] ?? '';
$sql = "SELECT no_pendaftaran,nik_nisn,nama_lengkap,jenis_kelamin,tempat_lahir,tanggal_lahir,alamat,asal_sekolah,jalur,tipe_batih,nama_batih,pekerjaan_batih,kontak_batih,nama_berkas,status,sumber,waktu_daftar FROM v_pendaftar WHERE status<>'Draft'";
$rows = in_array($status, ['Pending', 'Diterima', 'Ditolak'], true) ? db_all($sql . ' AND status=? ORDER BY waktu_daftar DESC', 's', [$status]) : db_all($sql . ' ORDER BY waktu_daftar DESC');
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="rekap_pendaftar_' . date('Ymd_His') . '.csv"');
echo "\xEF\xBB\xBF" . "sep=;\r\n";
$out = fopen('php://output', 'w');
fputcsv($out, ['No. Pendaftaran','NIK/NISN','Nama','Jenis Kelamin','Tempat Lahir','Tanggal Lahir','Alamat','Asal Sekolah','Jalur','Hubungan Wali','Nama Wali','Pekerjaan Wali','Kontak Wali','Berkas','Status','Sumber','Waktu Daftar'], ';');
foreach ($rows as $r) {
    foreach ($r as $k => $v) {
        $v = (string)$v;
        if ($v !== '' && strpbrk($v[0], '=+-@') !== false) $v = "'" . $v;      // cegah CSV injection
        if (in_array($k, ['nik_nisn', 'kontak_batih'], true) && $v !== '') $v = "\t" . $v; // jaga angka nol di depan
        $r[$k] = $v;
    }
    fputcsv($out, $r, ';');
}
fclose($out);
