<?php
// backend/api/data-discovery/history.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->listScanHistory();
