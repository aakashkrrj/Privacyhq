-- PrivacyHQ Governance Database Schema
-- Generated: 2023
-- Requires MySQL 8.0+

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. Identity & Access
-- -----------------------------------------------------

DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
);

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(50),
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id` INT,
    `profile_image` VARCHAR(255),
    `status` ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
);
CREATE INDEX `idx_users_role_id` ON `users`(`role_id`);
CREATE INDEX `idx_users_deleted_at` ON `users`(`deleted_at`);

CREATE TABLE `sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- 2. Consent Management
-- -----------------------------------------------------

DROP TABLE IF EXISTS `consent_history`;
DROP TABLE IF EXISTS `consents`;
DROP TABLE IF EXISTS `consent_purposes`;

CREATE TABLE `consent_purposes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `retention_period` INT DEFAULT 365, -- In Days
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL
);
CREATE INDEX `idx_consent_purposes_deleted_at` ON `consent_purposes`(`deleted_at`);

CREATE TABLE `consents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `purpose_id` INT NOT NULL,
    `status` ENUM('Active', 'Withdrawn', 'Expired') DEFAULT 'Active',
    `source` VARCHAR(100),
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`purpose_id`) REFERENCES `consent_purposes`(`id`) ON DELETE RESTRICT
);
CREATE INDEX `idx_consents_user_id` ON `consents`(`user_id`);

CREATE TABLE `consent_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consent_id` INT NOT NULL,
    `previous_status` ENUM('Active', 'Withdrawn', 'Expired'),
    `new_status` ENUM('Active', 'Withdrawn', 'Expired') NOT NULL,
    `changed_by` INT NULL,
    `remarks` TEXT,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consent_id`) REFERENCES `consents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- 3. Data Requests (DSAR)
-- -----------------------------------------------------

DROP TABLE IF EXISTS `request_history`;
DROP TABLE IF EXISTS `data_requests`;

CREATE TABLE `data_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id_code` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NULL, -- NULL if non-authenticated
    `user_email` VARCHAR(255) NOT NULL,
    `type` ENUM('Access', 'Deletion', 'Portability', 'Correction') NOT NULL,
    `status` ENUM('Pending', 'In Progress', 'Completed', 'Rejected') DEFAULT 'Pending',
    `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    `due_date` DATE NOT NULL,
    `progress_percentage` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
CREATE INDEX `idx_data_requests_status` ON `data_requests`(`status`);

CREATE TABLE `request_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT NOT NULL,
    `previous_status` VARCHAR(100),
    `new_status` VARCHAR(100) NOT NULL,
    `assigned_to` INT NULL,
    `changed_by` INT NOT NULL,
    `remarks` TEXT,
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `data_requests`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);

-- -----------------------------------------------------
-- 4. Privacy Assessments (DPIA)
-- -----------------------------------------------------

DROP TABLE IF EXISTS `assessment_answers`;
DROP TABLE IF EXISTS `assessments`;
DROP TABLE IF EXISTS `assessment_questions`;
DROP TABLE IF EXISTS `assessment_templates`;

CREATE TABLE `assessment_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL
);

CREATE TABLE `assessment_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`template_id`) REFERENCES `assessment_templates`(`id`) ON DELETE CASCADE
);

CREATE TABLE `assessments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `target_system` VARCHAR(255) NOT NULL,
    `risk_level` ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    `status` ENUM('Draft', 'Under Review', 'Approved', 'Rejected') DEFAULT 'Draft',
    `completion_percentage` INT DEFAULT 0,
    `assigned_to` INT NOT NULL,
    `due_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    FOREIGN KEY (`template_id`) REFERENCES `assessment_templates`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);
CREATE INDEX `idx_assessments_status` ON `assessments`(`status`);
CREATE INDEX `idx_assessments_deleted_at` ON `assessments`(`deleted_at`);

CREATE TABLE `assessment_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assessment_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `answer_text` TEXT,
    `answered_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `assessment_questions`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`answered_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
);

-- -----------------------------------------------------
-- 5. Vendor Risk Management
-- -----------------------------------------------------

DROP TABLE IF EXISTS `vendor_assessments`;
DROP TABLE IF EXISTS `vendors`;

CREATE TABLE `vendors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `service_type` VARCHAR(150),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL
);
CREATE INDEX `idx_vendors_deleted_at` ON `vendors`(`deleted_at`);

CREATE TABLE `vendor_assessments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` INT NOT NULL,
    `risk_score` INT NOT NULL DEFAULT 0,
    `status` ENUM('Compliant', 'Under Audit', 'Critical Review') DEFAULT 'Under Audit',
    `last_assessment_date` DATE,
    `next_assessment_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`vendor_id`) REFERENCES `vendors`(`id`) ON DELETE CASCADE
);
CREATE INDEX `idx_vendor_assessments_vendor_id` ON `vendor_assessments`(`vendor_id`);

-- -----------------------------------------------------
-- 6. System & Audit
-- -----------------------------------------------------

DROP TABLE IF EXISTS `report_exports`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL, -- NULL for system processes
    `module` VARCHAR(100) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `record_id` INT NOT NULL,
    `old_value` JSON NULL,
    `new_value` JSON NULL,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
CREATE INDEX `idx_audit_logs_module_record` ON `audit_logs`(`module`, `record_id`);
CREATE INDEX `idx_audit_logs_created_at` ON `audit_logs`(`created_at`);

CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
CREATE INDEX `idx_notifications_user_read` ON `notifications`(`user_id`, `is_read`);

CREATE TABLE `report_exports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `generated_by` INT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

SET FOREIGN_KEY_CHECKS = 1;
