<?php
namespace Backend\Models;

class GrcModel {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    // --- Controls ---
    public function createControl($data, $userId) {
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM grc_controls");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $code = 'CTRL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO grc_controls (control_code, name, description, control_type, category, owner, frequency, status, effectiveness, review_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $code,
            $data['name'],
            $data['description'] ?? '',
            $data['control_type'] ?? 'Preventive',
            $data['category'] ?? 'Security',
            $data['owner'] ?? '',
            $data['frequency'] ?? 'Annual',
            $data['status'] ?? 'Draft',
            $data['effectiveness'] ?? 'Not Tested',
            $data['review_date'] ?? date('Y-m-d', strtotime('+1 year')),
            $userId
        ]);
        return $this->pdo->lastInsertId();
    }

    // --- Requirements ---
    public function createRequirement($data, $userId) {
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM grc_compliance_requirements");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $code = 'REQ-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO grc_compliance_requirements (req_code, framework, statement, category, applicable_scope, owner, status, review_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $code,
            $data['framework'] ?? 'GDPR',
            $data['statement'] ?? '',
            $data['category'] ?? 'General',
            $data['applicable_scope'] ?? 'Global',
            $data['owner'] ?? '',
            $data['status'] ?? 'Active',
            $data['review_date'] ?? date('Y-m-d', strtotime('+1 year')),
            $userId
        ]);
        return $this->pdo->lastInsertId();
    }

    // --- Audits ---
    public function createAudit($data, $userId) {
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM grc_audits");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $code = 'AUD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO grc_audits (audit_code, name, scope, audit_type, owner, start_date, end_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $code,
            $data['name'],
            $data['scope'] ?? '',
            $data['audit_type'] ?? 'Internal',
            $data['owner'] ?? '',
            $data['start_date'] ?? date('Y-m-d'),
            $data['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            $data['status'] ?? 'Planned',
            $userId
        ]);
        return $this->pdo->lastInsertId();
    }

    // --- Findings ---
    public function createFinding($data, $userId) {
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM grc_findings");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $code = 'FND-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO grc_findings (finding_code, title, description, severity, source_type, source_id, owner, due_date, status, remediation_plan, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $code,
            $data['title'],
            $data['description'] ?? '',
            $data['severity'] ?? 'Medium',
            $data['source_type'] ?? 'Audit',
            $data['source_id'] ?? 0,
            $data['owner'] ?? '',
            $data['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            $data['status'] ?? 'Open',
            $data['remediation_plan'] ?? '',
            $userId
        ]);
        return $this->pdo->lastInsertId();
    }

    // --- Metrics ---
    public function getDashboardMetrics() {
        $totalRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE deleted_at IS NULL")->fetchColumn();
        $highRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE deleted_at IS NULL AND residual_level IN ('High', 'Critical')")->fetchColumn();
        $openFindings = $this->pdo->query("SELECT COUNT(*) FROM grc_findings WHERE deleted_at IS NULL AND status = 'Open'")->fetchColumn();
        $activeAudits = $this->pdo->query("SELECT COUNT(*) FROM grc_audits WHERE deleted_at IS NULL AND status = 'Active'")->fetchColumn();
        $controlStatus = $this->pdo->query("SELECT status, COUNT(*) as cnt FROM grc_controls WHERE deleted_at IS NULL GROUP BY status")->fetchAll(\PDO::FETCH_KEY_PAIR);

        return [
            'total_risks' => (int)$totalRisks,
            'high_risks' => (int)$highRisks,
            'open_findings' => (int)$openFindings,
            'active_audits' => (int)$activeAudits,
            'control_status' => $controlStatus
        ];
    }
}
