<?php
// governance/database/migrations/018_user_management_production_ready.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing User Management Production Ready Migration...\n";

/** @var mysqli $conn */

// 1. Ensure indexes exist on users table
$indexes = [
    'idx_users_email' => 'ADD INDEX idx_users_email (email)',
    'idx_users_role_status' => 'ADD INDEX idx_users_role_status (role_id, status)',
    'idx_users_created_at' => 'ADD INDEX idx_users_created_at (created_at)',
    'idx_users_deleted_at' => 'ADD INDEX idx_users_deleted_at (deleted_at)'
];

foreach ($indexes as $idxName => $idxSql) {
    $checkRes = $conn->query("SHOW INDEX FROM users WHERE Key_name = '$idxName'");
    if ($checkRes && $checkRes->num_rows === 0) {
        $alterSql = "ALTER TABLE users $idxSql";
        if ($conn->query($alterSql)) {
            echo "Index '$idxName' added to users successfully.\n";
        } else {
            echo "Warning adding index '$idxName': " . $conn->error . "\n";
        }
    } else {
        echo "Index '$idxName' already exists on users.\n";
    }
}

// 2. Ensure standard roles exist in roles table
$rolesData = [
    [1, 'Super Admin', 'Full access to all privacy governance modules and platform administration'],
    [2, 'DPO / Privacy Officer', 'Executive oversight for privacy policies, DSRs, ROPA, and regulatory reporting'],
    [3, 'Compliance Assessor', 'Conducts PIA/DPIA assessments, vendor risk audits, and mitigation reviews'],
    [4, 'Audit Specialist', 'Read-only access to audit logs, compliance reports, and evidence registers'],
    [5, 'Business User', 'Standard access to submit DSRs, report incidents, and view assigned policies']
];

foreach ($rolesData as $r) {
    $id = $r[0];
    $name = $conn->real_escape_string($r[1]);
    $desc = $conn->real_escape_string($r[2]);

    $checkRole = $conn->query("SELECT id FROM roles WHERE id = $id");
    if ($checkRole && $checkRole->num_rows === 0) {
        $conn->query("INSERT INTO roles (id, role_name, description, created_at, updated_at) VALUES ($id, '$name', '$desc', NOW(), NOW())");
        echo "Role #$id '$name' created.\n";
    } else {
        $conn->query("UPDATE roles SET role_name = '$name', description = '$desc', updated_at = NOW() WHERE id = $id");
        echo "Role #$id '$name' updated.\n";
    }
}

// 3. Ensure standard permissions exist in permissions table
$permsData = [
    ['view_dashboard', 'Dashboard', 'View executive and module compliance dashboards'],
    ['manage_users', 'User Management', 'Create, edit, delete users, assign roles, and manage permissions matrix'],
    ['view_audit_logs', 'Audit Logs', 'View and export immutable system audit trail and manage retention'],
    ['view_reports', 'Reports', 'Generate, schedule, and export compliance reports'],
    ['manage_ropa', 'ROPA', 'Create, update, and export Records of Processing Activities'],
    ['manage_policies', 'Policies', 'Create, update, upload, approve, and version privacy policies'],
    ['manage_assessments', 'Assessments', 'Create, perform, review, and approve PIA/DPIA assessments'],
    ['manage_incidents', 'Incident Management', 'Log, track, investigate, and resolve security privacy incidents'],
    ['manage_vendors', 'Vendor Risk', 'Manage vendors, perform DPA assessments, and track vendor risk'],
    ['manage_dsr', 'Data Subject Rights', 'Receive, verify, process, and fulfill data subject rights requests'],
    ['manage_consents', 'Consent Management', 'Track and manage user privacy consent receipts'],
    ['view_cookie_governance', 'Cookie Governance', 'Manage website cookie scans and consent banner categories']
];

foreach ($permsData as $p) {
    $pName = $conn->real_escape_string($p[0]);
    $pMod = $conn->real_escape_string($p[1]);
    $pDesc = $conn->real_escape_string($p[2]);

    $checkPerm = $conn->query("SELECT id FROM permissions WHERE permission_name = '$pName'");
    if ($checkPerm && $checkPerm->num_rows === 0) {
        $conn->query("INSERT INTO permissions (permission_name, module, description, created_at, updated_at) VALUES ('$pName', '$pMod', '$pDesc', NOW(), NOW())");
        echo "Permission '$pName' created.\n";
    } else {
        $conn->query("UPDATE permissions SET module = '$pMod', description = '$pDesc', updated_at = NOW() WHERE permission_name = '$pName'");
    }
}

// 4. Map default permissions for Super Admin (Role 1) & DPO (Role 2)
$allPermsRes = $conn->query("SELECT id FROM permissions");
$allPermIds = [];
while ($row = $allPermsRes->fetch_assoc()) {
    $allPermIds[] = (int)$row['id'];
}

foreach ($allPermIds as $pId) {
    // Super Admin (Role 1) gets all permissions
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at) VALUES (1, $pId, NOW())");
    // DPO (Role 2) gets all permissions
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at) VALUES (2, $pId, NOW())");
}

echo "Seeded default role_permissions matrix for Super Admin and DPO.\n";
echo "User Management Migration completed successfully!\n";
