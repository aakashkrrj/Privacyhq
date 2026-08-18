<?php
// governance/database/migrations/019_settings_module_production_ready.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Settings Module Production Ready Migration...\n";

/** @var mysqli $conn */

// 1. Create api_keys table
$sqlApiKeys = "
CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    key_prefix VARCHAR(16) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    scopes VARCHAR(255) DEFAULT 'read,write',
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('active','revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ak_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sqlApiKeys)) {
    echo "Table 'api_keys' created/verified successfully.\n";
} else {
    echo "Error creating table 'api_keys': " . $conn->error . "\n";
}

// 2. Create compliance_documents table
$sqlDocs = "
CREATE TABLE IF NOT EXISTS compliance_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General Compliance',
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_cd_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sqlDocs)) {
    echo "Table 'compliance_documents' created/verified successfully.\n";
} else {
    echo "Error creating table 'compliance_documents': " . $conn->error . "\n";
}

// 3. Create upload directories if not exist
$docDir = __DIR__ . '/../../uploads/documents';
$profileDir = __DIR__ . '/../../uploads/profile';

if (!file_exists($docDir)) {
    mkdir($docDir, 0777, true);
    echo "Created uploads/documents directory.\n";
}
if (!file_exists($profileDir)) {
    mkdir($profileDir, 0777, true);
    echo "Created uploads/profile directory.\n";
}

echo "Settings Module Migration completed successfully!\n";
