<?php
// backend/api/assessment/bootstrap.php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../core/ApiResponse.php';
require_once __DIR__ . '/../../core/BaseController.php';

require_once __DIR__ . '/../../models/PrivacyAssessment.php';
require_once __DIR__ . '/../../services/AssessmentService.php';
require_once __DIR__ . '/../../controllers/AssessmentController.php';

/** @var PDO $pdo */

$model = new \Backend\Models\PrivacyAssessment($pdo);
$service = new \Backend\Services\AssessmentService($model, $pdo);
$controller = new \Backend\Controllers\AssessmentController($service);
$controller->setPdo($pdo);
