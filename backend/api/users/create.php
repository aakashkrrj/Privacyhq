<?php
// backend/api/users/create.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';

// Super Admin only
require_permission('manage_users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$password = trim($_POST['password'] ?? '');

if (empty($email) || !$roleId || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email, role, and password are required fields."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address format."]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long."]);
    exit;
}

try {
    // Check if email already exists
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "A user with this email address already exists."]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (role_id, email, phone, password_hash, first_name, last_name, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$roleId, $email, $phone, $hash, $firstName, $lastName]);

    echo json_encode(["success" => true, "message" => "User account created successfully!"]);
    exit;
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    exit;
}
