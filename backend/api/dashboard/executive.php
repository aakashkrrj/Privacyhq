<?php
// backend/api/dashboard/executive.php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/DashboardService.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

/** @var PDO $pdo */

try {
    $dashboardService = new \Backend\Services\DashboardService($pdo);
    $kpis = $dashboardService->getKPIs();

    // Fetch recent timeline activities
    $stmtAct = $pdo->query("
        SELECT a.*, u.email as user_email 
        FROM activity_timeline a 
        JOIN users u ON a.performed_by = u.id 
        ORDER BY a.created_at DESC LIMIT 6
    ");
    $recentActivities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

    // Fetch upcoming deadlines (tasks and assessments due in the next 14 days)
    $stmtDead = $pdo->query("
        SELECT id, title, due_date, module, priority 
        FROM tasks 
        WHERE status != 'Completed' AND due_date >= CURDATE()
        ORDER BY due_date ASC LIMIT 6
    ");
    $upcomingDeadlines = $stmtDead->fetchAll(PDO::FETCH_ASSOC);

    // Fetch pending approvals (tasks of type 'Review' or 'Approve' or status 'Submitted')
    $stmtApp = $pdo->query("
        SELECT id, title, assigned_to, module, priority 
        FROM tasks 
        WHERE status != 'Completed' AND (task_type LIKE '%Review%' OR task_type LIKE '%Approve%')
        ORDER BY created_at DESC LIMIT 6
    ");
    $pendingApprovals = $stmtApp->fetchAll(PDO::FETCH_ASSOC);

    // Fetch incident severity distribution for Chart.js
    $stmtIncChart = $pdo->query("
        SELECT severity, COUNT(*) as count 
        FROM incidents 
        GROUP BY severity
    ");
    $incidentChart = $stmtIncChart->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "kpis" => $kpis,
            "recent_activities" => $recentActivities,
            "upcoming_deadlines" => $upcomingDeadlines,
            "pending_approvals" => $pendingApprovals,
            "incident_chart" => $incidentChart
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
