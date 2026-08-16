<?php
// governance/backend/api/risk-register/dashboard.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->dashboard();
