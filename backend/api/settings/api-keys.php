<?php
// governance/backend/api/settings/api-keys.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $controller->listApiKeys();
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $action = $_POST['action'] ?? 'create';
    if ($action === 'revoke') {
        $controller->revokeApiKey();
    } else {
        $controller->createApiKey();
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
