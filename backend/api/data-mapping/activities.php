<?php
// backend/api/data-mapping/activities.php
require_once __DIR__ . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'update') {
        $controller->updateActivity();
    } else if ($action === 'delete') {
        $controller->deleteActivity();
    } else {
        $controller->createActivity();
    }
} else {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $controller->getActivity();
    } else {
        $controller->listActivities();
    }
}
