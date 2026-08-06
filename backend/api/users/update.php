<?php
// backend/api/users/update.php
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
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Invalid user ID"]);
    exit;
}

try {
    if ($action === 'update_role') {
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        if (!$roleId) {
            echo json_encode(["success" => false, "message" => "Invalid role ID"]);
            exit;
        }

        // Prevent demoting the last Super Admin
        if ($roleId != 1) {
            $stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentRole = $stmt->fetchColumn();
            if ($currentRole == 1) {
                $count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 1 AND status = 'active'")->fetchColumn();
                if ($count <= 1) {
                    echo json_encode(["success" => false, "message" => "Cannot demote the last active Super Admin user."]);
                    exit;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET role_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$roleId, $userId]);
        echo json_encode(["success" => true, "message" => "User role updated successfully"]);
        exit;

    } elseif ($action === 'toggle_status') {
        $stmt = $pdo->prepare("SELECT status, role_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["success" => false, "message" => "User not found"]);
            exit;
        }

        $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';

        // Prevent disabling the last active Super Admin
        if ($user['role_id'] == 1 && $newStatus === 'suspended') {
            $count = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 1 AND status = 'active'")->fetchColumn();
            if ($count <= 1) {
                echo json_encode(["success" => false, "message" => "Cannot disable the last active Super Admin user."]);
                exit;
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        echo json_encode(["success" => true, "status" => $newStatus, "message" => "User status updated successfully"]);
        exit;

    } elseif ($action === 'reset_password') {
        $password = trim($_POST['password'] ?? '');
        if (strlen($password) < 6) {
            echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long"]);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hash, $userId]);
        echo json_encode(["success" => true, "message" => "User password reset successfully"]);
        exit;

    } else {
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
