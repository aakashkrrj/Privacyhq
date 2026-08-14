<?php
// backend/api/data-discovery/dashboard.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->getDashboard();
