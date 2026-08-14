<?php
// backend/api/data-mapping/diagram.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->getTopology();
