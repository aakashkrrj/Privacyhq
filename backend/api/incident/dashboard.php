<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Incident.php';
require_once __DIR__ . '/../../../backend/services/IncidentService.php';
require_once __DIR__ . '/../../../backend/controllers/IncidentController.php';

ApiBootstrap::requireMethod('GET');

$incidentModel = new \Backend\Models\Incident($pdo);
$incidentService = new \Backend\Services\IncidentService($pdo, $incidentModel);
$controller = new \Backend\Controllers\IncidentController($incidentService);
$controller->dashboard();
