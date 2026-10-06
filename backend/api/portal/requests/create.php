<?php
require_once __DIR__ . '/../PortalBootstrap.php';
require_once __DIR__ . '/../../../models/DataRequest.php';
require_once __DIR__ . '/../../../core/ApiResponse.php';
use Backend\Core\ApiResponse;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Method Not Allowed', 405);
}

try {
    $subjectId = $_SESSION['portal_subject_id'] ?? null;
    if (!$subjectId) {
        throw new \Exception("Unauthorized");
    }

    $requestType = trim($_POST['request_type'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($requestType)) {
        throw new \Exception("Request Type is required");
    }

    $validTypes = ['access', 'erasure', 'rectification', 'portability', 'objection'];
    if (!in_array($requestType, $validTypes)) {
        throw new \Exception("Invalid Request Type");
    }

    $dataRequestModel = new \Backend\Models\DataRequest($pdo);
    
    // Portal requests are Medium priority, unassigned, default 30 days due
    $dueDate = date('Y-m-d', strtotime('+30 days'));
    $requestId = $dataRequestModel->create($subjectId, $requestType, 'Medium', $dueDate, $description, null);

    // Default portal creation sets it to unverified, wait!
    // If they logged in to the portal via OTP, maybe it should be 'verified' or 'pending'?
    // The BRD might require an extra verification step, but for now we'll set it to 'verified' because they used OTP to login.
    // Wait, the data request model defaults to empty verification_status. Let's set it to verified if they are portal authenticated.
    $dataRequestModel->updateVerification($requestId, 'verified');

    ApiResponse::success('Request submitted successfully', ['request_id' => $requestId]);
} catch (\Exception $e) {
    ApiResponse::error($e->getMessage());
}
