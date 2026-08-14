<?php
// backend/api/data-discovery/sources.php
require_once __DIR__ . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'update') {
        $controller->updateSource();
    } else if ($action === 'delete') {
        $controller->deleteSource();
    } else {
        $controller->createSource();
    }
} else {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $controller->getSource();
    } else {
        $controller->listSources();
    }
}
