<?php
// api/vendor-crud.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_permission('manage_vendors');

require_once __DIR__ . '/../backend/models/Vendor.php';
require_once __DIR__ . '/../backend/models/VendorAssessment.php';
require_once __DIR__ . '/../backend/services/VendorService.php';
require_once __DIR__ . '/../backend/services/WorkflowService.php';
require_once __DIR__ . '/../backend/services/TaskService.php';
require_once __DIR__ . '/../backend/services/NotificationService.php';
require_once __DIR__ . '/../backend/services/ActivityService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 1. CSRF Validation
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Action is required.']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 1;

try {
    $vendorModel = new \Backend\Models\Vendor($pdo);
    $vaModel = new \Backend\Models\VendorAssessment($pdo);
    $vendorService = new \Backend\Services\VendorService($pdo, $vendorModel, $vaModel);

    if ($action === 'create') {
        $vendor_name = trim($_POST['vendor_name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $dpa_status  = trim($_POST['dpa_status'] ?? 'Pending');
        $risk_level  = trim($_POST['risk_level'] ?? 'Low');
        
        $vendorService->createVendor($vendor_name, $category, $dpa_status, $risk_level, $user_id);
        echo json_encode(['status' => 'success', 'message' => 'Vendor created successfully.']);
        
    } elseif ($action === 'update') {
        $vendor_id   = filter_var($_POST['vendor_id'] ?? 0, FILTER_VALIDATE_INT);
        $vendor_name = trim($_POST['vendor_name'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $dpa_status  = trim($_POST['dpa_status'] ?? 'Pending');
        $risk_level  = trim($_POST['risk_level'] ?? 'Low');

        $vendorService->updateVendor($vendor_id, $vendor_name, $category, $dpa_status, $risk_level, $user_id);
        echo json_encode(['status' => 'success', 'message' => 'Vendor updated successfully.']);

    } elseif ($action === 'delete') {
        $vendor_id = filter_var($_POST['vendor_id'] ?? 0, FILTER_VALIDATE_INT);
        
        $vendorService->deleteVendor($vendor_id, $user_id);
        echo json_encode(['status' => 'success', 'message' => 'Vendor deleted successfully.']);
        
    } else {
        throw new Exception("Unknown action.");
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>
