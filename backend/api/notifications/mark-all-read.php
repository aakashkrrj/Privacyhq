<?php
// backend/api/notifications/mark-all-read.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->markAllAsRead();
