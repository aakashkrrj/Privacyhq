<?php
// governance/backend/api/vendor-risk/get.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->getAssessment();
