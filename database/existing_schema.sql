-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jul 21, 2026 at 06:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `privacyhq`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessment_documents`
--

CREATE TABLE `assessment_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` enum('evidence','approval_signoff','external_report') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_notes`
--

CREATE TABLE `assessment_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `note_text` text NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_questions`
--

CREATE TABLE `assessment_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(50) NOT NULL COMMENT 'text, textarea, number, email, date, radio, checkbox, dropdown, file, yes_no',
  `is_required` tinyint(1) DEFAULT 0,
  `help_text` text DEFAULT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `display_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'For dropdown, radio, checkbox' CHECK (json_valid(`options_json`)),
  `validation_rules_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules_json`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_questions`
--

INSERT INTO `assessment_questions` (`id`, `section_id`, `question_text`, `question_type`, `is_required`, `help_text`, `placeholder`, `display_order`, `options_json`, `validation_rules_json`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'What data is collected?', 'textarea', 1, NULL, NULL, 10, NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 1, 'Is consent obtained?', 'yes_no', 1, NULL, NULL, 20, NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(3, 2, 'Where is data stored?', 'radio', 1, NULL, NULL, 10, '[\"AWS\", \"Azure\", \"On-Premise\"]', NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_responses`
--

CREATE TABLE `assessment_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `response_text` text DEFAULT NULL,
  `response_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_json`)),
  `answered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_responses`
--

INSERT INTO `assessment_responses` (`id`, `assessment_id`, `question_id`, `response_text`, `response_json`, `answered_by`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Employee names and salaries', NULL, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(2, 2, 2, 'Yes', NULL, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(3, 2, 3, 'AWS', NULL, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_risks`
--

CREATE TABLE `assessment_risks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `risk_category_id` bigint(20) UNSIGNED NOT NULL,
  `description` text NOT NULL,
  `inherent_risk_matrix_id` bigint(20) UNSIGNED DEFAULT NULL,
  `residual_risk_matrix_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('open','mitigated','accepted') DEFAULT 'open',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_risks`
--

INSERT INTO `assessment_risks` (`id`, `assessment_id`, `risk_category_id`, `description`, `inherent_risk_matrix_id`, `residual_risk_matrix_id`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'Risk of unencrypted data in transit', NULL, NULL, 'open', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 2, 2, 'Vendor might access sensitive data', NULL, NULL, 'mitigated', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_sections`
--

CREATE TABLE `assessment_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_sections`
--

