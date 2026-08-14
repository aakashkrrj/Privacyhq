<?php
namespace Backend\Services;

class DashboardService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get central dashboard KPIs.
     */
    public function getKPIs($userId = null) {
        $kpis = [];

        // 1. Pending Tasks
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tasks WHERE status != 'Completed'" . ($userId ? " AND assigned_to = ?" : ""));
        $stmt->execute($userId ? [$userId] : []);
        $kpis['pending_tasks'] = (int)$stmt->fetchColumn();

        // 2. Open Incidents
        $kpis['open_incidents'] = (int)$this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'Open'")->fetchColumn();

        // 3. Pending DSR
        $kpis['pending_dsr'] = (int)$this->pdo->query("SELECT COUNT(*) FROM dsr_requests WHERE status IN ('New', 'Under Review', 'In Progress')")->fetchColumn();

        // 4. Pending Assessments
        $kpis['pending_assessments'] = (int)$this->pdo->query("
            SELECT COUNT(*) FROM privacy_assessments p
            JOIN assessment_statuses s ON p.status_id = s.id
            WHERE s.status_name IN ('Draft', 'Assigned', 'In Progress', 'Submitted', 'Under Review')
        ")->fetchColumn();

        // 5. High Risks
        $kpis['high_risks'] = (int)$this->pdo->query("SELECT COUNT(*) FROM risk_register WHERE likelihood = 'High' OR impact = 'High'")->fetchColumn();

        // 6. Policy Reviews (Policies in Draft or Under Review)
        $kpis['policy_reviews'] = (int)$this->pdo->query("SELECT COUNT(*) FROM policies WHERE status IN ('Draft', 'Under Review')")->fetchColumn();

        // 7. Vendor Reviews (Vendors in Under Audit status)
        $kpis['vendor_reviews'] = (int)$this->pdo->query("SELECT COUNT(*) FROM vendor_assessments WHERE status = 'Under Audit'")->fetchColumn();

        return $kpis;
    }
}
