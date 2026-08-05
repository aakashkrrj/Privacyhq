<?php
// backend/api/ropa/export.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Ropa.php';
require_once __DIR__ . '/../../../backend/services/RopaService.php';
require_once __DIR__ . '/../../../backend/controllers/RopaController.php';

ApiBootstrap::requireMethod('GET');

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$model = new \Backend\Models\Ropa($pdo);
$service = new \Backend\Services\RopaService($pdo, $model);

// Query with large page size to fetch all records matching filters
$data = $service->getList($search, $status, 1, 1000000);
$items = $data['items'] ?? [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ropa_register_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'ID', 
    'Activity Name', 
    'Purpose', 
    'Department', 
    'Data Controller', 
    'Data Categories', 
    'Data Subjects', 
    'Recipients', 
    'Retention Period', 
    'Status'
]);

// Rows
foreach ($items as $row) {
    fputcsv($output, [
        $row['id'],
        $row['activity_name'],
        $row['purpose'],
        $row['department'],
        $row['data_controller'],
        $row['data_categories'],
        $row['data_subjects'],
        $row['recipients'],
        $row['retention_period'],
        $row['status']
    ]);
}

fclose($output);
exit();
