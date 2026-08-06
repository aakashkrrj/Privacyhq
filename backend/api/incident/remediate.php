<?php
// backend/api/incident/remediate.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Incident.php';
require_once __DIR__ . '/../../../backend/services/IncidentService.php';
require_once __DIR__ . '/../../../backend/controllers/IncidentController.php';

ApiBootstrap::requireMethod('POST');
ApiBootstrap::requireCsrf();

$model = new \Backend\Models\Incident($pdo);
$service = new \Backend\Services\IncidentService($pdo, $model);
$controller = new \Backend\Controllers\IncidentController($service);
$controller->remediate();
