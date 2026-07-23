<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/ConsentService.php';
requireLogin(true);

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) throw new Exception("Invalid ID");

    $service = new ConsentService();
    $data = $service->getConsentHistory($id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Consent history retrieved successfully.',
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}