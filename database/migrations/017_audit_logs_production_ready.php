<?php
// governance/database/migrations/017_audit_logs_production_ready.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Audit Logs Production Ready Migration...\n";

/** @var mysqli $conn */

// 1. Create audit_retention_settings table
$sqlTable = "
CREATE TABLE IF NOT EXISTS audit_retention_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    retention_days INT NOT NULL DEFAULT 90,
    auto_purge_enabled TINYINT(1) NOT NULL DEFAULT 1,
    archive_before_purge TINYINT(1) NOT NULL DEFAULT 0,
    last_purge_at TIMESTAMP NULL DEFAULT NULL,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sqlTable)) {
    echo "Table 'audit_retention_settings' created/verified successfully.\n";
} else {
    echo "Error creating table 'audit_retention_settings': " . $conn->error . "\n";
}

// 2. Seed initial default retention setting if empty
$res = $conn->query("SELECT COUNT(*) FROM audit_retention_settings");
$count = $res ? (int)$res->fetch_row()[0] : 0;
if ($count === 0) {
    $conn->query("INSERT INTO audit_retention_settings (retention_days, auto_purge_enabled, archive_before_purge, updated_by) VALUES (90, 1, 0, 1)");
    echo "Seeded default audit retention settings (90 days, auto-purge enabled).\n";
}

// 3. Add performance indexes to audit_logs table safely
$indexes = [
    'idx_al_module_action' => 'ADD INDEX idx_al_module_action (module, action)',
    'idx_al_date' => 'ADD INDEX idx_al_date (created_at)',
    'idx_al_user' => 'ADD INDEX idx_al_user (user_id)'
];

foreach ($indexes as $idxName => $idxSql) {
    $checkRes = $conn->query("SHOW INDEX FROM audit_logs WHERE Key_name = '$idxName'");
    if ($checkRes && $checkRes->num_rows === 0) {
        $alterSql = "ALTER TABLE audit_logs $idxSql";
        if ($conn->query($alterSql)) {
            echo "Index '$idxName' added to audit_logs successfully.\n";
        } else {
            echo "Warning adding index '$idxName': " . $conn->error . "\n";
        }
    } else {
        echo "Index '$idxName' already exists on audit_logs.\n";
    }
}

echo "Audit Logs Migration completed successfully!\n";
