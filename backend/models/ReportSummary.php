<?php
namespace Backend\Models;

class ReportSummary {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getSummary() {
        // 1. Active Audits (Assessments in Draft/Under Review)
        $auditsQuery = "
            SELECT COUNT(*) 
            FROM privacy_assessments 
            WHERE status_id != 3 AND deleted_at IS NULL
        ";
        $activeAudits = (int) $this->pdo->query($auditsQuery)->fetchColumn();

        // 2. DSAR Completion Rate
        $dsarQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(IF(status = 'completed', 1, 0)) as completed
            FROM data_requests
        ";
        $dsarRes = $this->pdo->query($dsarQuery)->fetch(\PDO::FETCH_ASSOC);
        $totalDsar = (int) ($dsarRes['total'] ?? 0);
        $completedDsar = (int) ($dsarRes['completed'] ?? 0);
        $dsarCompletion = 0;
        if ($totalDsar > 0) {
            $dsarCompletion = (int) round(($completedDsar / $totalDsar) * 100);
        }

        // 3. Risk Mitigation Progress
        $riskQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(IF(status = 'mitigated', 1, 0)) as mitigated
            FROM assessment_risks
        ";
        $riskRes = $this->pdo->query($riskQuery)->fetch(\PDO::FETCH_ASSOC);
        $totalRisks = (int) ($riskRes['total'] ?? 0);
        $mitigatedRisks = (int) ($riskRes['mitigated'] ?? 0);
        $riskMitigation = 0;
        if ($totalRisks > 0) {
            $riskMitigation = (int) round(($mitigatedRisks / $totalRisks) * 100);
        }

        return [
            'active_audits' => $activeAudits,
            'dsar_completion' => $dsarCompletion,
            'risk_mitigation' => $riskMitigation,
            'total_dsar' => $totalDsar,
            'total_risks' => $totalRisks
        ];
    }

    public function getVendorRiskReport() {
        // Query KPIs
        $kpiQuery = "
            SELECT 
                COUNT(v.id) as total,
                SUM(IF(va.risk_score >= 90, 1, 0)) as critical_risk,
                SUM(IF(va.risk_score >= 80 AND va.risk_score < 90, 1, 0)) as high_risk,
                SUM(IF(va.risk_score >= 50 AND va.risk_score < 80, 1, 0)) as medium_risk,
                SUM(IF(va.risk_score < 50 OR va.risk_score IS NULL, 1, 0)) as low_risk,
                SUM(IF(va.status = 'Compliant', 1, 0)) as compliant_count
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.deleted_at IS NULL
        ";
        $kpis = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);

        // Query Vendor List
        $listQuery = "
            SELECT v.id, v.name as vendor_name, v.service_type as category, va.status as dpa_status,
                   IF(va.risk_score >= 80, 'Critical', IF(va.risk_score >= 50, 'Medium', 'Low')) as risk_level
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.deleted_at IS NULL
            ORDER BY v.id DESC
        ";
        $vendors = $this->pdo->query($listQuery)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => $kpis,
            'vendors' => $vendors
        ];
    }

    public function getRiskRegisterReport() {
        // Query KPIs
        $highQuery = "
            SELECT COUNT(*) 
            FROM assessment_risks ar
            JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
            WHERE rm.risk_level_name = 'High' AND ar.status != 'mitigated'
        ";
        $highRisks = $this->pdo->query($highQuery)->fetchColumn();
        $totalRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks")->fetchColumn();
        $mitigatedRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'mitigated'")->fetchColumn();
        $needsAction = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'open'")->fetchColumn();

        // Query detailed list
        $listQuery = "
            SELECT 
                ar.id,
                ar.description as title,
                rc.category_name as category,
                rm.likelihood_name as likelihood,
                rm.impact_name as impact,
                rm.risk_level_name as risk_level,
                ar.status as status,
                rmit.implementation_details as mitigation
            FROM assessment_risks ar
            LEFT JOIN risk_categories rc ON ar.risk_category_id = rc.id
            LEFT JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
            LEFT JOIN risk_mitigations rmit ON ar.id = rmit.risk_id
            ORDER BY ar.id DESC
        ";
        $items = $this->pdo->query($listQuery)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total_risks' => $totalRisks ?? 0,
                'high_risks' => $highRisks ?? 0,
                'mitigated_risks' => $mitigatedRisks ?? 0,
                'needs_action' => $needsAction ?? 0
            ],
            'risks' => $items
        ];
    }

    public function getRopaReport() {
        // Query KPIs
        $kpiQuery = "
            SELECT 
                COUNT(*) as total_activities,
                SUM(IF(status = 'active', 1, 0)) as active_activities,
                SUM(IF(status = 'inactive', 1, 0)) as inactive_activities
            FROM processing_activities
            WHERE deleted_at IS NULL
        ";
        $kpis = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);

        // Query detailed list
        $listQuery = "
            SELECT *
            FROM processing_activities
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        $items = $this->pdo->query($listQuery)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total_activities' => $kpis['total_activities'] ?? 0,
                'active_activities' => $kpis['active_activities'] ?? 0,
                'inactive_activities' => $kpis['inactive_activities'] ?? 0
            ],
            'activities' => $items
        ];
    }

    public function getPoliciesReport() {
        $total = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies")->fetchColumn();
        $active = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'active'")->fetchColumn();
        $draft = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'draft'")->fetchColumn();
        $archived = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'archived'")->fetchColumn();

        $sql = "SELECT * FROM privacy_policies ORDER BY updated_at DESC";
        $items = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total_policies' => $total ?? 0,
                'active_policies' => $active ?? 0,
                'draft_policies' => $draft ?? 0,
                'archived_policies' => $archived ?? 0
            ],
            'policies' => $items
        ];
    }
}


