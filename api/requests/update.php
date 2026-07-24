<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/RequestService.php';
requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing ID']);
        exit;
    }

    $service = new RequestService();
    $userId = $_SESSION['user_id'] ?? 1;
    $service->updateRequest((int)$data['id'], $data, $userId);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($e->getMessage() === 'Request not found') {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Not found']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
    }
}
