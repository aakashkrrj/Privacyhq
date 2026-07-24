<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/RequestService.php';
requireLogin(true);

try {
    $service = new RequestService();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';

    $filters = [
        'status' => $_GET['status'] ?? null,
        'request_type' => $_GET['request_type'] ?? null,
        'priority' => $_GET['priority'] ?? null,
        'keyword' => $_GET['q'] ?? null
    ];
    $filters = array_filter($filters);

    $total = $service->countRequests($filters);
    $data = $service->getRequests($filters, $page, $limit, $sort, $order);

    echo json_encode([
        'success' => true,
        'data' => [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
}
