<?php
// governance/backend/api/vendor-risk/save.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->saveAssessment();
