<?php
// backend/api/dsr/assign.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/DataRequest.php';
require_once __DIR__ . '/../../../backend/models/RequestHistory.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/services/DsrService.php';
require_once __DIR__ . '/../../../backend/controllers/DsrController.php';

ApiBootstrap::requireMethod('POST');
ApiBootstrap::requireCsrf();

$dsrModel = new \Backend\Models\DataRequest($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$historyModel = new \Backend\Models\RequestHistory($pdo);
$service = new \Backend\Services\DsrService($pdo, $dsrModel, $subjectModel, $historyModel);
$controller = new \Backend\Controllers\DsrController($service);
$controller->assign();
