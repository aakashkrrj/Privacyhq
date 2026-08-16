<?php
// governance/database/migrations/012_risk_register_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Risk Register Enhancements Migration...\n";

/** @var mysqli $conn */

// 1. Ensure assessment_risks columns exist
$columns = [
    'risk_code' => "VARCHAR(50) DEFAULT NULL AFTER id",
    'category' => "VARCHAR(100) DEFAULT 'Data Privacy' AFTER description",
    'risk_source' => "VARCHAR(100) DEFAULT 'Internal Audit' AFTER category",
    'affected_asset' => "VARCHAR(255) DEFAULT 'Core System' AFTER risk_source",
    'owner' => "VARCHAR(100) DEFAULT 'Compliance Team' AFTER affected_asset",
    'department' => "VARCHAR(100) DEFAULT 'Privacy Governance' AFTER owner",
    'inherent_likelihood' => "INT DEFAULT 3 AFTER department",
    'inherent_impact' => "INT DEFAULT 3 AFTER inherent_likelihood",
    'inherent_score' => "INT DEFAULT 9 AFTER inherent_impact",
    'inherent_level' => "VARCHAR(20) DEFAULT 'Medium' AFTER inherent_score",
    'residual_likelihood' => "INT DEFAULT 2 AFTER inherent_level",
    'residual_impact' => "INT DEFAULT 2 AFTER residual_likelihood",
    'residual_score' => "INT DEFAULT 4 AFTER residual_impact",
    'residual_level' => "VARCHAR(20) DEFAULT 'Low' AFTER residual_score",
    'treatment_strategy' => "VARCHAR(100) DEFAULT 'Mitigate / Reduce' AFTER residual_level",
    'target_date' => "DATE DEFAULT NULL AFTER treatment_strategy",
    'review_date' => "DATE DEFAULT NULL AFTER target_date",
    'updated_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER created_by",
    'deleted_at' => "TIMESTAMP NULL DEFAULT NULL AFTER updated_at"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM assessment_risks LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE assessment_risks ADD COLUMN $col $definition");
        echo "Added column '$col' to assessment_risks table.\n";
    }
}

// 2. Ensure risk_mitigations table exists and has necessary columns
$conn->query("
    CREATE TABLE IF NOT EXISTS risk_mitigations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        risk_id BIGINT UNSIGNED NOT NULL,
        mitigation_title VARCHAR(255) DEFAULT NULL,
        implementation_details TEXT DEFAULT NULL,
        mitigation_owner VARCHAR(100) DEFAULT NULL,
        target_date DATE DEFAULT NULL,
        progress INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'In Progress',
        control_details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_risk_mit (risk_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table risk_mitigations verified.\n";

$mitColumns = [
    'mitigation_title' => "VARCHAR(255) DEFAULT NULL AFTER risk_id",
    'mitigation_owner' => "VARCHAR(100) DEFAULT NULL AFTER implementation_details",
    'target_date' => "DATE DEFAULT NULL AFTER mitigation_owner",
    'progress' => "INT DEFAULT 0 AFTER target_date",
    'status' => "VARCHAR(50) DEFAULT 'In Progress' AFTER progress",
    'control_details' => "TEXT DEFAULT NULL AFTER status"
];

foreach ($mitColumns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM risk_mitigations LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE risk_mitigations ADD COLUMN $col $definition");
        echo "Added column '$col' to risk_mitigations table.\n";
    }
}

// 3. Ensure risk_history table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS risk_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        risk_id BIGINT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        performed_by BIGINT UNSIGNED DEFAULT NULL,
        old_score INT DEFAULT NULL,
        new_score INT DEFAULT NULL,
        old_level VARCHAR(50) DEFAULT NULL,
        new_level VARCHAR(50) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_risk_hist (risk_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table risk_history verified.\n";

// 4. Update risk_code where NULL
$conn->query("UPDATE assessment_risks SET risk_code = CONCAT('RSK-', LPAD(id, 4, '0')) WHERE risk_code IS NULL OR risk_code = ''");

// 5. Seed default risk records if empty
$checkEmpty = $conn->query("SELECT COUNT(*) AS total FROM assessment_risks WHERE deleted_at IS NULL");
$row = $checkEmpty ? $checkEmpty->fetch_assoc() : null;
if (($row['total'] ?? 0) == 0) {
    $conn->query("
        INSERT INTO assessment_risks 
            (assessment_id, risk_code, description, category, risk_source, affected_asset, owner, department, inherent_likelihood, inherent_impact, inherent_score, inherent_level, residual_likelihood, residual_impact, residual_score, residual_level, treatment_strategy, status, target_date, created_by, created_at, updated_at)
        VALUES 
            (1, 'RSK-1001', 'Cross-Border Personal Data Transfer Exposure', 'Data Transfer', 'Vendor Audit', 'EU-US Data Pipeline', 'Nishtha Admin', 'Privacy Governance', 5, 5, 25, 'Critical', 3, 2, 6, 'Medium', 'Mitigate / Reduce', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, NOW(), NOW()),
            (1, 'RSK-1002', 'Unencrypted PII Backup Storage Bucket', 'Data Security', 'Internal Audit', 'AWS S3 Backups', 'DevOps Team Lead', 'Infrastructure', 4, 4, 16, 'High', 2, 2, 4, 'Low', 'Mitigate / Reduce', 'mitigated', DATE_ADD(CURDATE(), INTERVAL -5 DAY), 1, NOW(), NOW()),
            (1, 'RSK-1003', 'Third-Party Analytics Consent Non-Compliance', 'Third-Party Vendor', 'Cookie Scan', 'Web Analytics', 'Marketing Mgr', 'Marketing', 3, 4, 12, 'High', 2, 3, 6, 'Medium', 'Mitigate / Reduce', 'open', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 1, NOW(), NOW()),
            (1, 'RSK-1004', 'Over-Retention of Data Subject Accounts', 'Data Retention', 'Compliance Assessment', 'User Relational DB', 'Data DPO Counsel', 'Legal Governance', 2, 3, 6, 'Medium', 1, 2, 2, 'Low', 'Accept / Retain', 'mitigated', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1, NOW(), NOW())
    ");
    echo "Seeded default risk register records.\n";
}

echo "Risk Register Enhancements Migration completed successfully!\n";
