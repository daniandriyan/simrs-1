CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','dokter') NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `clinics` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` char(3) NOT NULL UNIQUE
);

CREATE TABLE `doctors` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `specialization` varchar(100),
  `clinic_id` int,
  FOREIGN KEY (`clinic_id`) REFERENCES `clinics`(`id`)
);

CREATE TABLE `medicines` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `stock` int DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL
);

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
);

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
);

CREATE TABLE `medical_records` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `bp` varchar(20),
  `temp` decimal(4,1),
  `weight` int,
  `subjective` text,
  `objective` text,
  `assessment` text,
  `plan` text,
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`)
);

CREATE TABLE `prescriptions` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `qty` int NOT NULL,
  `instruction` varchar(100),
  `status` enum('Pending','Dispensed') DEFAULT 'Pending',
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`)
);

CREATE TABLE `billings` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `invoice_number` varchar(20) NOT NULL UNIQUE,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `paid_at` datetime NULL,
  FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`)
);

-- Seed Initial Data
INSERT INTO clinics (name, code) VALUES ('Poli Jantung', 'JAN'), ('Poli Gigi', 'GIG');
INSERT INTO doctors (fullname, specialization, clinic_id) VALUES ('dr. Dani Ramdani', 'Spesialis Jantung', 1), ('dr. Siti Aminah', 'Dokter Gigi', 2);
INSERT INTO users (fullname, username, password, role) VALUES ('Admin SIMRS', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
INSERT INTO medicines (name, stock, price, unit) VALUES ('Amlodipine 5mg', 100, 2000, 'Tablet'), ('Amoxicillin', 50, 3500, 'Tablet');
