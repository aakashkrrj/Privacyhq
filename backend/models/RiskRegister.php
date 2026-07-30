<?php
namespace Backend\Models;

class RiskRegister {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Resolves an assessment ID dynamically based on legacy behavior.
     * Legacy UI forces the first available assessment ID for now.
     */
    public function resolveAssessmentId() {
        $stmt = $this->pdo->query("SELECT id FROM privacy_assessments LIMIT 1");
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $res ? $res['id'] : 1; // Fallback to 1 if none
    }

    public function getOrCreateCategory($categoryName) {
        $stmt = $this->pdo->prepare("SELECT id FROM risk_categories WHERE category_name = ? LIMIT 1");
        $stmt->execute([$categoryName]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($res) {
            return $res['id'];
        }

        $insertStmt = $this->pdo->prepare("INSERT INTO risk_categories (category_name) VALUES (?)");
        $insertStmt->execute([$categoryName]);
        return $this->pdo->lastInsertId();
    }

    public function getOrCreateMatrix($likelihood, $impact) {
        $likelihood_level = ($likelihood === 'High') ? 3 : (($likelihood === 'Medium') ? 2 : 1);
        $impact_level = ($impact === 'High') ? 3 : (($impact === 'Medium') ? 2 : 1);

        $stmt = $this->pdo->prepare("SELECT id FROM risk_matrix WHERE likelihood_level = ? AND impact_level = ? LIMIT 1");
        $stmt->execute([$likelihood_level, $impact_level]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($res) {
            return $res['id'];
        }

        $risk_score = $likelihood_level * $impact_level;
        $risk_level_name = ($risk_score >= 6) ? 'High' : (($risk_score >= 3) ? 'Medium' : 'Low');

        $insertStmt = $this->pdo->prepare("INSERT INTO risk_matrix (impact_level, likelihood_level, impact_name, likelihood_name, risk_score, risk_level_name) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$impact_level, $likelihood_level, $impact, $likelihood, $risk_score, $risk_level_name]);
        return $this->pdo->lastInsertId();
    }

    public function createRisk($assessmentId, $categoryId, $matrixId, $title, $statusDb, $createdBy, $mitigation) {
        $stmt = $this->pdo->prepare("INSERT INTO assessment_risks (assessment_id, risk_category_id, description, inherent_risk_matrix_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$assessmentId, $categoryId, $title, $matrixId, $statusDb, $createdBy]);
        $riskId = $this->pdo->lastInsertId();

        if (!empty($mitigation)) {
            $stmtMit = $this->pdo->prepare("INSERT INTO risk_mitigations (risk_id, implementation_details) VALUES (?, ?)");
            $stmtMit->execute([$riskId, $mitigation]);
        }

        return $riskId;
    }

    public function getList() {
        $query = "
            SELECT 
                ar.id,
                ar.description as title,
                rc.category_name as category,
                rm.likelihood_name as likelihood,
                rm.impact_name as impact,
                rm.risk_level_name as risk_level,
                ar.status as status_db,
                rmit.implementation_details as mitigation
            FROM assessment_risks ar
            LEFT JOIN risk_categories rc ON ar.risk_category_id = rc.id
            LEFT JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
            LEFT JOIN risk_mitigations rmit ON ar.id = rmit.risk_id
            ORDER BY ar.id DESC
        ";
        $stmt = $this->pdo->query($query);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $total = count($items);
        return ['total' => $total, 'items' => $items];
    }

    public function getDashboardMetrics() {
        // High Risks
        $highQuery = "
            SELECT COUNT(*) 
            FROM assessment_risks ar
            JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
            WHERE rm.risk_level_name = 'High' AND ar.status != 'mitigated'
        ";
        $highRisks = $this->pdo->query($highQuery)->fetchColumn();

        // Total Risks
        $totalRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks")->fetchColumn();

        // Mitigated Risks
        $mitigatedRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'mitigated'")->fetchColumn();

        // Needs Action
        $needsAction = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'open'")->fetchColumn();

        return [
            'total_risks' => $totalRisks ?? 0,
            'high_risks' => $highRisks ?? 0,
            'mitigated_risks' => $mitigatedRisks ?? 0,
            'needs_action' => $needsAction ?? 0
        ];
    }
}
