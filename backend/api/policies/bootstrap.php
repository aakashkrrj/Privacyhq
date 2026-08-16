<?php
// governance/backend/api/policies/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/Policy.php';
require_once __DIR__ . '/../../services/PolicyService.php';
require_once __DIR__ . '/../../controllers/PolicyController.php';

$policyModel = new \Backend\Models\Policy($pdo);
$policyService = new \Backend\Services\PolicyService($pdo, $policyModel);
$controller = new \Backend\Controllers\PolicyController($policyService);
