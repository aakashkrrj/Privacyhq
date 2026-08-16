<?php
// governance/backend/models/RiskRegister.php

namespace Backend\Models;

class RiskRegister
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getOrCreateCategory($categoryName)
    {
        $categoryName = trim($categoryName ?: 'Data Privacy');
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

    /**
     * Deterministic backend-authoritative risk scoring engine
     * Score = Likelihood (1-5) * Impact (1-5)
     * Thresholds:
     * 17 - 25 = Critical
     * 10 - 16 = High
     * 5  - 9  = Medium
     * 1  - 4  = Low
     */
    public function calculateScoreAndLevel($likelihood, $impact)
    {
        $l = max(1, min(5, (int)$likelihood));
        $i = max(1, min(5, (int)$impact));
        $score = $l * $i;

        if ($score >= 17) {
            $level = 'Critical';
        } elseif ($score >= 10) {
            $level = 'High';
        } elseif ($score >= 5) {
            $level = 'Medium';
        } else {
            $level = 'Low';
        }

        return [
            'likelihood' => $l,
            'impact' => $i,
            'score' => $score,
            'level' => $level
        ];
    }

    /**
     * Create a new risk record
     */
    public function createRisk($data, $userId = 1)
    {
        $inh = $this->calculateScoreAndLevel($data['inherent_likelihood'] ?? 3, $data['inherent_impact'] ?? 3);
        $res = $this->calculateScoreAndLevel($data['residual_likelihood'] ?? 2, $data['residual_impact'] ?? 2);

        $assessmentId = $data['assessment_id'] ?? 1;
        $title = trim($data['title'] ?? '');
        $category = trim($data['category'] ?? 'Data Privacy');
        $riskSource = trim($data['risk_source'] ?? 'Internal Audit');
        $affectedAsset = trim($data['affected_asset'] ?? 'Core System');
        $owner = trim($data['owner'] ?? 'Compliance Team');
        $department = trim($data['department'] ?? 'Privacy Governance');
        $treatmentStrategy = trim($data['treatment_strategy'] ?? 'Mitigate / Reduce');
        $targetDate = !empty($data['target_date']) ? $data['target_date'] : null;
        $status = trim($data['status'] ?? 'open');
        $mitigationPlan = trim($data['mitigation'] ?? ($data['mitigation_plan'] ?? ''));

        // Generate risk code
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM assessment_risks");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $riskCode = 'RSK-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $riskCategoryId = $this->getOrCreateCategory($category);

        $stmt = $this->pdo->prepare("
            INSERT INTO assessment_risks 
                (assessment_id, risk_category_id, risk_code, description, category, risk_source, affected_asset, owner, department,
                 inherent_likelihood, inherent_impact, inherent_score, inherent_level,
                 residual_likelihood, residual_impact, residual_score, residual_level,
                 treatment_strategy, status, target_date, created_by, created_at, updated_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $assessmentId,
            $riskCategoryId,
            $riskCode,
            $title,
            $category,
            $riskSource,
            $affectedAsset,
            $owner,
            $department,
            $inh['likelihood'],
            $inh['impact'],
            $inh['score'],
            $inh['level'],
            $res['likelihood'],
            $res['impact'],
            $res['score'],
            $res['level'],
            $treatmentStrategy,
            $status,
            $targetDate,
            $userId
        ]);

        $riskId = $this->pdo->lastInsertId();

        // Save initial mitigation details if provided
        if (!empty($mitigationPlan)) {
            $stmtMit = $this->pdo->prepare("
                INSERT INTO risk_mitigations (risk_id, mitigation_title, implementation_details, mitigation_owner, target_date, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'In Progress', NOW(), NOW())
            ");
            $stmtMit->execute([$riskId, $title . ' Mitigation Plan', $mitigationPlan, $owner, $targetDate]);
        }

        $this->logHistory($riskId, 'Risk Created', $userId, null, $res['score'], null, $res['level'], "Risk record created with code {$riskCode}.");

        return $riskId;
    }

    /**
     * Update an existing risk record
     */
    public function updateRisk($id, $data, $userId = 1)
    {
        $old = $this->findById($id);
        if (!$old) {
            throw new \Exception("Risk record not found.");
        }

        $inh = $this->calculateScoreAndLevel($data['inherent_likelihood'] ?? $old['inherent_likelihood'], $data['inherent_impact'] ?? $old['inherent_impact']);
        $res = $this->calculateScoreAndLevel($data['residual_likelihood'] ?? $old['residual_likelihood'], $data['residual_impact'] ?? $old['residual_impact']);

        $title = trim($data['title'] ?? $old['title']);
        $category = trim($data['category'] ?? $old['category']);
        $riskSource = trim($data['risk_source'] ?? $old['risk_source']);
        $affectedAsset = trim($data['affected_asset'] ?? $old['affected_asset']);
        $owner = trim($data['owner'] ?? $old['owner']);
        $department = trim($data['department'] ?? $old['department']);
        $treatmentStrategy = trim($data['treatment_strategy'] ?? $old['treatment_strategy']);
        $targetDate = !empty($data['target_date']) ? $data['target_date'] : $old['target_date'];
        $status = trim($data['status'] ?? $old['status_db']);
        $mitigationPlan = trim($data['mitigation'] ?? ($data['mitigation_plan'] ?? $old['mitigation']));

        $riskCategoryId = $this->getOrCreateCategory($category);

        $stmt = $this->pdo->prepare("
            UPDATE assessment_risks 
            SET risk_category_id = ?,
                description = ?,
                category = ?,
                risk_source = ?,
                affected_asset = ?,
                owner = ?,
                department = ?,
                inherent_likelihood = ?,
                inherent_impact = ?,
                inherent_score = ?,
                inherent_level = ?,
                residual_likelihood = ?,
                residual_impact = ?,
                residual_score = ?,
                residual_level = ?,
                treatment_strategy = ?,
                status = ?,
                target_date = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");

        $success = $stmt->execute([
            $riskCategoryId,
            $title,
            $category,
            $riskSource,
            $affectedAsset,
            $owner,
            $department,
            $inh['likelihood'],
            $inh['impact'],
            $inh['score'],
            $inh['level'],
            $res['likelihood'],
            $res['impact'],
            $res['score'],
            $res['level'],
            $treatmentStrategy,
            $status,
            $targetDate,
            $userId,
            $id
        ]);

        if ($success) {
            // Update or insert mitigation record
            $checkMit = $this->pdo->prepare("SELECT id FROM risk_mitigations WHERE risk_id = ? LIMIT 1");
            $checkMit->execute([$id]);
            $mitId = $checkMit->fetchColumn();

            if ($mitId) {
                $stmtMit = $this->pdo->prepare("
                    UPDATE risk_mitigations 
                    SET implementation_details = ?, mitigation_owner = ?, target_date = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmtMit->execute([$mitigationPlan, $owner, $targetDate, $mitId]);
            } else if (!empty($mitigationPlan)) {
                $stmtMit = $this->pdo->prepare("
                    INSERT INTO risk_mitigations (risk_id, mitigation_title, implementation_details, mitigation_owner, target_date, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'In Progress', NOW(), NOW())
                ");
                $stmtMit->execute([$id, $title . ' Mitigation Plan', $mitigationPlan, $owner, $targetDate]);
            }

            if ($old['residual_score'] != $res['score'] || $old['residual_level'] != $res['level']) {
                $this->logHistory($id, 'Risk Re-evaluated', $userId, $old['residual_score'], $res['score'], $old['residual_level'], $res['level'], "Residual score re-calculated to {$res['score']} ({$res['level']}).");
            }
            if ($old['status_db'] !== $status) {
                $this->logHistory($id, 'Status Updated', $userId, $old['residual_score'], $res['score'], $old['status_db'], $status, "Status updated from {$old['status_db']} to {$status}.");
            }
        }

        return $success;
    }

    /**
     * Soft delete risk
     */
    public function deleteRisk($id, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE assessment_risks SET deleted_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            $this->logHistory($id, 'Risk Deleted', $userId, null, null, null, null, "Risk record moved to trash/soft-deleted.");
        }
        return $success;
    }

    /**
     * Fetch single risk item details
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT ar.*,
                   ar.id,
                   COALESCE(ar.risk_code, CONCAT('RSK-', LPAD(ar.id, 4, '0'))) AS risk_code,
                   ar.description AS title,
                   ar.category,
                   ar.status AS status_db,
                   rmit.id AS mitigation_id,
                   rmit.implementation_details AS mitigation,
                   rmit.mitigation_owner,
                   rmit.progress AS mitigation_progress,
                   rmit.status AS mitigation_status,
                   rmit.control_details,
                   u.email AS creator_email, u.first_name AS creator_first, u.last_name AS creator_last
            FROM assessment_risks ar
            LEFT JOIN risk_mitigations rmit ON ar.id = rmit.risk_id
            LEFT JOIN users u ON ar.created_by = u.id
            WHERE ar.id = ? AND ar.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated and filtered risk list
     */
    public function getList($search = null, $categoryFilter = null, $riskLevelFilter = null, $statusFilter = null, $treatmentFilter = null, $ownerFilter = null, $limit = 10, $offset = 0)
    {
        $whereClauses = ["ar.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(ar.description LIKE ? OR ar.risk_code LIKE ? OR ar.affected_asset LIKE ? OR ar.owner LIKE ?)";
            $term = "%" . trim($search) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($categoryFilter)) {
            $whereClauses[] = "ar.category = ?";
            $params[] = $categoryFilter;
        }
        if (!empty($riskLevelFilter)) {
            $whereClauses[] = "ar.residual_level = ?";
            $params[] = $riskLevelFilter;
        }
        if (!empty($statusFilter)) {
            $whereClauses[] = "ar.status = ?";
            $params[] = $statusFilter;
        }
        if (!empty($treatmentFilter)) {
            $whereClauses[] = "ar.treatment_strategy = ?";
            $params[] = $treatmentFilter;
        }
        if (!empty($ownerFilter)) {
            $whereClauses[] = "ar.owner = ?";
            $params[] = $ownerFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching
        $countSql = "SELECT COUNT(*) FROM assessment_risks ar $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch paginated items
        $sql = "
            SELECT 
                ar.id,
                COALESCE(ar.risk_code, CONCAT('RSK-', LPAD(ar.id, 4, '0'))) AS risk_code,
                ar.description AS title,
                ar.category,
                ar.risk_source,
                ar.affected_asset,
                ar.owner,
                ar.department,
                ar.inherent_likelihood,
                ar.inherent_impact,
                ar.inherent_score,
                ar.inherent_level,
                ar.residual_likelihood,
                ar.residual_impact,
                ar.residual_score,
                ar.residual_level,
                ar.treatment_strategy,
                ar.status AS status_db,
                ar.target_date,
                ar.created_at,
                rmit.implementation_details AS mitigation,
                rmit.progress AS mitigation_progress,
                rmit.status AS mitigation_status
            FROM assessment_risks ar
            LEFT JOIN risk_mitigations rmit ON ar.id = rmit.risk_id
            $whereSql
            ORDER BY ar.residual_score DESC, ar.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Map status names for UI backward compatibility
        foreach ($items as &$i) {
            $i['likelihood'] = $i['residual_likelihood'] >= 4 ? 'High' : ($i['residual_likelihood'] >= 3 ? 'Medium' : 'Low');
            $i['impact'] = $i['residual_impact'] >= 4 ? 'High' : ($i['residual_impact'] >= 3 ? 'Medium' : 'Low');
            $i['risk_level'] = $i['residual_level'];
            $i['status'] = ($i['status_db'] === 'mitigated') ? 'Mitigated' : (($i['status_db'] === 'in_progress' || $i['status_db'] === 'in review') ? 'In Review' : 'Open');
        }

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Dashboard live telemetry metrics
     */
    public function getDashboardMetrics()
    {
        $kpiSql = "
            SELECT 
                COUNT(*) AS total_risks,
                SUM(IF(status != 'mitigated', 1, 0)) AS open_risks,
                SUM(IF(residual_level = 'Critical', 1, 0)) AS critical_risks,
                SUM(IF(residual_level = 'High', 1, 0)) AS high_risks,
                SUM(IF(residual_level = 'Medium', 1, 0)) AS medium_risks,
                SUM(IF(residual_level = 'Low', 1, 0)) AS low_risks,
                SUM(IF(status = 'mitigated', 1, 0)) AS mitigated_risks,
                SUM(IF(status IN ('in_progress', 'in review', 'open'), 1, 0)) AS needs_action,
                AVG(COALESCE(residual_score, 4)) AS avg_risk_score,
                SUM(IF(target_date IS NOT NULL AND target_date < CURDATE() AND status != 'mitigated', 1, 0)) AS overdue_mitigations
            FROM assessment_risks
            WHERE deleted_at IS NULL
        ";

        $data = $this->pdo->query($kpiSql)->fetch(\PDO::FETCH_ASSOC);

        // Recent activity
        $recentSql = "
            SELECT id, COALESCE(risk_code, CONCAT('RSK-', LPAD(id, 4, '0'))) AS risk_code, description AS title, category, residual_level, status, target_date
            FROM assessment_risks
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 5
        ";
        $recent = $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total_risks' => (int)($data['total_risks'] ?? 0),
            'open_risks' => (int)($data['open_risks'] ?? 0),
            'critical_risks' => (int)($data['critical_risks'] ?? 0),
            'high_risks' => (int)($data['high_risks'] ?? 0),
            'medium_risks' => (int)($data['medium_risks'] ?? 0),
            'low_risks' => (int)($data['low_risks'] ?? 0),
            'mitigated_risks' => (int)($data['mitigated_risks'] ?? 0),
            'needs_action' => (int)($data['needs_action'] ?? 0),
            'overdue_mitigations' => (int)($data['overdue_mitigations'] ?? 0),
            'avg_risk_score' => round((float)($data['avg_risk_score'] ?? 0), 1),
            'recent_activity' => $recent
        ];
    }

    /**
     * Interactive 5x5 Risk Matrix data (Likelihood 1-5 x Impact 1-5)
     */
    public function getMatrixData($type = 'residual')
    {
        $lCol = ($type === 'inherent') ? 'inherent_likelihood' : 'residual_likelihood';
        $iCol = ($type === 'inherent') ? 'inherent_impact' : 'residual_impact';
        $scoreCol = ($type === 'inherent') ? 'inherent_score' : 'residual_score';
        $levelCol = ($type === 'inherent') ? 'inherent_level' : 'residual_level';

        $sql = "
            SELECT 
                id, 
                COALESCE(risk_code, CONCAT('RSK-', LPAD(id, 4, '0'))) AS risk_code,
                description AS title,
                category,
                owner,
                $lCol AS likelihood,
                $iCol AS impact,
                $scoreCol AS score,
                $levelCol AS level,
                status
            FROM assessment_risks
            WHERE deleted_at IS NULL
        ";
        $risks = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Initialize 5x5 grid
        $grid = [];
        for ($l = 5; $l >= 1; $l--) {
            for ($i = 1; $i <= 5; $i++) {
                $score = $l * $i;
                $level = ($score >= 17) ? 'Critical' : (($score >= 10) ? 'High' : (($score >= 5) ? 'Medium' : 'Low'));
                $grid["{$l}_{$i}"] = [
                    'likelihood' => $l,
                    'impact' => $i,
                    'score' => $score,
                    'level' => $level,
                    'count' => 0,
                    'items' => []
                ];
            }
        }

        foreach ($risks as $r) {
            $l = max(1, min(5, (int)$r['likelihood']));
            $i = max(1, min(5, (int)$r['impact']));
            $key = "{$l}_{$i}";
            if (isset($grid[$key])) {
                $grid[$key]['count']++;
                $grid[$key]['items'][] = $r;
            }
        }

        return array_values($grid);
    }

    /**
     * Save/update mitigation details for a risk
     */
    public function saveMitigation($riskId, $mitigationTitle, $details, $owner, $targetDate, $progress = 0, $status = 'In Progress', $controlDetails = null, $userId = 1)
    {
        $risk = $this->findById($riskId);
        if (!$risk) {
            throw new \Exception("Risk record not found.");
        }

        $checkMit = $this->pdo->prepare("SELECT id FROM risk_mitigations WHERE risk_id = ? LIMIT 1");
        $checkMit->execute([$riskId]);
        $mitId = $checkMit->fetchColumn();

        $progress = max(0, min(100, (int)$progress));

        if ($mitId) {
            $stmt = $this->pdo->prepare("
                UPDATE risk_mitigations 
                SET mitigation_title = ?, implementation_details = ?, mitigation_owner = ?, target_date = ?, progress = ?, status = ?, control_details = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$mitigationTitle, $details, $owner, $targetDate ?: null, $progress, $status, $controlDetails, $mitId]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO risk_mitigations (risk_id, mitigation_title, implementation_details, mitigation_owner, target_date, progress, status, control_details, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$riskId, $mitigationTitle, $details, $owner, $targetDate ?: null, $progress, $status, $controlDetails]);
        }

        // If progress is 100%, set risk status to mitigated
        if ($progress >= 100 || $status === 'Completed') {
            $stmtUpd = $this->pdo->prepare("UPDATE assessment_risks SET status = 'mitigated', updated_at = NOW() WHERE id = ?");
            $stmtUpd->execute([$riskId]);
        }

        $this->logHistory($riskId, 'Mitigation Updated', $userId, $risk['residual_score'], $risk['residual_score'], $risk['residual_level'], $risk['residual_level'], "Mitigation progress updated to {$progress}% ({$status}).");

        return true;
    }

    /**
     * Log history event to risk_history
     */
    public function logHistory($riskId, $action, $userId = 1, $oldScore = null, $newScore = null, $oldLevel = null, $newLevel = null, $details = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO risk_history (risk_id, action, performed_by, old_score, new_score, old_level, new_level, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$riskId, $action, $userId, $oldScore, $newScore, $oldLevel, $newLevel, $details]);
        } catch (\Throwable $e) {}
    }

    /**
     * Get risk history logs
     */
    public function getHistory($riskId)
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, u.email, u.first_name, u.last_name
            FROM risk_history h
            LEFT JOIN users u ON h.performed_by = u.id
            WHERE h.risk_id = ?
            ORDER BY h.id DESC
        ");
        $stmt->execute([$riskId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
