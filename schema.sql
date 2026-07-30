CREATE DATABASE IF NOT EXISTS `sms_panel_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sms_panel_db`;

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table (Manager, Agent, Client)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('manager', 'agent', 'client') NOT NULL DEFAULT 'client',
  `name` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `team` VARCHAR(50) DEFAULT NULL,
  `country` VARCHAR(50) DEFAULT NULL,
  `usd_balance` DECIMAL(10,4) DEFAULT 0.0000,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Manager Account (Username: admin | Password: password123)
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `usd_balance`) VALUES
('admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'manager', 'System Administrator', 'admin@smsportal.local', 500.0000)
ON DUPLICATE KEY UPDATE `username`='admin';

-- Ranges / Terminations table
CREATE TABLE IF NOT EXISTS `ranges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `range_code` VARCHAR(50) NOT NULL,
  `prefix` VARCHAR(20) NOT NULL,
  `test_number` VARCHAR(30) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `payouts` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `memo` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- External APIs table
CREATE TABLE IF NOT EXISTS `external_apis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `api_url` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SMS CDR / Logs table
CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `datetime` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `range_code` VARCHAR(50) NOT NULL,
  `phone_number` VARCHAR(30) NOT NULL,
  `cli` VARCHAR(50) NOT NULL,
  `client_id` INT DEFAULT NULL,
  `message` TEXT NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'USD',
  `payout` DECIMAL(10,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
