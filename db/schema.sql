-- Database schema for LTO HRIS (MySQL)

CREATE DATABASE IF NOT EXISTS `lto_hris` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lto_hris`;

-- Roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users (HR staff / admins)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `username` VARCHAR(80) UNIQUE,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `account_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(160) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `requested_username` VARCHAR(80) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending_review',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User roles (many-to-many)
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  PRIMARY KEY(`user_id`,`role_id`),
  FOREIGN KEY (`user_id`) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employees (personnel master record)
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_number` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(120) NOT NULL,
  `middle_name` VARCHAR(120),
  `last_name` VARCHAR(120) NOT NULL,
  `birthdate` DATE,
  `gender` VARCHAR(16),
  `civil_status` VARCHAR(32),
  `position` VARCHAR(120),
  `department` VARCHAR(120),
  `date_hired` DATE,
  `status` VARCHAR(32) DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CSC Forms (track form types and file uploads)
CREATE TABLE IF NOT EXISTS `csc_forms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `form_type` VARCHAR(100) NOT NULL,
  `file_path` VARCHAR(255),
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personnel Data Sheet (PDS)
CREATE TABLE IF NOT EXISTS `pds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `file_path` VARCHAR(255),
  `year` SMALLINT,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SALN submissions
CREATE TABLE IF NOT EXISTS `saln_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `year` SMALLINT NOT NULL,
  `file_path` VARCHAR(255),
  `status` VARCHAR(32) DEFAULT 'Submitted',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Simple audit table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `action` VARCHAR(255),
  `meta` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed basic roles
INSERT IGNORE INTO `roles` (`name`, `description`) VALUES
('admin', 'System administrator'),
('hr_officer', 'HR officer with personnel management permissions');
