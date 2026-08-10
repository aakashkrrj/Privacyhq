<?php
// backend/api/audit-logs/bootstrap.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/BaseController.php';

require_once __DIR__ . '/../../services/AuditLogService.php';
require_once __DIR__ . '/../../controllers/AuditLogController.php';

/** @var PDO $pdo */

$service = new \Backend\Services\AuditLogService($pdo);
$controller = new \Backend\Controllers\AuditLogController($service);
