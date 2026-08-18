<?php
// governance/backend/api/settings/2fa.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'status';
    if ($action === 'setup') {
        $controller->setup2fa();
    } else {
        $controller->get2faStatus();
    }
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $action = $_POST['action'] ?? 'enable';
    if ($action === 'disable') {
        $controller->disable2fa();
    } else {
        $controller->enable2fa();
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
