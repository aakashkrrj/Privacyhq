<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/CookieGovernance.php';
require_once __DIR__ . '/../../controllers/CookieGovernanceController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\CookieGovernance();
$controller = new \Backend\Controllers\CookieGovernanceController($model);

$controller->index();
