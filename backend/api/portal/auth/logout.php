<?php
require_once __DIR__ . '/../PortalBootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(["status" => "error", "message" => "Invalid CSRF token"]);
    exit;
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

echo json_encode(["status" => "success", "message" => "Logged out"]);
