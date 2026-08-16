<?php
// governance/backend/api/risk-register/create.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
\Backend\Core\ApiBootstrap::requireCsrf();
$controller->create();
