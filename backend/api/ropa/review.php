<?php
// backend/api/ropa/review.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Ropa.php';
require_once __DIR__ . '/../../../backend/services/RopaService.php';
require_once __DIR__ . '/../../../backend/controllers/RopaController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\Ropa($pdo);
$service = new \Backend\Services\RopaService($pdo, $model);
$controller = new \Backend\Controllers\RopaController($service);
$controller->getIncomplete();
