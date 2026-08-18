<?php
// governance/backend/api/settings/team-permissions.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $controller->teamPermissions();
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $controller->saveTeamPermissions();
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
