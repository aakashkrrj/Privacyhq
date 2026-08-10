<?php
// backend/api/notifications/mark-read.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->markAsRead();
