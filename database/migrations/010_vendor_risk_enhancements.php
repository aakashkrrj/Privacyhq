<?php
// governance/database/migrations/010_vendor_risk_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Vendor Risk Enhancements Migration...\n";

/** @var mysqli $conn */

// 1. Add missing columns to vendor_assessments table
$columns = [
    'privacy_score' => "INT UNSIGNED DEFAULT 20 AFTER risk_score",
    'security_score' => "INT UNSIGNED DEFAULT 20 AFTER privacy_score",
    'operational_score' => "INT UNSIGNED DEFAULT 20 AFTER security_score",
    'legal_score' => "INT UNSIGNED DEFAULT 20 AFTER operational_score",
    'assessed_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER legal_score",
    'compliance_status' => "VARCHAR(50) DEFAULT 'Under Review' AFTER assessed_by",
    'assessment_notes' => "TEXT DEFAULT NULL AFTER compliance_status"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM vendor_assessments LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE vendor_assessments ADD COLUMN $col $definition");
        echo "Added column '$col' to vendor_assessments table.\n";
    }
}

// 2. Create vendor_risk_history table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS vendor_risk_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vendor_id BIGINT UNSIGNED NOT NULL,
        previous_risk_score INT DEFAULT 0,
        new_risk_score INT DEFAULT 0,
        previous_risk_level VARCHAR(50) DEFAULT NULL,
        new_risk_level VARCHAR(50) NOT NULL,
        previous_status VARCHAR(50) DEFAULT NULL,
        new_status VARCHAR(50) NOT NULL,
        changed_by BIGINT UNSIGNED DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_vendor_hist (vendor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table vendor_risk_history verified.\n";

// 3. Ensure corresponding vendor_assessments rows exist for all vendors
$conn->query("
    INSERT INTO vendor_assessments (vendor_id, risk_score, privacy_score, security_score, operational_score, legal_score, status, compliance_status, created_at, updated_at)
    SELECT v.id, 
           IF(v.risk_level = 'Critical', 90, IF(v.risk_level = 'High', 75, IF(v.risk_level = 'Medium', 50, 20))),
           IF(v.risk_level = 'Critical', 90, IF(v.risk_level = 'High', 75, IF(v.risk_level = 'Medium', 50, 20))),
           IF(v.risk_level = 'Critical', 90, IF(v.risk_level = 'High', 75, IF(v.risk_level = 'Medium', 50, 20))),
           IF(v.risk_level = 'Critical', 90, IF(v.risk_level = 'High', 75, IF(v.risk_level = 'Medium', 50, 20))),
           IF(v.risk_level = 'Critical', 90, IF(v.risk_level = 'High', 75, IF(v.risk_level = 'Medium', 50, 20))),
           IF(v.dpa_status = 'Signed', 'Compliant', 'Under Audit'),
           IF(v.dpa_status = 'Signed', 'Compliant', 'Under Review'),
           NOW(), NOW()
    FROM vendors v
    LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
    WHERE va.id IS NULL AND v.deleted_at IS NULL
");

echo "Vendor Risk Enhancements Migration completed successfully!\n";
