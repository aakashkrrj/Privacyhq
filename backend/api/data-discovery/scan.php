<?php
// backend/api/data-discovery/scan.php
require_once __DIR__ . '/bootstrap.php';
\Backend\Core\ApiBootstrap::requireMethod('POST');
$action = $_POST['action'] ?? 'trigger';

if ($action === 'trigger' || $action === 'start') {
    $controller->triggerScan();
} else {
    $controller->controlScan();
}
