-- ==========================================================
-- ENTERPRISE SIMRS PRO - FULL DATABASE SCHEMA
-- Generated: 2026-03-04
-- ==========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. CORE SYSTEM & AUTHENTICATION
CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','dokter') NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. MASTER DATA (CLINICS, DOCTORS, MEDICINES)
CREATE TABLE `clinics` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` char(3) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `doctors` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `specialization` varchar(100),
  `clinic_id` int,
  FOREIGN KEY (`clinic_id`) REFERENCES `clinics`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `medicines` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `stock` int DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. PATIENT & REGISTRATION
CREATE TABLE `patients` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `no_rm` char(8) NOT NULL UNIQUE,
  `fullname` varchar(100) NOT NULL,
  `nik` char(16) NOT NULL UNIQUE,
  `gender` enum('L','P') NOT NULL,
  `birth_date` date NOT NULL,
  `address` text,
  `phone` varchar(20),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `registrations` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `no_rawat` varchar(15) NOT NULL UNIQUE,
  `no_reg` int NOT NULL,
  `patient_id` int NOT NULL,
  `clinic_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `status` enum('Antri','Periksa','Selesai','Batal') DEFAULT 'Antri',
  `registration_date` date NOT NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`),
  FOREIGN KEY (`clinic_id`) REFERENCES `clinics`(`id`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. MEDICAL RECORDS (EMR)
CREATE TABLE `medical_records` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `bp` varchar(20), -- Blood Pressure
  `temp` decimal(4,1), -- Suhu
  `weight` int, -- Berat
  `subjective` text, -- SOAP
  `objective` text,
  `assessment` text,
  `plan` text,
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `prescriptions` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `qty` int NOT NULL,
  `instruction` varchar(100),
  `status` enum('Pending','Dispensed') DEFAULT 'Pending',
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. LABORATORY INFORMATION SYSTEM (LIS)
CREATE TABLE `lab_tests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50),
    `price` DECIMAL(12,2) NOT NULL,
    `normal_range` VARCHAR(100),
    `unit` VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `lab_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `test_id` INT NOT NULL,
    `status` ENUM('Pending', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending',
    `ordered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`),
    FOREIGN KEY (`test_id`) REFERENCES `lab_tests`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `lab_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `result_value` VARCHAR(255),
    `notes` TEXT,
    `input_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `lab_orders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. INPATIENT & BED MANAGEMENT
CREATE TABLE `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `class` ENUM('VIP', 'Kelas 1', 'Kelas 2', 'Kelas 3') NOT NULL,
    `price_per_day` DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `beds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `bed_number` VARCHAR(10) NOT NULL,
    `status` ENUM('Available', 'Occupied', 'Cleaning', 'Maintenance') DEFAULT 'Available',
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inpatient_admissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `bed_id` INT NOT NULL,
    `check_in_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `check_out_at` DATETIME NULL,
    `status` ENUM('Active', 'Discharged', 'Transfered') DEFAULT 'Active',
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`),
    FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. BILLING & FINANCE
CREATE TABLE `billings` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `invoice_number` varchar(20) NOT NULL UNIQUE,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `paid_at` datetime NULL,
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. SATUSEHAT INTEGRATION MAPPING
CREATE TABLE `satusehat_mapping` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_type` ENUM('Patient', 'Encounter', 'Practitioner', 'Organization', 'Location', 'Observation') NOT NULL,
    `local_id` INT NOT NULL,
    `satusehat_id` VARCHAR(100) NOT NULL,
    `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_resource` (`resource_type`, `local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. AUDIT TRAIL
CREATE TABLE `audit_logs` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `action` varchar(50),
  `description` text,
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEEDING INITIAL DATA
INSERT INTO `clinics` (name, code) VALUES ('Poli Jantung', 'JAN'), ('Poli Gigi', 'GIG');
INSERT INTO `doctors` (fullname, specialization, clinic_id) VALUES ('dr. Dani Ramdani', 'Spesialis Jantung', 1), ('dr. Siti Aminah', 'Dokter Gigi', 2);
INSERT INTO `users` (fullname, username, password, role) VALUES ('Admin SIMRS', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
INSERT INTO `medicines` (name, stock, price, unit) VALUES ('Amlodipine 5mg', 100, 2000, 'Tablet'), ('Amoxicillin', 50, 3500, 'Tablet');
INSERT INTO `lab_tests` (name, category, price, normal_range, unit) VALUES ('Hemoglobin', 'Darah Rutin', 50000, '12.0 - 16.0', 'g/dL'), ('Gula Darah Sewaktu', 'Kimia Darah', 35000, '< 200', 'mg/dL');
INSERT INTO `rooms` (name, class, price_per_day) VALUES ('Bangsal Mawar', 'Kelas 1', 500000), ('VVIP Sakura', 'VIP', 1500000);
INSERT INTO `beds` (room_id, bed_number) VALUES (1, 'M1-01'), (1, 'M1-02'), (2, 'V-01');

COMMIT;
