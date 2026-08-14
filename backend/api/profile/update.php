<?php
// backend/api/profile/update.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$controller->updateProfile();
