# SPMB SMA Gunung Madu - Petunjuk Cepat

## Instalasi (XAMPP)
1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka `http://localhost/phpmyadmin` -> tab **Import** -> pilih file **database.sql** (dikirim terpisah dari zip ini) -> **Go**.
   Database `db_spmb_gunungmadu` akan dibuat otomatis beserta isi awalnya.
3. Ekstrak zip ini sehingga foldernya menjadi `C:/xampp/htdocs/spmb-gunungmadu/`.
4. Buka `http://localhost/spmb-gunungmadu/` (halaman publik) atau `.../login.php` (gerbang masuk).

Jika MySQL Anda memakai password, ubah `$DB_PASS` di `koneksi.php`. Website memakai Tailwind CSS, Font Awesome, dan Chart.js dari CDN, jadi komputer perlu terhubung internet.

## Akun & Kode Awal
| Peran | Cara masuk |
|---|---|
| Pendaftar | Buat akun di tab **Buat Akun** (email/nomor HP + password) |
| Admin | Pilih peran Admin, kode: `SMAGM` |
| Operator | Pilih peran Operator, kode: `OPERATOR2027` |

Kode Admin dan Operator dapat diganti di **Dashboard Operator -> Pengaturan -> Kode Masuk**.

## Ringkasan Fitur
- **Publik (index.php):** beranda, statistik & hitung mundur, info PPDB (jadwal, jalur, syarat, biaya), profil sekolah, fasilitas, prestasi, ekskul, organisasi, guru, galeri, agenda, kontak (teks alamat, telepon, email)/WhatsApp, mode terang-gelap.
- **Pendaftar:** buat akun, login, isi biodata, simpan draft, indikator progres, unggah dokumen, kirim, bukti pendaftaran (cetak/PDF), lihat status & hasil, surat kelulusan.
- **Admin:** statistik & grafik, tabel dengan filter status & pencarian, detail (modal), Terima/Tolak (Tolak ada konfirmasi), isi pendaftar offline, ekspor Excel (CSV), cetak/PDF, atur kuota/batas daftar/jalur, tombol **Umumkan hasil seleksi**.
- **Operator:** tampilan website langsung dengan tombol **Edit** di setiap elemen (ubah teks, hapus, unggah foto), kontak & statistik, ganti kode masuk.

## Catatan Penting
- Hasil Diterima/Ditolak baru terlihat oleh pendaftar setelah Admin mencentang **Umumkan hasil seleksi** (Pengaturan PPDB).
- Ukuran berkas dibatasi maks. 2 MB (otomatis lebih kecil jika `max_allowed_packet` MySQL kecil). Jika ingin lebih besar, tambahkan `max_allowed_packet=16M` di `my.ini` (bagian `[mysqld]`) lalu restart MySQL, serta cek `upload_max_filesize` di `php.ini`.
- Seluruh isi website di `database.sql` hanyalah contoh. Ganti alamat, nomor WhatsApp, jadwal, dan konten lain lewat Dashboard Operator.
- Kebutuhan: PHP 7.4+ (disarankan 8.x) dengan ekstensi mysqli, mbstring, fileinfo (bawaan XAMPP).
