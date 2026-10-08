<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/GrcModel.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';

\Backend\Core\ApiBootstrap::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ($method === 'POST' ? 'create' : 'list');
$grcModel = new \Backend\Models\GrcModel($pdo);
$userId = $_SESSION['user_id'] ?? 0;

if ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    if ($action === 'create') {
        $id = $grcModel->createFinding($_POST, $userId);
        echo json_encode(['status' => 'success', 'data' => ['id' => $id]]);
        exit;
    }
}
if ($method === 'GET' && $action === 'list') {
    $stmt = $pdo->query("SELECT * FROM grc_findings WHERE deleted_at IS NULL");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}
http_response_code(400); echo json_encode(['error' => 'Invalid action']);
