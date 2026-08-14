<?php
// backend/api/data-discovery/bootstrap.php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/DataDiscovery.php';
require_once __DIR__ . '/../../services/DataDiscoveryService.php';
require_once __DIR__ . '/../../controllers/DataDiscoveryController.php';

/** @var PDO $pdo */
$model = new \Backend\Models\DataDiscovery($pdo);
$service = new \Backend\Services\DataDiscoveryService($pdo, $model);
$controller = new \Backend\Controllers\DataDiscoveryController($service);
$controller->setPdo($pdo);
