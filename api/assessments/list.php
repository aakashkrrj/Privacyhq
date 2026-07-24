<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../backend/services/AssessmentService.php';
requireLogin(true);

try {
    $service = new AssessmentService();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $sort = $_GET['sort'] ?? 'due_date';
    $order = $_GET['order'] ?? 'ASC';

    $filters = [
        'status_id' => $_GET['status_id'] ?? null,
        'keyword' => $_GET['q'] ?? null
    ];
    $filters = array_filter($filters);

    $total = $service->countAssessments($filters);
    $data = $service->getAssessments($filters, $page, $limit, $sort, $order);

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
