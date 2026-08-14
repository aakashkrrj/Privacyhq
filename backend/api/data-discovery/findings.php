<?php
// backend/api/data-discovery/findings.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->listFindings();
