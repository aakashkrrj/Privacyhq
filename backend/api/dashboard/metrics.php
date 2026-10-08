<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/DashboardService.php';
require_once __DIR__ . '/../../core/ApiBootstrap.php';

\Backend\Core\ApiBootstrap::requireAuth();
$dashboardService = new \Backend\Models\DashboardService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $metrics = $dashboardService->getExecutiveMetrics();
    $frameworks = $dashboardService->getFrameworkCompliance();
    $dpdp = $dashboardService->getDpdpScore();
    $enterprise = $dashboardService->getEnterpriseScore();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'metrics' => $metrics,
            'frameworks' => $frameworks,
            'dpdp' => $dpdp,
            'enterprise' => $enterprise
        ]
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
