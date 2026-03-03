-- ============================================
-- SIMRS Modern Database Schema
-- Version: 1.0.0
-- Database: simrs_modern
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ============================================
-- DATABASE CREATION
-- ============================================
CREATE DATABASE IF NOT EXISTS `simrs_modern` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `simrs_modern`;

-- ============================================
-- CORE TABLES
-- ============================================

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('admin', 'dokter', 'perawat', 'farmasi', 'lab', 'rad', 'kasir', 'admin_poli') DEFAULT 'admin',
  `access` TEXT DEFAULT NULL COMMENT 'Comma-separated module access',
  `status` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `password_changed_at` DATETIME DEFAULT NULL,
  `otp_code` VARCHAR(6) DEFAULT NULL,
  `otp_expires` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`, `access`) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@simrs.local', 'admin', 'all');

-- Modules table
CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dir` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `version` VARCHAR(20) DEFAULT '1.0.0',
  `author` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `is_core` TINYINT(1) DEFAULT 0,
  `installed_at` DATETIME DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dir` (`dir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Module settings
CREATE TABLE IF NOT EXISTS `module_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(100) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `type` VARCHAR(20) DEFAULT 'text',
  `label` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_key` (`module`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Module logs
CREATE TABLE IF NOT EXISTS `module_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(100) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(100) DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `context` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `module` (`module`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(64) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `ip_range` VARCHAR(255) DEFAULT '*' COMMENT 'Comma-separated IP ranges or * for all',
  `methods` VARCHAR(50) DEFAULT 'GET,POST' COMMENT 'Comma-separated allowed methods',
  `exp_time` DATETIME DEFAULT NULL,
  `last_used` DATETIME DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remember me tokens
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expiry` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL,
  `attempts` INT UNSIGNED DEFAULT 0,
  `expires` BIGINT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MASTER DATA TABLES (Healthcare)
-- ============================================

-- Poliklinik
CREATE TABLE IF NOT EXISTS `poliklinik` (
  `kd_poli` VARCHAR(10) NOT NULL,
  `nm_poli` VARCHAR(100) NOT NULL,
  `registrasi` INT UNSIGNED DEFAULT 0,
  `registrasilanjut` INT UNSIGNED DEFAULT 0,
  `kd_pj_ralan` VARCHAR(3) DEFAULT NULL,
  `kd_pj_ranap` VARCHAR(3) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`kd_poli`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dokter
CREATE TABLE IF NOT EXISTS `dokter` (
  `kd_dokter` VARCHAR(20) NOT NULL,
  `nm_dokter` VARCHAR(100) NOT NULL,
  `jk` ENUM('L', 'P') NOT NULL,
  `tmp_lahir` VARCHAR(100) DEFAULT NULL,
  `tgl_lahir` DATE DEFAULT NULL,
  `gol_darah` ENUM('A', 'B', 'AB', 'O', '-') DEFAULT '-',
  `agama` VARCHAR(20) DEFAULT NULL,
  `stts_nikah` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `no_telp` VARCHAR(20) DEFAULT NULL,
  `stts_napir` ENUM('1', '0') DEFAULT '1' COMMENT '1=Aktif, 0=Nonaktif',
  `kd_sps` VARCHAR(10) DEFAULT 'UMUM',
  `alumni` VARCHAR(100) DEFAULT NULL,
  `no_ijn_praktek` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`kd_dokter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penjab (Insurance)
CREATE TABLE IF NOT EXISTS `penjab` (
  `kd_pj` VARCHAR(3) NOT NULL,
  `png_jawab` VARCHAR(100) NOT NULL,
  `nama_perusahaan` VARCHAR(255) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `kota` VARCHAR(100) DEFAULT NULL,
  `telp` VARCHAR(20) DEFAULT NULL,
  `attn` VARCHAR(100) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `jenis` ENUM('BPJS', 'ASURANSI', 'UMUM') DEFAULT 'UMUM',
  PRIMARY KEY (`kd_pj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PATIENT TABLES
-- ============================================

-- Pasien (Patients)
CREATE TABLE IF NOT EXISTS `pasien` (
  `no_rkm_medis` VARCHAR(15) NOT NULL,
  `nm_pasien` VARCHAR(255) NOT NULL,
  `no_ktp` VARCHAR(20) DEFAULT NULL,
  `jk` ENUM('L', 'P') NOT NULL,
  `tmp_lahir` VARCHAR(100) DEFAULT NULL,
  `tgl_lahir` DATE NOT NULL,
  `gol_darah` ENUM('A', 'B', 'AB', 'O', '-') DEFAULT '-',
  `agama` VARCHAR(20) DEFAULT 'ISLAM',
  `stts_nikah` VARCHAR(20) DEFAULT 'BELUM MENIKAH',
  `pekerjaan` VARCHAR(100) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `kelurahan` VARCHAR(100) DEFAULT NULL,
  `kecamatan` VARCHAR(100) DEFAULT NULL,
  `kabupaten` VARCHAR(100) DEFAULT NULL,
  `propinsi` VARCHAR(100) DEFAULT NULL,
  `no_tlp` VARCHAR(20) DEFAULT NULL,
  `no_peserta` VARCHAR(30) DEFAULT NULL,
  `kd_pj` VARCHAR(3) DEFAULT NULL,
  `tgl_daftar` DATE NOT NULL,
  `umur` VARCHAR(20) DEFAULT NULL,
  `pnd` VARCHAR(20) DEFAULT '-',
  `keluarga` VARCHAR(20) DEFAULT NULL,
  `namakeluarga` VARCHAR(255) DEFAULT NULL,
  `pekerjaanpj` VARCHAR(100) DEFAULT NULL,
  `alamatpj` TEXT DEFAULT NULL,
  `kelurahanpj` VARCHAR(100) DEFAULT NULL,
  `kecamatanpj` VARCHAR(100) DEFAULT NULL,
  `kabupatenpj` VARCHAR(100) DEFAULT NULL,
  `propinsipj` VARCHAR(100) DEFAULT NULL,
  `perusahaan_pasien` VARCHAR(255) DEFAULT NULL,
  `suku_bangsa` VARCHAR(50) DEFAULT NULL,
  `bahasa_pasien` VARCHAR(50) DEFAULT NULL,
  `cacat_fisik` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `nip` VARCHAR(20) DEFAULT '-',
  `kd_kel` VARCHAR(10) DEFAULT NULL,
  `kd_kec` VARCHAR(10) DEFAULT NULL,
  `kd_kab` VARCHAR(10) DEFAULT NULL,
  `kd_prop` VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (`no_rkm_medis`),
  KEY `kd_pj` (`kd_pj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reg Periksa (Registration)
CREATE TABLE IF NOT EXISTS `reg_periksa` (
  `no_rawat` VARCHAR(17) NOT NULL,
  `no_reg` VARCHAR(8) DEFAULT NULL,
  `no_rkm_medis` VARCHAR(15) NOT NULL,
  `kd_dokter` VARCHAR(20) NOT NULL,
  `kd_poli` VARCHAR(10) NOT NULL,
  `p_jawab` VARCHAR(255) DEFAULT NULL,
  `almt_pj` TEXT DEFAULT NULL,
  `hubungan` VARCHAR(20) DEFAULT NULL,
  `biaya_obat_operasi` DECIMAL(12,2) DEFAULT 0.00,
  `pertambahan` DECIMAL(12,2) DEFAULT 0.00,
  `jasa_medis_dokter` DECIMAL(12,2) DEFAULT 0.00,
  `jasa_medis_perawat` DECIMAL(12,2) DEFAULT 0.00,
  `jasa_medis_lain` DECIMAL(12,2) DEFAULT 0.00,
  `kamar` DECIMAL(12,2) DEFAULT 0.00,
  `jasa_sarana` DECIMAL(12,2) DEFAULT 0.00,
  `jasa_perawat` DECIMAL(12,2) DEFAULT 0.00,
  `total_biaya` DECIMAL(12,2) DEFAULT 0.00,
  `tgl_registrasi` DATE NOT NULL,
  `jam_reg` TIME NOT NULL,
  `daftar` VARCHAR(10) DEFAULT 'REGULER',
  `limit_reg` INT UNSIGNED DEFAULT 0,
  `intruksi` TEXT DEFAULT NULL,
  `diagnosa_awal` TEXT DEFAULT NULL,
  `poli_ralan` VARCHAR(10) DEFAULT NULL,
  `stts` VARCHAR(30) DEFAULT 'Belum',
  `stts_daftar` VARCHAR(30) DEFAULT 'BARU',
  `status_bayar` VARCHAR(20) DEFAULT 'Belum Bayar',
  `status_lanjut` VARCHAR(20) DEFAULT 'Rawat Jalan',
  `kd_pj` VARCHAR(3) NOT NULL,
  `kd_dokter_ranap` VARCHAR(20) DEFAULT NULL,
  `kelas_ranap` VARCHAR(50) DEFAULT NULL,
  `insert_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`no_rawat`),
  KEY `no_rkm_medis` (`no_rkm_medis`),
  KEY `kd_dokter` (`kd_dokter`),
  KEY `kd_poli` (`kd_poli`),
  KEY `kd_pj` (`kd_pj`),
  KEY `tgl_registrasi` (`tgl_registrasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking Registrasi
CREATE TABLE IF NOT EXISTS `booking_registrasi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal_periksa` DATE NOT NULL,
  `jam_periksa` TIME NOT NULL,
  `no_rkm_medis` VARCHAR(15) NOT NULL,
  `kd_dokter` VARCHAR(20) NOT NULL,
  `kd_poli` VARCHAR(10) NOT NULL,
  `no_reg` VARCHAR(8) DEFAULT NULL,
  `limit_reg` INT UNSIGNED DEFAULT 0,
  `status_kirim` TINYINT(1) DEFAULT 0,
  `suhu_badan` DECIMAL(4,2) DEFAULT NULL,
  `tinggi` DECIMAL(5,2) DEFAULT NULL,
  `berat` DECIMAL(5,2) DEFAULT NULL,
  `keluhan` TEXT DEFAULT NULL,
  `informasi_tambahan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tanggal_periksa` (`tanggal_periksa`),
  KEY `no_rkm_medis` (`no_rkm_medis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default Poliklinik
INSERT INTO `poliklinik` (`kd_poli`, `nm_poli`, `status`) VALUES
('POLI001', 'Poli Penyakit Dalam', 1),
('POLI002', 'Poli Anak', 1),
('POLI003', 'Poli Bedah', 1),
('POLI004', 'Poli Gigi', 1),
('POLI005', 'Poli Mata', 1),
('POLI006', 'Poli Kandungan', 1),
('POLI007', 'Poli THT', 1),
('POLI008', 'Poli Jantung', 1),
('POLI009', 'Poli Kulit', 1),
('POLI010', 'Poli Jiwa', 1);

-- Default Penjab
INSERT INTO `penjab` (`kd_pj`, `png_jawab`, `jenis`, `status`) VALUES
('UMU', 'Umum', 'UMUM', 1),
('BPJ', 'BPJS Kesehatan', 'BPJS', 1),
('ASI', 'Asuransi Swasta', 'ASURANSI', 1);

-- Insert default modules
INSERT INTO `modules` (`dir`, `name`, `description`, `version`, `author`, `status`, `is_core`) VALUES
('Dashboard', 'Dashboard', 'Main dashboard', '1.0.0', 'SIMRS Team', 1, 1),
('Users', 'Users', 'User management', '1.0.0', 'SIMRS Team', 1, 1),
('Settings', 'Settings', 'System settings', '1.0.0', 'SIMRS Team', 1, 1),
('Master', 'Master', 'Master data management', '1.0.0', 'SIMRS Team', 1, 1);
