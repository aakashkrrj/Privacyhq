<?php
// governance/backend/api/audit-logs/retention.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $controller->getRetention();
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $controller->saveRetention();
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
