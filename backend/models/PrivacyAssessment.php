<?php
// backend/models/PrivacyAssessment.php

namespace Backend\Models;

class PrivacyAssessment
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new assessment
     */
    public function create($processingActivityId, $templateId, $title, $assignedTo, $reviewerId, $priority, $dueDate, $creatorId)
    {
        // Get or Create status 'Draft' (id = 1 usually)
        $statusId = 1; // Draft status ID

        $stmt = $this->pdo->prepare("
            INSERT INTO privacy_assessments 
                (processing_activity_id, template_id, status_id, title, assigned_to, reviewer_id, priority, due_date, created_by, updated_by)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $processingActivityId,
            $templateId,
            $statusId,
            $title,
            $assignedTo,
            $reviewerId,
            $priority,
            $dueDate,
            $creatorId,
            $creatorId
        ]);

        $id = $this->pdo->lastInsertId();
        $this->addStatusHistory($id, null, $statusId, $creatorId, 'Initial DPIA assessment creation');
        return $id;
    }

    /**
     * Get list of all assessments
     */
    public function getList($assignedTo = null)
    {
        $sql = "
            SELECT 
                pa.*, 
                t.template_name,
                ast.status_name AS status,
                u_assign.email AS assessor_email,
                u_assign.first_name AS assessor_first,
                u_assign.last_name AS assessor_last,
                u_rev.email AS reviewer_email,
                u_rev.first_name AS reviewer_first,
                u_rev.last_name AS reviewer_last
            FROM privacy_assessments pa
            LEFT JOIN assessment_templates t ON pa.template_id = t.id
            LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
            LEFT JOIN users u_assign ON pa.assigned_to = u_assign.id
            LEFT JOIN users u_rev ON pa.reviewer_id = u_rev.id
            WHERE pa.deleted_at IS NULL
        ";

        $params = [];
        if ($assignedTo !== null) {
            $sql .= " AND pa.assigned_to = ?";
            $params[] = $assignedTo;
        }

        $sql .= " ORDER BY pa.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get detail of a specific assessment
     */
    public function getById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                pa.*,
                ast.status_name AS status,
                t.template_name,
                u_assign.email AS assessor_email,
                u_assign.first_name AS assessor_first,
                u_assign.last_name AS assessor_last,
                u_rev.email AS reviewer_email,
                u_rev.first_name AS reviewer_first,
                u_rev.last_name AS reviewer_last
            FROM privacy_assessments pa
            LEFT JOIN assessment_templates t ON pa.template_id = t.id
            LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
            LEFT JOIN users u_assign ON pa.assigned_to = u_assign.id
            LEFT JOIN users u_rev ON pa.reviewer_id = u_rev.id
            WHERE pa.id = ? AND pa.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get sections and questions for a template
     */
    public function getQuestionsByTemplate($templateId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                s.id AS section_id,
                s.section_name,
                s.description AS section_description,
                q.id AS question_id,
                q.question_text,
                q.question_type,
                q.is_required,
                q.help_text,
                q.placeholder,
                q.options_json,
                COALESCE(q.weight_yes, 0) AS weight_yes,
                COALESCE(q.weight_no, 0) AS weight_no,
                q.score_options_json,
                q.risk_category_id
            FROM assessment_sections s
            JOIN assessment_questions q ON q.section_id = s.id
            WHERE s.template_id = ? AND s.deleted_at IS NULL AND q.deleted_at IS NULL
            ORDER BY s.display_order ASC, q.display_order ASC
        ");
        $stmt->execute([$templateId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update full assessment details
     */
    public function update($id, $title, $assignedTo, $reviewerId, $priority, $dueDate)
    {
        $stmt = $this->pdo->prepare("
            UPDATE privacy_assessments 
            SET title = ?, assigned_to = ?, reviewer_id = ?, priority = ?, due_date = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$title, $assignedTo, $reviewerId, $priority, $dueDate, $id]);
    }

    /**
     * Soft delete assessment
     */
    public function delete($id)
    {
        $stmt = $this->pdo->prepare("UPDATE privacy_assessments SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get responses for an assessment
     */
    public function getResponses($assessmentId)
    {
        $stmt = $this->pdo->prepare("
            SELECT question_id, response_text 
            FROM assessment_responses 
            WHERE assessment_id = ?
        ");
        $stmt->execute([$assessmentId]);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Save/autosave a single response
     */
    public function saveResponse($assessmentId, $questionId, $responseText, $responseJson, $userId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO assessment_responses 
                (assessment_id, question_id, response_text, response_json, answered_by, updated_at)
            VALUES 
                (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                response_text = VALUES(response_text),
                response_json = VALUES(response_json),
                answered_by = VALUES(answered_by),
                updated_at = NOW()
        ");
        return $stmt->execute([$assessmentId, $questionId, $responseText, $responseJson, $userId]);
    }

    /**
     * Update status
     */
    public function updateStatus($assessmentId, $statusName, $changedBy = null, $reason = null)
    {
        // Get previous status ID
        $prevStmt = $this->pdo->prepare("SELECT status_id FROM privacy_assessments WHERE id = ?");
        $prevStmt->execute([$assessmentId]);
        $prevStatusId = $prevStmt->fetchColumn();

        // Get status ID
        $stmtStatus = $this->pdo->prepare("SELECT id FROM assessment_statuses WHERE status_name = ? LIMIT 1");
        $stmtStatus->execute([$statusName]);
        $statusId = $stmtStatus->fetchColumn();

        if (!$statusId) {
            throw new \Exception("Status name '{$statusName}' is invalid.");
        }

        $completedAtClause = ($statusName === 'Approved') ? ", completed_at = NOW()" : "";

        $stmt = $this->pdo->prepare("
            UPDATE privacy_assessments 
            SET status_id = ?, updated_at = NOW() {$completedAtClause}
            WHERE id = ?
        ");
        $res = $stmt->execute([$statusId, $assessmentId]);

        if ($res) {
            if (!$changedBy && session_status() !== PHP_SESSION_NONE) {
                $changedBy = $_SESSION['user_id'] ?? 1;
            }
            $this->addStatusHistory($assessmentId, $prevStatusId, $statusId, $changedBy ?: 1, $reason);
        }

        return $res;
    }

    /**
     * Add notes / reviewer comments
     */
    public function addNote($assessmentId, $noteText, $userId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO assessment_notes (assessment_id, note_text, created_by)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$assessmentId, $noteText, $userId]);
    }

    /**
     * Get notes / reviewer comments
     */
    public function getNotes($assessmentId)
    {
        $stmt = $this->pdo->prepare("
            SELECT n.*, u.email, u.first_name, u.last_name 
            FROM assessment_notes n
            LEFT JOIN users u ON n.created_by = u.id
            WHERE n.assessment_id = ?
            ORDER BY n.id DESC
        ");
        $stmt->execute([$assessmentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Add document upload (evidence)
     */
    public function addDocument($assessmentId, $filePath, $userId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO assessment_documents (assessment_id, document_type, file_path, uploaded_by)
            VALUES (?, 'evidence', ?, ?)
        ");
        return $stmt->execute([$assessmentId, $filePath, $userId]);
    }

    /**
     * Get uploaded documents
     */
    public function getDocuments($assessmentId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM assessment_documents 
            WHERE assessment_id = ?
            ORDER BY id DESC
        ");
        $stmt->execute([$assessmentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Add risk finding
     */
    public function addRiskFinding($assessmentId, $categoryId, $description, $inherentRiskId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO assessment_risks (assessment_id, risk_category_id, description, inherent_risk_matrix_id, status)
            VALUES (?, ?, ?, ?, 'open')
        ");
        return $stmt->execute([$assessmentId, $categoryId, $description, $inherentRiskId]);
    }

    /**
     * Clear risk findings for a recalculation
     */
    public function clearRiskFindings($assessmentId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM assessment_risks WHERE assessment_id = ?");
        return $stmt->execute([$assessmentId]);
    }

    /**
     * Get risk findings
     */
    public function getRiskFindings($assessmentId)
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, c.category_name 
            FROM assessment_risks r
            LEFT JOIN risk_categories c ON r.risk_category_id = c.id
            WHERE r.assessment_id = ?
        ");
        $stmt->execute([$assessmentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Reassign metadata
     */
    public function updateMetadata($assessmentId, $assignedTo, $reviewerId, $dueDate, $priority)
    {
        $stmt = $this->pdo->prepare("
            UPDATE privacy_assessments 
            SET assigned_to = ?, reviewer_id = ?, due_date = ?, priority = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$assignedTo, $reviewerId, $dueDate, $priority, $assessmentId]);
    }

    /**
     * Get Dashboard Telemetry Metrics
     */
    public function getDashboardMetrics()
    {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                SUM(IF(ast.status_name = 'Draft' OR ast.status_name = 'Assigned', 1, 0)) AS draft_count,
                SUM(IF(ast.status_name = 'Submitted' OR ast.status_name = 'Under Review' OR ast.status_name = 'Pending Review', 1, 0)) AS pending_count,
                SUM(IF(ast.status_name = 'Approved', 1, 0)) AS approved_count,
                SUM(IF(ast.status_name = 'Rejected', 1, 0)) AS rejected_count,
                SUM(IF(pa.due_date < CURRENT_DATE AND ast.status_name != 'Approved', 1, 0)) AS overdue_count
            FROM privacy_assessments pa
            LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
            WHERE pa.deleted_at IS NULL
        ";
        $counts = $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);

        // Risk distribution
        $riskSql = "
            SELECT 
                COALESCE(calculated_risk_level, 'Low') AS risk_level,
                COUNT(*) AS cnt
            FROM privacy_assessments
            WHERE deleted_at IS NULL
            GROUP BY calculated_risk_level
        ";
        $riskRows = $this->pdo->query($riskSql)->fetchAll(\PDO::FETCH_ASSOC);
        $riskDist = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
        foreach ($riskRows as $rr) {
            $lvl = ucfirst(strtolower($rr['risk_level']));
            if (isset($riskDist[$lvl])) {
                $riskDist[$lvl] = (int)$rr['cnt'];
            }
        }

        // Recent Assessments
        $recent = $this->pdo->query("
            SELECT pa.id, pa.title, pa.priority, pa.due_date, pa.calculated_risk_level AS risk_level, ast.status_name AS status
            FROM privacy_assessments pa
            LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
            WHERE pa.deleted_at IS NULL
            ORDER BY pa.id DESC LIMIT 5
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total' => (int)($counts['total'] ?? 0),
            'draft' => (int)($counts['draft_count'] ?? 0),
            'pending' => (int)($counts['pending_count'] ?? 0),
            'approved' => (int)($counts['approved_count'] ?? 0),
            'rejected' => (int)($counts['rejected_count'] ?? 0),
            'overdue' => (int)($counts['overdue_count'] ?? 0),
            'risk_distribution' => $riskDist,
            'recent' => $recent
        ];
    }

    /**
     * Add entry to assessment status history
     */
    public function addStatusHistory($assessmentId, $prevStatusId, $newStatusId, $changedBy, $reason = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO assessment_status_history (assessment_id, previous_status_id, new_status_id, changed_by, reason, changed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$assessmentId, $prevStatusId, $newStatusId, $changedBy, $reason]);
    }

    /**
     * Fetch chronological history for assessment
     */
    public function getHistory($assessmentId)
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, 
                   ast_old.status_name AS old_status, 
                   ast_new.status_name AS new_status,
                   u.email, u.first_name, u.last_name
            FROM assessment_status_history h
            LEFT JOIN assessment_statuses ast_old ON h.previous_status_id = ast_old.id
            LEFT JOIN assessment_statuses ast_new ON h.new_status_id = ast_new.id
            LEFT JOIN users u ON h.changed_by = u.id
            WHERE h.assessment_id = ?
            ORDER BY h.id DESC
        ");
        $stmt->execute([$assessmentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update calculated risk score & risk level
     */
    public function updateCalculatedRisk($assessmentId, $riskScore, $riskLevel)
    {
        $stmt = $this->pdo->prepare("
            UPDATE privacy_assessments 
            SET risk_score = ?, calculated_risk_level = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([(int)$riskScore, $riskLevel, $assessmentId]);
    }
}
