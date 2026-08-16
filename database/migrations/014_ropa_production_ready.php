<?php
// governance/database/migrations/014_ropa_production_ready.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing ROPA Production Ready Migration...\n";

/** @var mysqli $conn */

// 1. Alter status column in processing_activities to VARCHAR(50) DEFAULT 'active'
// to support active, draft, under_review, approved, inactive, archived
$conn->query("ALTER TABLE processing_activities MODIFY COLUMN status VARCHAR(50) DEFAULT 'active'");
echo "Altered 'status' column in processing_activities to VARCHAR(50).\n";

// 2. Ensure all ROPA fields exist
$columns = [
    'ropa_code' => "VARCHAR(50) DEFAULT NULL",
    'activity_name' => "VARCHAR(255) NOT NULL",
    'purpose' => "TEXT NOT NULL",
    'department' => "VARCHAR(100) DEFAULT 'General Privacy'",
    'data_controller' => "VARCHAR(255) DEFAULT 'PrivacyHQ Inc'",
    'business_owner' => "VARCHAR(100) DEFAULT 'Data Owner'",
    'controller_role' => "VARCHAR(50) DEFAULT 'Controller'",
    'legal_basis' => "VARCHAR(100) DEFAULT 'Legitimate Interest'",
    'data_categories' => "TEXT DEFAULT NULL",
    'data_subjects' => "TEXT DEFAULT NULL",
    'processing_operations' => "TEXT DEFAULT NULL",
    'data_source' => "VARCHAR(255) DEFAULT 'Direct Input'",
    'recipients' => "TEXT DEFAULT NULL",
    'third_parties' => "TEXT DEFAULT NULL",
    'international_transfers' => "VARCHAR(50) DEFAULT 'No'",
    'transfer_safeguards' => "TEXT DEFAULT NULL",
    'retention_period' => "VARCHAR(100) DEFAULT NULL",
    'retention_basis' => "VARCHAR(255) DEFAULT 'Legal Obligation'",
    'disposal_mechanism' => "VARCHAR(255) DEFAULT 'Secure Erasure'",
    'storage_location' => "VARCHAR(255) DEFAULT 'AWS Cloud'",
    'safeguards' => "TEXT DEFAULT NULL",
    'technical_measures' => "TEXT DEFAULT NULL",
    'organizational_measures' => "TEXT DEFAULT NULL",
    'risk_level' => "VARCHAR(50) DEFAULT 'Medium'",
    'status' => "VARCHAR(50) DEFAULT 'active'",
    'review_date' => "DATE DEFAULT NULL",
    'created_by' => "BIGINT UNSIGNED DEFAULT NULL",
    'updated_by' => "BIGINT UNSIGNED DEFAULT NULL",
    'deleted_at' => "TIMESTAMP NULL DEFAULT NULL"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM processing_activities LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE processing_activities ADD COLUMN $col $definition");
        echo "Added missing column '$col' to processing_activities.\n";
    }
}

// 3. Ensure Indexes for Performance
$indexes = [
    'idx_pa_status_del' => "INDEX idx_pa_status_del (status, deleted_at)",
    'idx_pa_dept' => "INDEX idx_pa_dept (department)",
    'idx_pa_legal' => "INDEX idx_pa_legal (legal_basis)",
    'idx_pa_review' => "INDEX idx_pa_review (review_date)",
    'idx_pa_code' => "INDEX idx_pa_code (ropa_code)"
];

foreach ($indexes as $idxName => $idxSql) {
    $checkIdx = $conn->query("SHOW INDEX FROM processing_activities WHERE Key_name = '$idxName'");
    if ($checkIdx && $checkIdx->num_rows == 0) {
        $conn->query("ALTER TABLE processing_activities ADD $idxSql");
        echo "Added index '$idxName' to processing_activities.\n";
    }
}

// 4. Ensure ropa_history table exists
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

// 5. Fill missing ROPA codes and review dates
$conn->query("UPDATE processing_activities SET ropa_code = CONCAT('ROPA-', LPAD(id, 4, '0')) WHERE ropa_code IS NULL OR ropa_code = ''");
$conn->query("UPDATE processing_activities SET review_date = DATE_ADD(created_at, INTERVAL 1 YEAR) WHERE review_date IS NULL");

echo "ROPA Production Ready Migration (014) completed successfully!\n";
