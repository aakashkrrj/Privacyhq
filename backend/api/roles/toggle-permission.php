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

// Security: Prevent removing permissions from Super Admin (role ID 1)
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
        echo json_encode(["success" => true, "status" => "removed", "message" => "Permission revoked from role successfully."]);
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmtIns->execute([$roleId, $permissionId]);
        echo json_encode(["success" => true, "status" => "added", "message" => "Permission granted to role successfully."]);
    }
    exit;
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
