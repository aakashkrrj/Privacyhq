<?php
// backend/api/audit-logs/list.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->listLogs();
