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

// Audit logging for export event
if (function_exists('log_audit_event')) {
    $userId = $_SESSION['user_id'] ?? null;
    log_audit_event($pdo, 'Consent Management', 'Export Excel', $userId, null, null, "Exported " . count($data) . " consent records to Excel");
}

$filename = 'consent_export_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Consents</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 12px; }
        th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; text-align: left; padding: 10px; border: 1px solid #cbd5e1; }
        td { padding: 8px; border: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .status-granted { background-color: #dcfce7; color: #15803d; font-weight: bold; }
        .status-revoked { background-color: #fee2e2; color: #b91c1c; font-weight: bold; }
        .status-pending { background-color: #fef3c7; color: #b45309; font-weight: bold; }
        .status-expired { background-color: #f3f4f6; color: #4b5563; font-weight: bold; }
      </style>';
echo '</head><body>';

echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Consent ID</th>';
echo '<th>User Identifier</th>';
echo '<th>Consent Category</th>';
echo '<th>Status</th>';
echo '<th>Collection Method</th>';
echo '<th>Source</th>';
echo '<th>Captured Date</th>';
echo '<th>Expiration Date</th>';
echo '<th>Last Updated</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if (empty($data)) {
    echo '<tr><td colspan="9" style="text-align:center; color:#94a3b8;">No consent records found matching criteria.</td></tr>';
} else {
    foreach ($data as $row) {
        $statusLabel = 'Granted';
        $statusClass = 'status-granted';

        if ($row['status'] === 'withdrawn') {
            $statusLabel = 'Revoked';
            $statusClass = 'status-revoked';
        } elseif ($row['status'] === 'opt_out') {
            $statusLabel = 'Pending';
            $statusClass = 'status-pending';
        } elseif ($row['status'] === 'expired') {
            $statusLabel = 'Expired';
            $statusClass = 'status-expired';
        }

        $collectionMethod = !empty($row['collection_method']) ? ucwords(str_replace('_', ' ', $row['collection_method'])) : 'Web Portal';
        $source = !empty($row['source']) ? htmlspecialchars($row['source']) : 'Manual';
        $capturedDate = !empty($row['granted_at']) ? htmlspecialchars($row['granted_at']) : htmlspecialchars($row['created_at'] ?? 'N/A');
        $expirationDate = !empty($row['expires_at']) ? htmlspecialchars($row['expires_at']) : 'N/A';
        $lastUpdated = !empty($row['updated_at']) ? htmlspecialchars($row['updated_at']) : htmlspecialchars($row['created_at'] ?? 'N/A');

        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['subject_email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
        echo '<td class="' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</td>';
        echo '<td>' . htmlspecialchars($collectionMethod) . '</td>';
        echo '<td>' . $source . '</td>';
        echo '<td>' . $capturedDate . '</td>';
        echo '<td>' . $expirationDate . '</td>';
        echo '<td>' . $lastUpdated . '</td>';
        echo '</tr>';
    }
}

echo '</tbody>';
echo '</table>';
echo '</body></html>';
exit;
