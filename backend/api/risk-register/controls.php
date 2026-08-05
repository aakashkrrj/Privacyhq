<?php
// backend/api/risk-register/controls.php

use Backend\Core\ApiBootstrap;
use Backend\Core\ApiResponse;

require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    ApiBootstrap::requireCsrf();
    
    $mitigationId = filter_input(INPUT_POST, 'mitigation_id', FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? '');
    
    if (!$mitigationId || !in_array($status, ['planned', 'in_progress', 'implemented'])) {
        ApiResponse::error("Invalid parameters provided.");
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update mitigation status
        $stmt = $pdo->prepare("UPDATE risk_mitigations SET status = ? WHERE id = ?");
        $stmt->execute([$status, $mitigationId]);
        
        // Also update parent risk status if mitigation is implemented
        $stmtGet = $pdo->prepare("SELECT risk_id FROM risk_mitigations WHERE id = ? LIMIT 1");
        $stmtGet->execute([$mitigationId]);
        $riskId = $stmtGet->fetchColumn();
        
        if ($riskId) {
            $riskStatus = ($status === 'implemented') ? 'mitigated' : (($status === 'in_progress') ? 'in review' : 'open');
            $stmtRisk = $pdo->prepare("UPDATE assessment_risks SET status = ? WHERE id = ?");
            $stmtRisk->execute([$riskStatus, $riskId]);
        }
        
        if (function_exists('log_audit_event')) {
            log_audit_event($pdo, 'Risk Register', 'Update Control Status', 1, $mitigationId, null, $status);
        }
        
        $pdo->commit();
        ApiResponse::success("Control status updated successfully!");
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        ApiResponse::error($e->getMessage());
    }
} else {
    // GET request
    try {
        $sql = "
            SELECT 
                rmit.id,
                ar.description as risk_title,
                rmit.implementation_details,
                rmit.status
            FROM risk_mitigations rmit
            JOIN assessment_risks ar ON rmit.risk_id = ar.id
            ORDER BY rmit.id DESC
        ";
        $stmt = $pdo->query($sql);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        ApiResponse::success("Success", $items);
    } catch (\Exception $e) {
        ApiResponse::error($e->getMessage());
    }
}
