<?php
// governance/backend/api/vendor-risk/export.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->export();
