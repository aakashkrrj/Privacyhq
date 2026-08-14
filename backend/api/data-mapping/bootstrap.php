<?php
// backend/api/data-mapping/bootstrap.php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/DataMapping.php';
require_once __DIR__ . '/../../services/DataMappingService.php';
require_once __DIR__ . '/../../controllers/DataMappingController.php';

/** @var PDO $pdo */
$model = new \Backend\Models\DataMapping($pdo);
$service = new \Backend\Services\DataMappingService($pdo, $model);
$controller = new \Backend\Controllers\DataMappingController($service);
$controller->setPdo($pdo);
