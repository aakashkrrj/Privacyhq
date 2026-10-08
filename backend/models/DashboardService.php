<?php
namespace Backend\Models;

class DashboardService {
    private $pdo;

    public function __construct(\PDO $pdo = null) {
        $this->pdo = $pdo ?? $GLOBALS['pdo'];
    }

    public function getExecutiveMetrics() {
        // 1. DSR Metrics
        $dsr_pending = (int)$this->pdo->query("SELECT COUNT(*) FROM data_requests WHERE status IN ('Pending', 'In Progress')")->fetchColumn();
        
        // 2. Consent Metrics
        $consent_active = (int)$this->pdo->query("SELECT COUNT(*) FROM consents WHERE status = 'Active'")->fetchColumn();
        $consent_revoked = (int)$this->pdo->query("SELECT COUNT(*) FROM consents WHERE status = 'Revoked'")->fetchColumn();
        
        // 3. PIA Metrics
        $pia_open = (int)$this->pdo->query("
            SELECT COUNT(*) FROM privacy_assessments pa 
            JOIN assessment_statuses s ON pa.status_id = s.id 
            WHERE s.status_name NOT IN ('Approved', 'Completed') AND pa.deleted_at IS NULL
        ")->fetchColumn();
        $pia_high_risk = (int)$this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE source_type = 'PIA' AND residual_level IN ('High', 'Critical') AND status != 'mitigated'")->fetchColumn();
        
        // 4. TPRM (Vendors)
        $vendors_total = (int)$this->pdo->query("SELECT COUNT(*) FROM vendors WHERE deleted_at IS NULL")->fetchColumn();
        $vendor_high_risk = (int)$this->pdo->query("SELECT COUNT(*) FROM vendors WHERE risk_level IN ('High', 'Critical') AND deleted_at IS NULL")->fetchColumn();
        
        // 5. Incidents
        $incidents_open = (int)$this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status NOT IN ('Resolved', 'Closed') AND deleted_at IS NULL")->fetchColumn();
        $incidents_critical = (int)$this->pdo->query("SELECT COUNT(*) FROM incidents WHERE severity = 'Critical' AND status NOT IN ('Resolved', 'Closed') AND deleted_at IS NULL")->fetchColumn();
        
        // 6. GRC Risk
        $grc_total_risks = (int)$this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'open'")->fetchColumn();
        $grc_high_risks = (int)$this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE residual_level IN ('High', 'Critical') AND status = 'open'")->fetchColumn();
        
        // 7. GRC Findings / Remediation
        $findings_open = (int)$this->pdo->query("SELECT COUNT(*) FROM grc_findings WHERE status NOT IN ('Closed', 'Remediated')")->fetchColumn();
        $remediation_overdue = (int)$this->pdo->query("SELECT COUNT(*) FROM grc_findings WHERE due_date < CURDATE() AND status NOT IN ('Closed', 'Remediated')")->fetchColumn();
        
        // 8. Frameworks (Phase 9)
        $frameworks_active = (int)$this->pdo->query("SELECT COUNT(*) FROM grc_frameworks")->fetchColumn();
        
        // 9. DSPM (Phase 10)
        $dspm_total = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE deleted_at IS NULL")->fetchColumn();
        $dspm_restricted = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE classification = 'Restricted' AND deleted_at IS NULL")->fetchColumn();
        $dspm_unclassified = (int)$this->pdo->query("SELECT COUNT(*) FROM data_assets WHERE (classification IS NULL OR classification = '') AND deleted_at IS NULL")->fetchColumn();
        $dspm_high_risk = (int)$this->pdo->query("
            SELECT COUNT(DISTINCT da.id) FROM data_assets da 
            JOIN data_asset_risks dar ON da.id = dar.asset_id 
            JOIN assessment_risks ar ON dar.risk_id = ar.id 
            WHERE ar.residual_level IN ('High', 'Critical') AND da.deleted_at IS NULL
        ")->fetchColumn();

        return [
            'dsr' => ['pending' => $dsr_pending],
            'consent' => ['active' => $consent_active, 'revoked' => $consent_revoked],
            'pia' => ['open' => $pia_open, 'high_risk' => $pia_high_risk],
            'vendor' => ['total' => $vendors_total, 'high_risk' => $vendor_high_risk],
            'incident' => ['open' => $incidents_open, 'critical' => $incidents_critical],
            'grc' => ['total_risks' => $grc_total_risks, 'high_risks' => $grc_high_risks, 'open_findings' => $findings_open, 'overdue_remediation' => $remediation_overdue],
            'framework' => ['active' => $frameworks_active],
            'dspm' => ['total' => $dspm_total, 'restricted' => $dspm_restricted, 'unclassified' => $dspm_unclassified, 'high_risk' => $dspm_high_risk]
        ];
    }

    public function getFrameworkCompliance() {
        require_once __DIR__ . '/GrcModel.php';
        $grcModel = new \Backend\Models\GrcModel($this->pdo);
        
        $frameworks = $this->pdo->query("SELECT * FROM grc_frameworks WHERE deleted_at IS NULL")->fetchAll(\PDO::FETCH_ASSOC);
        
        $results = [];
        $totalScore = 0;
        
        foreach ($frameworks as $fw) {
            $grcModel->calculateFrameworkScore($fw['id']); // Update the DB scores
            // Fetch updated framework
            $updatedFw = $this->pdo->query("SELECT * FROM grc_frameworks WHERE id = " . (int)$fw['id'])->fetch(\PDO::FETCH_ASSOC);
            $results[] = $updatedFw;
            $totalScore += (float)$updatedFw['score'];
        }
        
        $avgScore = count($frameworks) > 0 ? ($totalScore / count($frameworks)) : 0;
        
        return [
            'frameworks' => $results,
            'average_score' => round($avgScore, 2)
        ];
    }

    public function getDpdpScore() {
        require_once __DIR__ . '/GrcModel.php';
        $grcModel = new \Backend\Models\GrcModel($this->pdo);
        
        // Find DPDP framework if exists
        $stmt = $this->pdo->prepare("SELECT id FROM grc_frameworks WHERE name LIKE '%DPDP%' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute();
        $dpdpId = $stmt->fetchColumn();
        
        if (!$dpdpId) {
            return ['status' => 'incomplete', 'message' => 'Assessment data incomplete'];
        }
        
        $grcModel->calculateFrameworkScore($dpdpId);
        $fw = $this->pdo->query("SELECT * FROM grc_frameworks WHERE id = " . (int)$dpdpId)->fetch(\PDO::FETCH_ASSOC);
        
        if ((int)$fw['assessed_count'] == 0) {
            return ['status' => 'incomplete', 'message' => 'Assessment data incomplete'];
        }
        
        return ['status' => 'success', 'score' => (float)$fw['score']];
    }

    public function getEnterpriseScore() {
        /*
            Enterprise Score Formula:
            - Base framework compliance (50% weight)
            - Risk Posture Penalty (Deduction for Critical/High open risks)
            - Incident Penalty (Deduction for Critical open incidents)
            - Finding Penalty (Deduction for overdue findings)
        */
        $metrics = $this->getExecutiveMetrics();
        $frameworkData = $this->getFrameworkCompliance();
        
        if (count($frameworkData['frameworks']) == 0) {
            return ['status' => 'incomplete', 'message' => 'Assessment data incomplete'];
        }
        
        $baseScore = $frameworkData['average_score'];
        
        // Penalties
        $riskPenalty = min(30, ($metrics['grc']['high_risks'] * 2)); // Up to 30 points deducted for high risks
        $incidentPenalty = min(10, ($metrics['incident']['critical'] * 5)); // Up to 10 points
        $findingPenalty = min(10, ($metrics['grc']['overdue_remediation'] * 2)); // Up to 10 points
        
        $finalScore = max(0, $baseScore - $riskPenalty - $incidentPenalty - $findingPenalty);
        
        return [
            'status' => 'success',
            'score' => round($finalScore, 2),
            'breakdown' => [
                'base_framework_score' => $baseScore,
                'risk_penalty' => $riskPenalty,
                'incident_penalty' => $incidentPenalty,
                'finding_penalty' => $findingPenalty
            ]
        ];
    }
}
