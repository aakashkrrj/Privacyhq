<?php
// backend/api/data-mapping/flows.php
require_once __DIR__ . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'delete') {
        $controller->deleteFlow();
    } else {
        $controller->createFlow();
    }
} else {
    $controller->listFlows();
}
