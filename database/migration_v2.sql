-- -----------------------------------------------------
-- PrivacyHQ Database Migration v2
-- -----------------------------------------------------
-- This migration upgrades the existing `privacyhq` schema
-- by merging new system tables and extending existing tables
-- without dropping or recreating any existing data.
-- -----------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. ALTER EXISTING TABLES (Merging useful fields)
-- -----------------------------------------------------

-- 1a. users table
ALTER TABLE `users`
    ADD COLUMN `phone` VARCHAR(50) NULL AFTER `email`,
    ADD COLUMN `profile_image` VARCHAR(255) NULL AFTER `last_name`;

-- 1b. consents table
-- Note: 'collection_method' exists, adding 'source' as requested for merging, 
-- and 'granted_at' to explicitly track the grant time separately from created_at.
ALTER TABLE `consents`
    ADD COLUMN `source` VARCHAR(100) NULL AFTER `status`,
    ADD COLUMN `granted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `source`;

-- 1c. data_requests table
ALTER TABLE `data_requests`
    ADD COLUMN `request_id_code` VARCHAR(50) NULL UNIQUE AFTER `id`,
    ADD COLUMN `priority` ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium' AFTER `status`,
    ADD COLUMN `progress_percentage` INT DEFAULT 0 AFTER `due_date`;

-- 1d. request_history table
ALTER TABLE `request_history`
    ADD COLUMN `assigned_to` BIGINT(20) UNSIGNED NULL AFTER `changed_by`,
    ADD CONSTRAINT `fk_req_history_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL;


-- -----------------------------------------------------
-- 2. CREATE NEW SYSTEM TABLES
-- -----------------------------------------------------
-- Using BIGINT(20) UNSIGNED for FK compatibility with existing tables

-- 2a. sessions
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2b. audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `module` VARCHAR(100) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `record_id` BIGINT(20) UNSIGNED NOT NULL,
    `old_value` JSON NULL,
    `new_value` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_audit_logs_module_record` ON `audit_logs`(`module`, `record_id`);
CREATE INDEX `idx_audit_logs_created_at` ON `audit_logs`(`created_at`);

-- 2c. settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2d. notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_notifications_user_read` ON `notifications`(`user_id`, `is_read`);

-- 2e. report_exports
CREATE TABLE IF NOT EXISTS `report_exports` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `generated_by` BIGINT(20) UNSIGNED NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_report_exports_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2f. vendors
CREATE TABLE IF NOT EXISTS `vendors` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `service_type` VARCHAR(150) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_vendors_deleted_at` ON `vendors`(`deleted_at`);

-- 2g. vendor_assessments
CREATE TABLE IF NOT EXISTS `vendor_assessments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `risk_score` INT NOT NULL DEFAULT 0,
    `status` ENUM('Compliant', 'Under Audit', 'Critical Review') DEFAULT 'Under Audit',
    `last_assessment_date` DATE NULL,
    `next_assessment_date` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vendor_assessments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_vendor_assessments_vendor_id` ON `vendor_assessments`(`vendor_id`);

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- MIGRATION COMPLETE
-- -----------------------------------------------------
