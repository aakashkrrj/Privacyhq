-- -----------------------------------------------------
-- PrivacyHQ Database Migration v2 (Final)
-- -----------------------------------------------------
-- This migration upgrades the existing `privacyhq` schema
-- by merging new system tables and extending existing tables
-- without dropping or recreating any existing data.
-- -----------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. STORED PROCEDURE FOR SAFE COLUMN ADDITION
-- -----------------------------------------------------
-- Using a stored procedure ensures that the ALTER TABLE ADD COLUMN
-- commands can be executed safely multiple times without errors.

DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
DROP PROCEDURE IF EXISTS `add_fk_if_not_exists`;
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

DELIMITER //

CREATE PROCEDURE `add_column_if_not_exists` (
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN columnName VARCHAR(255),
    IN columnDefinition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = dbName
        AND TABLE_NAME = tableName 
        AND COLUMN_NAME = columnName
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

CREATE PROCEDURE `add_fk_if_not_exists` (
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN constraintName VARCHAR(255),
    IN fkDefinition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = dbName
        AND TABLE_NAME = tableName 
        AND CONSTRAINT_NAME = constraintName
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD CONSTRAINT `', constraintName, '` ', fkDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

CREATE PROCEDURE `add_index_if_not_exists` (
    IN dbName VARCHAR(255),
    IN tableName VARCHAR(255),
    IN indexName VARCHAR(255),
    IN indexColumns TEXT,
    IN isUnique BOOLEAN
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = dbName
        AND TABLE_NAME = tableName 
        AND INDEX_NAME = indexName
    ) THEN
        IF isUnique THEN
            SET @sql = CONCAT('CREATE UNIQUE INDEX `', indexName, '` ON `', tableName, '` (', indexColumns, ')');
        ELSE
            SET @sql = CONCAT('CREATE INDEX `', indexName, '` ON `', tableName, '` (', indexColumns, ')');
        END IF;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

-- -----------------------------------------------------
-- 2. ALTER EXISTING TABLES (Merging useful fields safely)
-- -----------------------------------------------------

-- 2a. users table
CALL add_column_if_not_exists(DATABASE(), 'users', 'phone', 'VARCHAR(50) NULL AFTER `email`');
CALL add_column_if_not_exists(DATABASE(), 'users', 'profile_image', 'VARCHAR(255) NULL AFTER `last_name`');

-- 2b. consents table
CALL add_column_if_not_exists(DATABASE(), 'consents', 'source', 'VARCHAR(100) NULL AFTER `status`');
CALL add_column_if_not_exists(DATABASE(), 'consents', 'granted_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `source`');

-- 2c. data_requests table
-- Add request_id_code allowing NULL first to avoid duplicate entry errors on populated tables
CALL add_column_if_not_exists(DATABASE(), 'data_requests', 'request_id_code', 'VARCHAR(50) NULL AFTER `id`');

-- Populate existing rows safely using a variable to generate REQ-XXXXXX formats
SET @row_number = 0;
UPDATE `data_requests` 
SET `request_id_code` = CONCAT('REQ-', LPAD(@row_number := @row_number + 1, 6, '0')) 
WHERE `request_id_code` IS NULL;

-- Alter column to enforce NOT NULL
ALTER TABLE `data_requests` MODIFY `request_id_code` VARCHAR(50) NOT NULL;

-- Add UNIQUE constraint safely
CALL add_index_if_not_exists(DATABASE(), 'data_requests', 'idx_data_requests_req_id', 'request_id_code', TRUE);

CALL add_column_if_not_exists(DATABASE(), 'data_requests', 'priority', 'ENUM(''Low'', ''Medium'', ''High'', ''Urgent'') DEFAULT ''Medium'' AFTER `status`');
CALL add_column_if_not_exists(DATABASE(), 'data_requests', 'progress_percentage', 'INT DEFAULT 0 AFTER `due_date`');

-- 2d. request_history table
CALL add_column_if_not_exists(DATABASE(), 'request_history', 'assigned_to', 'BIGINT(20) UNSIGNED NULL AFTER `changed_by`');
CALL add_fk_if_not_exists(DATABASE(), 'request_history', 'fk_req_history_assigned', 'FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL');


-- -----------------------------------------------------
-- 3. CREATE NEW SYSTEM TABLES
-- -----------------------------------------------------
-- To prevent duplicate index and FK creation, constraints and
-- indexes are defined directly inside the CREATE TABLE statements.

-- 3a. sessions
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `token` VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_sessions_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3b. audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) UNSIGNED NULL,
    `module` VARCHAR(100) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `record_id` BIGINT(20) UNSIGNED NULL,
    `old_value` JSON NULL,
    `new_value` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_audit_logs_module_record` (`module`, `record_id`),
    INDEX `idx_audit_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3c. settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3d. notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3e. report_exports
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

-- 3f. vendors
CREATE TABLE IF NOT EXISTS `vendors` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `service_type` VARCHAR(150) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    INDEX `idx_vendors_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3g. vendor_assessments
CREATE TABLE IF NOT EXISTS `vendor_assessments` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` BIGINT(20) UNSIGNED NOT NULL,
    `risk_score` INT NOT NULL DEFAULT 0,
    `status` ENUM('Compliant', 'Under Audit', 'Critical Review') DEFAULT 'Under Audit',
    `last_assessment_date` DATE NULL,
    `next_assessment_date` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vendor_assessments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
    INDEX `idx_vendor_assessments_vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. CLEANUP
-- -----------------------------------------------------
DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
DROP PROCEDURE IF EXISTS `add_fk_if_not_exists`;
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- MIGRATION COMPLETE
-- -----------------------------------------------------
