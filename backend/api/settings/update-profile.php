<?php

use Backend\Core\ApiBootstrap;

require_once __DIR__ . '/../../core/ApiBootstrap.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../services/SettingsService.php';
require_once __DIR__ . '/../../controllers/SettingsController.php';

ApiBootstrap::requireMethod('POST');
ApiBootstrap::requireCsrf();

$userModel = new \Backend\Models\User($pdo);

$settingsService = new \Backend\Services\SettingsService(
    $pdo,
    $userModel
);

$controller = new \Backend\Controllers\SettingsController(
    $settingsService
);

$controller->updateProfile();