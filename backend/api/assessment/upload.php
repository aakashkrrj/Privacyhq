<?php
// backend/api/assessment/upload.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->uploadEvidence();
