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

    public function getDsrReport() {
        // Query DSR counts
        $total = $this->pdo->query("SELECT COUNT(*) FROM data_requests")->fetchColumn();
        $pending = $this->pdo->query("SELECT COUNT(*) FROM data_requests WHERE status IN ('open','verifying','processing')")->fetchColumn();
        $completed = $this->pdo->query("SELECT COUNT(*) FROM data_requests WHERE status = 'completed'")->fetchColumn();

        // SLA Compliance
        $completedWithinSla = $this->pdo->query("
            SELECT COUNT(*) 
            FROM data_requests 
            WHERE status = 'completed' AND updated_at <= due_date
        ")->fetchColumn();
        $slaCompliance = $completed > 0 ? round(($completedWithinSla / $completed) * 100) . '%' : '100%';

        // Average Resolution Time
        $avgDays = $this->pdo->query("
            SELECT ROUND(AVG(DATEDIFF(updated_at, created_at))) 
            FROM data_requests 
            WHERE status = 'completed'
        ")->fetchColumn() ?: 0;

        // Open High/Urgent Priority
        $highPriority = $this->pdo->query("
            SELECT COUNT(*) 
            FROM data_requests 
            WHERE status IN ('open','verifying','processing') AND priority IN ('High','Urgent')
        ")->fetchColumn();

        // Verification status counts
        $verRaw = $this->pdo->query("
            SELECT 
                SUM(IF(verification_status = 'pending', 1, 0)) as pending_ver,
                SUM(IF(verification_status = 'verified', 1, 0)) as verified_ver,
                SUM(IF(verification_status = 'failed', 1, 0)) as failed_ver
            FROM data_requests
        ")->fetch(\PDO::FETCH_ASSOC);

        // Distributions
        $distRaw = $this->pdo->query("
            SELECT request_type, COUNT(*) as count 
            FROM data_requests 
            GROUP BY request_type
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $typeDistribution = [];
        foreach ($distRaw as $row) {
            $typeDistribution[$row['request_type']] = $row['count'];
        }

        $statusRaw = $this->pdo->query("
            SELECT status, COUNT(*) as count 
            FROM data_requests 
            GROUP BY status
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $statusDistribution = [];
        foreach ($statusRaw as $row) {
            $statusDistribution[$row['status']] = $row['count'];
        }

        // Details
        $sql = "
            SELECT dr.*, ds.identifier_hash as subject_email 
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            ORDER BY dr.id DESC
        ";
        $items = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total' => $total ?? 0,
                'pending' => $pending ?? 0,
                'completed' => $completed ?? 0,
                'sla_compliance' => $slaCompliance,
                'avg_resolution' => $avgDays . ' days',
                'high_priority' => $highPriority ?? 0,
                'verification' => [
                    'pending' => $verRaw['pending_ver'] ?? 0,
                    'verified' => $verRaw['verified_ver'] ?? 0,
                    'failed' => $verRaw['failed_ver'] ?? 0
                ]
            ],
            'distributions' => [
                'type' => $typeDistribution,
                'status' => $statusDistribution
            ],
            'requests' => $items
        ];
    }

    public function getIncidentReport() {
        $total = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE deleted_at IS NULL")->fetchColumn();
        $open = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'Open' AND deleted_at IS NULL")->fetchColumn();
        $investigating = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'Investigating' AND deleted_at IS NULL")->fetchColumn();
        $resolved = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'Resolved' AND deleted_at IS NULL")->fetchColumn();

        // Severity Distribution
        $sevRaw = $this->pdo->query("
            SELECT severity, COUNT(*) as count 
            FROM incidents 
            WHERE deleted_at IS NULL 
            GROUP BY severity
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $severityDist = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
        foreach ($sevRaw as $row) {
            $severityDist[$row['severity']] = $row['count'];
        }

        // Avg Resolution Time
        $avgDays = $this->pdo->query("
            SELECT ROUND(AVG(DATEDIFF(resolved_at, created_at))) 
            FROM incidents 
            WHERE status = 'Resolved' AND deleted_at IS NULL
        ")->fetchColumn() ?: 0;

        // High/Critical count
        $highCrit = $this->pdo->query("
            SELECT COUNT(*) 
            FROM incidents 
            WHERE severity IN ('High', 'Critical') AND deleted_at IS NULL
        ")->fetchColumn();

        // Escalations and Notifications
        $escalated = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE is_escalated = 1 AND deleted_at IS NULL")->fetchColumn();
        $dpoNotified = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE dpo_notified = 1 AND deleted_at IS NULL")->fetchColumn();
        $regulatoryNotified = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE regulatory_status != 'Not Required' AND deleted_at IS NULL")->fetchColumn();

        $sql = "SELECT * FROM incidents WHERE deleted_at IS NULL ORDER BY created_at DESC";
        $items = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total' => $total ?? 0,
                'open' => $open ?? 0,
                'investigating' => $investigating ?? 0,
                'resolved' => $resolved ?? 0,
                'avg_resolution' => $avgDays . ' days',
                'high_critical' => $highCrit ?? 0,
                'escalated' => $escalated ?? 0,
                'dpo_notified' => $dpoNotified ?? 0,
                'regulatory_notified' => $regulatoryNotified ?? 0
            ],
            'severity_distribution' => $severityDist,
            'incidents' => $items
        ];
    }
}




