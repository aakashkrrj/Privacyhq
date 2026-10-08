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
        $id = $grcModel->createFramework($_POST, $userId);
        echo json_encode(['status' => 'success', 'data' => ['id' => $id]]);
        exit;
    }
    
    if ($action === 'assess_requirement') {
        $reqId = $_POST['requirement_id'] ?? 0;
        $status = $_POST['assessment_status'] ?? 'Not Assessed';
        $notes = $_POST['notes'] ?? '';
        $grcModel->assessRequirement($reqId, $status, $notes);
        echo json_encode(['status' => 'success']);
        exit;
    }
    
    if ($action === 'map_control') {
        $reqId = $_POST['requirement_id'] ?? 0;
        $controlId = $_POST['control_id'] ?? 0;
        $success = $grcModel->mapRequirementToControl($reqId, $controlId);
        echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $success ? 'Mapped' : 'Mapping failed or duplicate']);
        exit;
    }
}

if ($method === 'GET') {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT * FROM grc_frameworks WHERE deleted_at IS NULL");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
    
    if ($action === 'get') {
        $id = $_GET['id'] ?? 0;
        $fw = $grcModel->getFramework($id);
        if ($fw) {
            echo json_encode(['status' => 'success', 'data' => $fw]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
        }
        exit;
    }
}

http_response_code(400); 
echo json_encode(['error' => 'Invalid action']);
