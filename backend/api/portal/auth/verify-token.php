<?php
require_once __DIR__ . '/../PortalBootstrap.php';
require_once __DIR__ . '/../../../services/PortalAuthService.php';

$token = $_GET['token'] ?? '';

$authService = new \Backend\Services\PortalAuthService($pdo);
if ($authService->verifyMagicLink($token)) {
    header('Location: /governance/portal/dashboard.php');
    exit;
} else {
    // Redirect to login with error, or show a generic error page
    header('Location: /governance/portal/index.php?error=invalid_token');
    exit;
}
