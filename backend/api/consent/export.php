<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Consent.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/models/ConsentPurpose.php';
require_once __DIR__ . '/../../../backend/models/ConsentHistory.php';
require_once __DIR__ . '/../../../backend/services/ConsentService.php';

ApiBootstrap::requireMethod('GET');

$consentModel = new \Backend\Models\Consent($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$purposeModel = new \Backend\Models\ConsentPurpose($pdo);
$historyModel = new \Backend\Models\ConsentHistory($pdo);
$consentService = new \Backend\Services\ConsentService($pdo, $consentModel, $subjectModel, $purposeModel, $historyModel);

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$data = $consentService->getExportList($search, $statusFilter, $categoryFilter);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=consent_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['ID', 'User Identifier', 'Category', 'Status', 'Source', 'Captured At']);

foreach ($data as $row) {
    $statusLabel = 'Granted';
    if ($row['status'] === 'withdrawn') {
        $statusLabel = 'Revoked';
    } elseif ($row['status'] === 'opt_out') {
        $statusLabel = 'Pending';
    }
    
    fputcsv($output, [
        $row['id'],
        $row['subject_email'],
        $row['category'],
        $statusLabel,
        $row['source'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
