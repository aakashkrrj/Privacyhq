<?php
/**
 * Dashboard API Endpoint
 * 
 * Provides JSON data for the dashboard UI.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../backend/services/DashboardService.php';

// Protect API endpoint
requireLogin(true);

try {
    $dashboardService = new DashboardService();
    
    $stats = $dashboardService->getDashboardStats();
    $compliance = $dashboardService->getComplianceOverview();
    
    // Limits
    $recentConsents = $dashboardService->getRecentConsents(5);
    $recentRequests = $dashboardService->getRecentRequests(5);
    $recentAssessments = $dashboardService->getRecentAssessments(5);
    $recentActivity = $dashboardService->getRecentAuditLogs(5);

    echo json_encode([
        'success' => true,
        'generated_at' => date('c'),
        'stats' => $stats,
        'compliance' => $compliance,
        'recent_consents' => $recentConsents,
        'recent_requests' => $recentRequests,
        'recent_assessments' => $recentAssessments,
        'recent_activity' => $recentActivity
    ]);

} catch (Exception $e) {
    // Return safe generic error message
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching dashboard data.'
    ]);
}
