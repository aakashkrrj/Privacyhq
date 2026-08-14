<?php
// backend/api/tasks/bootstrap.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/BaseController.php';

require_once __DIR__ . '/../../services/TaskService.php';
require_once __DIR__ . '/../../services/WorkflowService.php';
require_once __DIR__ . '/../../services/NotificationService.php';
require_once __DIR__ . '/../../services/ActivityService.php';
require_once __DIR__ . '/../../controllers/TaskController.php';

/** @var PDO $pdo */

$service = new \Backend\Services\TaskService($pdo);
$controller = new \Backend\Controllers\TaskController($service);
