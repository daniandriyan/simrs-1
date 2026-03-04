-- Master Pemeriksaan Lab
CREATE TABLE `lab_tests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50),
    `price` DECIMAL(12,2) NOT NULL,
    `normal_range` VARCHAR(100), -- Contoh: 11.0 - 16.0
    `unit` VARCHAR(20) -- Contoh: g/dL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permintaan Lab (Orders)
CREATE TABLE `lab_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL,
    `test_id` INT NOT NULL,
    `status` ENUM('Pending', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending',
    `ordered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations`(`id`),
    FOREIGN KEY (`test_id`) REFERENCES `lab_tests`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hasil Lab (Results)
CREATE TABLE `lab_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `result_value` VARCHAR(255),
    `notes` TEXT,
    `input_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `lab_orders`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Lab
INSERT INTO `lab_tests` (name, category, price, normal_range, unit) VALUES 
('Hemoglobin', 'Darah Rutin', 50000, '12.0 - 16.0', 'g/dL'),
('Gula Darah Sewaktu', 'Kimia Darah', 35000, '< 200', 'mg/dL'),
('Cholesterol Total', 'Kimia Darah', 45000, '< 200', 'mg/dL');
