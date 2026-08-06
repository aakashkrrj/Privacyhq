<?php
// backend/api/dsr/export.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/DataRequest.php';
require_once __DIR__ . '/../../../backend/models/RequestHistory.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/services/DsrService.php';

ApiBootstrap::requireMethod('GET');

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type = trim($_GET['type'] ?? '');

$dsrModel = new \Backend\Models\DataRequest($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$historyModel = new \Backend\Models\RequestHistory($pdo);
$service = new \Backend\Services\DsrService($pdo, $dsrModel, $subjectModel, $historyModel);

// Query with large page size to fetch all records matching filters
$data = $service->getList($search, $status, $type, 1, 1000000);
$items = $data['items'] ?? [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="dsr_register_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'ID', 
    'Request Code', 
    'Subject Email', 
    'Request Type', 
    'Status', 
    'Priority', 
    'Due Date', 
    'Created At'
]);

// Rows
foreach ($items as $row) {
    fputcsv($output, [
        $row['id'],
        $row['request_id_code'],
        $row['subject_email'],
        $row['request_type'],
        $row['status'],
        $row['priority'],
        $row['due_date'],
        $row['created_at']
    ]);
}

fclose($output);
exit();
