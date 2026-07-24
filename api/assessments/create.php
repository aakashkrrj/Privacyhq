<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/AssessmentService.php';
requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['processing_activity_id']) || empty($data['template_id']) || empty($data['status_id']) || empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    $service = new AssessmentService();
    $userId = $_SESSION['user_id'] ?? 1; // Fallback for testing
    $id = $service->createAssessment($data, $userId);

    echo json_encode(['success' => true, 'data' => ['id' => $id]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
}
