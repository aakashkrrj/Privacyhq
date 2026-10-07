<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/GrcModel.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';

\Backend\Core\ApiBootstrap::enforceAuth();

$grcModel = new \Backend\Models\GrcModel($pdo);
$metrics = $grcModel->getDashboardMetrics();

echo json_encode(['status' => 'success', 'data' => $metrics]);
