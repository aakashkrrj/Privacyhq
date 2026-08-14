<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Vendor.php';
require_once __DIR__ . '/../../../backend/models/VendorAssessment.php';
require_once __DIR__ . '/../../../backend/services/VendorService.php';
require_once __DIR__ . '/../../../backend/services/WorkflowService.php';
require_once __DIR__ . '/../../../backend/services/TaskService.php';
require_once __DIR__ . '/../../../backend/services/NotificationService.php';
require_once __DIR__ . '/../../../backend/services/ActivityService.php';
require_once __DIR__ . '/../../../backend/controllers/VendorController.php';

ApiBootstrap::requireMethod('POST');
ApiBootstrap::requireCsrf();

$vendorModel = new \Backend\Models\Vendor($pdo);
$assessmentModel = new \Backend\Models\VendorAssessment($pdo);
$vendorService = new \Backend\Services\VendorService($pdo, $vendorModel, $assessmentModel);
$controller = new \Backend\Controllers\VendorController($vendorService);

$controller->create();
