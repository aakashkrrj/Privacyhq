<?php
// backend/api/tasks/list.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('GET');
$controller->listMyTasks();
