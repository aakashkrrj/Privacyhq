<?php
// governance/backend/controllers/PolicyController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class PolicyController extends BaseController
{
    private $policyService;

    public function __construct($policyService)
    {
        $this->policyService = $policyService;
    }

    public function dashboard()
    {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->policyService->getDashboardMetrics();
            ApiResponse::success('Policy dashboard metrics loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listRecords()
    {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $department = trim($_GET['department'] ?? '');
            $approvalStatus = trim($_GET['approval_status'] ?? '');
            $owner = trim($_GET['owner'] ?? '');
            $expired = trim($_GET['expired'] ?? '');
            $reviewDue = trim($_GET['review_due'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
            $sortField = trim($_GET['sort'] ?? 'updated_at');
            $sortDir = trim($_GET['dir'] ?? 'DESC');

            $data = $this->policyService->getList($search, $status, $category, $department, $approvalStatus, $owner, $expired, $reviewDue, $page, $pageSize, $sortField, $sortDir);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing policy record ID.");
            }
            $data = $this->policyService->getPolicyById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function create()
    {
        $this->checkPermission('manage_policies');
        try {
            $data = [
                'title' => trim($_POST['title'] ?? $_POST['policy_name'] ?? ''),
                'category' => trim($_POST['category'] ?? 'Data Privacy'),
                'description' => trim($_POST['description'] ?? ''),
                'policy_owner' => trim($_POST['policy_owner'] ?? 'DPO / Compliance Team'),
                'department' => trim($_POST['department'] ?? 'Legal & Governance'),
                'version' => trim($_POST['version'] ?? '1.0'),
                'effective_date' => trim($_POST['effective_date'] ?? date('Y-m-d')),
                'review_date' => trim($_POST['review_date'] ?? ''),
                'expiry_date' => trim($_POST['expiry_date'] ?? ''),
                'status' => trim($_POST['status'] ?? 'draft'),
                'approval_status' => trim($_POST['approval_status'] ?? 'draft'),
                'change_summary' => trim($_POST['change_summary'] ?? 'Initial policy creation.')
            ];

            $content = trim($_POST['content'] ?? '');
            if (!empty($content)) {
                $uploadDir = __DIR__ . '/../../uploads/policies/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'policy_' . time() . '_' . rand(1000, 9999) . '.txt';
                $documentPath = 'uploads/policies/' . $filename;
                file_put_contents($uploadDir . $filename, $content);

                $data['document_path'] = $documentPath;
                $data['file_name'] = $filename;
                $data['file_type'] = 'txt';
                $data['file_size'] = strlen($content);
            }

            $id = $this->policyService->createPolicy($data, $this->getUserId());
            ApiResponse::success('Policy document saved successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function upload()
    {
        $this->checkPermission('manage_policies');
        try {
            $title = trim($_POST['title'] ?? $_POST['policy_name'] ?? '');
            $policyId = filter_input(INPUT_POST, 'policy_id', FILTER_VALIDATE_INT) ?: 0;

            if (!isset($_FILES['policy_file']) || $_FILES['policy_file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception("File upload failed or no file selected.");
            }

            $file = $_FILES['policy_file'];
            
            // Validate file extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'docx', 'txt'];
            if (!in_array($ext, $allowedExts)) {
                throw new \Exception("Unsupported file type (.$ext). Only PDF, DOCX, and TXT files are allowed.");
            }

            // Validate file size (10MB limit)
            $maxBytes = 10 * 1024 * 1024;
            if ($file['size'] > $maxBytes) {
                throw new \Exception("File size exceeds maximum allowed limit of 10MB.");
            }

            // MIME type check
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'application/pdf',
                'text/plain',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
                'application/octet-stream' // fallback
            ];
            if (!in_array($mime, $allowedMimes)) {
                throw new \Exception("Invalid file MIME content type: $mime");
            }

            $uploadDir = __DIR__ . '/../../uploads/policies/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $sanitizedBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $newFileName = 'pol_' . time() . '_' . rand(1000, 9999) . '_' . substr($sanitizedBaseName, 0, 30) . '.' . $ext;
            $documentPath = 'uploads/policies/' . $newFileName;
            $targetPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new \Exception("Failed to store uploaded file on server.");
            }

            $data = [
                'title' => $title,
                'category' => trim($_POST['category'] ?? 'Data Privacy'),
                'description' => trim($_POST['description'] ?? ''),
                'policy_owner' => trim($_POST['policy_owner'] ?? 'DPO / Compliance Team'),
                'department' => trim($_POST['department'] ?? 'Legal & Governance'),
                'version' => trim($_POST['version'] ?? '1.0'),
                'effective_date' => trim($_POST['effective_date'] ?? date('Y-m-d')),
                'review_date' => trim($_POST['review_date'] ?? ''),
                'expiry_date' => trim($_POST['expiry_date'] ?? ''),
                'status' => trim($_POST['status'] ?? 'draft'),
                'approval_status' => trim($_POST['approval_status'] ?? 'draft'),
                'change_summary' => trim($_POST['change_summary'] ?? 'Uploaded policy document file.'),
                'document_path' => $documentPath,
                'file_name' => $file['name'],
                'file_type' => $ext,
                'file_size' => $file['size']
            ];

            if ($policyId > 0) {
                // Uploading new version for existing policy
                $verId = $this->policyService->uploadPolicyVersion($policyId, $data, $this->getUserId());
                ApiResponse::success('New policy version uploaded successfully!', ['id' => $policyId, 'version_id' => $verId]);
            } else {
                // Creating new policy with file
                $id = $this->policyService->createPolicy($data, $this->getUserId());
                ApiResponse::success('Policy document uploaded successfully!', ['id' => $id]);
            }
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function versions()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing policy ID.");
            }
            $data = $this->policyService->getVersions($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function history()
    {
        $this->checkPermission('view_dashboard');
        try {
            $name = trim($_GET['name'] ?? '');
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if ($id > 0) {
                $data = $this->policyService->getApprovalHistory($id);
            } elseif (!empty($name)) {
                $data = $this->policyService->getHistory($name);
            } else {
                throw new \Exception("Policy ID or policy name is required for history.");
            }
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function submitApproval()
    {
        $this->checkPermission('manage_policies');
        try {
            $id = filter_input(INPUT_POST, 'policy_id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            $comments = trim($_POST['comments'] ?? '');

            if (!$id) {
                throw new \Exception("Invalid or missing policy ID.");
            }

            $this->policyService->submitForApproval($id, $comments, $this->getUserId());
            ApiResponse::success("Policy document submitted for compliance approval!");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function actionApproval()
    {
        $this->checkPermission('manage_policies');
        try {
            $id = filter_input(INPUT_POST, 'policy_id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            $action = strtolower(trim($_POST['action'] ?? $_POST['status'] ?? ''));
            $comments = trim($_POST['comments'] ?? '');

            if (!$id || empty($action)) {
                throw new \Exception("Invalid request parameters for approval workflow.");
            }

            // Normalization: 'active' -> 'approve', 'archived' -> 'reject'
            if ($action === 'active') $action = 'approve';
            if ($action === 'archived') $action = 'reject';

            $this->policyService->processApprovalAction($id, $action, $comments, $this->getUserId(), 'Compliance Approver');
            ApiResponse::success("Policy approval action executed successfully!");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete()
    {
        $this->checkPermission('manage_policies');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing policy ID.");
            }

            $success = $this->policyService->deletePolicy($id, $this->getUserId());
            ApiResponse::success('Policy document deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function download()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new \Exception("Invalid or missing policy ID.");
            }

            $policy = $this->policyService->getPolicyById($id);
            $relativePath = $policy['document_path'];

            if (empty($relativePath)) {
                throw new \Exception("No document file associated with this record.");
            }

            // Path Traversal Security Verification
            $baseUploadDir = realpath(__DIR__ . '/../../uploads/policies');
            $fullPath = realpath(__DIR__ . '/../../' . $relativePath);

            if (!$fullPath || !file_exists($fullPath)) {
                throw new \Exception("The document file was not found on the server. Path: " . htmlspecialchars($relativePath));
            }

            // Ensure fullPath is within uploads/policies/ directory
            if ($baseUploadDir && strpos($fullPath, $baseUploadDir) !== 0) {
                throw new \Exception("Security Violation: Path traversal attempt blocked.");
            }

            $downloadName = !empty($policy['file_name']) ? $policy['file_name'] : basename($fullPath);

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $contentType = 'application/octet-stream';
            if ($ext === 'pdf') {
                $contentType = 'application/pdf';
            } elseif ($ext === 'txt') {
                $contentType = 'text/plain; charset=utf-8';
            } elseif ($ext === 'docx') {
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            }

            // Audit log download action
            if (function_exists('log_audit_event')) {
                log_audit_event($this->policyService, 'Policies', 'Download Document', $this->getUserId(), $id, null, $downloadName);
            }

            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
            header('Content-Length: ' . filesize($fullPath));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            readfile($fullPath);
            exit();
        } catch (\Exception $e) {
            header('Content-Type: text/html; charset=UTF-8');
            echo "<!DOCTYPE html><html><head><title>Document Download Failed</title>";
            echo "<style>body{font-family:sans-serif;background:#f3f4f6;padding:40px;text-align:center;} .card{background:#fff;max-width:480px;margin:0 auto;padding:32px;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.1);border:1px solid #fee2e2;} h2{color:#dc2626;margin-top:0;} button{background:#4b5563;color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-weight:600;margin-top:16px;}</style></head><body>";
            echo "<div class='card'><h2>Document Download Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p><button onclick='window.close()'>Close Window</button></div></body></html>";
            exit();
        }
    }

    public function export()
    {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $department = trim($_GET['department'] ?? '');
            $approvalStatus = trim($_GET['approval_status'] ?? '');
            $owner = trim($_GET['owner'] ?? '');
            $expired = trim($_GET['expired'] ?? '');
            $reviewDue = trim($_GET['review_due'] ?? '');
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->policyService->exportReport($search, $status, $category, $department, $approvalStatus, $owner, $expired, $reviewDue, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}

// Alias for class loader compatibility
if (!class_exists('\Backend\Controllers\PolicyController', false)) {
    class_alias('\Backend\Controllers\PolicyController', '\Backend\Controllers\PolicyController');
}
