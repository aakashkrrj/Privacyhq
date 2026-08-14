<?php
// governance/database/migrations/009_vendor_management_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Vendor Management Enhancements Migration...\n";

/** @var mysqli $conn */

// 1. Add missing columns to vendors table if not exists
$columns = [
    'contact_name' => "VARCHAR(255) NULL AFTER service_type",
    'contact_email' => "VARCHAR(255) NULL AFTER contact_name",
    'dpa_status' => "ENUM('Pending', 'Signed', 'Not Required') DEFAULT 'Pending' AFTER contact_email",
    'risk_level' => "ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Low' AFTER dpa_status",
    'data_shared' => "TEXT NULL AFTER risk_level",
    'status' => "ENUM('Active', 'Inactive', 'Under Review', 'Pending Review') DEFAULT 'Active' AFTER data_shared",
    'next_assessment_date' => "DATE NULL AFTER status",
    'contract_expiry' => "DATE NULL AFTER next_assessment_date",
    'notes' => "TEXT NULL AFTER contract_expiry"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM vendors LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE vendors ADD COLUMN $col $definition");
        echo "Added column '$col' to vendors table.\n";
    }
}

// 2. Ensure seed vendors exist if vendor count is low
$count = $conn->query("SELECT COUNT(*) FROM vendors WHERE deleted_at IS NULL")->fetch_row()[0];
if ($count < 3) {
    $conn->query("
        INSERT INTO vendors (name, service_type, contact_name, contact_email, dpa_status, risk_level, data_shared, status, next_assessment_date, contract_expiry, created_at, updated_at) VALUES
        ('Amazon Web Services (AWS)', 'Cloud Storage', 'Cloud Infrastructure Ops', 'aws-compliance@amazon.com', 'Signed', 'High', 'System backups, database storage, user authentication logs', 'Active', '2027-01-15', '2028-12-31', NOW(), NOW()),
        ('Salesforce CRM', 'Software', 'Enterprise Ops', 'privacy@salesforce.com', 'Signed', 'Medium', 'Customer CRM profiles, contact phone numbers, support tickets', 'Active', '2026-11-30', '2027-05-31', NOW(), NOW()),
        ('Google Analytics 4', 'Analytics', 'Marketing Lead', 'dpo@google.com', 'Pending', 'High', 'User IP addresses, browser fingerprinting, session telemetry', 'Under Review', '2026-09-15', '2027-01-01', NOW(), NOW())
    ");
    echo "Inserted default seed vendors.\n";
}

// 3. Ensure corresponding vendor_assessments rows exist
$conn->query("
    INSERT INTO vendor_assessments (vendor_id, risk_score, status, created_at, updated_at)
    SELECT v.id, 
           IF(v.risk_level = 'Critical', 95, IF(v.risk_level = 'High', 85, IF(v.risk_level = 'Medium', 55, 20))),
           IF(v.dpa_status = 'Signed', 'Compliant', 'Under Audit'),
           NOW(), NOW()
    FROM vendors v
    LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
    WHERE va.id IS NULL AND v.deleted_at IS NULL
");

// 4. Ensure tasks table exists for workflow dispatching
$conn->query("
    CREATE TABLE IF NOT EXISTS tasks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        record_id BIGINT UNSIGNED DEFAULT NULL,
        task_type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        assigned_to BIGINT UNSIGNED DEFAULT NULL,
        assigned_by BIGINT UNSIGNED DEFAULT NULL,
        priority VARCHAR(20) DEFAULT 'Medium',
        status VARCHAR(50) DEFAULT 'Pending',
        due_date DATE DEFAULT NULL,
        parent_task_id BIGINT UNSIGNED DEFAULT NULL,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "Vendor Management Enhancements Migration completed successfully!\n";
