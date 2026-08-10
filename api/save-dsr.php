<?php
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_permission('manage_dsr');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../backend/models/DataRequest.php';
require_once __DIR__ . '/../backend/models/DataSubject.php';
require_once __DIR__ . '/../backend/models/RequestHistory.php';
require_once __DIR__ . '/../backend/services/DsrService.php';
require_once __DIR__ . '/../backend/services/WorkflowService.php';
require_once __DIR__ . '/../backend/services/TaskService.php';
require_once __DIR__ . '/../backend/services/NotificationService.php';
require_once __DIR__ . '/../backend/services/ActivityService.php';

$subject_email = trim($_POST['subject_email'] ?? '');
$request_type = trim($_POST['request_type'] ?? 'Access');

if (empty($subject_email)) {
    echo json_encode(['status' => 'error', 'message' => 'Subject email is required.']);
    exit;
}

// Map 'Access' to valid enum 'access'
$req_type_map = ['Access' => 'access', 'Erasure' => 'erasure', 'Rectification' => 'rectification', 'Portability' => 'portability', 'Objection' => 'objection'];
$request_type_mapped = $req_type_map[$request_type] ?? 'access';

try {
    $dsrModel = new \Backend\Models\DataRequest($pdo);
    $subjectModel = new \Backend\Models\DataSubject($pdo);
    $historyModel = new \Backend\Models\RequestHistory($pdo);
    $dsrService = new \Backend\Services\DsrService($pdo, $dsrModel, $subjectModel, $historyModel);

    $userId = $_SESSION['user_id'] ?? 1;
    $requestId = $dsrService->createRequest($subject_email, 'customer', $request_type_mapped, 'Medium', $userId);

    echo json_encode(['status' => 'success', 'message' => 'DSR logged successfully.']);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}