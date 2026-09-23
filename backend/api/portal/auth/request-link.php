<?php
require_once __DIR__ . '/../PortalBootstrap.php';
require_once __DIR__ . '/../../../services/PortalAuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(["status" => "error", "message" => "Invalid email address"]);
    exit;
}

$authService = new \Backend\Services\PortalAuthService($pdo);
$authService->requestMagicLink($email);

// Always return generic success
echo json_encode([
    "status" => "success",
    "message" => "If this email exists in our records, you will receive a verification link."
]);
