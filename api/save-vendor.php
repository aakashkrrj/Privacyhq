<?php
header('Content-Type: application/json');

// Database Connection
require_once "../includes/db.php";
require_permission('manage_vendors');

if ($conn->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Connection Failed: ' . $conn->connect_error
    ]);
    exit();
}

// Check Request Method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_name  = trim($_POST['vendor_name'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $data_shared  = trim($_POST['data_shared'] ?? '');
    $status       = 'Under Audit'; // Default initial status for vendor_assessments
    $risk_score   = 15; // Default risk score

    if (empty($vendor_name) || empty($service_type) || empty($data_shared)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please fill in all required fields.'
        ]);
        exit();
    }

    // Prepare & Bind Insert Statement for vendors
    $stmt = $conn->prepare("INSERT INTO vendors (name, service_type) VALUES (?, ?)");
    $stmt->bind_param("ss", $vendor_name, $service_type);

    if ($stmt->execute()) {
        $vendor_id = $stmt->insert_id;
        $stmt->close();

        // Insert into vendor_assessments
        $stmt_va = $conn->prepare("INSERT INTO vendor_assessments (vendor_id, risk_score, status) VALUES (?, ?, ?)");
        $stmt_va->bind_param("iis", $vendor_id, $risk_score, $status);
        if ($stmt_va->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Vendor onboarded successfully!'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Assessment Insert Error: ' . $stmt_va->error
            ]);
        }
        $stmt_va->close();

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database Insert Error: ' . $stmt->error
        ]);
        $stmt->close();
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Request Method.'
    ]);
}