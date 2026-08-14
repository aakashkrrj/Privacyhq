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
        $controller->createCookie();
    } else if ($action === 'update') {
        $controller->updateCookie();
    } else if ($action === 'delete') {
        $controller->deleteCookie();
    }
} else {
    $controller->listCookies();
}
