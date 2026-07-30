<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/DataRequest.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/models/RequestHistory.php';
require_once __DIR__ . '/../../../backend/services/DsrService.php';
require_once __DIR__ . '/../../../backend/controllers/DsrController.php';

ApiBootstrap::requireMethod('GET');

$dsrModel = new \Backend\Models\DataRequest($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$historyModel = new \Backend\Models\RequestHistory($pdo);
$dsrService = new \Backend\Services\DsrService($pdo, $dsrModel, $subjectModel, $historyModel);
$controller = new \Backend\Controllers\DsrController($dsrService);
$controller->dashboard();
