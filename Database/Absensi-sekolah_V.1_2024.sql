-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping structure for table absensi-sekolah_v1_2025.absen
CREATE TABLE IF NOT EXISTS `absen` (
  `absen_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_toleransi` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `absen_in` varchar(20) NOT NULL,
  `absen_out` varchar(20) NOT NULL,
  `status_masuk` varchar(15) NOT NULL,
  `status_pulang` varchar(15) NOT NULL,
  `map_in` varchar(150) NOT NULL,
  `map_out` varchar(150) NOT NULL,
  `kehadiran` varchar(5) NOT NULL,
  `keterangan` text NOT NULL,
  PRIMARY KEY (`absen_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.absen: ~0 rows (approximately)
INSERT INTO `absen` (`absen_id`, `user_id`, `tanggal`, `jam_masuk`, `jam_toleransi`, `jam_pulang`, `absen_in`, `absen_out`, `status_masuk`, `status_pulang`, `map_in`, `map_out`, `kehadiran`, `keterangan`) VALUES
	(87, 1, '2025-06-30', '00:00:00', '00:00:00', '00:00:00', '00:00:00', '06:24:27', '-', 'Pulang Cepat', '', '-6.2652225,106.9978466', 'Sakit', 'Sakit');

-- Dumping structure for table absensi-sekolah_v1_2025.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(40) NOT NULL,
  `username` varchar(30) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(60) NOT NULL,
  `avatar` varchar(150) NOT NULL,
  `registrasi_date` date NOT NULL,
  `tanggal_login` datetime NOT NULL,
  `time` varchar(30) NOT NULL,
  `status` varchar(10) NOT NULL,
  `level` int NOT NULL,
  `ip` varchar(40) NOT NULL,
  `browser` varchar(40) NOT NULL,
  `active` varchar(2) NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.admin: ~3 rows (approximately)
INSERT INTO `admin` (`admin_id`, `fullname`, `username`, `phone`, `email`, `password`, `avatar`, `registrasi_date`, `tanggal_login`, `time`, `status`, `level`, `ip`, `browser`, `active`) VALUES
	(1, 'Coki Widodo', 'Widodo', '089666665781', 'swidodo.com@gmail.com', '$2y$10$iUZpF3UFbPjH4U/zErn5I.mixtbptmapoRYp6tIi69MqTVqaNirRy', 'avatar-Widodo-1670038763.jpg', '2022-03-22', '2025-08-01 22:40:45', '1754062845', 'Online', 1, '1', 'Google Crome', 'Y'),
	(6, 'Intan Permata sari', 'Intan', '089666665781', 'intanpermatasari@gmail.com', '$2y$10$lIKR1cqN8kNusBU45zqvAuINgD.g9X3/2rDBC6qvjT4oejy1jP53S', 'avatar.jpg', '2022-12-01', '2022-12-03 10:22:26', '1670047459', 'Offline', 1, '::1', 'Google Chrome 107.0.0.0', 'Y'),
	(7, 'Intan', 'Intan', '083160901108', 'intanwidodo@gmail.com', '$2y$10$qcLhXtELoswkSi.j4xEwFuK76EgZdLLR7aDlOikwJyp16B1y.dKXS', 'avatar.jpg', '2023-07-07', '2023-07-07 05:08:01', '1688699281', 'Offline', 3, '::1', 'Google Chrome 114.0.0.0', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.izin
CREATE TABLE IF NOT EXISTS `izin` (
  `izin_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `files` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alasan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` varchar(150) NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `status` varchar(10) NOT NULL,
  PRIMARY KEY (`izin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.izin: ~2 rows (approximately)
INSERT INTO `izin` (`izin_id`, `user_id`, `tanggal`, `tanggal_selesai`, `files`, `alasan`, `keterangan`, `time`, `date`, `status`) VALUES
	(39, 1, '2023-11-28', '2023-11-28', '0cd61cd07d9efc37649649d2e7b6978a.jpg', '', 'Sakit', '838:59:59', '2023-11-28', 'Y'),
	(40, 14, '2023-11-28', '2023-11-29', 'd76246dc8f61954d52885fa056e067b9.jpg', '', 'Izin sakit', '16:51:15', '2023-11-28', 'Y'),
	(41, 1, '2025-06-30', '2025-06-30', '', 'Sakit', 'Keterangan sakit', '01:59:43', '2025-06-30', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.jadwal
CREATE TABLE IF NOT EXISTS `jadwal` (
  `jadwal_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `hari` varchar(20) NOT NULL,
  `status` varchar(10) NOT NULL,
  PRIMARY KEY (`jadwal_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.jadwal: ~3 rows (approximately)
INSERT INTO `jadwal` (`jadwal_id`, `admin_id`, `hari`, `status`) VALUES
	(7, 7, 'Rabu', 'Y'),
	(8, 7, 'Selasa', 'Y'),
	(9, 7, 'Kamis', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.kartu_nama
CREATE TABLE IF NOT EXISTS `kartu_nama` (
  `kartu_id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(40) NOT NULL,
  `foto` varchar(100) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`kartu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.kartu_nama: ~0 rows (approximately)
INSERT INTO `kartu_nama` (`kartu_id`, `nama`, `foto`, `active`) VALUES
	(2, 'Template 1', 'slider-2025-07-17-1752739148.png', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.kelas
CREATE TABLE IF NOT EXISTS `kelas` (
  `kelas_id` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(40) NOT NULL,
  PRIMARY KEY (`kelas_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.kelas: ~3 rows (approximately)
INSERT INTO `kelas` (`kelas_id`, `nama_kelas`) VALUES
	(1, 'Kelas 1A'),
	(2, 'Kelas 1B'),
	(3, 'Kelas 2');

-- Dumping structure for table absensi-sekolah_v1_2025.lain_lain
CREATE TABLE IF NOT EXISTS `lain_lain` (
  `lain_lain_id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(40) NOT NULL,
  `tipe` varchar(20) NOT NULL,
  PRIMARY KEY (`lain_lain_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.lain_lain: ~5 rows (approximately)
INSERT INTO `lain_lain` (`lain_lain_id`, `nama`, `tipe`) VALUES
	(1, 'Asia/Jakarta', 'timezone'),
	(2, 'Asia/Makassar', 'timezone'),
	(3, 'Asia/Jayapura', 'timezone'),
	(4, 'Izin', 'izin'),
	(5, 'Sakit', 'izin');

-- Dumping structure for table absensi-sekolah_v1_2025.level
CREATE TABLE IF NOT EXISTS `level` (
  `level_id` int NOT NULL AUTO_INCREMENT,
  `level_nama` varchar(20) NOT NULL,
  PRIMARY KEY (`level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.level: ~3 rows (approximately)
INSERT INTO `level` (`level_id`, `level_nama`) VALUES
	(1, 'Superadmin'),
	(2, 'User'),
	(3, 'Guru');

-- Dumping structure for table absensi-sekolah_v1_2025.libur
CREATE TABLE IF NOT EXISTS `libur` (
  `libur_id` int NOT NULL AUTO_INCREMENT,
  `libur_hari` varchar(20) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`libur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.libur: ~3 rows (approximately)
INSERT INTO `libur` (`libur_id`, `libur_hari`, `active`) VALUES
	(1, 'Sabtu', 'N'),
	(2, 'Minggu', 'N'),
	(3, 'Jumat', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.libur_nasional
CREATE TABLE IF NOT EXISTS `libur_nasional` (
  `libur_nasional_id` int NOT NULL AUTO_INCREMENT,
  `libur_tanggal` date NOT NULL,
  `keterangan` varchar(60) NOT NULL,
  PRIMARY KEY (`libur_nasional_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.libur_nasional: ~2 rows (approximately)
INSERT INTO `libur_nasional` (`libur_nasional_id`, `libur_tanggal`, `keterangan`) VALUES
	(1, '2023-08-17', 'Hari Kemerdakaan Indonesia'),
	(7, '2023-11-30', 'Hari Guru');

-- Dumping structure for table absensi-sekolah_v1_2025.lokasi
CREATE TABLE IF NOT EXISTS `lokasi` (
  `lokasi_id` int NOT NULL AUTO_INCREMENT,
  `lokasi_nama` varchar(30) NOT NULL,
  `lokasi_alamat` text NOT NULL,
  `lokasi_latitude` varchar(100) NOT NULL,
  `lokasi_longitude` varchar(100) NOT NULL,
  `lokasi_radius` varchar(20) NOT NULL,
  `lokasi_qrcode` varchar(100) NOT NULL,
  `lokasi_tanggal` date NOT NULL,
  `lokasi_jam_mulai` time NOT NULL,
  `lokasi_jam_selesai` time NOT NULL,
  `lokasi_status` varchar(2) NOT NULL,
  PRIMARY KEY (`lokasi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.lokasi: ~0 rows (approximately)
INSERT INTO `lokasi` (`lokasi_id`, `lokasi_nama`, `lokasi_alamat`, `lokasi_latitude`, `lokasi_longitude`, `lokasi_radius`, `lokasi_qrcode`, `lokasi_tanggal`, `lokasi_jam_mulai`, `lokasi_jam_selesai`, `lokasi_status`) VALUES
	(2, 'S-widodo.com', 'Jl. Rizai kedaton bandar lampung', '-6.270526188764531', '106.99354513305688', '4000', '7FFB1668764933', '2022-11-18', '16:48:53', '16:48:53', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.modul
CREATE TABLE IF NOT EXISTS `modul` (
  `modul_id` int NOT NULL AUTO_INCREMENT,
  `modul_nama` varchar(45) NOT NULL,
  PRIMARY KEY (`modul_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.modul: ~12 rows (approximately)
INSERT INTO `modul` (`modul_id`, `modul_nama`) VALUES
	(1, 'Siswa'),
	(2, 'Siswa Tidak Aktif'),
	(3, 'Lokasi'),
	(5, 'Kelas'),
	(6, 'Waktu'),
	(7, 'Jadwal Piket'),
	(8, 'Libur'),
	(9, 'Izin'),
	(10, 'Laporan'),
	(11, 'Pengarutan Web'),
	(13, 'Admin'),
	(14, 'Hak Akses'),
	(15, 'Master Data'),
	(16, 'Kartu Nama');

-- Dumping structure for table absensi-sekolah_v1_2025.role
CREATE TABLE IF NOT EXISTS `role` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `level_id` int NOT NULL,
  `modul_id` int NOT NULL,
  `lihat` varchar(5) NOT NULL,
  `modifikasi` varchar(5) NOT NULL,
  `hapus` varchar(5) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.role: ~20 rows (approximately)
INSERT INTO `role` (`role_id`, `level_id`, `modul_id`, `lihat`, `modifikasi`, `hapus`) VALUES
	(2, 1, 2, 'Y', 'Y', 'Y'),
	(5, 1, 5, 'Y', 'Y', 'Y'),
	(11, 1, 11, 'Y', 'Y', 'Y'),
	(13, 1, 13, 'Y', 'Y', 'Y'),
	(14, 1, 14, 'Y', 'Y', 'Y'),
	(16, 2, 2, 'Y', 'Y', 'Y'),
	(19, 2, 5, 'Y', 'Y', 'Y'),
	(25, 2, 11, 'Y', 'Y', 'Y'),
	(27, 2, 13, 'Y', 'N', 'N'),
	(28, 2, 14, 'Y', 'N', 'N'),
	(29, 1, 3, 'Y', 'Y', 'Y'),
	(30, 3, 9, 'Y', 'Y', 'Y'),
	(31, 1, 6, 'Y', 'Y', 'Y'),
	(32, 1, 7, 'Y', 'Y', 'Y'),
	(33, 1, 8, 'Y', 'Y', 'Y'),
	(34, 1, 9, 'Y', 'Y', 'Y'),
	(35, 1, 10, 'Y', 'Y', 'Y'),
	(36, 3, 10, 'Y', 'Y', 'Y'),
	(37, 2, 7, 'Y', 'Y', 'Y'),
	(38, 1, 1, 'Y', 'Y', 'Y'),
	(39, 1, 15, 'Y', 'Y', 'Y'),
	(40, 1, 16, 'Y', 'Y', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.setting
CREATE TABLE IF NOT EXISTS `setting` (
  `site_id` int NOT NULL AUTO_INCREMENT,
  `site_name` varchar(50) NOT NULL,
  `site_phone` char(12) NOT NULL,
  `site_address` text NOT NULL,
  `site_owner` varchar(50) NOT NULL,
  `site_logo` varchar(100) NOT NULL,
  `site_favicon` varchar(60) NOT NULL,
  `site_kop` varchar(150) NOT NULL,
  `site_url` varchar(100) NOT NULL,
  `site_email` varchar(30) NOT NULL,
  `gmail_host` varchar(50) NOT NULL,
  `gmail_username` varchar(30) NOT NULL,
  `gmail_password` varchar(50) NOT NULL,
  `gmail_port` varchar(10) NOT NULL,
  `gmail_active` varchar(5) NOT NULL,
  `google_client_id` varchar(150) NOT NULL,
  `google_client_secret` varchar(150) NOT NULL,
  `google_client_active` varchar(5) NOT NULL,
  `timezone` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_token` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret_key` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_domain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_tipe` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `whatsapp_active` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.setting: ~0 rows (approximately)
INSERT INTO `setting` (`site_id`, `site_name`, `site_phone`, `site_address`, `site_owner`, `site_logo`, `site_favicon`, `site_kop`, `site_url`, `site_email`, `gmail_host`, `gmail_username`, `gmail_password`, `gmail_port`, `gmail_active`, `google_client_id`, `google_client_secret`, `google_client_active`, `timezone`, `whatsapp_phone`, `whatsapp_token`, `secret_key`, `whatsapp_domain`, `whatsapp_tipe`, `whatsapp_template`, `whatsapp_active`) VALUES
	(1, 'App. Absensi Siswa', '083160901108', 'Jl. Zainal Bidin Labuhan Ratu gg. Harapan 1 No 18', 'Widodo', 'sw-logoweb.png', 'sw-favicon.png', 'kop.jpg', 'http://localhost/aplikasi/Absensi-sekolah', 'swidodo.com@gmail.com', 'smtp.gmail.com', 'swidodo.com@gmail.com', 'uppaqftddopetqbw', '465', 'N', '---', '----', 'Y', 'Asia/Jakarta', '083160901108', '23848237487', '-', 'https://kudus.wablas.com/api/v2/send-message', 'POST', 'Assalamualaikum wr wb, Bapak/Ibu Kami  dari Perusahaan Menginformasikan bahwa :\r\n\r\nNama: {{nama}}\r\nHari/Tanggal: {{tanggal}}\r\n\r\n=============\r\nTelah {{tipe}} Sekolah\r\nJam Sekolah : {{jam_sekolah}}\r\nJam Absen : {{jam_absen}}\r\nStatus : {{status}}\r\n=============\r\nLokasi :  {{lokasi}}\r\n\r\nTerimakasih, Wassalamualaikum wr wb\r\nHormat kami,\r\nS-widodo.com', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.statistik
CREATE TABLE IF NOT EXISTS `statistik` (
  `statistik_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `jumlah` varchar(20) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`statistik_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.statistik: ~2 rows (approximately)
INSERT INTO `statistik` (`statistik_id`, `user_id`, `jumlah`, `date`) VALUES
	(1, 12, '2', '2023-06-28'),
	(2, 2, '1', '2023-06-28');

-- Dumping structure for table absensi-sekolah_v1_2025.tahun_pelajaran
CREATE TABLE IF NOT EXISTS `tahun_pelajaran` (
  `tahun_pelajaran_id` int NOT NULL AUTO_INCREMENT,
  `tahun_mulai` year NOT NULL,
  `tahun_selesai` year NOT NULL,
  `status` varchar(5) NOT NULL,
  PRIMARY KEY (`tahun_pelajaran_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.tahun_pelajaran: ~0 rows (approximately)
INSERT INTO `tahun_pelajaran` (`tahun_pelajaran_id`, `tahun_mulai`, `tahun_selesai`, `status`) VALUES
	(1, '2023', '2024', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.user
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `rfid` varchar(50) DEFAULT NULL,
  `nisn` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(120) NOT NULL,
  `nama_lengkap` varchar(70) NOT NULL,
  `tempat_lahir` varchar(30) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` varchar(10) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `lokasi` int NOT NULL,
  `alamat` text NOT NULL,
  `telp` varchar(15) NOT NULL,
  `avatar` varchar(160) NOT NULL,
  `tanggal_registrasi` datetime NOT NULL,
  `tanggal_login` datetime NOT NULL,
  `ip` varchar(30) NOT NULL,
  `browser` varchar(40) NOT NULL,
  `time` varchar(30) NOT NULL,
  `status` varchar(15) NOT NULL,
  `active` varchar(2) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.user: ~3 rows (approximately)
INSERT INTO `user` (`user_id`, `rfid`, `nisn`, `email`, `password`, `nama_lengkap`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `kelas`, `lokasi`, `alamat`, `telp`, `avatar`, `tanggal_registrasi`, `tanggal_login`, `ip`, `browser`, `time`, `status`, `active`) VALUES
	(1, '111111', '1234567812345678', 'swidodo.com@gmail.com', '$2y$10$9EUCR3BB/sJfbXNLnN0/dekSb7FscJqMMbNf1wnN9lW9RWUxCS.WG', 'Widodo', 'Kudus', '1991-08-07', 'Laki-laki', '1', 2, 'Jl, Zainal abidin labuhan ratu Bandar Lampung', '6283160901108', 'aboutsjpg.jpg', '2023-06-23 00:00:00', '2025-06-30 01:59:26', '', '', '1751223566', 'Online', 'Y'),
	(13, '-', '3445678', 'intan@gmail.com', '$2y$10$VLhGoWTxDxqM3an1dQ.rnOm/mF8v6OilH3EdD5QdDm86aDIoQpFVa', 'Intan', 'Bandar Lampung', '2023-08-02', 'Perempuan', '1', 2, 'Bandar Lampung', '083160901108', 'foto-vivijpg.jpg', '2023-08-09 10:40:27', '2023-08-09 10:40:27', '::1', 'Google Chrome 115.0.0.0', '1691545227', 'Offline', 'Y'),
	(14, '-', '2353255', 'swidodo.com@gmail.com', '$2y$10$3LWXBiuzyHimYKfOKpX.j.V5ivDYdtmmf2vzH5XMLd1vqkJGufKo6', 'Sample', 'Kudus', '1991-07-30', 'Laki-laki', '1', 2, 'Sample Alamat', '83160901108', 'avatar.jpg', '2023-08-11 16:34:36', '2023-08-11 16:34:36', '::1', 'Google Chrome 115.0.0.0', '1691746476', 'Offline', 'Y');

-- Dumping structure for table absensi-sekolah_v1_2025.waktu
CREATE TABLE IF NOT EXISTS `waktu` (
  `waktu_id` int NOT NULL AUTO_INCREMENT,
  `hari` varchar(15) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_telat` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`waktu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table absensi-sekolah_v1_2025.waktu: ~7 rows (approximately)
INSERT INTO `waktu` (`waktu_id`, `hari`, `jam_masuk`, `jam_telat`, `jam_pulang`, `active`) VALUES
	(1, 'Senin', '07:30:00', '07:40:00', '13:00:00', 'Y'),
	(2, 'Selasa', '07:30:00', '09:30:00', '09:30:00', 'Y'),
	(3, 'Rabu', '07:30:00', '09:30:00', '12:30:00', 'Y'),
	(4, 'Kamis', '07:30:00', '09:30:00', '15:30:00', 'Y'),
	(5, 'Jumat', '07:30:00', '09:30:00', '09:30:00', 'N'),
	(6, 'Sabtu', '07:30:00', '09:30:00', '09:30:00', 'Y'),
	(7, 'Minggu', '08:58:00', '08:58:00', '14:58:00', 'Y');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
