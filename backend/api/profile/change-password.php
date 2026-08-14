<?php
// backend/api/profile/change-password.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->changePassword();
