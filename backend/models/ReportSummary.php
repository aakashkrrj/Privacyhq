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
}
