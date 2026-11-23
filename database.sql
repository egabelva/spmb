-- ============================================================
-- SPMB SMA Gunung Madu - database.sql (skema relasional per menu)
-- Cara pakai: phpMyAdmin -> tab Import -> pilih file ini -> Go.
-- PERINGATAN: file ini MENGHAPUS database db_spmb_gunungmadu yang lama
-- (jika ada) lalu membuatnya kembali dari nol beserta data contoh.
-- Total: 31 tabel + 1 view (v_pendaftar).
-- ============================================================
DROP DATABASE IF EXISTS db_spmb_gunungmadu;
CREATE DATABASE db_spmb_gunungmadu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_spmb_gunungmadu;
SET NAMES utf8mb4;

-- ============================================================
-- A. AKUN & AKSES (4 tabel)
-- ============================================================
CREATE TABLE akun (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(150) NOT NULL,
  email_hp VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_akun_email_hp (email_hp)
) ENGINE=InnoDB;

CREATE TABLE kode_akses (
  peran ENUM('Admin','Operator') NOT NULL,
  kode VARCHAR(100) NOT NULL,
  diubah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (peran)
) ENGINE=InnoDB;

CREATE TABLE log_aktivitas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  akun_id INT UNSIGNED NULL,
  peran VARCHAR(20) NOT NULL DEFAULT 'Tamu',
  aksi VARCHAR(50) NOT NULL,
  keterangan VARCHAR(255) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_log_waktu (waktu),
  KEY idx_log_aksi (aksi),
  CONSTRAINT fk_log_akun FOREIGN KEY (akun_id) REFERENCES akun (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE media_sosial (
  platform VARCHAR(30) NOT NULL,
  url VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (platform)
) ENGINE=InnoDB;

-- ============================================================
-- B. PENDAFTARAN PPDB (6 tabel)
-- ============================================================
CREATE TABLE jalur_pendaftaran (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(50) NOT NULL,
  urutan INT NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_jalur_nama (nama)
) ENGINE=InnoDB;

CREATE TABLE pendaftar (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  akun_id INT UNSIGNED NULL,
  jalur_id INT UNSIGNED NULL,
  no_pendaftaran VARCHAR(20) NULL,
  status ENUM('Draft','Pending','Diterima','Ditolak') NOT NULL DEFAULT 'Draft',
  sumber ENUM('Online','Offline') NOT NULL DEFAULT 'Online',
  waktu_daftar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pendaftar_akun (akun_id),
  UNIQUE KEY uq_pendaftar_nomor (no_pendaftaran),
  KEY idx_pendaftar_status (status),
  KEY idx_pendaftar_jalur (jalur_id),
  CONSTRAINT fk_pendaftar_akun FOREIGN KEY (akun_id) REFERENCES akun (id) ON DELETE SET NULL,
  CONSTRAINT fk_pendaftar_jalur FOREIGN KEY (jalur_id) REFERENCES jalur_pendaftaran (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE data_siswa (
  pendaftar_id INT UNSIGNED NOT NULL,
  nik_nisn VARCHAR(20) NOT NULL DEFAULT '',
  nama_lengkap VARCHAR(150) NOT NULL DEFAULT '',
  jenis_kelamin VARCHAR(20) NOT NULL DEFAULT '',
  tempat_lahir VARCHAR(50) NOT NULL DEFAULT '',
  tanggal_lahir DATE NULL,
  alamat VARCHAR(1000) NOT NULL DEFAULT '',
  asal_sekolah VARCHAR(150) NOT NULL DEFAULT '',
  PRIMARY KEY (pendaftar_id),
  KEY idx_siswa_nik (nik_nisn),
  KEY idx_siswa_nama (nama_lengkap),
  CONSTRAINT fk_siswa_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orang_tua_wali (
  pendaftar_id INT UNSIGNED NOT NULL,
  tipe VARCHAR(20) NOT NULL DEFAULT '',
  nama VARCHAR(150) NOT NULL DEFAULT '',
  pekerjaan VARCHAR(100) NOT NULL DEFAULT '',
  kontak VARCHAR(25) NOT NULL DEFAULT '',
  PRIMARY KEY (pendaftar_id),
  CONSTRAINT fk_wali_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE berkas_pendaftar (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pendaftar_id INT UNSIGNED NOT NULL,
  nama_berkas VARCHAR(255) NOT NULL,
  tipe_berkas VARCHAR(100) NOT NULL,
  data_berkas LONGBLOB NOT NULL,
  diunggah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_berkas_pendaftar (pendaftar_id),
  CONSTRAINT fk_berkas_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE riwayat_status (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pendaftar_id INT UNSIGNED NOT NULL,
  status_lama VARCHAR(10) NULL,
  status_baru VARCHAR(10) NOT NULL,
  diubah_oleh VARCHAR(20) NOT NULL DEFAULT 'Sistem',
  waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_riwayat_pendaftar (pendaftar_id),
  CONSTRAINT fk_riwayat_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- C. PENGATURAN & IDENTITAS (3 tabel, satu baris tiap tabel: id = 1)
-- ============================================================
CREATE TABLE pengaturan_ppdb (
  id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  kuota INT UNSIGNED NOT NULL DEFAULT 0,
  deadline DATETIME NULL,
  hasil_diumumkan TINYINT(1) NOT NULL DEFAULT 0,
  siswa_aktif INT UNSIGNED NOT NULL DEFAULT 0,
  diubah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE kontak_sekolah (
  id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  alamat VARCHAR(500) NOT NULL DEFAULT '',
  telepon VARCHAR(50) NOT NULL DEFAULT '',
  email VARCHAR(100) NOT NULL DEFAULT '',
  wa VARCHAR(25) NOT NULL DEFAULT '',
  diubah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE logo_sekolah (
  id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  logo_tipe VARCHAR(100) NOT NULL,
  logo MEDIUMBLOB NOT NULL,
  diubah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ============================================================
-- D. KONTEN WEBSITE: SATU TABEL PER MENU (18 tabel)
-- Struktur sama: judul, isi, tautan, urutan, gambar (BLOB)
-- ============================================================
CREATE TABLE banner (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  judul VARCHAR(200) NOT NULL DEFAULT '',
  isi TEXT NOT NULL,
  tautan VARCHAR(255) NOT NULL DEFAULT '',
  urutan INT NOT NULL DEFAULT 0,
  gambar_tipe VARCHAR(100) NULL,
  gambar MEDIUMBLOB NULL,
  dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_urutan (urutan)
) ENGINE=InnoDB;
CREATE TABLE profil_sekolah LIKE banner;
CREATE TABLE visi LIKE banner;
CREATE TABLE misi LIKE banner;
CREATE TABLE sejarah LIKE banner;
CREATE TABLE akreditasi LIKE banner;
CREATE TABLE fasilitas LIKE banner;
CREATE TABLE prestasi LIKE banner;
CREATE TABLE ekstrakurikuler LIKE banner;
CREATE TABLE organisasi LIKE banner;
CREATE TABLE guru LIKE banner;
CREATE TABLE galeri LIKE banner;
CREATE TABLE agenda LIKE banner;
CREATE TABLE jadwal_ppdb LIKE banner;
CREATE TABLE syarat_pendaftaran LIKE banner;
CREATE TABLE biaya_pendaftaran LIKE banner;

-- ============================================================
-- E. VIEW: gabungan data pendaftar (tanpa kolom berkas) agar mudah dibaca
-- ============================================================
CREATE VIEW v_pendaftar AS
SELECT p.id, p.akun_id, p.no_pendaftaran,
  COALESCE(s.nik_nisn,'') AS nik_nisn, COALESCE(s.nama_lengkap,'') AS nama_lengkap,
  COALESCE(s.jenis_kelamin,'') AS jenis_kelamin, COALESCE(s.tempat_lahir,'') AS tempat_lahir,
  s.tanggal_lahir AS tanggal_lahir, COALESCE(s.alamat,'') AS alamat, COALESCE(s.asal_sekolah,'') AS asal_sekolah,
  COALESCE(j.nama,'') AS jalur,
  COALESCE(o.tipe,'') AS tipe_batih, COALESCE(o.nama,'') AS nama_batih,
  COALESCE(o.pekerjaan,'') AS pekerjaan_batih, COALESCE(o.kontak,'') AS kontak_batih,
  COALESCE((SELECT GROUP_CONCAT(b.nama_berkas ORDER BY b.id SEPARATOR ', ') FROM berkas_pendaftar b WHERE b.pendaftar_id = p.id),'') AS nama_berkas,
  COALESCE((SELECT b2.tipe_berkas FROM berkas_pendaftar b2 WHERE b2.pendaftar_id = p.id ORDER BY b2.id LIMIT 1),'') AS tipe_berkas,
  p.status, p.sumber, p.waktu_daftar
FROM pendaftar p
LEFT JOIN data_siswa s ON s.pendaftar_id = p.id
LEFT JOIN orang_tua_wali o ON o.pendaftar_id = p.id
LEFT JOIN jalur_pendaftaran j ON j.id = p.jalur_id;

-- ============================================================
-- F. DATA AWAL (contoh - ubah lewat Dashboard Operator / Admin)
-- ============================================================
INSERT INTO kode_akses (peran, kode) VALUES ('Admin','SMAGM'), ('Operator','OPERATOR2027');
INSERT INTO media_sosial (platform, url) VALUES ('instagram',''), ('facebook',''), ('youtube',''), ('tiktok','');
INSERT INTO jalur_pendaftaran (nama, urutan, aktif) VALUES ('Domisili',1,1), ('Afirmasi',2,1), ('Prestasi',3,1), ('Perpindahan Tugas Orang Tua',4,1);
INSERT INTO pengaturan_ppdb (id, kuota, deadline, hasil_diumumkan, siswa_aktif) VALUES (1, 180, '2027-06-30 23:59:00', 0, 0);
INSERT INTO kontak_sekolah (id, alamat, telepon, email, wa) VALUES (1, 'Alamat sekolah (contoh - ubah lewat Dashboard Operator)', '0721-000000', 'info@smagunungmadu.example', '');

INSERT INTO banner (judul, isi, urutan) VALUES ('Penerimaan Murid Baru SMA Gunung Madu 2027', 'Daftarkan dirimu secara online dan jadilah bagian dari keluarga besar kami.', 1);
INSERT INTO profil_sekolah (judul, isi, urutan) VALUES ('', 'SMA Gunung Madu adalah sekolah menengah atas yang berkomitmen mencetak lulusan yang cerdas, hebat, dan bermartabat. (Teks contoh - ubah lewat Dashboard Operator.)', 1);
INSERT INTO visi (judul, isi, urutan) VALUES ('', 'Menjadi sekolah unggul yang smart, hebat, dan bermartabat.', 1);
INSERT INTO misi (judul, isi, urutan) VALUES ('', 'Menyelenggarakan pembelajaran yang berkualitas dan menyenangkan.', 1), ('', 'Membentuk karakter siswa yang berakhlak mulia dan berintegritas.', 2), ('', 'Mengembangkan bakat dan prestasi siswa di bidang akademik maupun non-akademik.', 3);
INSERT INTO sejarah (judul, isi, urutan) VALUES ('', 'Sejarah singkat sekolah (teks contoh - ubah lewat Dashboard Operator).', 1);
INSERT INTO akreditasi (judul, isi, urutan) VALUES ('A', 'Keterangan SK akreditasi (contoh - sesuaikan dengan data resmi sekolah).', 1);
INSERT INTO fasilitas (judul, isi, urutan) VALUES ('Laboratorium', 'Laboratorium untuk menunjang praktik pembelajaran.', 1), ('Perpustakaan', 'Ruang baca dengan koleksi buku pelajaran dan referensi.', 2), ('Ruang Kelas', 'Ruang kelas yang nyaman untuk belajar.', 3), ('Masjid', 'Sarana ibadah bagi warga sekolah.', 4), ('Lapangan Olahraga', 'Lapangan untuk kegiatan olahraga dan upacara.', 5);
INSERT INTO prestasi (judul, isi, urutan) VALUES ('Prestasi contoh 1', 'Tingkat/tahun (contoh - ganti dengan prestasi sebenarnya)', 1), ('Prestasi contoh 2', 'Tingkat/tahun (contoh - ganti dengan prestasi sebenarnya)', 2);
INSERT INTO ekstrakurikuler (judul, isi, urutan) VALUES ('Pramuka', 'Kegiatan kepramukaan dan kepemimpinan.', 1), ('Paskibra', 'Pasukan pengibar bendera sekolah.', 2), ('Futsal', 'Latihan dan pertandingan futsal.', 3), ('PMR', 'Palang Merah Remaja dan kegiatan kemanusiaan.', 4);
INSERT INTO organisasi (judul, isi, urutan) VALUES ('OSIS', 'Organisasi Siswa Intra Sekolah.', 1), ('MPK', 'Majelis Perwakilan Kelas.', 2);
INSERT INTO guru (judul, isi, urutan) VALUES ('Nama Guru 1 (contoh)', 'Mata pelajaran / jabatan', 1), ('Nama Guru 2 (contoh)', 'Mata pelajaran / jabatan', 2);
INSERT INTO galeri (judul, isi, urutan) VALUES ('Kegiatan belajar (contoh)', '', 1), ('Kegiatan sekolah (contoh)', '', 2);
INSERT INTO agenda (judul, isi, urutan) VALUES ('Masa Pengenalan Lingkungan Sekolah (MPLS)', 'Awal tahun pelajaran (contoh)', 1), ('Pekan Olahraga dan Seni', 'Setiap semester (contoh)', 2), ('Pelepasan Siswa Kelas XII', 'Akhir tahun pelajaran (contoh)', 3);
INSERT INTO jadwal_ppdb (judul, isi, urutan) VALUES ('Pendaftaran online', 'Contoh: Mei - Juni 2027', 1), ('Verifikasi berkas', 'Contoh: Juni 2027', 2), ('Pengumuman hasil seleksi', 'Contoh: Juli 2027', 3), ('Daftar ulang', 'Contoh: Juli 2027', 4);
INSERT INTO syarat_pendaftaran (judul, isi, urutan) VALUES ('Ijazah / Surat Keterangan Lulus (SKL)', '', 1), ('Kartu Keluarga', '', 2), ('Akta kelahiran', '', 3), ('Pas foto terbaru', '', 4);
INSERT INTO biaya_pendaftaran (judul, isi, urutan) VALUES ('Biaya pendaftaran', 'Contoh: Gratis', 1), ('Biaya daftar ulang', 'Contoh: sesuai ketentuan sekolah', 2);
