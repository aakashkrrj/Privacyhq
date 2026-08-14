<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/CookieGovernance.php';
require_once __DIR__ . '/../../services/CookieGovernanceService.php';
require_once __DIR__ . '/../../controllers/CookieGovernanceController.php';

$model = new \Backend\Models\CookieGovernance($pdo);
$service = new \Backend\Services\CookieGovernanceService($pdo, $model);
$controller = new \Backend\Controllers\CookieGovernanceController($service);

$controller->consent();
