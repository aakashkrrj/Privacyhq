<?php
// governance/database/migrations/015_policies_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Policies Module Enhancements Migration (015)...\n";

/** @var mysqli $conn */

// 1. Modify status column in privacy_policies to VARCHAR(50) DEFAULT 'draft'
$conn->query("ALTER TABLE privacy_policies MODIFY COLUMN status VARCHAR(50) DEFAULT 'draft'");
echo "Altered 'status' column in privacy_policies to VARCHAR(50).\n";

// 2. Ensure all policy fields exist on privacy_policies
$columns = [
    'policy_code' => "VARCHAR(50) DEFAULT NULL AFTER id",
    'category' => "VARCHAR(100) DEFAULT 'Data Privacy' AFTER policy_name",
    'description' => "TEXT DEFAULT NULL AFTER category",
    'policy_owner' => "VARCHAR(100) DEFAULT 'DPO / Compliance Team' AFTER description",
    'department' => "VARCHAR(100) DEFAULT 'Legal & Compliance' AFTER policy_owner",
    'review_date' => "DATE DEFAULT NULL AFTER effective_date",
    'expiry_date' => "DATE DEFAULT NULL AFTER review_date",
    'approval_status' => "VARCHAR(50) DEFAULT 'draft' AFTER status",
    'file_name' => "VARCHAR(255) DEFAULT NULL AFTER document_path",
    'file_type' => "VARCHAR(50) DEFAULT NULL AFTER file_name",
    'file_size' => "BIGINT UNSIGNED DEFAULT 0 AFTER file_type",
    'uploaded_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER file_size",
    'updated_by' => "BIGINT UNSIGNED DEFAULT NULL AFTER uploaded_by",
    'deleted_at' => "TIMESTAMP NULL DEFAULT NULL AFTER updated_at"
];

foreach ($columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM privacy_policies LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE privacy_policies ADD COLUMN $col $definition");
        echo "Added column '$col' to privacy_policies table.\n";
    }
}

// 3. Create policy_versions table
$conn->query("
    CREATE TABLE IF NOT EXISTS policy_versions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        policy_id BIGINT UNSIGNED NOT NULL,
        version_number VARCHAR(50) NOT NULL,
        document_path VARCHAR(255) DEFAULT NULL,
        file_name VARCHAR(255) DEFAULT NULL,
        file_type VARCHAR(50) DEFAULT NULL,
        file_size BIGINT UNSIGNED DEFAULT 0,
        change_summary TEXT DEFAULT NULL,
        uploaded_by BIGINT UNSIGNED DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pv_policy (policy_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table policy_versions verified.\n";

// 4. Create policy_approvals table
$conn->query("
    CREATE TABLE IF NOT EXISTS policy_approvals (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        policy_id BIGINT UNSIGNED NOT NULL,
        version_id BIGINT UNSIGNED DEFAULT NULL,
        action VARCHAR(100) NOT NULL,
        actor_id BIGINT UNSIGNED DEFAULT NULL,
        actor_role VARCHAR(100) DEFAULT 'Compliance Admin',
        old_status VARCHAR(50) DEFAULT NULL,
        new_status VARCHAR(50) DEFAULT NULL,
        comments TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pa_policy (policy_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table policy_approvals verified.\n";

// 5. Create Performance Indexes on privacy_policies
$indexes = [
    'idx_pp_status_del' => "INDEX idx_pp_status_del (status, deleted_at)",
    'idx_pp_category' => "INDEX idx_pp_category (category)",
    'idx_pp_dept' => "INDEX idx_pp_dept (department)",
    'idx_pp_owner' => "INDEX idx_pp_owner (policy_owner)",
    'idx_pp_code' => "INDEX idx_pp_code (policy_code)",
    'idx_pp_appr' => "INDEX idx_pp_appr (approval_status)"
];

foreach ($indexes as $idxName => $idxSql) {
    $checkIdx = $conn->query("SHOW INDEX FROM privacy_policies WHERE Key_name = '$idxName'");
    if ($checkIdx && $checkIdx->num_rows == 0) {
        $conn->query("ALTER TABLE privacy_policies ADD $idxSql");
        echo "Added index '$idxName' to privacy_policies.\n";
    }
}

// 6. Update policy_code where NULL
$conn->query("UPDATE privacy_policies SET policy_code = CONCAT('POL-', LPAD(id, 4, '0')) WHERE policy_code IS NULL OR policy_code = ''");
$conn->query("UPDATE privacy_policies SET review_date = DATE_ADD(created_at, INTERVAL 1 YEAR) WHERE review_date IS NULL");
$conn->query("UPDATE privacy_policies SET expiry_date = DATE_ADD(created_at, INTERVAL 2 YEAR) WHERE expiry_date IS NULL");
$conn->query("UPDATE privacy_policies SET approval_status = status WHERE approval_status IS NULL OR approval_status = ''");

// 7. Seed default policy records if empty
$checkEmpty = $conn->query("SELECT COUNT(*) AS total FROM privacy_policies WHERE deleted_at IS NULL");
$row = $checkEmpty ? $checkEmpty->fetch_assoc() : null;
if (($row['total'] ?? 0) == 0) {
    $conn->query("
        INSERT INTO privacy_policies 
            (policy_code, policy_name, category, description, policy_owner, department, version, effective_date, review_date, expiry_date, status, approval_status, document_path, file_name, file_type, file_size, uploaded_by, created_at, updated_at)
        VALUES 
            ('POL-0001', 'Global Enterprise Privacy & Data Protection Policy', 'Data Privacy', 'Master organizational privacy compliance framework governing data subject rights, consent, and processing.', 'Chief Privacy Officer', 'Legal & Governance', '1.0', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'active', 'approved', 'uploads/policies/global_privacy_policy_v1.0.pdf', 'global_privacy_policy_v1.0.pdf', 'pdf', 1048576, 1, NOW(), NOW()),
            ('POL-0002', 'Data Retention & Secure Disposal Procedure', 'Information Security', 'Defines retention schedules, legal hold protocols, and automated purging rules across databases.', 'Head of IT & Security', 'Engineering & IT', '2.1', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'active', 'approved', 'uploads/policies/data_retention_policy_v2.1.pdf', 'data_retention_policy_v2.1.pdf', 'pdf', 524288, 1, NOW(), NOW()),
            ('POL-0003', 'Employee & Personnel Data Handling Standard', 'HR & Employee Privacy', 'Standard operating procedures for managing employee PII, payroll data, and background checks.', 'HR Director', 'Human Resources', '1.2', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 'draft', 'pending_approval', 'uploads/policies/hr_privacy_standard_v1.2.pdf', 'hr_privacy_standard_v1.2.pdf', 'pdf', 786432, 1, NOW(), NOW())
    ");
    echo "Seeded default policy records.\n";
}

echo "Policies Module Migration (015) completed successfully!\n";
