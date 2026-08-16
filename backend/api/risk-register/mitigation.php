<?php
// governance/backend/api/risk-register/mitigation.php
require_once __DIR__ . '/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    \Backend\Core\ApiBootstrap::requireCsrf();
    $controller->saveMitigation();
} else {
    \Backend\Core\ApiBootstrap::requireMethod('GET');
    $controller->get();
}
