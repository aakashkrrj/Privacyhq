<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/DataAsset.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../includes/functions.php';

\Backend\Core\ApiBootstrap::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ($method === 'POST' ? 'create' : 'list');
$assetModel = new \Backend\Models\DataAsset($pdo);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$userId = $_SESSION['user_id'];

if ($method === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    
    if ($action === 'create') {
        $id = $assetModel->createAsset($_POST, $userId);
        echo json_encode(['status' => 'success', 'data' => ['id' => $id]]);
        exit;
    }
    
    if ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $assetModel->updateAsset($id, $_POST);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'link_ropa') {
        $assetId = $_POST['asset_id'] ?? 0;
        $ropaId = $_POST['ropa_id'] ?? 0;
        $assetModel->linkRopa($assetId, $ropaId);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'link_category') {
        $assetId = $_POST['asset_id'] ?? 0;
        $cat = $_POST['category_name'] ?? '';
        $assetModel->linkCategory($assetId, $cat);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'link_risk') {
        $assetId = $_POST['asset_id'] ?? 0;
        $riskId = $_POST['risk_id'] ?? 0;
        $assetModel->linkRisk($assetId, $riskId);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'import') {
        $json = $_POST['assets_json'] ?? '[]';
        $assets = json_decode($json, true) ?? [];
        $res = $assetModel->importAssets($assets, $userId);
        echo json_encode(['status' => 'success', 'data' => $res]);
        exit;
    }
}

if ($method === 'GET') {
    if ($action === 'list') {
        $search = $_GET['search'] ?? '';
        $classification = $_GET['classification'] ?? '';
        $type = $_GET['asset_type'] ?? '';

        $sql = "SELECT * FROM data_assets WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (asset_name LIKE ? OR owner LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($classification)) {
            $sql .= " AND classification = ?";
            $params[] = $classification;
        }
        if (!empty($type)) {
            $sql .= " AND asset_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }
    
    if ($action === 'get') {
        $id = $_GET['id'] ?? 0;
        $asset = $assetModel->getAsset($id);
        if ($asset) {
            echo json_encode(['status' => 'success', 'data' => $asset]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Not found']);
        }
        exit;
    }

    if ($action === 'metrics') {
        echo json_encode(['status' => 'success', 'data' => $assetModel->getDashboardMetrics()]);
        exit;
    }
}

http_response_code(400); 
echo json_encode(['error' => 'Invalid action']);
