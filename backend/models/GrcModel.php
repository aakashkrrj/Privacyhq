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

    public function updateControl($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE grc_controls 
            SET name = ?, description = ?, control_type = ?, category = ?, owner = ?, frequency = ?, status = ?, effectiveness = ?, review_date = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['control_type'] ?? 'Preventive',
            $data['category'] ?? 'Security',
            $data['owner'] ?? '',
            $data['frequency'] ?? 'Annual',
            $data['status'] ?? 'Draft',
            $data['effectiveness'] ?? 'Not Tested',
            $data['review_date'] ?? null,
            $id
        ]);
    }

    public function deleteControl($id) {
        $stmt = $this->pdo->prepare("UPDATE grc_controls SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
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

    public function updateRequirement($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE grc_compliance_requirements 
            SET framework = ?, statement = ?, category = ?, applicable_scope = ?, owner = ?, status = ?, review_date = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $data['framework'] ?? 'GDPR',
            $data['statement'] ?? '',
            $data['category'] ?? 'General',
            $data['applicable_scope'] ?? 'Global',
            $data['owner'] ?? '',
            $data['status'] ?? 'Active',
            $data['review_date'] ?? null,
            $id
        ]);
    }

    public function deleteRequirement($id) {
        $stmt = $this->pdo->prepare("UPDATE grc_compliance_requirements SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
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

    public function updateAudit($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE grc_audits 
            SET name = ?, scope = ?, audit_type = ?, owner = ?, start_date = ?, end_date = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $data['name'],
            $data['scope'] ?? '',
            $data['audit_type'] ?? 'Internal',
            $data['owner'] ?? '',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 'Planned',
            $id
        ]);
    }

    public function deleteAudit($id) {
        $stmt = $this->pdo->prepare("UPDATE grc_audits SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
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

    public function updateFinding($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE grc_findings 
            SET title = ?, description = ?, severity = ?, owner = ?, due_date = ?, status = ?, remediation_plan = ?, remediation_status = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['severity'] ?? 'Medium',
            $data['owner'] ?? '',
            $data['due_date'] ?? null,
            $data['status'] ?? 'Open',
            $data['remediation_plan'] ?? '',
            $data['remediation_status'] ?? 'Pending',
            $id
        ]);
    }

    public function deleteFinding($id) {
        $stmt = $this->pdo->prepare("UPDATE grc_findings SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- Frameworks (Phase 9) ---
    public function createFramework($data, $userId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO grc_frameworks (name, version, description, status, owner, effective_date, review_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['version'] ?? '1.0',
            $data['description'] ?? '',
            $data['status'] ?? 'Draft',
            $data['owner'] ?? '',
            $data['effective_date'] ?? date('Y-m-d'),
            $data['review_date'] ?? date('Y-m-d', strtotime('+1 year')),
            $userId
        ]);
        return $this->pdo->lastInsertId();
    }

    public function getFramework($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM grc_frameworks WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateFramework($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE grc_frameworks 
            SET name = ?, version = ?, description = ?, status = ?, owner = ?, effective_date = ?, review_date = ?
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $data['name'],
            $data['version'] ?? '1.0',
            $data['description'] ?? '',
            $data['status'] ?? 'Draft',
            $data['owner'] ?? '',
            $data['effective_date'] ?? null,
            $data['review_date'] ?? null,
            $id
        ]);
    }

    // --- Assessment & Scoring (Phase 9) ---
    public function assessRequirement($reqId, $assessmentStatus, $notes = '') {
        $stmt = $this->pdo->prepare("
            UPDATE grc_compliance_requirements
            SET assessment_status = ?, assessment_date = CURRENT_DATE, assessment_notes = ?
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$assessmentStatus, $notes, $reqId]);
        
        // Recalculate framework score
        $req = $this->pdo->query("SELECT framework_id FROM grc_compliance_requirements WHERE id = $reqId")->fetch();
        if ($req && $req['framework_id']) {
            $this->calculateFrameworkScore($req['framework_id']);
        }
        return true;
    }

    public function calculateFrameworkScore($frameworkId) {
        $stmt = $this->pdo->prepare("
            SELECT assessment_status, COUNT(*) as cnt 
            FROM grc_compliance_requirements 
            WHERE framework_id = ? AND deleted_at IS NULL 
            GROUP BY assessment_status
        ");
        $stmt->execute([$frameworkId]);
        $stats = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $compliant = $stats['Compliant'] ?? 0;
        $partial = $stats['Partially Compliant'] ?? 0;
        $nonCompliant = $stats['Non-Compliant'] ?? 0;
        $notAssessed = $stats['Not Assessed'] ?? 0;
        $notApplicable = $stats['Not Applicable'] ?? 0;

        $assessedCount = $compliant + $partial + $nonCompliant;
        $totalApplicable = $assessedCount + $notAssessed;

        // Scoring Formula: (Compliant*100 + Partial*50) / (Applicable Assessed)
        $score = 0.00;
        if ($assessedCount > 0) {
            $score = (($compliant * 100) + ($partial * 50)) / $assessedCount;
        }

        $stmtUpdate = $this->pdo->prepare("
            UPDATE grc_frameworks 
            SET score = ?, assessed_count = ?, compliant_count = ?, partial_count = ?, non_compliant_count = ?, not_assessed_count = ?, calculated_at = NOW()
            WHERE id = ?
        ");
        $stmtUpdate->execute([$score, $assessedCount, $compliant, $partial, $nonCompliant, $notAssessed, $frameworkId]);
        return $score;
    }

    public function mapRequirementToControl($requirementId, $controlId) {
        // Prevent duplicate mapping
        $check = $this->pdo->prepare("SELECT 1 FROM grc_requirement_controls WHERE requirement_id = ? AND control_id = ?");
        $check->execute([$requirementId, $controlId]);
        if ($check->fetch()) return false;

        $stmt = $this->pdo->prepare("INSERT INTO grc_requirement_controls (requirement_id, control_id) VALUES (?, ?)");
        return $stmt->execute([$requirementId, $controlId]);
    }

    // --- Metrics ---
    public function getDashboardMetrics() {
        $totalRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE deleted_at IS NULL")->fetchColumn();
        $highRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE deleted_at IS NULL AND residual_level IN ('High', 'Critical')")->fetchColumn();
        $openFindings = $this->pdo->query("SELECT COUNT(*) FROM grc_findings WHERE deleted_at IS NULL AND status = 'Open'")->fetchColumn();
        $activeAudits = $this->pdo->query("SELECT COUNT(*) FROM grc_audits WHERE deleted_at IS NULL AND status = 'Active'")->fetchColumn();
        $controlStatus = $this->pdo->query("SELECT status, COUNT(*) as cnt FROM grc_controls WHERE deleted_at IS NULL GROUP BY status")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $frameworks = $this->pdo->query("SELECT COUNT(*) FROM grc_frameworks WHERE deleted_at IS NULL")->fetchColumn();
        $avgScore = $this->pdo->query("SELECT AVG(score) FROM grc_frameworks WHERE deleted_at IS NULL AND status = 'Active'")->fetchColumn();

        return [
            'total_risks' => (int)$totalRisks,
            'high_risks' => (int)$highRisks,
            'open_findings' => (int)$openFindings,
            'active_audits' => (int)$activeAudits,
            'control_status' => $controlStatus,
            'total_frameworks' => (int)$frameworks,
            'average_compliance_score' => round((float)$avgScore, 2)
        ];
    }
}
