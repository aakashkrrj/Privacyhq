<?php
// backend/api/profile/bootstrap.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/BaseController.php';

require_once __DIR__ . '/../../services/ProfileService.php';
require_once __DIR__ . '/../../controllers/ProfileController.php';

/** @var PDO $pdo */

$service = new \Backend\Services\ProfileService($pdo);
$controller = new \Backend\Controllers\ProfileController($service);
