<?php
// backend/api/reports/export.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/ReportingService.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized");
}

$module = $_GET['module'] ?? 'Incident';
$format = $_GET['format'] ?? 'csv';

try {
    $reportingService = new \Backend\Services\ReportingService();
    $headers = [];
    $data = [];

    // Query data based on requested module
    if ($module === 'Incident') {
        $stmt = $pdo->query("SELECT id, summary, severity, status, created_at FROM incidents");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = [
            'id' => 'Incident ID',
            'summary' => 'Summary',
            'severity' => 'Severity',
            'status' => 'Status',
            'created_at' => 'Date Logged'
        ];
    } elseif ($module === 'Assessment') {
        $stmt = $pdo->query("SELECT id, title, priority, created_at FROM privacy_assessments");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = [
            'id' => 'Assessment ID',
            'title' => 'Title',
            'priority' => 'Priority',
            'created_at' => 'Created At'
        ];
    } elseif ($module === 'Risk') {
        $stmt = $pdo->query("SELECT id, title, likelihood, impact, status FROM risk_register");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = [
            'id' => 'Risk ID',
            'title' => 'Risk Title',
            'likelihood' => 'Likelihood',
            'impact' => 'Impact',
            'status' => 'Status'
        ];
    } elseif ($module === 'Vendor') {
        $stmt = $pdo->query("SELECT id, name, service_type, created_at FROM vendors WHERE deleted_at IS NULL");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = [
            'id' => 'Vendor ID',
            'name' => 'Name',
            'service_type' => 'Service Type',
            'created_at' => 'Added At'
        ];
    }

    if ($format === 'csv') {
        $reportingService->exportToCsv($data, $headers, strtolower($module) . '_report.csv');
    } else {
        $reportingService->exportToPrint($data, $headers, $module . ' Compliance Report');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Export Error: " . $e->getMessage();
}
