<?php
// backend/api/tasks/complete.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->complete();
