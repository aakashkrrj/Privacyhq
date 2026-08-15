<?php
// governance/backend/api/vendor-risk/dashboard.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->dashboard();
