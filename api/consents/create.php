<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/ConsentService.php';
requireLogin(true);

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $service = new ConsentService();
    
    $data = $service->createConsent($input, $_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Consent created successfully.',
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}