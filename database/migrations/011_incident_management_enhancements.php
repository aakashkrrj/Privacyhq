<?php
// governance/database/migrations/011_incident_management_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Incident Management Enhancements Migration...\n";

/** @var mysqli $conn */

// 1. Add missing columns to incidents table
$columns = [
    'incident_type' => "VARCHAR(100) DEFAULT 'Data Privacy' AFTER summary",
    'priority' => "VARCHAR(20) DEFAULT 'Medium' AFTER severity",
    'assigned_to' => "BIGINT UNSIGNED DEFAULT NULL AFTER status",
    'assigned_team' => "VARCHAR(100) DEFAULT 'Response Team' AFTER assigned_to",
    'reported_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER assigned_team",
    'affected_system' => "VARCHAR(255) DEFAULT 'Core System' AFTER impacted_records",
    'due_date' => "DATE DEFAULT NULL AFTER affected_system",
    'containment_actions' => "TEXT DEFAULT NULL AFTER due_date",
    'remediation_notes' => "TEXT DEFAULT NULL AFTER containment_actions",
    'root_cause' => "TEXT DEFAULT NULL AFTER remediation_notes",
    'preventive_actions' => "TEXT DEFAULT NULL AFTER root_cause",
    'is_escalated' => "TINYINT(1) DEFAULT 0 AFTER preventive_actions",
    'dpo_notified' => "TINYINT(1) DEFAULT 0 AFTER is_escalated",
    'regulatory_status' => "VARCHAR(100) DEFAULT 'Not Required' AFTER dpo_notified",
    'resolved_at' => "DATETIME DEFAULT NULL AFTER updated_at"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM incidents LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE incidents ADD COLUMN $col $definition");
        echo "Added column '$col' to incidents table.\n";
    }
}

// 2. Create incident_timeline table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS incident_timeline (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        incident_id BIGINT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        performed_by BIGINT UNSIGNED DEFAULT NULL,
        old_status VARCHAR(50) DEFAULT NULL,
        new_status VARCHAR(50) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_inc_tl (incident_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table incident_timeline verified.\n";

// 3. Insert default seed incident if empty
$checkEmpty = $conn->query("SELECT COUNT(*) AS total FROM incidents WHERE deleted_at IS NULL");
$row = $checkEmpty ? $checkEmpty->fetch_assoc() : null;
if (($row['total'] ?? 0) == 0) {
    $conn->query("
        INSERT INTO incidents 
            (summary, description, incident_type, severity, priority, impacted_records, affected_system, status, reported_by, created_at, updated_at)
        VALUES 
            ('Unauthorized API Access Attempt', 'Multiple failed OAuth token attempts detected on production API gateway.', 'Security Incident', 'High', 'High', 150, 'OAuth Gateway', 'Investigating', 1, NOW(), NOW()),
            ('Customer Email Log Exposure', 'Debugging logs contained plain-text email addresses in temporary storage.', 'Data Privacy', 'Medium', 'Medium', 45, 'Customer Logs', 'Open', 1, NOW(), NOW())
    ");
    echo "Seeded default incidents.\n";
}

echo "Incident Management Enhancements Migration completed successfully!\n";
