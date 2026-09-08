<?php
header('Content-Type: application/json');
require_once "../includes/db.php";

if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Connection Failed: ' . $conn->connect_error
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $summary = trim($_POST['summary'] ?? '');
    $severity = trim($_POST['severity'] ?? 'Medium');
    $impacted_records = intval($_POST['impacted_records'] ?? 0);
    $status = 'Open';

    if (empty($summary)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Incident summary is required.'
        ]);
        exit();
    }
    
    // Ensure severity is valid enum
    $valid_severities = ['Low', 'Medium', 'High', 'Critical'];
    if (!in_array($severity, $valid_severities)) {
        $severity = 'Medium';
    }

    $stmt = $conn->prepare("INSERT INTO incidents (summary, severity, impacted_records, status) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssis", $summary, $severity, $impacted_records, $status);
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Incident logged successfully!'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Insert Error: ' . $stmt->error
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database Query Error: ' . $conn->error
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Request Method.'
    ]);
}
