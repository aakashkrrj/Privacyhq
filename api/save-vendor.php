<?php
header('Content-Type: application/json');

// Database Connection
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$db   = 'privacy_governance';

$conn = new mysqli($host, $user, $pass, $db);

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
    $status       = 'Compliant'; // Default initial status

    if (empty($vendor_name) || empty($service_type) || empty($data_shared)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please fill in all required fields.'
        ]);
        exit();
    }

    // Prepare & Bind Insert Statement
    $stmt = $conn->prepare("INSERT INTO vendors (vendor_name, service_type, data_shared, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $vendor_name, $service_type, $data_shared, $status);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Vendor onboarded successfully!'
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
        'message' => 'Invalid Request Method.'
    ]);
}

$conn->close();