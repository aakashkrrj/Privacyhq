<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Vendor.php';
require_once __DIR__ . '/../../../backend/models/VendorAssessment.php';
require_once __DIR__ . '/../../../backend/services/VendorService.php';
require_once __DIR__ . '/../../../backend/controllers/VendorController.php';

ApiBootstrap::requireMethod('GET');

$vendorModel = new \Backend\Models\Vendor($pdo);
$assessmentModel = new \Backend\Models\VendorAssessment($pdo);
$vendorService = new \Backend\Services\VendorService($pdo, $vendorModel, $assessmentModel);
$controller = new \Backend\Controllers\VendorController($vendorService);

$controller->listVendors();
