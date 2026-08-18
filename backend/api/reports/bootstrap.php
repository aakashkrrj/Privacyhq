<?php
// governance/backend/api/reports/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/ReportSummary.php';
require_once __DIR__ . '/../../services/ReportService.php';
require_once __DIR__ . '/../../controllers/ReportController.php';

$model = new \Backend\Models\ReportSummary($pdo);
$service = new \Backend\Services\ReportService($pdo, $model);
$controller = new \Backend\Controllers\ReportController($service);
