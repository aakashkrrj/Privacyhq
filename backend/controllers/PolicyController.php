<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class PolicyController extends BaseController {
    private $policyService;

    public function __construct($policyService) {
        $this->policyService = $policyService;
    }

    public function create() {
        $this->checkPermission('manage_policies');
        try {
            $name = trim($_POST['title'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $status = trim($_POST['status'] ?? 'Draft');
            $content = trim($_POST['content'] ?? '');

            $documentPath = null;
            if (!empty($content)) {
                // Ensure directory exists
                $uploadDir = __DIR__ . '/../../uploads/policies/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'policy_' . time() . '_' . rand(1000, 9999) . '.txt';
                $documentPath = 'uploads/policies/' . $filename;
                file_put_contents($uploadDir . $filename, $content);
            }

            $id = $this->policyService->createPolicy($name, $version, $status, $documentPath, $this->getUserId());
            ApiResponse::success('Policy document saved successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function upload() {
        $this->checkPermission('manage_policies');
        try {
            $name = trim($_POST['title'] ?? '');
            $version = trim($_POST['version'] ?? '1.0');
            $status = trim($_POST['status'] ?? 'Draft');

            if (!isset($_FILES['policy_file']) || $_FILES['policy_file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception("File upload failed or no file selected.");
            }

            $file = $_FILES['policy_file'];
            // Validate type: txt, pdf, docx
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['txt', 'pdf', 'docx'])) {
                throw new \Exception("Unsupported file type. Only PDF, TXT, and DOCX are allowed.");
            }

            // Validate size: 5MB limit
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new \Exception("File size exceeds the 5MB limit.");
            }

            $uploadDir = __DIR__ . '/../../uploads/policies/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'uploaded_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $documentPath = 'uploads/policies/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                throw new \Exception("Failed to save uploaded file.");
            }

            $id = $this->policyService->createPolicy($name, $version, $status, $documentPath, $this->getUserId());
            ApiResponse::success('Policy document uploaded successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listRecords() {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $data = $this->policyService->getList($search, $status);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function history() {
        $this->checkPermission('view_dashboard');
        try {
            $name = trim($_GET['name'] ?? '');
            if (empty($name)) {
                throw new \Exception("Policy name is required for version history.");
            }
            $data = $this->policyService->getHistory($name);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateStatus() {
        $this->checkPermission('manage_policies');
        try {
            $id = filter_input(INPUT_POST, 'policy_id', FILTER_VALIDATE_INT);
            $status = trim($_POST['status'] ?? '');

            if (!$id || empty($status)) {
                throw new \Exception("Invalid request parameters.");
            }

            $policy = $this->policyService->getPolicyById($id);
            if (!$policy) {
                throw new \Exception("Policy document record not found.");
            }
            $this->checkOwnershipOrPermission('manage_policies', $policy);

            $this->policyService->updateStatus($id, $status, $this->getUserId());
            ApiResponse::success("Policy status updated successfully!");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->policyService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function download() {
        $this->checkPermission('manage_policies');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new \Exception("Invalid request ID.");
            }

            $policy = $this->policyService->getPolicyById($id);
            if (!$policy) {
                throw new \Exception("Policy document record not found.");
            }
            $this->checkOwnershipOrPermission('manage_policies', $policy);

            $relativePath = $policy['document_path'];
            if (empty($relativePath)) {
                throw new \Exception("No document file associated with this record.");
            }

            $fullPath = __DIR__ . '/../../' . $relativePath;
            if (!file_exists($fullPath)) {
                throw new \Exception("The document file was not found on the server. Path: " . htmlspecialchars($relativePath));
            }

            // Set content type
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $contentType = 'application/octet-stream';
            if ($ext === 'pdf') {
                $contentType = 'application/pdf';
            } elseif ($ext === 'txt') {
                $contentType = 'text/plain';
            } elseif ($ext === 'docx') {
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            }

            header('Content-Type: ' . $contentType);
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit();
        } catch (\Exception $e) {
            // Render a user-friendly error page instead of API JSON response
            header('Content-Type: text/html; charset=UTF-8');
            echo "<!DOCTYPE html>
            <html>
            <head>
                <title>Document Error</title>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
            </head>
            <body class='bg-light py-5'>
                <div class='container max-w-md bg-white p-5 rounded shadow-sm border border-danger text-center'>
                    <h3 class='text-danger fw-bold mb-3'><i class='bi bi-exclamation-triangle'></i> Document Access Failed</h3>
                    <p class='text-muted'>{$e->getMessage()}</p>
                    <button onclick='window.close()' class='btn btn-secondary mt-3'>Close Window</button>
                </div>
            </body>
            </html>";
            exit();
        }
    }
}

