<?php
header('Content-Type: application/json');

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

$subject_email = trim($_POST['subject_email'] ?? '');
$request_type = trim($_POST['request_type'] ?? 'Access');
$status = 'Pending';

// Automatically set standard 30-day deadline
$due_date = date('Y-m-d', strtotime('+30 days'));

if (empty($subject_email)) {
    echo json_encode(['status' => 'error', 'message' => 'Subject email is required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO dsr_requests (subject_email, request_type, status, due_date, created_at) VALUES (?, ?, ?, ?, NOW())");

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssss", $subject_email, $request_type, $status, $due_date);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'DSR logged successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();