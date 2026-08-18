<?php
// governance/backend/api/users/bootstrap.php
use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../services/UserService.php';
require_once __DIR__ . '/../../controllers/UserController.php';

$model = new \Backend\Models\User($pdo);
$service = new \Backend\Services\UserService($pdo, $model);
$controller = new \Backend\Controllers\UserController($service);
