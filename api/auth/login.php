<?php
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Read raw JSON or POST array
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = [];
$email = $data['email'] ?? $_POST['email'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
    exit;
}

$result = loginUser($email, $password);

if ($result['success']) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => $result['error']]);
}
