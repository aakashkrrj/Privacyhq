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

        return $this->pdo->lastInsertId();
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
                q.weight_yes,
                q.weight_no,
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
    public function updateStatus($assessmentId, $statusName)
    {
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
        return $stmt->execute([$statusId, $assessmentId]);
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
}
