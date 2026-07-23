<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/ConsentService.php';
requireLogin(true);

try {
    $service = new ConsentService();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';

    $filters = [
        'status' => $_GET['status'] ?? null,
        'purpose_id' => $_GET['purpose_id'] ?? null,
        'created_date' => $_GET['created_date'] ?? null,
        'expiry_date' => $_GET['expiry_date'] ?? null,
        'keyword' => $_GET['keyword'] ?? null,
    ];

    $data = $service->getConsents($filters, $page, $limit, $sort, $order);
    
    echo json_encode([
        'success' => true,
        'message' => 'Consents retrieved successfully.',
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}