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
}
