<?php
// governance/backend/api/audit-logs/get.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->get();
