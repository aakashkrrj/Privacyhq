<?php
// governance/backend/api/risk-register/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/RiskRegister.php';
require_once __DIR__ . '/../../services/RiskRegisterService.php';
require_once __DIR__ . '/../../controllers/RiskRegisterController.php';

$riskModel = new \Backend\Models\RiskRegister($pdo);
$riskService = new \Backend\Services\RiskRegisterService($pdo, $riskModel);
$controller = new \Backend\Controllers\RiskRegisterController($riskService);
