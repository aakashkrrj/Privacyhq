<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/CookieGovernance.php';
require_once __DIR__ . '/../../services/CookieGovernanceService.php';
require_once __DIR__ . '/../../controllers/CookieGovernanceController.php';

$model = new \Backend\Models\CookieGovernance($pdo);
$service = new \Backend\Services\CookieGovernanceService($pdo, $model);
$controller = new \Backend\Controllers\CookieGovernanceController($service);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? ($_POST['action'] ?? 'create') : 'list');

if ($method === 'POST') {
    ApiBootstrap::requireCsrf();
    if ($action === 'create') {
        $controller->createCategory();
    } else if ($action === 'update') {
        $controller->updateCategory();
    } else if ($action === 'delete') {
        $controller->deleteCategory();
    } else if ($action === 'reassign') {
        $controller->reassignCategory();
    }
} else {
    $controller->listCategories();
}
