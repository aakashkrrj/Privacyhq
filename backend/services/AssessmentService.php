<?php
// backend/services/AssessmentService.php

namespace Backend\Services;

class AssessmentService
{
    private $assessmentModel;
    private $pdo;

    public function __construct($assessmentModel, \PDO $pdo)
    {
        $this->assessmentModel = $assessmentModel;
        $this->pdo = $pdo;
    }

    /**
     * Create a new assessment
     */
    public function createAssessment($processingActivityId, $templateId, $title, $assignedTo, $reviewerId, $priority, $dueDate, $creatorId)
    {
        if (empty($title) || !$processingActivityId || !$templateId) {
            throw new \Exception("Required creation fields are missing.");
        }

        $id = $this->assessmentModel->create($processingActivityId, $templateId, $title, $assignedTo, $reviewerId, $priority, $dueDate, $creatorId);
        
        // Write audit log
        log_audit_event($this->pdo, 'Assessment', 'Create', $creatorId, $id, null, "Created assessment: $title");

        return $id;
    }

    /**
     * Get list of assessments with record-level security checks
     */
    public function getAssessmentsForUser($userId, $userRoleId)
    {
        // Super Admin (1) and DPO (2) can see everything. Others only see assigned.
        if ($userRoleId == 1 || $userRoleId == 2) {
            return $this->assessmentModel->getList();
        } else {
            return $this->assessmentModel->getList($userId);
        }
    }

    /**
     * Get specific assessment checking permissions
     */
    public function getAssessmentDetail($id, $userId, $userRoleId)
    {
        $assessment = $this->assessmentModel->getById($id);
        if (!$assessment) {
            throw new \Exception("Assessment not found.");
        }

        // Record-level security: only Super Admin, DPO, assignee, or reviewer can view
        if ($userRoleId != 1 && $userRoleId != 2 && $assessment['assigned_to'] != $userId && $assessment['reviewer_id'] != $userId) {
            throw new \Exception("Access Denied: You are not assigned to this assessment.");
        }

        $questions = $this->assessmentModel->getQuestionsByTemplate($assessment['template_id']);
        $responses = $this->assessmentModel->getResponses($id);
        $notes = $this->assessmentModel->getNotes($id);
        $documents = $this->assessmentModel->getDocuments($id);
        $findings = $this->assessmentModel->getRiskFindings($id);

        return [
            'assessment' => $assessment,
            'questions'  => $questions,
            'responses'  => $responses,
            'notes'      => $notes,
            'documents'  => $documents,
            'findings'   => $findings
        ];
    }

    /**
     * Save dynamic questionnaire answers (draft status)
     */
    public function saveResponses($assessmentId, $answers, $userId, $userRoleId)
    {
        $assessment = $this->assessmentModel->getById($assessmentId);
        if (!$assessment) {
            throw new \Exception("Assessment not found.");
        }

        // Check permission to modify: assignee or super admin
        if ($userRoleId != 1 && $assessment['assigned_to'] != $userId) {
            throw new \Exception("Access Denied: You cannot answer this assessment.");
        }

        // Update status to 'In Progress' if currently 'Draft' or 'Assigned'
        if (in_array($assessment['status'], ['Draft', 'Assigned'])) {
            $this->assessmentModel->updateStatus($assessmentId, 'In Progress');
        }

        foreach ($answers as $questionId => $ans) {
            $textVal = is_string($ans) ? trim($ans) : '';
            $jsonVal = is_array($ans) ? json_encode($ans) : null;
            
            $this->assessmentModel->saveResponse($assessmentId, $questionId, $textVal, $jsonVal, $userId);
        }

        return true;
    }

    /**
     * Submit assessment for review
     */
    public function submitAssessment($assessmentId, $userId, $userRoleId)
    {
        $assessment = $this->assessmentModel->getById($assessmentId);
        if (!$assessment) {
            throw new \Exception("Assessment not found.");
        }

        if ($userRoleId != 1 && $assessment['assigned_to'] != $userId) {
            throw new \Exception("Access Denied: You cannot submit this assessment.");
        }

        // Calculate scores and findings before status transition
        $this->recalculateAssessmentRisks($assessmentId);

        // Update status to 'Submitted'
        $this->assessmentModel->updateStatus($assessmentId, 'Submitted');

        // Write audit log
        log_audit_event($this->pdo, 'Assessment', 'Submit', $userId, $assessmentId, 'In Progress', 'Submitted');

        return true;
    }