INSERT INTO `assessment_sections` (`id`, `template_id`, `section_name`, `description`, `display_order`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Data Collection', NULL, 10, 1, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 1, 'Data Storage', NULL, 20, 1, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_statuses`
--

CREATE TABLE `assessment_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_statuses`
--

INSERT INTO `assessment_statuses` (`id`, `status_name`, `description`, `display_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Draft', NULL, 10, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'Under Review', NULL, 20, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(3, 'Approved', NULL, 30, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_status_history`
--

CREATE TABLE `assessment_status_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` bigint(20) UNSIGNED NOT NULL,
  `previous_status_id` bigint(20) UNSIGNED DEFAULT NULL,
  `new_status_id` bigint(20) UNSIGNED NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_templates`
--

CREATE TABLE `assessment_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_type_id` bigint(20) UNSIGNED NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `version_number` varchar(50) NOT NULL,
  `is_current_version` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `status_id` bigint(20) UNSIGNED NOT NULL,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_templates`
--

INSERT INTO `assessment_templates` (`id`, `assessment_type_id`, `template_name`, `version_number`, `is_current_version`, `description`, `status_id`, `effective_date`, `expiry_date`, `published_at`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Standard DPIA', '1.0', 1, NULL, 3, '2024-01-01', NULL, NULL, 1, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_types`
--

CREATE TABLE `assessment_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_types`
--

INSERT INTO `assessment_types` (`id`, `type_name`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'DPIA', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'Vendor Assessment', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `consents`
--

CREATE TABLE `consents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `data_subject_id` bigint(20) UNSIGNED NOT NULL,
  `consent_purpose_id` bigint(20) UNSIGNED NOT NULL,
  `policy_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('opt_in','opt_out','withdrawn','expired') NOT NULL,
  `collection_method` varchar(100) DEFAULT NULL COMMENT 'e.g., web_portal, mobile_app',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consents`
--

INSERT INTO `consents` (`id`, `data_subject_id`, `consent_purpose_id`, `policy_id`, `status`, `collection_method`, `ip_address`, `user_agent`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 'opt_in', NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(2, 2, 1, 2, 'withdrawn', NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(3, 3, 1, 2, 'opt_in', NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(4, 4, 1, 2, 'withdrawn', NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(5, 5, 1, 2, 'opt_in', NULL, NULL, NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `consent_history`
--

CREATE TABLE `consent_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consent_id` bigint(20) UNSIGNED NOT NULL,
  `previous_status` enum('opt_in','opt_out','withdrawn','expired') DEFAULT NULL,
  `new_status` enum('opt_in','opt_out','withdrawn','expired') NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'FK to users if manually changed by admin, NULL if self-serve',
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consent_purposes`
--

CREATE TABLE `consent_purposes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purpose_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_essential` tinyint(1) DEFAULT 0,
  `retention_days` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL implies indefinite',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consent_purposes`
--

INSERT INTO `consent_purposes` (`id`, `purpose_name`, `description`, `is_essential`, `retention_days`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Marketing', 'Email marketing and newsletters', 0, 365, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'Analytics', 'Website usage analytics', 0, 730, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(3, 'Core Services', 'Essential platform functionality', 1, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `data_requests`
--

CREATE TABLE `data_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `data_subject_id` bigint(20) UNSIGNED NOT NULL,
  `request_type` enum('access','erasure','rectification','portability','objection') NOT NULL,
  `status` enum('open','verifying','processing','completed','rejected','expired') DEFAULT 'open',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Privacy Officer handling the request',
  `due_date` date NOT NULL,
  `verification_status` enum('pending','verified','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `data_requests`
--

INSERT INTO `data_requests` (`id`, `data_subject_id`, `request_type`, `status`, `assigned_to`, `due_date`, `verification_status`, `created_at`, `updated_at`) VALUES
(1, 1, 'access', 'open', 1, '2026-08-01', 'pending', '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(2, 2, 'erasure', 'processing', 1, '2026-08-05', 'pending', '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(3, 3, 'portability', 'completed', 1, '2026-07-01', 'pending', '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `data_subjects`
--

CREATE TABLE `data_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `identifier_hash` varchar(255) NOT NULL COMMENT 'Hashed email, phone, or UUID for privacy',
  `type` enum('customer','employee','vendor_contact','citizen') NOT NULL,
  `status` enum('active','erased','frozen') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `data_subjects`
--

INSERT INTO `data_subjects` (`id`, `identifier_hash`, `type`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'adbed8cd7d3a54a66442392ff7dfa7c7', 'customer', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'f586ff72d2793bcce0c7d0e57fadb25b', 'customer', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(3, 'e74835ba1204ad9e042a1763c09dedb4', 'customer', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(4, '5028ae51296136951573b789c9840409', 'customer', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(5, 'a0291141772f5f2bf8ee1bddb082a6b8', 'customer', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(6, '02dbd151d07dd95568bbd4a12bfcefc1', 'employee', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(7, 'b562e59c7468255f7abccc48551174d2', 'employee', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(8, '9f12459017c185b0263f0a6cdf5efc70', 'employee', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(9, 'fd69f90d14eb63e95a3c18bb949e3396', 'employee', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(10, '5fce8ce23b9dc5d6edf0e39c7c8263c7', 'employee', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mitigation_library`
--

CREATE TABLE `mitigation_library` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `control_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `control_type` enum('technical','administrative','physical') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mitigation_library`
--

INSERT INTO `mitigation_library` (`id`, `control_name`, `description`, `control_type`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'AES-256 Encryption', NULL, 'technical', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'Annual Privacy Training', NULL, 'administrative', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `privacy_assessments`
--

CREATE TABLE `privacy_assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `processing_activity_id` bigint(20) UNSIGNED NOT NULL,
  `template_id` bigint(20) UNSIGNED NOT NULL,
  `status_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `updated_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `privacy_assessments`
--

INSERT INTO `privacy_assessments` (`id`, `processing_activity_id`, `template_id`, `status_id`, `title`, `assigned_to`, `due_date`, `completed_at`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 'CRM Upgrade DPIA', 1, NULL, NULL, 1, 1, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 2, 1, 3, 'Workday Integration DPIA', 1, NULL, '2026-07-19 07:59:16', 1, 1, '2026-07-17 03:20:46', '2026-07-19 07:59:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `privacy_policies`
--

CREATE TABLE `privacy_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `policy_name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL,
  `effective_date` date NOT NULL,
  `status` enum('draft','active','archived') DEFAULT 'draft',
  `document_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `privacy_policies`
--

INSERT INTO `privacy_policies` (`id`, `policy_name`, `version`, `effective_date`, `status`, `document_path`, `created_at`, `updated_at`) VALUES
(1, 'Global Privacy Policy', '1.0', '2023-01-01', 'archived', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(2, 'Global Privacy Policy', '1.1', '2024-01-01', 'active', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `processing_activities`
--

CREATE TABLE `processing_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `purpose` text NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `processing_activities`
--

INSERT INTO `processing_activities` (`id`, `activity_name`, `purpose`, `department`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Customer CRM', 'Manage customer relationships', 'Sales', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'HR Payroll System', 'Employee salary processing', 'HR', 'active', '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `request_history`
--

CREATE TABLE `request_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `data_request_id` bigint(20) UNSIGNED NOT NULL,
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `comments` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_categories`
--

CREATE TABLE `risk_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_categories`
--

INSERT INTO `risk_categories` (`id`, `category_name`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Data Breach', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(2, 'Unauthorized Access', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL),
(3, 'Compliance Failure', NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `risk_matrix`
--

CREATE TABLE `risk_matrix` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `impact_level` int(10) UNSIGNED NOT NULL COMMENT 'e.g., 1 to 5',
  `likelihood_level` int(10) UNSIGNED NOT NULL COMMENT 'e.g., 1 to 5',
  `impact_name` varchar(50) NOT NULL COMMENT 'e.g., Low, Medium, High',
  `likelihood_name` varchar(50) NOT NULL COMMENT 'e.g., Unlikely, Possible, Certain',
  `risk_score` int(10) UNSIGNED NOT NULL COMMENT 'impact * likelihood',
  `risk_level_name` varchar(50) NOT NULL COMMENT 'e.g., Low, Medium, High, Critical',
  `color_code` varchar(20) DEFAULT NULL COMMENT 'Hex code for UI heatmap',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_matrix`
--

INSERT INTO `risk_matrix` (`id`, `impact_level`, `likelihood_level`, `impact_name`, `likelihood_name`, `risk_score`, `risk_level_name`, `color_code`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 'High', 'Possible', 9, 'High', '#FF0000', '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(2, 1, 1, 'Low', 'Unlikely', 1, 'Low', '#00FF00', '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `risk_mitigations`
--

CREATE TABLE `risk_mitigations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `risk_id` bigint(20) UNSIGNED NOT NULL,
  `mitigation_library_id` bigint(20) UNSIGNED DEFAULT NULL,
  `implementation_details` text NOT NULL,
  `status` enum('planned','in_progress','implemented') DEFAULT 'planned',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_mitigations`
--

INSERT INTO `risk_mitigations` (`id`, `risk_id`, `mitigation_library_id`, `implementation_details`, `status`, `assigned_to`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Apply TLS 1.3 to all API endpoints', 'planned', NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46'),
(2, 2, 2, 'Vendor signed DPA and completed training', 'implemented', NULL, NULL, '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'Full access to all modules', '2026-07-17 03:20:46', '2026-07-17 03:20:46');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `email`, `password_hash`, `first_name`, `last_name`, `status`, `last_login_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'admin@privacyhq.com', '$2y$10$n9LPWU2qnzpaGcpgpgzVyezOUhkqEOWLzSLgLsw.3noqUZbFCSz8W', 'Super', 'Admin', 'active', '2026-07-17 03:53:34', '2026-07-17 03:20:46', '2026-07-19 07:59:16', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessment_documents`
--
ALTER TABLE `assessment_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_documents_assessment` (`assessment_id`);

--
-- Indexes for table `assessment_notes`
--
ALTER TABLE `assessment_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_notes_assessment` (`assessment_id`);

--
-- Indexes for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_questions_section_id` (`section_id`),
  ADD KEY `idx_questions_type` (`question_type`);

--
-- Indexes for table `assessment_responses`
--
ALTER TABLE `assessment_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_assessment_question` (`assessment_id`,`question_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `answered_by` (`answered_by`),
  ADD KEY `idx_responses_assessment` (`assessment_id`);

--
-- Indexes for table `assessment_risks`
--
ALTER TABLE `assessment_risks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `risk_category_id` (`risk_category_id`),
  ADD KEY `inherent_risk_matrix_id` (`inherent_risk_matrix_id`),
  ADD KEY `residual_risk_matrix_id` (`residual_risk_matrix_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_risks_assessment` (`assessment_id`);

--
-- Indexes for table `assessment_sections`
--
ALTER TABLE `assessment_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_template_section` (`template_id`,`section_name`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_sections_template_id` (`template_id`);

--
-- Indexes for table `assessment_statuses`
--
ALTER TABLE `assessment_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `assessment_status_history`
--
ALTER TABLE `assessment_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `previous_status_id` (`previous_status_id`),
  ADD KEY `new_status_id` (`new_status_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_status_history_assessment` (`assessment_id`);

--
-- Indexes for table `assessment_templates`
--
ALTER TABLE `assessment_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_template_version` (`template_name`,`version_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_templates_type` (`assessment_type_id`),
  ADD KEY `idx_templates_status` (`status_id`),
  ADD KEY `idx_templates_effective_date` (`effective_date`);

--
-- Indexes for table `assessment_types`
--
ALTER TABLE `assessment_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `consents`
--
ALTER TABLE `consents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_subject_purpose` (`data_subject_id`,`consent_purpose_id`),
  ADD KEY `consent_purpose_id` (`consent_purpose_id`),
  ADD KEY `idx_consents_data_subject` (`data_subject_id`),
  ADD KEY `idx_consents_status` (`status`),
  ADD KEY `idx_consents_policy` (`policy_id`);

--
-- Indexes for table `consent_history`
--
ALTER TABLE `consent_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_consent_history_consent_id` (`consent_id`);

--
-- Indexes for table `consent_purposes`
--
ALTER TABLE `consent_purposes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purpose_name` (`purpose_name`);

--
-- Indexes for table `data_requests`
--
ALTER TABLE `data_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `data_subject_id` (`data_subject_id`),
  ADD KEY `idx_data_requests_status` (`status`),
  ADD KEY `idx_data_requests_assigned_to` (`assigned_to`);

--
-- Indexes for table `data_subjects`
--
ALTER TABLE `data_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identifier_hash` (`identifier_hash`);

--
-- Indexes for table `mitigation_library`
--
ALTER TABLE `mitigation_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_name` (`control_name`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `privacy_assessments`
--
ALTER TABLE `privacy_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_assessments_activity` (`processing_activity_id`),
  ADD KEY `idx_assessments_template` (`template_id`),
  ADD KEY `idx_assessments_status` (`status_id`);

--
-- Indexes for table `privacy_policies`
--
ALTER TABLE `privacy_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_policy_version` (`policy_name`,`version`);

--
-- Indexes for table `processing_activities`
--
ALTER TABLE `processing_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_history`
--
ALTER TABLE `request_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_request_history_request_id` (`data_request_id`);

--
-- Indexes for table `risk_categories`
--
ALTER TABLE `risk_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `risk_matrix`
--
ALTER TABLE `risk_matrix`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_impact_likelihood` (`impact_level`,`likelihood_level`);

--
-- Indexes for table `risk_mitigations`
--
ALTER TABLE `risk_mitigations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mitigation_library_id` (`mitigation_library_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_mitigations_risk` (`risk_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessment_documents`
--
ALTER TABLE `assessment_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_notes`
--
ALTER TABLE `assessment_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assessment_responses`
--
ALTER TABLE `assessment_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assessment_risks`
--
ALTER TABLE `assessment_risks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assessment_sections`
--
ALTER TABLE `assessment_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assessment_statuses`
--
ALTER TABLE `assessment_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assessment_status_history`
--
ALTER TABLE `assessment_status_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_templates`
--
ALTER TABLE `assessment_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assessment_types`
--
ALTER TABLE `assessment_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `consents`
--
ALTER TABLE `consents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `consent_history`
--
ALTER TABLE `consent_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consent_purposes`
--
ALTER TABLE `consent_purposes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `data_requests`
--
ALTER TABLE `data_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `data_subjects`
--
ALTER TABLE `data_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `mitigation_library`
--
ALTER TABLE `mitigation_library`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `privacy_assessments`
--
ALTER TABLE `privacy_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `privacy_policies`
--
ALTER TABLE `privacy_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `processing_activities`
--
ALTER TABLE `processing_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `request_history`
--
ALTER TABLE `request_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_categories`
--
ALTER TABLE `risk_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `risk_matrix`
--
ALTER TABLE `risk_matrix`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `risk_mitigations`
--
ALTER TABLE `risk_mitigations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessment_documents`
--
ALTER TABLE `assessment_documents`
  ADD CONSTRAINT `assessment_documents_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_notes`
--
ALTER TABLE `assessment_notes`
  ADD CONSTRAINT `assessment_notes_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_questions`
--
ALTER TABLE `assessment_questions`
  ADD CONSTRAINT `assessment_questions_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `assessment_sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessment_questions_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_responses`
--
ALTER TABLE `assessment_responses`
  ADD CONSTRAINT `assessment_responses_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`),
  ADD CONSTRAINT `assessment_responses_ibfk_3` FOREIGN KEY (`answered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_risks`
--
ALTER TABLE `assessment_risks`
  ADD CONSTRAINT `assessment_risks_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_risks_ibfk_2` FOREIGN KEY (`risk_category_id`) REFERENCES `risk_categories` (`id`),
  ADD CONSTRAINT `assessment_risks_ibfk_3` FOREIGN KEY (`inherent_risk_matrix_id`) REFERENCES `risk_matrix` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessment_risks_ibfk_4` FOREIGN KEY (`residual_risk_matrix_id`) REFERENCES `risk_matrix` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessment_risks_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_sections`
--
ALTER TABLE `assessment_sections`
  ADD CONSTRAINT `assessment_sections_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `assessment_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_sections_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessment_sections_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_status_history`
--
ALTER TABLE `assessment_status_history`
  ADD CONSTRAINT `assessment_status_history_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `privacy_assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_status_history_ibfk_2` FOREIGN KEY (`previous_status_id`) REFERENCES `assessment_statuses` (`id`),
  ADD CONSTRAINT `assessment_status_history_ibfk_3` FOREIGN KEY (`new_status_id`) REFERENCES `assessment_statuses` (`id`),
  ADD CONSTRAINT `assessment_status_history_ibfk_4` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessment_templates`
--
ALTER TABLE `assessment_templates`
  ADD CONSTRAINT `assessment_templates_ibfk_1` FOREIGN KEY (`assessment_type_id`) REFERENCES `assessment_types` (`id`),
  ADD CONSTRAINT `assessment_templates_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `assessment_statuses` (`id`),
  ADD CONSTRAINT `assessment_templates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `assessment_templates_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `consents`
--
ALTER TABLE `consents`
  ADD CONSTRAINT `consents_ibfk_1` FOREIGN KEY (`data_subject_id`) REFERENCES `data_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consents_ibfk_2` FOREIGN KEY (`consent_purpose_id`) REFERENCES `consent_purposes` (`id`),
  ADD CONSTRAINT `consents_ibfk_3` FOREIGN KEY (`policy_id`) REFERENCES `privacy_policies` (`id`);

--
-- Constraints for table `consent_history`
--
ALTER TABLE `consent_history`
  ADD CONSTRAINT `consent_history_ibfk_1` FOREIGN KEY (`consent_id`) REFERENCES `consents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consent_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `data_requests`
--
ALTER TABLE `data_requests`
  ADD CONSTRAINT `data_requests_ibfk_1` FOREIGN KEY (`data_subject_id`) REFERENCES `data_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `data_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `privacy_assessments`
--
ALTER TABLE `privacy_assessments`
  ADD CONSTRAINT `privacy_assessments_ibfk_1` FOREIGN KEY (`processing_activity_id`) REFERENCES `processing_activities` (`id`),
  ADD CONSTRAINT `privacy_assessments_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `assessment_templates` (`id`),
  ADD CONSTRAINT `privacy_assessments_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `assessment_statuses` (`id`),
  ADD CONSTRAINT `privacy_assessments_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `privacy_assessments_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `privacy_assessments_ibfk_6` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_history`
--
ALTER TABLE `request_history`
  ADD CONSTRAINT `request_history_ibfk_1` FOREIGN KEY (`data_request_id`) REFERENCES `data_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `risk_mitigations`
--
ALTER TABLE `risk_mitigations`
  ADD CONSTRAINT `risk_mitigations_ibfk_1` FOREIGN KEY (`risk_id`) REFERENCES `assessment_risks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risk_mitigations_ibfk_2` FOREIGN KEY (`mitigation_library_id`) REFERENCES `mitigation_library` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `risk_mitigations_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
