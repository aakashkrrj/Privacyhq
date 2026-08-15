<?php
// governance/backend/api/vendor-risk/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/VendorRisk.php';
require_once __DIR__ . '/../../services/VendorRiskService.php';
require_once __DIR__ . '/../../controllers/VendorRiskController.php';

$vendorRiskModel = new \Backend\Models\VendorRisk($pdo);
$vendorRiskService = new \Backend\Services\VendorRiskService($pdo, $vendorRiskModel);
$controller = new \Backend\Controllers\VendorRiskController($vendorRiskService);
