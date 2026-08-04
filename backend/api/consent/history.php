<?php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Consent.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/models/ConsentPurpose.php';
require_once __DIR__ . '/../../../backend/models/ConsentHistory.php';
require_once __DIR__ . '/../../../backend/services/ConsentService.php';
require_once __DIR__ . '/../../../backend/controllers/ConsentController.php';

ApiBootstrap::requireMethod('GET');

$consentModel = new \Backend\Models\Consent($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$purposeModel = new \Backend\Models\ConsentPurpose($pdo);
$historyModel = new \Backend\Models\ConsentHistory($pdo);
$consentService = new \Backend\Services\ConsentService($pdo, $consentModel, $subjectModel, $purposeModel, $historyModel);
$controller = new \Backend\Controllers\ConsentController($consentService);
$controller->history();
