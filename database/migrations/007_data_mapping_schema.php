<?php
// governance/database/migrations/007_data_mapping_schema.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Data Mapping Schema Migration...\n";

/** @var mysqli $conn */

// 1. Check & Upgrade processing_activities Table
$conn->query("
    CREATE TABLE IF NOT EXISTS processing_activities (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        activity_name VARCHAR(255) NOT NULL,
        purpose TEXT DEFAULT NULL,
        department VARCHAR(100) DEFAULT 'Engineering',
        data_controller VARCHAR(255) DEFAULT 'PrivacyHQ Core',
        processor VARCHAR(255) DEFAULT NULL,
        data_categories TEXT DEFAULT NULL,
        data_subjects TEXT DEFAULT NULL,
        recipients TEXT DEFAULT NULL,
        legal_basis VARCHAR(100) DEFAULT 'Legitimate Interest',
        retention_period VARCHAR(100) DEFAULT '3 Years',
        storage_location VARCHAR(255) DEFAULT 'AWS US-East',
        safeguards TEXT DEFAULT NULL,
        risk_level VARCHAR(50) NOT NULL DEFAULT 'Medium',
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_by BIGINT UNSIGNED DEFAULT NULL,
        updated_by BIGINT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table processing_activities verified.\n";

// Add missing columns to processing_activities if table already existed without them
$columnsToAddPA = [
    'data_controller' => "VARCHAR(255) DEFAULT 'PrivacyHQ Core'",
    'processor' => "VARCHAR(255) DEFAULT NULL",
    'legal_basis' => "VARCHAR(100) DEFAULT 'Legitimate Interest'",
    'storage_location' => "VARCHAR(255) DEFAULT 'AWS US-East'",
    'safeguards' => "TEXT DEFAULT NULL",
    'risk_level' => "VARCHAR(50) NOT NULL DEFAULT 'Medium'"
];
foreach ($columnsToAddPA as $col => $definition) {
    $checkCol = $conn->query("SHOW COLUMNS FROM processing_activities LIKE '$col'");
    if ($checkCol && $checkCol->num_rows == 0) {
        $conn->query("ALTER TABLE processing_activities ADD COLUMN $col $definition");
        echo "Added column $col to processing_activities.\n";
    }
}

// 2. Check & Upgrade data_flows Table
$conn->query("
    CREATE TABLE IF NOT EXISTS data_flows (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        processing_activity_id BIGINT UNSIGNED DEFAULT NULL,
        flow_name VARCHAR(255) DEFAULT NULL,
        source_system VARCHAR(255) NOT NULL,
        target_system VARCHAR(255) NOT NULL,
        data_type VARCHAR(255) DEFAULT NULL,
        data_subject_category VARCHAR(255) DEFAULT 'Customers',
        transfer_method VARCHAR(100) DEFAULT 'REST API (HTTPS)',
        encryption_status VARCHAR(100) DEFAULT 'Encrypted in Transit & Rest',
        risk_level VARCHAR(50) DEFAULT 'Low',
        description TEXT DEFAULT NULL,
        created_by BIGINT UNSIGNED DEFAULT NULL,
        updated_by BIGINT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_source (source_system),
        INDEX idx_target (target_system),
        INDEX idx_activity (processing_activity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table data_flows verified.\n";

$columnsToAddDF = [
    'processing_activity_id' => "BIGINT UNSIGNED DEFAULT NULL",
    'flow_name' => "VARCHAR(255) DEFAULT NULL",
    'data_subject_category' => "VARCHAR(255) DEFAULT 'Customers'",
    'description' => "TEXT DEFAULT NULL",
    'created_by' => "BIGINT UNSIGNED DEFAULT NULL",
    'updated_by' => "BIGINT UNSIGNED DEFAULT NULL"
];
foreach ($columnsToAddDF as $col => $definition) {
    $checkCol = $conn->query("SHOW COLUMNS FROM data_flows LIKE '$col'");
    if ($checkCol && $checkCol->num_rows == 0) {
        $conn->query("ALTER TABLE data_flows ADD COLUMN $col $definition");
        echo "Added column $col to data_flows.\n";
    }
}

// Seed Initial Processing Activities & Data Flows if empty
$checkCount = $conn->query("SELECT COUNT(*) FROM data_flows WHERE deleted_at IS NULL")->fetch_row()[0];
if ($checkCount == 0) {
    echo "Seeding initial Data Mapping activities & flows...\n";

    // Seed Processing Activities
    $conn->query("
        INSERT INTO processing_activities (activity_name, purpose, department, data_controller, processor, data_categories, data_subjects, recipients, legal_basis, retention_period, storage_location, safeguards, risk_level, status) VALUES
        ('Customer Account Registration & Authentication', 'User authentication and profile management.', 'Engineering', 'PrivacyHQ Inc', 'AWS Auth', 'Identity, Email, Password Hash, IP Address', 'Customers, Portal Users', 'Internal DB, Auth0', 'Contractual Necessity', '5 Years after Account Deletion', 'AWS us-east-1 RDS', 'AES-256 Encryption, MFA, TLS 1.3', 'Low', 'active'),
        ('Billing & Subscription Invoicing', 'Process payments and generate tax invoices.', 'Finance', 'PrivacyHQ Inc', 'Stripe Payments', 'Credit Card Last4, Billing Address, Tax ID', 'Customers, Enterprise Clients', 'Stripe, Xero Accounting', 'Legal Obligation', '7 Years (Tax Compliance)', 'Stripe Vault, AWS Vault', 'PCI-DSS Level 1 Compliance, TLS 1.3', 'Medium', 'active'),
        ('Employee HR & Payroll Administration', 'Process monthly salaries and tax withholdings.', 'Human Resources', 'PrivacyHQ Inc', 'Keka HRMS', 'Aadhaar, PAN, Bank Account, Salary Details', 'Employees, Contractors', 'Income Tax Dept, Direct Deposit Bank', 'Legal Obligation', '10 Years', 'On-Premise Encrypted Storage', 'Role-Based Access Control, Sealed Vault', 'High', 'active'),
        ('Customer Support & CRM Ticket Processing', 'Track and resolve customer inquiries and support tickets.', 'Customer Success', 'PrivacyHQ Inc', 'Zendesk', 'Email, Support Notes, Phone, Device Logs', 'Customers, Prospects', 'Zendesk Cloud', 'Legitimate Interest', '2 Years after Ticket Resolution', 'Zendesk EU Data Center', 'TLS 1.3, Encrypted Attachments', 'Medium', 'active'),
        ('Marketing Analytics & Campaign Targeting', 'Analyze user behavior and measure campaign conversion rates.', 'Marketing', 'PrivacyHQ Inc', 'Google Analytics / Mixpanel', 'IP Address, Device ID, Page Views, Cookies', 'Website Visitors, Leads', 'Google Analytics, Meta Ads', 'Consent', '14 Months', 'Google Cloud US', 'Anonymization, IP Masking', 'Medium', 'active');
    ");

    // Seed Data Flows
    $conn->query("
        INSERT INTO data_flows (processing_activity_id, flow_name, source_system, target_system, data_type, data_subject_category, transfer_method, encryption_status, risk_level, description) VALUES
        (1, 'User Registration Pipeline', 'Web Portal Frontend', 'API Gateway', 'Email, Password Hash, IP Address', 'Customers', 'REST API (HTTPS)', 'Encrypted in Transit & Rest', 'Low', 'User submits registration form on web portal.'),
        (1, 'API to PostgreSQL Persist', 'API Gateway', 'PostgreSQL Production', 'User Profile Record', 'Customers', 'gRPC / TLS 1.3', 'Encrypted in Transit & Rest', 'Low', 'API Gateway writes sanitized user profile to database.'),
        (2, 'Payment Processing Pipeline', 'Checkout Portal', 'Stripe Payment Gateway', 'Tokenized Payment Details', 'Customers', 'HTTPS / TLS 1.3', 'Encrypted in Transit & Rest', 'Medium', 'Tokenized card payload dispatched securely to Stripe.'),
        (2, 'Invoice Sync Pipeline', 'Stripe API', 'Xero Accounting', 'Billing Transaction History', 'Enterprise Clients', 'REST Webhook (HTTPS)', 'In Transit Only', 'Medium', 'Stripe triggers webhook to sync transaction with Xero.'),
        (3, 'Employee Payroll Sync', 'HRMS Portal', 'AWS S3 Document Vault', 'PAN, Salary Slips, Tax Form 16', 'Employees', 'SFTP / Encrypted Payload', 'Encrypted in Transit & Rest', 'High', 'Monthly salary records stored in S3 document vault.'),
        (4, 'Support Ticket Pipeline', 'Web Contact Form', 'Zendesk Support Desk', 'Customer Email, Ticket Notes', 'Portal Users', 'HTTPS REST API', 'Encrypted in Transit & Rest', 'Low', 'Customer support ticket submitted to Zendesk.'),
        (5, 'Telemetry Analytics Pipeline', 'Web Browser Session', 'Google Analytics 4', 'Anonymized IP, Page Views', 'Website Visitors', 'HTTPS Beacon', 'In Transit Only', 'Low', 'Browser dispatches anonymized event telemetry to GA4.');
    ");
}

echo "Data Mapping migration executed successfully!\n";
