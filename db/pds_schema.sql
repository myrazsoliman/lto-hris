-- Comprehensive PDS Database Schema for CSC Form No. 212
-- This script adds detailed tables to the existing lto_hris database

USE `lto_hris`;

-- Drop existing simple PDS table if it exists
DROP TABLE IF EXISTS `pds`;

-- Main PDS Record Table
CREATE TABLE IF NOT EXISTS `pds_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `year` SMALLINT NOT NULL,
  `status` ENUM('draft', 'submitted', 'for_review', 'approved', 'returned') DEFAULT 'draft',
  `submitted_at` TIMESTAMP NULL,
  `approved_at` TIMESTAMP NULL,
  `approved_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES employees(id) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY `unique_employee_year` (`employee_id`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 1: Personal Information
CREATE TABLE IF NOT EXISTS `pds_personal_info` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  -- Personal Information
  `surname` VARCHAR(120) NOT NULL,
  `first_name` VARCHAR(120) NOT NULL,
  `middle_name` VARCHAR(120),
  `name_extension` VARCHAR(20), -- Jr., Sr., III, etc.
  `date_of_birth` DATE,
  `place_of_birth` VARCHAR(255),
  `sex` ENUM('Male', 'Female'),
  `civil_status` ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced', 'Common Law'),
  `citizenship` VARCHAR(50),
  `height_m` DECIMAL(3,2),
  `weight_kg` DECIMAL(3,1),
  `blood_type` ENUM('A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'),
  `gsis_id` VARCHAR(50),
  `pagibig_id` VARCHAR(50),
  `philhealth_id` VARCHAR(50),
  `sss_id` VARCHAR(50),
  `tin_id` VARCHAR(50),
  `agency_employee_no` VARCHAR(50),
  `residential_address` TEXT,
  `residential_zip_code` VARCHAR(10),
  `residential_telephone` VARCHAR(20),
  `permanent_address` TEXT,
  `permanent_zip_code` VARCHAR(10),
  `permanent_telephone` VARCHAR(20),
  `email_address` VARCHAR(150),
  `mobile_number` VARCHAR(20),
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 1: Family Background
CREATE TABLE IF NOT EXISTS `pds_family_background` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  -- Spouse Information
  `spouse_surname` VARCHAR(120),
  `spouse_first_name` VARCHAR(120),
  `spouse_middle_name` VARCHAR(120),
  `spouse_name_extension` VARCHAR(20),
  `spouse_occupation` VARCHAR(100),
  `spouse_employer_business_name` VARCHAR(150),
  `spouse_business_address` TEXT,
  `spouse_telephone` VARCHAR(20),
  -- Father Information
  `father_surname` VARCHAR(120),
  `father_first_name` VARCHAR(120),
  `father_middle_name` VARCHAR(120),
  `father_name_extension` VARCHAR(20),
  -- Mother Information
  `mother_maiden_surname` VARCHAR(120),
  `mother_first_name` VARCHAR(120),
  `mother_middle_name` VARCHAR(120),
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Children Information
CREATE TABLE IF NOT EXISTS `pds_children` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `child_name` VARCHAR(255) NOT NULL,
  `date_of_birth` DATE,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 2: Educational Background
CREATE TABLE IF NOT EXISTS `pds_education` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `level` ENUM('Elementary', 'High School', 'College', 'Vocational/Trade Course', 'Graduate Studies') NOT NULL,
  `school_name` VARCHAR(255) NOT NULL,
  `degree_course` VARCHAR(255),
  `period_from` DATE,
  `period_to` DATE,
  `highest_level_units_earned` VARCHAR(100),
  `year_graduated` SMALLINT,
  `scholarship_academic_honors` TEXT,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 2: Civil Service Eligibility
CREATE TABLE IF NOT EXISTS `pds_civil_service_eligibility` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `career_service` ENUM('Professional', 'Sub-Professional', 'Bar', 'Board', 'Others') NOT NULL,
  `rating` VARCHAR(20),
  `date_of_examination_conferment` DATE,
  `place_of_examination_conferment` VARCHAR(255),
  `license_number` VARCHAR(50),
  `date_of_release` DATE,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 3: Work Experience
CREATE TABLE IF NOT EXISTS `pds_work_experience` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `inclusive_dates_from` DATE NOT NULL,
  `inclusive_dates_to` DATE, -- NULL if present
  `position_title` VARCHAR(255) NOT NULL,
  `department_agency_office` VARCHAR(255) NOT NULL,
  `monthly_salary` DECIMAL(10,2),
  `salary_grade` VARCHAR(10),
  `step_increment` VARCHAR(10),
  `status_of_appointment` ENUM('Permanent', 'Temporary', 'Coterminous', 'Casual', 'Substitute', 'Others') NOT NULL,
  `government_service` ENUM('Yes', 'No') NOT NULL,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 3: Voluntary Work
CREATE TABLE IF NOT EXISTS `pds_voluntary_work` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `name_organization_address` VARCHAR(255) NOT NULL,
  `inclusive_dates_from` DATE NOT NULL,
  `inclusive_dates_to` DATE NOT NULL,
  `number_of_hours` INT,
  `position_nature_of_work` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 3: Learning and Development (Training Programs)
CREATE TABLE IF NOT EXISTS `pds_training_programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `title_of_learning_development_programs` VARCHAR(255) NOT NULL,
  `inclusive_dates_from` DATE NOT NULL,
  `inclusive_dates_to` DATE NOT NULL,
  `number_of_hours` INT,
  `type_of_ld` ENUM('Managerial', 'Supervisory', 'Technical', 'Others') NOT NULL,
  `sponsored_by` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 3: Other Information
CREATE TABLE IF NOT EXISTS `pds_other_information` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `special_skills_hobbies` TEXT,
  `non_academic_distinctions_recognitions` TEXT,
  `membership_association_organizations` TEXT,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 4: Questions (34-40)
CREATE TABLE IF NOT EXISTS `pds_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `q34_related_by_blood_marriage` ENUM('Yes', 'No'),
  `q34_relationship_details` TEXT,
  `q35_guilty_administrative_offense` ENUM('Yes', 'No'),
  `q35_offense_details` TEXT,
  `q36_criminally_charged` ENUM('Yes', 'No'),
  `q36_case_details` TEXT,
  `q37_convicted_final_judgment` ENUM('Yes', 'No'),
  `q37_case_details` TEXT,
  `q38_separated_service` ENUM('Yes', 'No'),
  `q38_reason_details` TEXT,
  `q39_immigrant_status` ENUM('Yes', 'No'),
  `q39_country_details` TEXT,
  `q40_indigenous_member` ENUM('Yes', 'No'),
  `q40_group_details` TEXT,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 4: References
CREATE TABLE IF NOT EXISTS `pds_references` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `reference_number` TINYINT NOT NULL, -- 1, 2, or 3
  `name` VARCHAR(255) NOT NULL,
  `address` TEXT NOT NULL,
  `telephone` VARCHAR(20),
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 4: Government Issued ID
CREATE TABLE IF NOT EXISTS `pds_government_id` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `id_type` VARCHAR(50) NOT NULL, -- Passport, Driver's License, etc.
  `id_number` VARCHAR(50) NOT NULL,
  `date_issued` DATE,
  `issuing_authority` VARCHAR(255),
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page 4: Signature and Thumbmark
CREATE TABLE IF NOT EXISTS `pds_signature` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pds_id` INT NOT NULL,
  `applicant_signature` TEXT, -- Base64 encoded signature image
  `thumbmark` TEXT, -- Base64 encoded thumbmark image
  `date_signed` DATE,
  FOREIGN KEY (`pds_id`) REFERENCES pds_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_pds_employee_year ON pds_records(employee_id, year);
CREATE INDEX idx_pds_status ON pds_records(status);
CREATE INDEX idx_pds_children_pds_id ON pds_children(pds_id);
CREATE INDEX idx_pds_education_pds_id ON pds_education(pds_id);
CREATE INDEX idx_pds_work_experience_pds_id ON pds_work_experience(pds_id);
CREATE INDEX idx_pds_training_programs_pds_id ON pds_training_programs(pds_id);
