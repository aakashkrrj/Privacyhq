<?php
header('Content-Type: application/json');

// Enable error reporting for logging, but suppress display to prevent breaking JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!$conn || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')]);
    exit;
}

$summary = trim($_POST['summary'] ?? '');
$severity = trim($_POST['severity'] ?? 'Medium');
$impacted_records = intval($_POST['impacted_records'] ?? 0);
$status = 'Open';

if (empty($summary)) {
    echo json_encode(['status' => 'error', 'message' => 'Incident summary is required.']);
    exit;
}

// Prepare statement matching your incidents table schema
$stmt = $conn->prepare("INSERT INTO incidents (summary, severity, impacted_records, status, created_at) VALUES (?, ?, ?, ?, NOW())");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssis", $summary, $severity, $impacted_records, $status);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Incident logged successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();