<?php
// governance/database/migrations/002_dsr_module_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Running DSR Database Migration...\n";

// Helper function to check if column exists
function columnExists($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($res && $res->num_rows > 0);
}

// 1. Update data_subjects
if (!columnExists($conn, 'data_subjects', 'name')) {
    $conn->query("ALTER TABLE data_subjects ADD COLUMN name varchar(255) DEFAULT NULL");
}
if (!columnExists($conn, 'data_subjects', 'email')) {
    $conn->query("ALTER TABLE data_subjects ADD COLUMN email varchar(255) DEFAULT NULL");
}
if (!columnExists($conn, 'data_subjects', 'phone')) {
    $conn->query("ALTER TABLE data_subjects ADD COLUMN phone varchar(50) DEFAULT NULL");
}
if (!columnExists($conn, 'data_subjects', 'department')) {
    $conn->query("ALTER TABLE data_subjects ADD COLUMN department varchar(100) DEFAULT NULL");
}

// 2. Update data_requests
if (!columnExists($conn, 'data_requests', 'description')) {
    $conn->query("ALTER TABLE data_requests ADD COLUMN description text DEFAULT NULL");
}
if (!columnExists($conn, 'data_requests', 'created_by')) {
    $conn->query("ALTER TABLE data_requests ADD COLUMN created_by bigint(20) unsigned DEFAULT NULL");
}
if (!columnExists($conn, 'data_requests', 'resolved_at')) {
    $conn->query("ALTER TABLE data_requests ADD COLUMN resolved_at timestamp NULL DEFAULT NULL");
}
if (!columnExists($conn, 'data_requests', 'deleted_at')) {
    $conn->query("ALTER TABLE data_requests ADD COLUMN deleted_at timestamp NULL DEFAULT NULL");
}

// Update status enum
$conn->query("ALTER TABLE data_requests MODIFY COLUMN status enum('open','assigned','verifying','processing','waiting','completed','rejected','cancelled','expired') DEFAULT 'open'");

// 3. Create dsr_notes table
$conn->query("
    CREATE TABLE IF NOT EXISTS dsr_notes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        data_request_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        note_text text NOT NULL,
        is_public tinyint(1) NOT NULL DEFAULT 0,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_dsr_notes_request (data_request_id),
        CONSTRAINT fk_dsr_notes_request FOREIGN KEY (data_request_id) REFERENCES data_requests (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 4. Create dsr_attachments table
$conn->query("
    CREATE TABLE IF NOT EXISTS dsr_attachments (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        data_request_id bigint(20) unsigned NOT NULL,
        file_name varchar(255) NOT NULL,
        file_path varchar(255) NOT NULL,
        file_size bigint(20) unsigned NOT NULL DEFAULT 0,
        file_type varchar(100) DEFAULT NULL,
        uploaded_by bigint(20) unsigned DEFAULT NULL,
        uploaded_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_dsr_attachments_request (data_request_id),
        CONSTRAINT fk_dsr_attachments_request FOREIGN KEY (data_request_id) REFERENCES data_requests (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Populate sample data for data_subjects email/name if missing
$conn->query("
    UPDATE data_subjects 
    SET name = 'John Doe', email = 'john.doe@example.com', phone = '+1-555-0192', department = 'Engineering' 
    WHERE id = 1 AND (name IS NULL OR name = '')
");

echo "DSR Migration finished cleanly!\n";
