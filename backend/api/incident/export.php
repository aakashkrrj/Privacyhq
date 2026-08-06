<?php
// backend/api/incident/export.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Incident.php';
require_once __DIR__ . '/../../../backend/services/IncidentService.php';

ApiBootstrap::requireMethod('GET');

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$severity = trim($_GET['severity'] ?? '');

$model = new \Backend\Models\Incident($pdo);
$service = new \Backend\Services\IncidentService($pdo, $model);

// Query with large page size to fetch all records matching filters
$data = $service->getList($search, $status, $severity, 1, 1000000);
$items = $data['items'] ?? [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="incident_register_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'ID', 
    'Summary', 
    'Severity', 
    'Status', 
    'Impacted Records', 
    'Created At', 
    'Resolved At'
]);

// Rows
foreach ($items as $row) {
    fputcsv($output, [
        $row['id'],
        $row['summary'],
        $row['severity'],
        $row['status'],
        $row['impacted_records'],
        $row['created_at'],
        $row['resolved_at']
    ]);
}

fclose($output);
exit();
