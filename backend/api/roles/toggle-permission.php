<?php
// backend/api/roles/toggle-permission.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';

// Super Admin only
require_permission('manage_users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$permissionId = filter_input(INPUT_POST, 'permission_id', FILTER_VALIDATE_INT);

if (!$roleId || !$permissionId) {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
    exit;
}

// Ensure role is active
$stmtRole = $pdo->prepare("SELECT status FROM roles WHERE id = ?");
$stmtRole->execute([$roleId]);
$roleStatus = $stmtRole->fetchColumn();
if ($roleStatus === 'disabled') {
    echo json_encode(["success" => false, "message" => "Permissions cannot be modified on a disabled role."]);
    exit;
}
if ($roleId == 1) {
    echo json_encode(["success" => false, "message" => "Super Admin permissions cannot be modified."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND permission_id = ?");
    $stmt->execute([$roleId, $permissionId]);
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        $stmtDel = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?");
        $stmtDel->execute([$roleId, $permissionId]);
        log_audit_event($pdo, 'RBAC', 'Revoke Permission', $_SESSION['user_id'], $roleId, "Permission: $permissionId", null);
        echo json_encode(["success" => true, "status" => "removed", "message" => "Permission revoked from role successfully."]);
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmtIns->execute([$roleId, $permissionId]);
        log_audit_event($pdo, 'RBAC', 'Grant Permission', $_SESSION['user_id'], $roleId, null, "Permission: $permissionId");
        echo json_encode(["success" => true, "status" => "added", "message" => "Permission granted to role successfully."]);
    }
    exit;
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
