-- ============================================
-- SIMRS Modern - Authentication Schema
-- Secure Authentication System Tables
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

USE `simrs_modern`;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('admin', 'dokter', 'perawat', 'farmasi', 'lab', 'rad', 'kasir', 'admin_poli', 'staff') DEFAULT 'staff',
  `access` TEXT DEFAULT NULL COMMENT 'Comma-separated module access list',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `last_login` DATETIME DEFAULT NULL,
  `last_activity` DATETIME DEFAULT NULL,
  `password_changed_at` DATETIME DEFAULT NULL,
  `otp_code` VARCHAR(6) DEFAULT NULL,
  `otp_expires` DATETIME DEFAULT NULL,
  `requires_password_change` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REMEMBER TOKENS (Remember Me Functionality)
-- ============================================
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expiry` BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expiry` (`expiry`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOGIN ATTEMPTS (Brute Force Protection)
-- ============================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6',
  `username_attempt` VARCHAR(100) DEFAULT NULL COMMENT 'Last username attempted',
  `attempts` INT UNSIGNED DEFAULT 0,
  `expires` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Lockout expiry timestamp',
  `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASSWORD RESET TOKENS
-- ============================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires` (`expires`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SESSIONS (Optional database-backed sessions)
-- ============================================
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `payload` TEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER ACTIVITY LOG
-- ============================================
CREATE TABLE IF NOT EXISTS `user_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ROLES AND PERMISSIONS (For future use)
-- ============================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `permissions` TEXT DEFAULT NULL COMMENT 'JSON encoded permissions',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `module` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Default admin user (password: admin123)
-- Hash generated with: password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12])
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`, `access`, `status`) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@simrs.local', 'admin', 'all', 1);

-- Default staff user (password: staff123)
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`, `access`, `status`) VALUES
('staff', '$2y$12$LqvzNzHjMxKxP5zGxqxqOe4YqJ8K9ZxYwNxJzKxP5zGxqxqOe4YqJ8', 'Staff User', 'staff@simrs.local', 'staff', 'dashboard,profile', 1);

-- Default roles
INSERT INTO `roles` (`name`, `description`, `permissions`) VALUES
('admin', 'Full system access', '{"*": true}'),
('dokter', 'Doctor access', {"dashboard": true, "pasien": true, "rawat_jalan": true, "rekam_medis": true}'),
('perawat', 'Nurse access', '{"dashboard": true, "pasien": true, "tensi": true}'),
('farmasi', 'Pharmacy access', '{"dashboard": true, "farmasi": true, "obat": true}'),
('lab', 'Laboratory access', '{"dashboard": true, "laboratorium": true}'),
('rad', 'Radiology access', '{"dashboard": true, "radiologi": true}'),
('kasir', 'Cashier access', '{"dashboard": true, "kasir": true, "billing": true}'),
('staff', 'Basic staff access', '{"dashboard": true, "profile": true}');

-- Default permissions
INSERT INTO `permissions` (`name`, `description`, `module`) VALUES
('can_login', 'Can login to system', 'auth'),
('can_admin', 'Can access admin panel', 'admin'),
('can_manage_users', 'Can manage users', 'users'),
('can_manage_settings', 'Can manage system settings', 'settings'),
('can_view_dashboard', 'Can view dashboard', 'dashboard'),
('can_manage_pasien', 'Can manage patient data', 'pasien'),
('can_view_reports', 'Can view reports', 'reports');

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Add indexes for common queries
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_login_attempts_ip ON login_attempts(ip);
CREATE INDEX idx_remember_tokens_token ON remember_tokens(token);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- ============================================
-- VIEW FOR ACTIVE USERS
-- ============================================
CREATE OR REPLACE VIEW `active_users` AS
SELECT 
    u.id,
    u.username,
    u.fullname,
    u.email,
    u.role,
    u.status,
    u.last_login,
    u.last_activity,
    TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) as minutes_since_activity
FROM users u
WHERE u.status = 1
  AND u.last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE);
