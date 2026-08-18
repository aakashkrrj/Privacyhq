<?php
// governance/backend/api/reports/schedules.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $controller->listSchedules();
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $action = $_POST['action'] ?? 'save';
    if ($action === 'toggle') {
        $controller->toggleSchedule();
    } elseif ($action === 'delete') {
        $controller->deleteSchedule();
    } else {
        $controller->saveSchedule();
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
