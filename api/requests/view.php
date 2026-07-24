<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/RequestService.php';
requireLogin(true);

try {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing ID']);
        exit;
    }

    $id = (int)$_GET['id'];
    $service = new RequestService();
    $data = $service->getRequestById($id);

    if (!$data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not found']);
        exit;
    }

    $history = $service->getRequestHistory($id);

    echo json_encode([
        'success' => true,
        'data' => [
            'request' => $data,
            'history' => $history
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
}
