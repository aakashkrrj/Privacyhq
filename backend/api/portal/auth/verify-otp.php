<?php
require_once __DIR__ . '/../PortalBootstrap.php';
require_once __DIR__ . '/../../../services/PortalAuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(["status" => "error", "message" => "Invalid CSRF token"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$otp = $input['otp'] ?? '';
$actionContext = $input['action_context'] ?? 'general';

$authService = new \Backend\Services\PortalAuthService($pdo);
$success = $authService->verifyOtp($_SESSION['portal_subject_id'], $otp, $actionContext);

if ($success) {
    echo json_encode(["status" => "success", "message" => "Step-up verification successful"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid or expired OTP"]);
}