    /**
     * Recalculate score and findings from responses
     */
    public function recalculateAssessmentRisks($assessmentId)
    {
        $assessment = $this->assessmentModel->getById($assessmentId);
        $questions = $this->assessmentModel->getQuestionsByTemplate($assessment['template_id']);
        $responses = $this->assessmentModel->getResponses($assessmentId);

        $this->assessmentModel->clearRiskFindings($assessmentId);

        $totalScore = 0;

        foreach ($questions as $q) {
            $qid = $q['question_id'];
            $response = $responses[$qid] ?? '';

            $score = 0;
            $hasRisk = false;
            $description = '';

            if ($q['question_type'] === 'yes_no') {
                if (strtolower($response) === 'no' && $q['weight_no'] > 0) {
                    $score = $q['weight_no'];
                    $hasRisk = true;
                    $description = "Risk detected on: " . $q['question_text'] . " (Answered No)";
                } elseif (strtolower($response) === 'yes' && $q['weight_yes'] > 0) {
                    $score = $q['weight_yes'];
                    $hasRisk = true;
                    $description = "Risk detected on: " . $q['question_text'] . " (Answered Yes)";
                }
            } elseif ($q['question_type'] === 'radio' || $q['question_type'] === 'dropdown') {
                if (!empty($q['score_options_json'])) {
                    $optScores = json_decode($q['score_options_json'], true);
                    if (isset($optScores[$response])) {
                        $score = (int)$optScores[$response];
                        if ($score >= 3) {
                            $hasRisk = true;
                            $description = "Elevated risk detected: " . $q['question_text'] . " (Answered: " . $response . ")";
                        }
                    }
                }
            }

            $totalScore += $score;

            if ($hasRisk && $q['risk_category_id']) {
                // risk_matrix: ID 1 = High (score >= 3), ID 2 = Low (score < 3)
                $riskMatrixId = ($score >= 3) ? 1 : 2;
                $this->assessmentModel->addRiskFinding($assessmentId, $q['risk_category_id'], $description, $riskMatrixId);
            }
        }

        // Determine Risk Matrix mapping
        // Low: 1, Medium: 2, High: 3
        $riskMatrixId = 1;
        if ($totalScore >= 6) {
            $riskMatrixId = 3;
        } elseif ($totalScore >= 3) {
            $riskMatrixId = 2;
        }

        // Save calculated score in assessment_risks as inherent risk matrix mapping
        // Status transitions are handled by callers (submitAssessment, approveAssessment)

        return $totalScore;
    }

    /**
     * Approve assessment
     */
    public function approveAssessment($assessmentId, $noteText, $userId, $userRoleId)
    {
        $assessment = $this->assessmentModel->getById($assessmentId);
        if (!$assessment) {
            throw new \Exception("Assessment not found.");
        }

        // Only reviewer or super admin can approve
        if ($userRoleId != 1 && $assessment['reviewer_id'] != $userId) {
            throw new \Exception("Access Denied: You are not designated as reviewer for this assessment.");
        }

        $this->assessmentModel->updateStatus($assessmentId, 'Approved');

        if (!empty($noteText)) {
            $this->assessmentModel->addNote($assessmentId, $noteText, $userId);
        }

        // Push findings to Risk Register
        $findings = $this->assessmentModel->getRiskFindings($assessmentId);
        $stmtReg = $this->pdo->prepare("
            INSERT INTO risk_register (title, category, likelihood, impact, mitigation, owner, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Open')
        ");
        foreach ($findings as $f) {
            // risk_matrix: ID 1 = High, ID 2 = Low
            $likelihood = ($f['inherent_risk_matrix_id'] == 1) ? 'High' : 'Medium';
            $impact = ($f['inherent_risk_matrix_id'] == 1) ? 'High' : 'Medium';
            $mitigation = "Address finding: " . $f['description'];
            $owner = $assessment['assessor_email'] ?? 'Assessor';

            $stmtReg->execute([
                "PIA Risk - " . $assessment['title'],
                $f['category_name'] ?? 'Compliance',
                $likelihood,
                $impact,
                $mitigation,
                $owner
            ]);
        }

        // Write audit log
        log_audit_event($this->pdo, 'Assessment', 'Approve', $userId, $assessmentId, 'Under Review', 'Approved');

        return true;
    }

    /**
     * Reject / Request Changes
     */
    public function rejectAssessment($assessmentId, $noteText, $userId, $userRoleId)
    {
        $assessment = $this->assessmentModel->getById($assessmentId);
        if (!$assessment) {
            throw new \Exception("Assessment not found.");
        }

        if ($userRoleId != 1 && $assessment['reviewer_id'] != $userId) {
            throw new \Exception("Access Denied: You are not designated as reviewer for this assessment.");
        }

        $this->assessmentModel->updateStatus($assessmentId, 'Rejected');

        if (!empty($noteText)) {
            $this->assessmentModel->addNote($assessmentId, $noteText, $userId);
        }

        // Write audit log
        log_audit_event($this->pdo, 'Assessment', 'Reject', $userId, $assessmentId, 'Under Review', 'Rejected');

        return true;
    }
}
