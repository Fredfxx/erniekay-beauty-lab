-- Database schema for Erniekay Beauty Lab Appointments
-- Run this in phpMyAdmin on your hosting server

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` VARCHAR(255) NOT NULL,
  `service` VARCHAR(100) NOT NULL,
  `preferred_date` DATE NOT NULL,
  `preferred_time` VARCHAR(20) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
