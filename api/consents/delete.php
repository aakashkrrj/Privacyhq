<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/ConsentService.php';
requireLogin(true);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($input['id']) ? (int)$input['id'] : 0);
    if (!$id) throw new Exception("Invalid ID");

    $service = new ConsentService();
    $service->deleteConsent($id, $_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Consent deleted successfully.',
        'data' => null
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}