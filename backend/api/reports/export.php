<?php
// governance/backend/api/reports/export.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../core/PdfGenerator.php';
require_once __DIR__ . '/../../core/XlsxGenerator.php';

use Backend\Core\PdfGenerator;
use Backend\Core\XlsxGenerator;

$module = trim($_GET['module'] ?? $_GET['report_type'] ?? 'General');
$format = strtolower(trim($_GET['format'] ?? 'pdf'));
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

try {
    $execRecord = null;
    if ($id > 0) {
        $execRecord = $service->getExecutionById($id);
        if ($execRecord) {
            $module = $execRecord['report_type'];
            $format = strtolower($execRecord['file_format']);
        }
    }

    $title = $module . ' Compliance & Governance Report';
    $data = [];
    $headers = [];

    if ($module === 'Incident' || $module === 'Incident Summary') {
        $rep = $service->getIncidentReport();
        $data = $rep['incidents'];
        $headers = [
            'id' => 'ID',
            'summary' => 'Summary Title',
            'severity' => 'Severity',
            'status' => 'Status',
            'created_at' => 'Date Reported'
        ];
    } elseif ($module === 'Risk' || $module === 'Risk Register') {
        $rep = $service->getRiskRegisterReport();
        $data = $rep['risks'];
        $headers = [
            'id' => 'Risk ID',
            'title' => 'Risk Description',
            'category' => 'Category',
            'likelihood' => 'Likelihood',
            'impact' => 'Impact',
            'risk_level' => 'Risk Level',
            'status' => 'Status'
        ];
    } elseif ($module === 'Vendor' || $module === 'Vendor Risk') {
        $rep = $service->getVendorRiskReport();
        $data = $rep['vendors'];
        $headers = [
            'id' => 'Vendor ID',
            'vendor_name' => 'Vendor Name',
            'category' => 'Category',
            'dpa_status' => 'DPA Status',
            'risk_level' => 'Risk Rating'
        ];
    } elseif ($module === 'ROPA' || $module === 'ROPA Inventory') {
        $rep = $service->getRopaReport();
        $data = $rep['activities'];
        $headers = [
            'ropa_code' => 'ROPA Code',
            'activity_name' => 'Activity Name',
            'department' => 'Department',
            'legal_basis' => 'Legal Basis',
            'controller_role' => 'Role',
            'status' => 'Status'
        ];
    } elseif ($module === 'Policies' || $module === 'Policies Report') {
        $rep = $service->getPoliciesReport();
        $data = $rep['policies'];
        $headers = [
            'policy_code' => 'Policy Code',
            'policy_name' => 'Policy Title',
            'category' => 'Category',
            'department' => 'Department',
            'version' => 'Version',
            'status' => 'Status'
        ];
    } elseif ($module === 'DSR' || $module === 'DSR Performance') {
        $rep = $service->getDsrReport();
        $data = $rep['requests'];
        $headers = [
            'request_id_code' => 'DSR Code',
            'request_type' => 'Type',
            'subject_email' => 'Subject Email',
            'priority' => 'Priority',
            'status' => 'Status',
            'created_at' => 'Date Requested'
        ];
    } else {
        $stmt = $pdo->query("SELECT report_code, report_type, title, execution_type, file_format, status, created_at FROM report_executions WHERE deleted_at IS NULL ORDER BY id DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = [
            'report_code' => 'Code',
            'report_type' => 'Module',
            'title' => 'Report Title',
            'execution_type' => 'Type',
            'file_format' => 'Format',
            'status' => 'Status',
            'created_at' => 'Generated At'
        ];
    }

    $sanitizedModule = preg_replace('/[^a-zA-Z0-9]/', '_', $module);
    $baseFileName = 'PrivacyHQ_' . $sanitizedModule . '_Report_' . date('Y-m-d');

    // ROW 118: GENUINE BINARY PDF EXPORT
    if ($format === 'pdf') {
        $pdfGen = new PdfGenerator();
        $pdfGen->addHeader('PrivacyHQ — ' . $title, 'Generated Date: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($data));
        $pdfGen->addMetadataBlocks([
            'Module' => $module,
            'Records' => count($data),
            'Status' => 'Compliant',
            'Export' => 'Binary PDF'
        ]);

        $pdfGen->addTable(array_values($headers), $data);
        $pdfBytes = $pdfGen->output();

        $filename = $baseFileName . '.pdf';

        // Audit Event
        if (function_exists('log_audit_event')) {
            log_audit_event($pdo, 'Reports', 'Export PDF', $_SESSION['user_id'] ?? 1, $id ?: null, null, $filename);
        }

        @header('Content-Type: application/pdf');
        @header('Content-Disposition: attachment; filename="' . $filename . '"');
        @header('Content-Length: ' . strlen($pdfBytes));
        @header('Cache-Control: private, max-age=0, must-revalidate');
        @header('Pragma: public');

        echo $pdfBytes;
        exit();

    // ROW 119: GENUINE BINARY XLSX EXCEL WORKBOOK EXPORT
    } elseif ($format === 'excel' || $format === 'xlsx') {
        $xlsxGen = new XlsxGenerator($headers, $data, 'PrivacyHQ — ' . $title);
        $xlsxBytes = $xlsxGen->output();

        $filename = $baseFileName . '.xlsx';

        // Audit Event
        if (function_exists('log_audit_event')) {
            log_audit_event($pdo, 'Reports', 'Export XLSX', $_SESSION['user_id'] ?? 1, $id ?: null, null, $filename);
        }

        @header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        @header('Content-Disposition: attachment; filename="' . $filename . '"');
        @header('Content-Length: ' . strlen($xlsxBytes));
        @header('Cache-Control: private, max-age=0, must-revalidate');
        @header('Pragma: public');

        echo $xlsxBytes;
        exit();

    // CSV SPREADSHEET EXPORT
    } else {
        $filename = $baseFileName . '.csv';

        // Audit Event
        if (function_exists('log_audit_event')) {
            log_audit_event($pdo, 'Reports', 'Export CSV', $_SESSION['user_id'] ?? 1, $id ?: null, null, $filename);
        }

        @header('Content-Type: text/csv; charset=utf-8');
        @header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['PrivacyHQ Governance Analytics & Compliance Report']);
        fputcsv($output, ['Module: ' . $module, 'Export Date: ' . date('Y-m-d H:i:s'), 'Total Records: ' . count($data)]);
        fputcsv($output, []);
        fputcsv($output, array_values($headers));

        foreach ($data as $row) {
            $formattedRow = [];
            foreach ($headers as $key => $label) {
                $val = $row[$key] ?? '';
                if (is_array($val)) $val = json_encode($val);
                $formattedRow[] = $val;
            }
            fputcsv($output, $formattedRow);
        }

        fclose($output);
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Export Error: " . $e->getMessage();
}
