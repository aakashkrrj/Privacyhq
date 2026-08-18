<?php
// governance/backend/api/settings/notifications.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $controller->notificationPreferences();
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $controller->updateNotificationPreferences();
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
