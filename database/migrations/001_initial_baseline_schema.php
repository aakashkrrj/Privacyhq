<?php
// governance/database/migrations/001_initial_baseline_schema.php
// Initial Baseline Database Schema Migration for PrivacyHQ Governance
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Initial Baseline Database Migration (001_initial_baseline_schema.php)...\n";

// Disable foreign key checks for clean baseline initialization
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");

// Table: roles (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`);
");

// Table: permissions (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_name` (`permission_name`);
");

// Table: role_permissions (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;
");

// Table: users (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
");

// Table: settings (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: data_subjects (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `data_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identifier_hash` varchar(255) NOT NULL COMMENT 'Hashed email, phone, or UUID for privacy',
  `type` enum('customer','employee','vendor_contact','citizen') NOT NULL,
  `status` enum('active','erased','frozen') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier_hash` (`identifier_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: privacy_policies (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `privacy_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `policy_code` varchar(50) DEFAULT NULL,
  `policy_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'Data Privacy',
  `description` text DEFAULT NULL,
  `policy_owner` varchar(100) DEFAULT 'DPO / Compliance Team',
  `department` varchar(100) DEFAULT 'Legal & Compliance',
  `version` varchar(50) NOT NULL,
  `effective_date` date NOT NULL,
  `review_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `approval_status` varchar(50) DEFAULT 'draft',
  `document_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT 0,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_policy_version` (`policy_name`,`version`),
  KEY `idx_pp_status_del` (`status`,`deleted_at`),
  KEY `idx_pp_category` (`category`),
  KEY `idx_pp_dept` (`department`),
  KEY `idx_pp_owner` (`policy_owner`),
  KEY `idx_pp_code` (`policy_code`),
  KEY `idx_pp_appr` (`approval_status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: assessment_types (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: assessment_statuses (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_name` varchar(100) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`);
");

// Table: assessment_templates (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_type_id` bigint(20) unsigned NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `version_number` varchar(50) NOT NULL,
  `is_current_version` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `status_id` bigint(20) unsigned NOT NULL,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_version` (`template_name`,`version_number`);
");

// Table: assessment_sections (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`template_id`) REFERENCES `assessment_templates` (`id`) ON DELETE CASCADE;
");

// Table: assessment_questions (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` bigint(20) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(50) NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `help_text` text DEFAULT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `options_json` longtext DEFAULT NULL,
  `validation_rules_json` longtext DEFAULT NULL,
  `weight_yes` int(11) DEFAULT 0,
  `weight_no` int(11) DEFAULT 0,
  `score_options_json` text DEFAULT NULL,
  `risk_category_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`section_id`) REFERENCES `assessment_sections` (`id`) ON DELETE CASCADE;
");

// Table: assessment_responses (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `response_text` text DEFAULT NULL,
  `response_json` longtext DEFAULT NULL,
  `answered_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_assessment_question` (`assessment_id`,`question_id`),
  FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`);
");

// Table: assessment_risks (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_risks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `risk_category_id` bigint(20) unsigned NOT NULL,
  `description` text NOT NULL,
  `inherent_risk_matrix_id` bigint(20) unsigned DEFAULT NULL,
  `residual_risk_matrix_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('open','mitigated','accepted') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE;
");

// Table: privacy_assessments (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `privacy_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `processing_activity_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned NOT NULL,
  `status_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `reviewer_id` bigint(20) unsigned DEFAULT NULL,
  `priority enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`template_id`) REFERENCES `assessment_templates` (`id`),
  FOREIGN KEY (`status_id`) REFERENCES `assessment_statuses` (`id`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
");

// Table: consent_purposes (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `consent_purposes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purpose_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_essential` tinyint(1) DEFAULT 0,
  `retention_days` int(10) unsigned DEFAULT NULL COMMENT 'NULL implies indefinite',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purpose_name` (`purpose_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: consents (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data_subject_id` bigint(20) unsigned NOT NULL,
  `consent_purpose_id` bigint(20) unsigned NOT NULL,
  `policy_id` bigint(20) unsigned NOT NULL,
  `status` enum('opt_in','opt_out','withdrawn','expired') NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT current_timestamp(),
  `collection_method` varchar(100) DEFAULT NULL COMMENT 'e.g., web_portal, mobile_app',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subject_purpose` (`data_subject_id`,`consent_purpose_id`),
  KEY `consent_purpose_id` (`consent_purpose_id`),
  KEY `idx_consents_data_subject` (`data_subject_id`),
  KEY `idx_consents_status` (`status`),
  KEY `idx_consents_policy` (`policy_id`),
  CONSTRAINT `consents_ibfk_1` FOREIGN KEY (`data_subject_id`) REFERENCES `data_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consents_ibfk_2` FOREIGN KEY (`consent_purpose_id`) REFERENCES `consent_purposes` (`id`),
  CONSTRAINT `consents_ibfk_3` FOREIGN KEY (`policy_id`) REFERENCES `privacy_policies` (`id`);
");

// Table: consent_history (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `consent_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `consent_id` bigint(20) unsigned NOT NULL,
  `previous_status` enum('opt_in','opt_out','withdrawn','expired') DEFAULT NULL,
  `new_status` enum('opt_in','opt_out','withdrawn','expired') NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to users if manually changed by admin, NULL if self-serve',
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_consent_history_consent_id` (`consent_id`),
  CONSTRAINT `consent_history_ibfk_1` FOREIGN KEY (`consent_id`) REFERENCES `consents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consent_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: data_requests (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `data_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id_code` varchar(50) NOT NULL,
  `data_subject_id` bigint(20) unsigned NOT NULL,
  `request_type` enum('access','erasure','rectification','portability','objection') NOT NULL,
  `status` enum('open','verifying','processing','completed','rejected','expired') DEFAULT 'open',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `assigned_to` bigint(20) unsigned DEFAULT NULL COMMENT 'Privacy Officer handling the request',
  `due_date` date NOT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `verification_status` enum('pending','verified','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_data_requests_req_id` (`request_id_code`),
  KEY `data_subject_id` (`data_subject_id`),
  KEY `idx_data_requests_status` (`status`),
  KEY `idx_data_requests_assigned_to` (`assigned_to`),
  CONSTRAINT `data_requests_ibfk_1` FOREIGN KEY (`data_subject_id`) REFERENCES `data_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `data_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;
");

// Table: request_history (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `request_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data_request_id` bigint(20) unsigned NOT NULL,
  `changed_by` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `comments` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_request_history_request_id` (`data_request_id`),
  KEY `fk_req_history_assigned` (`assigned_to`),
  CONSTRAINT `fk_req_history_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `request_history_ibfk_1` FOREIGN KEY (`data_request_id`) REFERENCES `data_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `request_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: cookie_domains (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `cookie_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_name` varchar(255) NOT NULL UNIQUE,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`);
");

// Table: cookie_banner_configs (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `cookie_banner_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL UNIQUE,
  `banner_title` varchar(255) DEFAULT 'Cookie Consent Preferences',
  `banner_text` text,
  `language` varchar(50) DEFAULT 'en',
  `categories_presented` varchar(255) DEFAULT 'Essential,Functional,Performance,Analytics,Advertising',
  `accept_all_text` varchar(100) DEFAULT 'Accept All',
  `reject_all_text` varchar(100) DEFAULT 'Reject Non-Essential',
  `preferences_text` varchar(100) DEFAULT 'Manage Preferences',
  `branding_color` varchar(50) DEFAULT '#005faa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`domain_id`) REFERENCES `cookie_domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-29 15:44:02


-- Seed Cookie Governance permissions
INSERT INTO permissions (permission_name, module, description) VALUES 
('view_cookie_governance', 'Cookie Governance', 'View cookie governance dashboard'),
('manage_cookie_sources', 'Cookie Governance', 'Manage cookie websites and domains'),
('run_cookie_scans', 'Cookie Governance', 'Execute cookie scans on registered domains'),
('classify_cookies', 'Cookie Governance', 'Adjust category classification for cookies'),
('manage_cookie_banner', 'Cookie Governance', 'Configure cookie preference banner'),
('view_cookie_reports', 'Cookie Governance', 'Export and view cookie reports')
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name);

-- Grant to Super Admin (1), DPO (2), Assessor (3)
INSERT INTO role_permissions (role_id, permission_id) 
SELECT 1, id FROM permissions WHERE permission_name IN ('view_cookie_governance','manage_cookie_sources','run_cookie_scans','classify_cookies','manage_cookie_banner','view_cookie_reports')
ON DUPLICATE KEY UPDATE role_id=role_id;
INSERT INTO role_permissions (role_id, permission_id) 
SELECT 2, id FROM permissions WHERE permission_name IN ('view_cookie_governance','manage_cookie_sources','run_cookie_scans','classify_cookies','manage_cookie_banner','view_cookie_reports')
ON DUPLICATE KEY UPDATE role_id=role_id;
INSERT INTO role_permissions (role_id, permission_id) 
SELECT 3, id FROM permissions WHERE permission_name IN ('view_cookie_governance','manage_cookie_sources','run_cookie_scans','classify_cookies','manage_cookie_banner','view_cookie_reports';
");

// Table: activity_timeline (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `activity_timeline` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `record_id` bigint(20) unsigned NOT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
    FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
");

// Table: audit_logs (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(100) NOT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_logs_user` (`user_id`),
  KEY `idx_audit_logs_module_record` (`module`,`record_id`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  KEY `idx_al_module_action` (`module`,`action`),
  KEY `idx_al_date` (`created_at`),
  KEY `idx_al_user` (`user_id`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: notification_preferences (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `notification_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `in_app_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `privacy_incident_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `consent_updates` tinyint(1) NOT NULL DEFAULT 1,
  `assessment_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `risk_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `system_announcements` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Table: notifications (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` bigint(20) unsigned NOT NULL,
  `category` enum('Assignment','Reminder','Approval','Escalation','Comment','Mention','Deadline') NOT NULL DEFAULT 'Assignment',
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
");

// Table: sessions (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_sessions_user` (`user_id`),
  KEY `idx_sessions_expires_at` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: risk_categories (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `risk_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: risk_matrix (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `risk_matrix` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `impact_level` int(10) unsigned NOT NULL COMMENT 'e.g., 1 to 5',
  `likelihood_level` int(10) unsigned NOT NULL COMMENT 'e.g., 1 to 5',
  `impact_name` varchar(50) NOT NULL COMMENT 'e.g., Low, Medium, High',
  `likelihood_name` varchar(50) NOT NULL COMMENT 'e.g., Unlikely, Possible, Certain',
  `risk_score` int(10) unsigned NOT NULL COMMENT 'impact * likelihood',
  `risk_level_name` varchar(50) NOT NULL COMMENT 'e.g., Low, Medium, High, Critical',
  `color_code` varchar(20) DEFAULT NULL COMMENT 'Hex code for UI heatmap',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_impact_likelihood` (`impact_level`,`likelihood_level`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: risk_register (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `risk_register` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `likelihood` varchar(50) DEFAULT 'Medium',
  `impact` varchar(50) DEFAULT 'Medium',
  `mitigation` text DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`);
");

// Table: mitigation_library (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `mitigation_library` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `control_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `control_type` enum('technical','administrative','physical') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `control_name` (`control_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: incidents (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `incidents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `summary` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `impacted_records` int(11) NOT NULL DEFAULT 0,
  `status` enum('Open','Investigating','Resolved') NOT NULL DEFAULT 'Open',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `containment_actions` longtext DEFAULT NULL,
  `remediation_notes` longtext DEFAULT NULL,
  `is_escalated` tinyint(1) NOT NULL DEFAULT 0,
  `dpo_notified` tinyint(1) NOT NULL DEFAULT 0,
  `regulatory_status` varchar(100) NOT NULL DEFAULT 'Not Required',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_created_at` (`created_at`);
");

// Table: processing_activities (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `processing_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(255) NOT NULL,
  `purpose` text NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `data_controller` varchar(255) DEFAULT NULL,
  `data_categories` text DEFAULT NULL,
  `data_subjects` text DEFAULT NULL,
  `recipients` text DEFAULT NULL,
  `retention_period` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`);
");

// Table: vendors (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `service_type` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vendors_deleted_at` (`deleted_at`);
");

// Table: vendor_assessments (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `vendor_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `risk_score` int(11) NOT NULL DEFAULT 0,
  `status` enum('Compliant','Under Audit','Critical Review') DEFAULT 'Under Audit',
  `last_assessment_date` date DEFAULT NULL,
  `next_assessment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vendor_assessments_vendor_id` (`vendor_id`),
  CONSTRAINT `fk_vendor_assessments_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;
");

// Table: tasks (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `record_id` bigint(20) unsigned NOT NULL,
  `task_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `status` enum('Pending','In Progress','Completed','Escalated','Cancelled') NOT NULL DEFAULT 'Pending',
  `parent_task_id` bigint(20) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
");

// Table: report_exports (Source: Local Database (Authoritative SHOW CREATE TABLE))
$conn->query("
CREATE TABLE IF NOT EXISTS `report_exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_report_exports_user` (`generated_by`),
  CONSTRAINT `fk_report_exports_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table: assessment_documents (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `document_type` enum('evidence','approval_signoff','external_report') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE;
");

// Table: assessment_notes (Source: db_schema.sql (Base Schema))
$conn->query("
CREATE TABLE IF NOT EXISTS `assessment_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint(20) unsigned NOT NULL,
  `note_text` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE;
");

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "Baseline database migration completed successfully.\n";
