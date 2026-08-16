<?php
// governance/database/migrations/013_ropa_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing ROPA Enhancements Migration...\n";

/** @var mysqli $conn */

// 1. Ensure processing_activities columns exist
$columns = [
    'ropa_code' => "VARCHAR(50) DEFAULT NULL AFTER id",
    'business_owner' => "VARCHAR(100) DEFAULT 'Data Owner' AFTER data_controller",
    'controller_role' => "VARCHAR(50) DEFAULT 'Controller' AFTER business_owner",
    'processing_operations' => "TEXT DEFAULT NULL AFTER data_subjects",
    'data_source' => "VARCHAR(255) DEFAULT 'Direct Input' AFTER processing_operations",
    'third_parties' => "TEXT DEFAULT NULL AFTER recipients",
    'international_transfers' => "VARCHAR(50) DEFAULT 'No' AFTER third_parties",
    'transfer_safeguards' => "TEXT DEFAULT NULL AFTER international_transfers",
    'retention_basis' => "VARCHAR(255) DEFAULT 'Legal Obligation' AFTER retention_period",
    'disposal_mechanism' => "VARCHAR(255) DEFAULT 'Secure Erasure' AFTER retention_basis",
    'technical_measures' => "TEXT DEFAULT NULL AFTER safeguards",
    'organizational_measures' => "TEXT DEFAULT NULL AFTER technical_measures",
    'review_date' => "DATE DEFAULT NULL AFTER status",
    'created_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER review_date",
    'updated_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER created_by"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM processing_activities LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE processing_activities ADD COLUMN $col $definition");
        echo "Added column '$col' to processing_activities table.\n";
    }
}

// 2. Ensure ropa_history table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS ropa_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ropa_id BIGINT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        performed_by BIGINT UNSIGNED DEFAULT NULL,
        old_status VARCHAR(50) DEFAULT NULL,
        new_status VARCHAR(50) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ropa_hist (ropa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table ropa_history verified.\n";

// 3. Update ropa_code where NULL
$conn->query("UPDATE processing_activities SET ropa_code = CONCAT('ROPA-', LPAD(id, 4, '0')) WHERE ropa_code IS NULL OR ropa_code = ''");

// 4. Update review_date where NULL
$conn->query("UPDATE processing_activities SET review_date = DATE_ADD(created_at, INTERVAL 1 YEAR) WHERE review_date IS NULL");

// 5. Seed default ROPA records if empty
$checkEmpty = $conn->query("SELECT COUNT(*) AS total FROM processing_activities WHERE deleted_at IS NULL");
$row = $checkEmpty ? $checkEmpty->fetch_assoc() : null;
if (($row['total'] ?? 0) == 0) {
    $conn->query("
        INSERT INTO processing_activities 
            (ropa_code, activity_name, purpose, department, status, data_controller, business_owner, controller_role, data_categories, data_subjects, processing_operations, data_source, recipients, third_parties, international_transfers, transfer_safeguards, retention_period, retention_basis, disposal_mechanism, legal_basis, storage_location, safeguards, technical_measures, organizational_measures, review_date, created_by, created_at, updated_at)
        VALUES 
            ('ROPA-1001', 'Customer Account & Billing Management', 'Fulfill SaaS subscription services and process recurring invoices.', 'Finance & Billing', 'active', 'PrivacyHQ Inc', 'Head of Finance', 'Controller', 'Name, Email, Payment Info, IP Address', 'Customers, Subscribers', 'Collection, Storage, Invoice Generation', 'Direct User Entry', 'Stripe, Xero Accounting', 'Payment Processors', 'Yes', 'Standard Contractual Clauses (SCCs)', '7 Years', 'Tax & Financial Legal Obligation', 'Automated Database Purge', 'Contractual Necessity', 'AWS EU-Central-1', 'TLS 1.3, AES-256 Encryption', 'KMS Encryption Keys', 'SOC 2 Type II Certified Processors', DATE_ADD(CURDATE(), INTERVAL 180 DAY), 1, NOW(), NOW()),
            ('ROPA-1002', 'Employee Payroll & HR Compliance', 'Process monthly salary payments and mandatory tax filings.', 'Human Resources', 'active', 'PrivacyHQ Inc', 'HR Director', 'Controller', 'PII, SSN/Tax ID, Bank Account Details, Salary', 'Employees, Contractors', 'Payroll Calculation, Tax Deduction', 'Employee Onboarding Forms', 'ADP Payroll, State Tax Agencies', 'External Auditor', 'No', 'N/A', '10 Years', 'Labor & Tax Law Compliance', 'Secure Shredding & Purging', 'Legal Obligation', 'On-Premises Encrypted Vault', 'Role-Based Access Control (RBAC)', 'Hardware Security Modules (HSM)', 'Strict HR Data Access Policies', DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1, NOW(), NOW()),
            ('ROPA-1003', 'Marketing Newsletter & Event Analytics', 'Send product announcements and track webinar engagement.', 'Marketing', 'active', 'PrivacyHQ Inc', 'Growth Marketing Lead', 'Controller', 'Email Address, First Name, Campaign Clicks', 'Leads, Event Attendees', 'Newsletter Dispatch, Click Analytics', 'Website Registration Form', 'HubSpot, Mailchimp', 'Analytics Providers', 'Yes', 'EU-US Data Privacy Framework', '2 Years', 'Consent & Legitimate Interest', 'Opt-out Deletion Engine', 'Consent', 'US East Cloud Cluster', 'Double Opt-In, TLS Encryption', 'Pseudonymized Tracking IDs', 'Marketing Opt-In Compliance Training', DATE_ADD(CURDATE(), INTERVAL -15 DAY), 1, NOW(), NOW())
    ");
    echo "Seeded default ROPA processing activities.\n";
}

echo "ROPA Enhancements Migration completed successfully!\n";
