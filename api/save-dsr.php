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

if (empty($subject_email)) {
    echo json_encode(['status' => 'error', 'message' => 'Subject email is required.']);
    exit;
}

    // Map 'Access' to valid enum 'access'
    $req_type_map = ['Access' => 'access', 'Erasure' => 'erasure', 'Rectification' => 'rectification', 'Portability' => 'portability', 'Objection' => 'objection'];
    $request_type_mapped = $req_type_map[$request_type] ?? 'access';
    $status = 'open';

    // 1. Get or Create Data Subject
    $identifier_hash = hash('sha256', strtolower(trim($subject_email)));
    
    $ds_stmt = $conn->prepare("SELECT id FROM data_subjects WHERE identifier_hash = ?");
    $ds_stmt->bind_param("s", $identifier_hash);
    $ds_stmt->execute();
    $res = $ds_stmt->get_result();
    
    $data_subject_id = null;
    if ($row = $res->fetch_assoc()) {
        $data_subject_id = $row['id'];
    } else {
        $ds_insert = $conn->prepare("INSERT INTO data_subjects (identifier_hash, type) VALUES (?, 'customer')");
        $ds_insert->bind_param("s", $identifier_hash);
        $ds_insert->execute();
        $data_subject_id = $ds_insert->insert_id;
        $ds_insert->close();
    }
    $ds_stmt->close();

    // Automatically set standard 30-day deadline
    $due_date = date('Y-m-d', strtotime('+30 days'));
    
    // Generate request ID
    $request_id_code = 'REQ-' . strtoupper(substr(uniqid(), -6));

    $stmt = $conn->prepare("INSERT INTO data_requests (request_id_code, data_subject_id, request_type, status, due_date) VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("sisss", $request_id_code, $data_subject_id, $request_type_mapped, $status, $due_date);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'DSR logged successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();