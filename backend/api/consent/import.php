<?php
use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/Consent.php';
require_once __DIR__ . '/../../../backend/models/DataSubject.php';
require_once __DIR__ . '/../../../backend/models/ConsentPurpose.php';
require_once __DIR__ . '/../../../backend/models/ConsentHistory.php';
require_once __DIR__ . '/../../../backend/services/ConsentService.php';
require_once __DIR__ . '/../../../backend/controllers/ConsentController.php';

ApiBootstrap::requireMethod('POST');
ApiBootstrap::requireCsrf();

$consentModel = new \Backend\Models\Consent($pdo);
$subjectModel = new \Backend\Models\DataSubject($pdo);
$purposeModel = new \Backend\Models\ConsentPurpose($pdo);
$historyModel = new \Backend\Models\ConsentHistory($pdo);
$consentService = new \Backend\Services\ConsentService($pdo, $consentModel, $subjectModel, $purposeModel, $historyModel);

// Get currently logged-in user (fallback to 1 if session doesn't have it)
$userId = $_SESSION['user_id'] ?? 1;

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    Backend\Core\ApiResponse::error('No file uploaded or file upload error occurred.');
}

$tmpPath = $_FILES['csv_file']['tmp_name'];
$fileName = $_FILES['csv_file']['name'];

// Validate file extension
$ext = pathinfo($fileName, PATHINFO_EXTENSION);
if (strtolower($ext) !== 'csv') {
    Backend\Core\ApiResponse::error('Invalid file type. Please upload a CSV file.');
}

$handle = fopen($tmpPath, 'r');
if (!$handle) {
    Backend\Core\ApiResponse::error('Unable to open the uploaded CSV file.');
}

// Parse headers
$headers = fgetcsv($handle);
$emailIdx = -1;
$categoryIdx = -1;
$statusIdx = -1;

if ($headers) {
    foreach ($headers as $index => $header) {
        $cleanHeader = strtolower(trim($header));
        if (in_array($cleanHeader, ['email', 'user_identifier', 'identifier_hash', 'user identifier', 'user'])) {
            $emailIdx = $index;
        } elseif (in_array($cleanHeader, ['category', 'consent_category', 'purpose', 'consent purpose'])) {
            $categoryIdx = $index;
        } elseif (in_array($cleanHeader, ['status', 'consent_status', 'initial_status'])) {
            $statusIdx = $index;
        }
    }
}

// Fallback to columns 0, 1, 2 if headers not mapped
if ($emailIdx === -1) $emailIdx = 0;
if ($categoryIdx === -1) $categoryIdx = 1;
if ($statusIdx === -1) $statusIdx = 2;

$successCount = 0;
$errors = [];
$rowNumber = 1; // start from row after header

while (($row = fgetcsv($handle)) !== false) {
    $rowNumber++;
    // Skip empty lines
    if (empty($row) || (count($row) == 1 && empty($row[0]))) {
        continue;
    }

    $email = isset($row[$emailIdx]) ? trim($row[$emailIdx]) : '';
    $category = isset($row[$categoryIdx]) ? trim($row[$categoryIdx]) : '';
    $status = isset($row[$statusIdx]) ? trim($row[$statusIdx]) : 'Granted';

    if (empty($email)) {
        $errors[] = "Row {$rowNumber}: User Identifier is empty.";
        continue;
    }
    if (empty($category)) {
        $errors[] = "Row {$rowNumber}: Consent Category is empty.";
        continue;
    }

    // Normalize status values to Granted, Revoked, Pending
    $normalizedStatus = 'Granted';
    $statusLower = strtolower($status);
    if (in_array($statusLower, ['revoked', 'withdrawn'])) {
        $normalizedStatus = 'Revoked';
    } elseif (in_array($statusLower, ['pending', 'opt_out'])) {
        $normalizedStatus = 'Pending';
    } elseif (in_array($statusLower, ['granted', 'opt_in'])) {
        $normalizedStatus = 'Granted';
    } else {
        $errors[] = "Row {$rowNumber}: Invalid status '{$status}'. Expected Granted, Revoked, or Pending.";
        continue;
    }

    try {
        $consentService->createConsent($email, $category, $normalizedStatus, $userId);
        $successCount++;
    } catch (\Exception $e) {
        $errors[] = "Row {$rowNumber}: " . $e->getMessage();
    }
}

fclose($handle);

if (function_exists('log_audit_event') && $successCount > 0) {
    log_audit_event($pdo, 'Consent Management', 'Import CSV', $userId, null, null, "Imported {$successCount} consent records from CSV: {$fileName}");
}

Backend\Core\ApiResponse::success('Import processed', [
    'success_count' => $successCount,
    'errors' => $errors
]);
