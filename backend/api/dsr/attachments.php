<?php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/DataRequest.php';
require_once __DIR__ . '/../../models/DataSubject.php';
require_once __DIR__ . '/../../models/RequestHistory.php';
require_once __DIR__ . '/../../services/DsrService.php';
require_once __DIR__ . '/../../controllers/DsrController.php';

ApiBootstrap::requireMethod('POST');
ApiBootstrap::requireCsrf();

$dsrModel = new \Backend\Models\DataRequest($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$historyModel = new \Backend\Models\RequestHistory($pdo);

$dsrService = new \Backend\Services\DsrService($pdo, $dsrModel, $subjectModel, $historyModel);
$controller = new \Backend\Controllers\DsrController($dsrService);

$action = $_GET['action'] ?? 'upload';
if ($action === 'delete') {
    $controller->deleteAttachment();
} else {
    $controller->uploadAttachment();
}
