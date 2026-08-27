<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Consent.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/models/ConsentPurpose.php';
require_once __DIR__ . '/../../../backend/models/ConsentHistory.php';
require_once __DIR__ . '/../../../backend/services/ConsentService.php';

ApiBootstrap::requireMethod('GET');

// Enforce permission
if (function_exists('require_permission')) {
    require_permission('view_dashboard');
}

$consentModel = new \Backend\Models\Consent($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$purposeModel = new \Backend\Models\ConsentPurpose($pdo);
$historyModel = new \Backend\Models\ConsentHistory($pdo);
$consentService = new \Backend\Services\ConsentService($pdo, $consentModel, $subjectModel, $purposeModel, $historyModel);

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');

$data = $consentService->getExportList($search, $statusFilter, $categoryFilter, $dateFilter);

// Audit logging for export event
if (function_exists('log_audit_event')) {
    $userId = $_SESSION['user_id'] ?? null;
    log_audit_event($pdo, 'Consent Management', 'Export CSV', $userId, null, null, "Exported " . count($data) . " consent records");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=consent_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for Excel compatibility
fprintf($output, "\xEF\xBB\xBF");

// Header row
fputcsv($output, [
    'Consent ID',
    'User Identifier',
    'Consent Category',
    'Status',
    'Collection Method',
    'Source',
    'Captured Date',
    'Expiration Date',
    'Last Updated'
]);

foreach ($data as $row) {
    $statusLabel = 'Granted';
    if ($row['status'] === 'withdrawn') {
        $statusLabel = 'Revoked';
    } elseif ($row['status'] === 'opt_out') {
        $statusLabel = 'Pending';
    } elseif ($row['status'] === 'expired') {
        $statusLabel = 'Expired';
    }

    $collectionMethod = !empty($row['collection_method']) ? ucwords(str_replace('_', ' ', $row['collection_method'])) : 'Web Portal';
    $source = !empty($row['source']) ? $row['source'] : 'Manual';
    $capturedDate = !empty($row['granted_at']) ? $row['granted_at'] : ($row['created_at'] ?? 'N/A');
    $expirationDate = !empty($row['expires_at']) ? $row['expires_at'] : 'N/A';
    $lastUpdated = !empty($row['updated_at']) ? $row['updated_at'] : ($row['created_at'] ?? 'N/A');
    
    fputcsv($output, [
        $row['id'],
        $row['subject_email'],
        $row['category'],
        $statusLabel,
        $collectionMethod,
        $source,
        $capturedDate,
        $expirationDate,
        $lastUpdated
    ]);
}

fclose($output);
exit;
