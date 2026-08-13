<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/Consent.php';
require_once __DIR__ . '/../../models/DataSubject.php';
require_once __DIR__ . '/../../models/ConsentPurpose.php';
require_once __DIR__ . '/../../models/ConsentHistory.php';
require_once __DIR__ . '/../../services/ConsentService.php';
require_once __DIR__ . '/../../services/ScannerAbstraction.php';
require_once __DIR__ . '/../../services/CookieGovernanceService.php';
require_once __DIR__ . '/../../controllers/CookieGovernanceController.php';

ApiBootstrap::requireMethod('POST');

$consentModel = new \Backend\Models\Consent($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$purposeModel = new \Backend\Models\ConsentPurpose($pdo);
$historyModel = new \Backend\Models\ConsentHistory($pdo);
$consentService = new \Backend\Services\ConsentService($pdo, $consentModel, $subjectModel, $purposeModel, $historyModel);

$service = new \Backend\Services\CookieGovernanceService($pdo, $consentService);
$controller = new \Backend\Controllers\CookieGovernanceController($service);

$controller->saveConsentPreferences();
