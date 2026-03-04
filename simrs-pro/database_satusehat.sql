-- Pemetaan ID Lokal ke UUID SatuSehat
CREATE TABLE `satusehat_mapping` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_type` ENUM('Patient', 'Encounter', 'Practitioner', 'Organization', 'Location', 'Observation') NOT NULL,
    `local_id` INT NOT NULL,
    `satusehat_id` VARCHAR(100) NOT NULL, -- UUID dari SatuSehat
    `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_resource` (`resource_type`, `local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log Transaksi API
CREATE TABLE `satusehat_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_type` VARCHAR(50),
    `method` ENUM('GET', 'POST', 'PUT', 'PATCH'),
    `request_body` LONGTEXT,
    `response_body` LONGTEXT,
    `status_code` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
