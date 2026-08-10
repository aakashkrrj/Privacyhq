<?php
// backend/api/notifications/delete.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->delete();
