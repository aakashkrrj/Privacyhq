<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../services/ScannerAbstraction.php';
require_once __DIR__ . '/../../services/CookieGovernanceService.php';
require_once __DIR__ . '/../../controllers/CookieGovernanceController.php';

ApiBootstrap::requireMethod('GET');

$service = new \Backend\Services\CookieGovernanceService($pdo);
$controller = new \Backend\Controllers\CookieGovernanceController($service);

$controller->listDomains();
