<?php
// governance/backend/api/incident/timeline.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->timeline();
