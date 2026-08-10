<?php
// backend/api/search/global.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/SearchService.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$query = $_GET['q'] ?? '';
try {
    $searchService = new \Backend\Services\SearchService($pdo);
    $userId = $_SESSION['user_id'] ?? null;
    $results = $searchService->search($query, $userId);

    echo json_encode(["success" => true, "data" => $results]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
