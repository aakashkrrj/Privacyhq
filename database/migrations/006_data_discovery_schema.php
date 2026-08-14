<?php
// governance/database/migrations/006_data_discovery_schema.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Data Discovery Schema Migration...\n";

/** @var mysqli $conn */

// 1. Data Discovery Sources Table
$conn->query("
    CREATE TABLE IF NOT EXISTS discovery_sources (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        source_type VARCHAR(100) NOT NULL DEFAULT 'database',
        connection_uri VARCHAR(500) NOT NULL,
        host_port VARCHAR(100) DEFAULT NULL,
        environment VARCHAR(50) NOT NULL DEFAULT 'production',
        risk_level VARCHAR(50) NOT NULL DEFAULT 'medium',
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        pii_types_json TEXT DEFAULT NULL,
        pii_count INT UNSIGNED DEFAULT 0,
        sensitive_files_count INT UNSIGNED DEFAULT 0,
        compliance_score INT UNSIGNED DEFAULT 100,
        description TEXT DEFAULT NULL,
        last_scan_at TIMESTAMP NULL DEFAULT NULL,
        created_by BIGINT UNSIGNED DEFAULT NULL,
        updated_by BIGINT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table discovery_sources verified.\n";

// 2. Data Discovery Scans Table
$conn->query("
    CREATE TABLE IF NOT EXISTS discovery_scans (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source_id BIGINT UNSIGNED NOT NULL,
        scan_type VARCHAR(50) NOT NULL DEFAULT 'full',
        status VARCHAR(50) NOT NULL DEFAULT 'completed',
        progress_percentage INT UNSIGNED DEFAULT 100,
        items_scanned INT UNSIGNED DEFAULT 0,
        pii_records_found INT UNSIGNED DEFAULT 0,
        sensitive_files_found INT UNSIGNED DEFAULT 0,
        duration_seconds INT UNSIGNED DEFAULT 0,
        error_message TEXT DEFAULT NULL,
        started_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        created_by BIGINT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_source_id (source_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table discovery_scans verified.\n";

// 3. Sensitive Data Findings Table
$conn->query("
    CREATE TABLE IF NOT EXISTS discovery_sensitive_findings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        scan_id BIGINT UNSIGNED DEFAULT NULL,
        source_id BIGINT UNSIGNED NOT NULL,
        data_element_name VARCHAR(255) NOT NULL,
        classification_category VARCHAR(100) NOT NULL DEFAULT 'Personal',
        location_path VARCHAR(500) NOT NULL,
        record_count INT UNSIGNED DEFAULT 1,
        risk_severity VARCHAR(50) NOT NULL DEFAULT 'medium',
        confidence_score INT UNSIGNED DEFAULT 95,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_source_finding (source_id),
        INDEX idx_category (classification_category),
        INDEX idx_severity (risk_severity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table discovery_sensitive_findings verified.\n";

// Seed Initial Sources if empty
$checkCount = $conn->query("SELECT COUNT(*) FROM discovery_sources WHERE deleted_at IS NULL")->fetch_row()[0];
if ($checkCount == 0) {
    echo "Seeding initial Data Discovery sources & telemetry...\n";
    $conn->query("
        INSERT INTO discovery_sources (name, source_type, connection_uri, host_port, environment, risk_level, status, pii_types_json, pii_count, sensitive_files_count, compliance_score, description, last_scan_at) VALUES
        ('PostgreSQL Production', 'database', 'db.prod.internal', '5432', 'production', 'high', 'active', '[\"Aadhaar\",\"PAN\",\"Email\",\"Mobile\"]', 1250000, 142, 88, 'Core customer transactional database holding primary PII.', NOW() - INTERVAL 2 DAY),
        ('AWS S3 User Storage', 'cloud_storage', 's3://prod-user-documents', '443', 'production', 'medium', 'active', '[\"Passport\",\"Bank Statements\",\"Invoices\"]', 450000, 218, 92, 'Object storage for user KYC documents and uploaded PDFs.', NOW() - INTERVAL 1 DAY),
        ('MongoDB Atlas', 'nosql', 'cluster0.mongodb.net', '27017', 'production', 'high', 'active', '[\"Customer IDs\",\"Email\",\"Phone\"]', 680000, 65, 84, 'Document store for user profiles and activity telemetry.', NOW() - INTERVAL 4 DAY),
        ('Microsoft SQL Server', 'database', 'sql.company.local', '1433', 'staging', 'medium', 'active', '[\"Employee IDs\",\"Salary\",\"PAN\"]', 120000, 48, 95, 'HR and internal payroll system DB.', NOW() - INTERVAL 5 DAY),
        ('Google Drive', 'saas', 'drive.google.com', '443', 'production', 'low', 'active', '[\"Contracts\",\"Invoices\",\"HR Files\"]', 35000, 14, 98, 'Corporate shared drive for company documents.', NOW() - INTERVAL 7 DAY),
        ('Salesforce CRM', 'crm', 'salesforce.enterprise.com', '443', 'production', 'medium', 'active', '[\"Customers\",\"Leads\",\"Phone\"]', 280000, 0, 94, 'Enterprise CRM leads and customer account profiles.', NOW() - INTERVAL 3 DAY);
    ");

    // Seed Scan History
    $conn->query("
        INSERT INTO discovery_scans (source_id, scan_type, status, progress_percentage, items_scanned, pii_records_found, sensitive_files_found, duration_seconds, started_at, completed_at) VALUES
        (1, 'full', 'completed', 100, 48200, 1250000, 142, 345, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 6 MINUTE),
        (2, 'deep', 'completed', 100, 15400, 450000, 218, 180, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY + INTERVAL 3 MINUTE),
        (3, 'full', 'completed', 100, 89000, 680000, 65, 520, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY + INTERVAL 9 MINUTE),
        (4, 'quick', 'completed', 100, 12000, 120000, 48, 90, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY + INTERVAL 2 MINUTE);
    ");

    // Seed Sensitive Findings
    $conn->query("
        INSERT INTO discovery_sensitive_findings (scan_id, source_id, data_element_name, classification_category, location_path, record_count, risk_severity, confidence_score) VALUES
        (1, 1, 'Aadhaar Card Number', 'Sensitive', 'public.users.aadhaar_no', 850000, 'critical', 99),
        (1, 1, 'PAN Card Number', 'Financial', 'public.users.pan_no', 780000, 'high', 98),
        (1, 1, 'Primary Email Address', 'Personal', 'public.users.email', 1250000, 'medium', 99),
        (2, 2, 'Passport PDF Scans', 'Sensitive', 's3://prod-user-documents/kyc/2026/', 45000, 'critical', 96),
        (2, 2, 'Bank Account Statements', 'Financial', 's3://prod-user-documents/finance/', 128000, 'high', 95),
        (3, 3, 'User Phone Numbers', 'Personal', 'analytics.profiles.mobile', 680000, 'medium', 97),
        (4, 4, 'Employee Salary Ledger', 'Financial', 'dbo.employees.salary_amount', 12000, 'high', 99);
    ");
}

echo "Data Discovery migration executed successfully!\n";
