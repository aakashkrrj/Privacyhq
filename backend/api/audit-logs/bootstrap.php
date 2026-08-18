<?php
// governance/backend/api/audit-logs/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/AuditLog.php';
require_once __DIR__ . '/../../services/AuditLogService.php';
require_once __DIR__ . '/../../controllers/AuditLogController.php';

$model = new \Backend\Models\AuditLog($pdo);
$service = new \Backend\Services\AuditLogService($pdo, $model);
$controller = new \Backend\Controllers\AuditLogController($service);
