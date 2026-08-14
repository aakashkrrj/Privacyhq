<?php
// backend/controllers/AssessmentController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class AssessmentController extends BaseController
{
    private $assessmentService;

    public function __construct($assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Create/Assign assessment
     */
    public function create()
    {
        $this->checkPermission('manage_assessments');
        try {
            $title = trim($_POST['title'] ?? '');
            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT) ?: null;
            $reviewerId = filter_input(INPUT_POST, 'reviewer_id', FILTER_VALIDATE_INT) ?: null;
            $priority = trim($_POST['priority'] ?? 'Medium');
            $dueDate = trim($_POST['due_date'] ?? '');
            
            // Default template and processing activity
            $templateId = 1;
            $processingActivityId = 1;

            $id = $this->assessmentService->createAssessment(
                $processingActivityId,
                $templateId,
                $title,
                $assignedTo,
                $reviewerId,
                $priority,
                $dueDate,
                $this->getUserId()
            );

            ApiResponse::success('Assessment created and assigned successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Update assessment details
     */
    public function update()
    {
        $this->checkPermission('manage_assessments');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT) ?: ((int)($_POST['assigned_to'] ?? 0) ?: null);
            $reviewerId = filter_input(INPUT_POST, 'reviewer_id', FILTER_VALIDATE_INT) ?: ((int)($_POST['reviewer_id'] ?? 0) ?: null);
            $priority = trim($_POST['priority'] ?? 'Medium');
            $dueDate = trim($_POST['due_date'] ?? '');

            if (!$id) {
                throw new \Exception("Invalid Assessment ID.");
            }

            $this->assessmentService->updateAssessment($id, $title, $assignedTo, $reviewerId, $priority, $dueDate, $this->getUserId());
            ApiResponse::success('Assessment updated successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Delete assessment (soft delete)
     */
    public function delete()
    {
        $this->checkPermission('manage_assessments');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Assessment ID.");
            }

            $this->assessmentService->deleteAssessment($id, $this->getUserId());
            ApiResponse::success('Assessment deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * List assessments for logged-in user
     */
    public function listAssessments()
    {
        $this->checkPermission('view_dashboard');
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;
            $roleId = $_SESSION['role_id'] ?? 1;

            $data = $this->assessmentService->getAssessmentsForUser($userId, $roleId);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Fetch assessment details
     */
    public function get()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid assessment ID.");
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;
            $roleId = $_SESSION['role_id'] ?? 1;

            $data = $this->assessmentService->getAssessmentDetail($id, $userId, $roleId);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Autosave / Save responses
     */
    public function save()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT) ?: (int)($_POST['assessment_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid assessment ID.");
            }

            $answers = $_POST['answers'] ?? [];
            if (!is_array($answers)) {
                throw new \Exception("Invalid answers format.");
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;
            $roleId = $_SESSION['role_id'] ?? 1;

            $this->assessmentService->saveResponses($id, $answers, $userId, $roleId);
            ApiResponse::success('Progress saved successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Submit assessment for review
     */
    public function submit()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT) ?: (int)($_POST['assessment_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid assessment ID.");
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;
            $roleId = $_SESSION['role_id'] ?? 1;

            $this->assessmentService->submitAssessment($id, $userId, $roleId);
            ApiResponse::success('Assessment submitted for review successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Approve assessment
     */
    public function approve()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT) ?: (int)($_POST['assessment_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid assessment ID.");
            }

            $notes = trim($_POST['notes'] ?? '');

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;
            $roleId = $_SESSION['role_id'] ?? 1;

            $this->assessmentService->approveAssessment($id, $notes, $userId, $roleId);
            ApiResponse::success('Assessment approved successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Reject assessment
     */
    public function reject()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT) ?: (int)($_POST['assessment_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid assessment ID.");
            }

            $notes = trim($_POST['notes'] ?? '');

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;
            $roleId = $_SESSION['role_id'] ?? 1;

            $this->assessmentService->rejectAssessment($id, $notes, $userId, $roleId);
            ApiResponse::success('Assessment rejected successfully.');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Upload evidence document
     */
    public function uploadEvidence()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT) ?: (int)($_POST['assessment_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid assessment ID.");
            }

            if (!isset($_FILES['evidence_file']) || $_FILES['evidence_file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception("File upload failed or no file selected.");
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userId = $_SESSION['user_id'] ?? 1;

            $file = $_FILES['evidence_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $uploadDir = __DIR__ . '/../../uploads/evidence/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'evidence_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $filePath = 'uploads/evidence/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                throw new \Exception("Failed to save uploaded file on server.");
            }

            // Save document record in DB
            $stmt = $this->pdo->prepare("
                INSERT INTO assessment_documents (assessment_id, document_type, file_path, uploaded_by)
                VALUES (?, 'evidence', ?, ?)
            ");
            $stmt->execute([$id, $filePath, $userId]);

            ApiResponse::success('Evidence uploaded successfully!', ['file_path' => $filePath]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getDashboard()
    {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->assessmentService->getDashboard();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getHistory()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Assessment ID.");
            }
            $data = $this->assessmentService->getHistory($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            $format = strtolower(trim($_GET['format'] ?? 'csv'));
            if (!$id) {
                throw new \Exception("Invalid Assessment ID.");
            }
            $this->assessmentService->exportAssessment($id, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    /**
     * Provide access to pdo (required for helper injection)
     */
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
