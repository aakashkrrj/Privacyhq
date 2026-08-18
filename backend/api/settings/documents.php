<?php
// governance/backend/api/settings/documents.php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'download') {
        $controller->downloadDocument();
    } else {
        $controller->listDocuments();
    }
} elseif ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $action = $_POST['action'] ?? 'upload';
    if ($action === 'delete') {
        $controller->deleteDocument();
    } else {
        $controller->uploadDocument();
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
