<?php
// backend/api/notifications/bootstrap.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/BaseController.php';

require_once __DIR__ . '/../../services/NotificationService.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

/** @var PDO $pdo */

$service = new \Backend\Services\NotificationService($pdo);
$controller = new \Backend\Controllers\NotificationController($service);
