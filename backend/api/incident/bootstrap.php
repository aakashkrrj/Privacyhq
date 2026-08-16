<?php
// governance/backend/api/incident/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/Incident.php';
require_once __DIR__ . '/../../services/IncidentService.php';
require_once __DIR__ . '/../../controllers/IncidentController.php';

$incidentModel = new \Backend\Models\Incident($pdo);
$incidentService = new \Backend\Services\IncidentService($pdo, $incidentModel);
$controller = new \Backend\Controllers\IncidentController($incidentService);
