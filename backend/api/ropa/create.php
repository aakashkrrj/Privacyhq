<?php
// governance/backend/api/ropa/create.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
\Backend\Core\ApiBootstrap::requireCsrf();
$controller->create();
