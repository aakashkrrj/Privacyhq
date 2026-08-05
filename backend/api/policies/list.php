<?php
// backend/api/policies/list.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Policy.php';
require_once __DIR__ . '/../../../backend/services/PolicyService.php';
require_once __DIR__ . '/../../../backend/controllers/PolicyController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\Policy($pdo);
$service = new \Backend\Services\PolicyService($pdo, $model);
$controller = new \Backend\Controllers\PolicyController($service);
$controller->listRecords();
