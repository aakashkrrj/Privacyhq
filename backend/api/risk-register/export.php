<?php
// backend/api/risk-register/export.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/RiskRegister.php';
require_once __DIR__ . '/../../../backend/services/RiskRegisterService.php';
require_once __DIR__ . '/../../../backend/controllers/RiskRegisterController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\RiskRegister($pdo);
$service = new \Backend\Services\RiskRegisterService($pdo, $model);

$data = $service->getList();
$items = $data['items'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="risk_register_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, ['ID', 'Risk Title', 'Category', 'Likelihood', 'Impact', 'Risk Level', 'Status', 'Mitigation Strategy']);

// Data rows
foreach ($items as $row) {
    fputcsv($output, [
        $row['id'],
        $row['title'],
        $row['category'],
        $row['likelihood'],
        $row['impact'],
        $row['risk_level'],
        $row['status'],
        $row['mitigation']
    ]);
}

fclose($output);
exit();
