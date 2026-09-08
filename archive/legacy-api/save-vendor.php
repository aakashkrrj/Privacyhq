<?php
header('Content-Type: application/json');

// Database Connection
require_once "../includes/db.php";
require_permission('manage_vendors');

require_once __DIR__ . '/../backend/models/Vendor.php';
require_once __DIR__ . '/../backend/models/VendorAssessment.php';
require_once __DIR__ . '/../backend/services/VendorService.php';
require_once __DIR__ . '/../backend/services/WorkflowService.php';
require_once __DIR__ . '/../backend/services/TaskService.php';
require_once __DIR__ . '/../backend/services/NotificationService.php';
require_once __DIR__ . '/../backend/services/ActivityService.php';

// Check Request Method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed.']);
        exit;
    }

    $vendor_name  = trim($_POST['vendor_name'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $data_shared  = trim($_POST['data_shared'] ?? '');

    if (empty($vendor_name) || empty($service_type) || empty($data_shared)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please fill in all required fields.'
        ]);
        exit();
    }

    try {
        $vendorModel = new \Backend\Models\Vendor($pdo);
        $vaModel = new \Backend\Models\VendorAssessment($pdo);
        $vendorService = new \Backend\Services\VendorService($pdo, $vendorModel, $vaModel);

        $userId = $_SESSION['user_id'] ?? 1;
        $vendorService->createVendor($vendor_name, $service_type, 'Pending', 'Low', $userId);

        echo json_encode([
            'status' => 'success',
            'message' => 'Vendor onboarded successfully!'
        ]);
    } catch (\Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}