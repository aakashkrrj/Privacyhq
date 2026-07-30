<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/RiskRegister.php';
require_once __DIR__ . '/../../../backend/services/RiskRegisterService.php';
require_once __DIR__ . '/../../../backend/controllers/RiskRegisterController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\RiskRegister($pdo);
$service = new \Backend\Services\RiskRegisterService($pdo, $model);
$controller = new \Backend\Controllers\RiskRegisterController($service);
$controller->dashboard();
