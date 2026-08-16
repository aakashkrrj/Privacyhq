<?php
// governance/backend/api/incident/escalate.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
\Backend\Core\ApiBootstrap::requireCsrf();
$controller->escalate();
