<?php
// backend/api/roles/roles-api.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';

// Super Admin only
require_permission('manage_users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$action = trim($_POST['action'] ?? '');

try {
    if ($action === 'create') {
        $name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            echo json_encode(["success" => false, "message" => "Role name is required."]);
            exit;
        }

        // Check duplicates
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ?");
        $stmtCheck->execute([$name]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "A role with this name already exists."]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO roles (role_name, description, status) VALUES (?, ?, 'active')");
        $stmt->execute([$name, $description]);
        $newId = $pdo->lastInsertId();

        log_audit_event($pdo, 'RBAC', 'Create Role', $_SESSION['user_id'], $newId, null, "Created role: $name");
        echo json_encode(["success" => true, "message" => "Role created successfully!"]);
        exit;

    } elseif ($action === 'update') {
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$roleId || empty($name)) {
            echo json_encode(["success" => false, "message" => "Invalid parameters."]);
            exit;
        }

        // Prevent modification of system roles name
        if ($roleId <= 4) {
            echo json_encode(["success" => false, "message" => "System roles names cannot be modified."]);
            exit;
        }

        // Get old name
        $stmtOld = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
        $stmtOld->execute([$roleId]);
        $oldName = $stmtOld->fetchColumn();

        $stmt = $pdo->prepare("UPDATE roles SET role_name = ?, description = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $description, $roleId]);

        log_audit_event($pdo, 'RBAC', 'Update Role', $_SESSION['user_id'], $roleId, $oldName, "Updated role: $name");
        echo json_encode(["success" => true, "message" => "Role metadata updated successfully!"]);
        exit;

    } elseif ($action === 'delete') {
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

        if (!$roleId) {
            echo json_encode(["success" => false, "message" => "Invalid role ID."]);
            exit;
        }

        if ($roleId <= 4) {
            echo json_encode(["success" => false, "message" => "Default system roles cannot be deleted."]);
            exit;
        }

        // Check if users are assigned to this role
        $stmtUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL");
        $stmtUsers->execute([$roleId]);
        if ($stmtUsers->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "Cannot delete role: active users are assigned to it."]);
            exit;
        }

        // Get name for audit
        $stmtName = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
        $stmtName->execute([$roleId]);
        $roleName = $stmtName->fetchColumn();

        // Delete permissions mapping first
        $stmtDelPerms = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmtDelPerms->execute([$roleId]);

        // Delete role
        $stmtDelRole = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $stmtDelRole->execute([$roleId]);

        log_audit_event($pdo, 'RBAC', 'Delete Role', $_SESSION['user_id'], $roleId, $roleName, null);
        echo json_encode(["success" => true, "message" => "Role deleted successfully."]);
        exit;

    } elseif ($action === 'clone') {
        $sourceRoleId = filter_input(INPUT_POST, 'source_role_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$sourceRoleId || empty($name)) {
            echo json_encode(["success" => false, "message" => "Invalid parameters."]);
            exit;
        }

        // Check duplicate
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ?");
        $stmtCheck->execute([$name]);
        if ($stmtCheck->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "A role with this name already exists."]);
            exit;
        }

        // Create new role
        $stmt = $pdo->prepare("INSERT INTO roles (role_name, description, status) VALUES (?, ?, 'active')");
        $stmt->execute([$name, $description]);
        $newRoleId = $pdo->lastInsertId();

        // Clone permissions mapping
        $stmtPerms = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
        $stmtPerms->execute([$sourceRoleId]);
        $perms = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($perms)) {
            $stmtInsert = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($perms as $pid) {
                $stmtInsert->execute([$newRoleId, $pid]);
            }
        }

        log_audit_event($pdo, 'RBAC', 'Clone Role', $_SESSION['user_id'], $newRoleId, "Source: $sourceRoleId", "Cloned role: $name");
        echo json_encode(["success" => true, "message" => "Role cloned successfully!"]);
        exit;

    } elseif ($action === 'toggle_status') {
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

        if (!$roleId) {
            echo json_encode(["success" => false, "message" => "Invalid role ID."]);
            exit;
        }

        if ($roleId <= 4) {
            echo json_encode(["success" => false, "message" => "Default system roles status cannot be modified."]);
            exit;
        }

        // Check if users are assigned to this role
        $stmtUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ? AND status = 'active' AND deleted_at IS NULL");
        $stmtUsers->execute([$roleId]);
        if ($stmtUsers->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "Cannot disable role: active users are assigned to it."]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT status, role_name FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        $newStatus = ($role['status'] === 'active') ? 'disabled' : 'active';
        $stmtUpdate = $pdo->prepare("UPDATE roles SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $roleId]);

        log_audit_event($pdo, 'RBAC', 'Toggle Role Status', $_SESSION['user_id'], $roleId, $role['status'], $newStatus);
        echo json_encode(["success" => true, "message" => "Role status updated to " . $newStatus . " successfully."]);
        exit;

    } else {
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
