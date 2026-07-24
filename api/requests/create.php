<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/RequestService.php';
requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['data_subject_id']) || empty($data['request_type']) || empty($data['due_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $service = new RequestService();
    $userId = $_SESSION['user_id'] ?? 1; // Fallback to 1 if testing
    $id = $service->createRequest($data, $userId);

    echo json_encode(['success' => true, 'data' => ['id' => $id]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
}
