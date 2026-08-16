<?php
// governance/backend/api/ropa/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/Ropa.php';
require_once __DIR__ . '/../../services/RopaService.php';
require_once __DIR__ . '/../../controllers/RopaController.php';

$ropaModel = new \Backend\Models\Ropa($pdo);
$ropaService = new \Backend\Services\RopaService($pdo, $ropaModel);
$controller = new \Backend\Controllers\RopaController($ropaService);
