<?php
require_once __DIR__ . '/../PortalBootstrap.php';
require_once __DIR__ . '/../../../models/DataRequest.php';
use Backend\Core\ApiResponse;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Method Not Allowed', 405);
}

try {
    $subjectId = $_SESSION['portal_subject_id'] ?? null;
    if (!$subjectId) {
        throw new \Exception("Unauthorized");
    }

    $stmt = $pdo->prepare("
        SELECT id, request_id_code, request_type, status, priority, created_at, resolved_at 
        FROM data_requests 
        WHERE data_subject_id = ? AND deleted_at IS NULL 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$subjectId]);
    $requests = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    ApiResponse::success('Success', $requests);
} catch (\Exception $e) {
    ApiResponse::error($e->getMessage());
}
