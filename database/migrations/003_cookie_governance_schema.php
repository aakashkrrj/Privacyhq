<?php
// governance/database/migrations/003_cookie_governance_schema.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Cookie Governance Database Migration...\n";

// 1. Create cookie_categories table
$conn->query("
    CREATE TABLE IF NOT EXISTS cookie_categories (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        description text DEFAULT NULL,
        is_necessary tinyint(1) NOT NULL DEFAULT 0,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_cat_name (name),
        UNIQUE KEY uk_cat_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 2. Create cookies table
$conn->query("
    CREATE TABLE IF NOT EXISTS cookies (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        domain varchar(255) NOT NULL,
        category_id bigint(20) unsigned DEFAULT NULL,
        provider varchar(255) DEFAULT NULL,
        party_type enum('first_party', 'third_party') NOT NULL DEFAULT 'first_party',
        risk_level enum('low', 'medium', 'high') NOT NULL DEFAULT 'low',
        purpose text DEFAULT NULL,
        retention varchar(100) DEFAULT 'Session',
        status enum('active', 'awaiting_review', 'blocked') NOT NULL DEFAULT 'awaiting_review',
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_cookie_name_domain (name, domain),
        KEY idx_cookies_category (category_id),
        KEY idx_cookies_party (party_type),
        KEY idx_cookies_status (status),
        KEY idx_cookies_risk (risk_level),
        CONSTRAINT fk_cookies_category FOREIGN KEY (category_id) REFERENCES cookie_categories (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 3. Create cookie_scans table
$conn->query("
    CREATE TABLE IF NOT EXISTS cookie_scans (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        domain varchar(255) NOT NULL,
        status enum('idle', 'scanning', 'paused', 'completed', 'cancelled') NOT NULL DEFAULT 'idle',
        progress_percentage int(11) NOT NULL DEFAULT 0,
        pages_scanned int(11) NOT NULL DEFAULT 0,
        cookies_found int(11) NOT NULL DEFAULT 0,
        time_taken_seconds int(11) NOT NULL DEFAULT 0,
        last_scan_at timestamp NULL DEFAULT NULL,
        next_scan_at timestamp NULL DEFAULT NULL,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_scans_domain (domain)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 4. Create cookie_banner_configs table
$conn->query("
    CREATE TABLE IF NOT EXISTS cookie_banner_configs (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        domain varchar(255) NOT NULL DEFAULT 'privacyhq.com',
        banner_title varchar(255) NOT NULL DEFAULT 'We value your privacy',
        banner_text text NOT NULL,
        position enum('bottom', 'top', 'floating') NOT NULL DEFAULT 'bottom',
        theme enum('light', 'dark', 'custom') NOT NULL DEFAULT 'light',
        primary_color varchar(20) NOT NULL DEFAULT '#4F46E5',
        background_color varchar(20) NOT NULL DEFAULT '#FFFFFF',
        text_color varchar(20) NOT NULL DEFAULT '#1F2937',
        privacy_policy_url varchar(255) DEFAULT '/privacy-policy.php',
        cookie_policy_url varchar(255) DEFAULT '/cookie-policy.php',
        is_active tinyint(1) NOT NULL DEFAULT 1,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_banner_domain (domain)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 5. Create cookie_consent_logs table
$conn->query("
    CREATE TABLE IF NOT EXISTS cookie_consent_logs (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent varchar(255) DEFAULT NULL,
        consent_choice enum('accept_all', 'reject_all', 'custom') NOT NULL DEFAULT 'accept_all',
        categories_accepted text DEFAULT NULL,
        consent_version varchar(20) NOT NULL DEFAULT 'v1.0',
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_consent_user (user_id),
        KEY idx_consent_choice (consent_choice),
        KEY idx_consent_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Populate Seed Categories
$conn->query("
    INSERT IGNORE INTO cookie_categories (id, name, slug, description, is_necessary) VALUES
    (1, 'Necessary', 'necessary', 'Essential cookies required for basic website operation, security, and authentication.', 1),
    (2, 'Analytics', 'analytics', 'Cookies that help understand how visitors interact with the site by gathering anonymous statistics.', 0),
    (3, 'Marketing', 'marketing', 'Cookies used to track visitors across websites to deliver relevant advertisements.', 0),
    (4, 'Preferences', 'preferences', 'Cookies that enable the website to remember choices such as language or region.', 0),
    (5, 'Unclassified', 'unclassified', 'Cookies that are currently undergoing classification and risk assessment.', 0)
");

// Populate Seed Banner Config
$conn->query("
    INSERT IGNORE INTO cookie_banner_configs (id, domain, banner_title, banner_text, position, theme, primary_color, background_color, text_color, privacy_policy_url, cookie_policy_url, is_active) VALUES
    (1, 'privacyhq.com', 'We Value Your Privacy', 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our web traffic. By clicking Accept All, you consent to our use of cookies.', 'bottom', 'light', '#4F46E5', '#FFFFFF', '#1F2937', '/privacy-policy.php', '/cookie-policy.php', 1)
");

// Populate Initial Seed Scan
$conn->query("
    INSERT IGNORE INTO cookie_scans (id, domain, status, progress_percentage, pages_scanned, cookies_found, time_taken_seconds, last_scan_at, next_scan_at) VALUES
    (1, 'privacyhq.com', 'completed', 100, 48, 12, 14, NOW() - INTERVAL 2 HOUR, NOW() + INTERVAL 7 DAY)
");

// Populate Initial Seed Cookies
$conn->query("
    INSERT IGNORE INTO cookies (id, name, domain, category_id, provider, party_type, risk_level, purpose, retention, status) VALUES
    (1, 'PHPSESSID', 'privacyhq.com', 1, 'PrivacyHQ Core', 'first_party', 'low', 'Preserves user session state across page requests.', 'Session', 'active'),
    (2, 'csrf_token', 'privacyhq.com', 1, 'PrivacyHQ Core', 'first_party', 'low', 'Prevents Cross-Site Request Forgery attacks.', 'Session', 'active'),
    (3, '_ga', 'privacyhq.com', 2, 'Google Analytics', 'first_party', 'low', 'Calculates visitor, session, and campaign data for site analytics reports.', '2 Years', 'active'),
    (4, '_gid', 'privacyhq.com', 2, 'Google Analytics', 'first_party', 'low', 'Stores information on how visitors use a website while creating analytics report.', '24 Hours', 'active'),
    (5, '_fbp', 'privacyhq.com', 3, 'Meta Platforms', 'third_party', 'medium', 'Used by Facebook to deliver behavioral advertisement products.', '90 Days', 'active'),
    (6, 'lang_pref', 'privacyhq.com', 4, 'PrivacyHQ Core', 'first_party', 'low', 'Remembers user interface language preference.', '1 Year', 'active'),
    (7, 'test_tracker_raw', 'privacyhq.com', 5, 'Unknown Partner', 'third_party', 'high', 'Unclassified tracking script detected during last automated scan.', '30 Days', 'awaiting_review')
");

// Populate Seed Consent Logs
$conn->query("
    INSERT IGNORE INTO cookie_consent_logs (user_id, ip_address, user_agent, consent_choice, categories_accepted, consent_version, created_at) VALUES
    (1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'accept_all', '[\"necessary\",\"analytics\",\"marketing\",\"preferences\"]', 'v1.0', NOW() - INTERVAL 1 DAY),
    (1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'custom', '[\"necessary\",\"analytics\"]', 'v1.0', NOW() - INTERVAL 3 HOUR)
");

echo "Cookie Governance Database Migration completed successfully!\n";
