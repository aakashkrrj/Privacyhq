<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class DsrController extends BaseController {
    private $dsrService;

    public function __construct($dsrService) {
        $this->dsrService = $dsrService;
        $this->checkPermission('manage_dsr');
    }

    public function create() {
        try {
            $name = trim($_POST['subject_name'] ?? '');
            $email = trim($_POST['subject_email'] ?? '');
            $phone = trim($_POST['subject_phone'] ?? '');
            $department = trim($_POST['subject_dept'] ?? ($_POST['department'] ?? ''));
            $subjectType = trim($_POST['subject_type'] ?? 'customer');
            $requestType = trim($_POST['request_type'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Medium');
            $dueDate = trim($_POST['due_date'] ?? '');
            $description = trim($_POST['description'] ?? '');

            $file = $_FILES['attachment'] ?? null;

            $requestId = $this->dsrService->createRequest($name, $email, $phone, $department, $subjectType, $requestType, $priority, $dueDate, $description, $file, $this->getUserId());
            ApiResponse::success('DSR Request logged successfully', ['id' => $requestId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update() {
        try {
            $id = (int)($_POST['request_id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Request ID");
            
            $name = trim($_POST['subject_name'] ?? '');
            $email = trim($_POST['subject_email'] ?? '');
            $phone = trim($_POST['subject_phone'] ?? '');
            $department = trim($_POST['subject_dept'] ?? ($_POST['department'] ?? ''));
            $subjectType = trim($_POST['subject_type'] ?? '');
            $requestType = trim($_POST['request_type'] ?? '');
            $priority = trim($_POST['priority'] ?? '');
            $dueDate = trim($_POST['due_date'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

            $this->dsrService->updateRequest($id, $name, $email, $phone, $department, $subjectType, $requestType, $priority, $dueDate, $description, $status, $assignedTo, $this->getUserId());
            ApiResponse::success('DSR Request updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function changeStatus() {
        try {
            $id = (int)($_POST['request_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            $comments = trim($_POST['comments'] ?? '');

            if (!$id || empty($newStatus)) {
                throw new \Exception("Request ID and Status are required.");
            }

            $this->dsrService->changeStatus($id, $newStatus, $comments, $this->getUserId());
            ApiResponse::success('Request status updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete() {
        try {
            $id = (int)($_POST['request_id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Request ID");

            $this->dsrService->deleteRequest($id, $this->getUserId());
            ApiResponse::success('Request deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listRequests() {
        try {
            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $priorityFilter = trim($_GET['priority'] ?? '');
            $typeFilter = trim($_GET['type'] ?? '');
            $assignedFilter = trim($_GET['assigned_to'] ?? '');
            $deptFilter = trim($_GET['department'] ?? '');
            $fromDate = trim($_GET['from_date'] ?? '');
            $toDate = trim($_GET['to_date'] ?? '');
            $sortBy = trim($_GET['sort_by'] ?? 'id');
            $sortOrder = trim($_GET['sort_order'] ?? 'DESC');
            $page = (int)($_GET['p'] ?? 1) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? 10) ?: 10;

            $data = $this->dsrService->getList($search, $statusFilter, $priorityFilter, $typeFilter, $assignedFilter, $deptFilter, $fromDate, $toDate, $sortBy, $sortOrder, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        try {
            $data = $this->dsrService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function details() {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Request ID");
            
            $data = $this->dsrService->getDetails($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function verify() {
        try {
            $id = (int)($_POST['request_id'] ?? 0);
            $status = trim($_POST['verification_status'] ?? '');
            if (!$id || empty($status)) {
                throw new \Exception("Invalid parameters");
            }
            $this->dsrService->verifyRequest($id, $status, $this->getUserId());
            ApiResponse::success('Verification status updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function assign() {
        try {
            $id = (int)($_POST['request_id'] ?? 0);
            $assigneeId = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
            if (!$id) {
                throw new \Exception("Invalid parameters");
            }
            $this->dsrService->assignRequest($id, $assigneeId, $this->getUserId());
            ApiResponse::success('Assignment updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function pending() {
        try {
            $data = $this->dsrService->getPendingAction();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function addNote() {
        try {
            $requestId = (int)($_POST['request_id'] ?? 0);
            $noteText = trim($_POST['note_text'] ?? '');
            $isPublic = isset($_POST['is_public']) && $_POST['is_public'] == '1' ? 1 : 0;

            $this->dsrService->addNote($requestId, $noteText, $isPublic, $this->getUserId());
            ApiResponse::success('Note added successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function uploadAttachment() {
        try {
            $requestId = (int)($_POST['request_id'] ?? 0);
            $file = $_FILES['attachment'] ?? null;
            if (!$requestId || !$file) {
                throw new \Exception("Please select a file to upload.");
            }
            $this->dsrService->handleAttachmentUpload($requestId, $file, $this->getUserId());
            ApiResponse::success('Attachment uploaded successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteAttachment() {
        try {
            $attachmentId = (int)($_POST['attachment_id'] ?? 0);
            if (!$attachmentId) throw new \Exception("Invalid Attachment ID");
            $this->dsrService->deleteAttachment($attachmentId, $this->getUserId());
            ApiResponse::success('Attachment deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export() {
        try {
            $format = strtolower(trim($_GET['format'] ?? 'csv'));
            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $priorityFilter = trim($_GET['priority'] ?? '');
            $typeFilter = trim($_GET['type'] ?? '');
            $assignedFilter = trim($_GET['assigned_to'] ?? '');
            $deptFilter = trim($_GET['department'] ?? '');
            $fromDate = trim($_GET['from_date'] ?? '');
            $toDate = trim($_GET['to_date'] ?? '');

            $res = $this->dsrService->getList($search, $statusFilter, $priorityFilter, $typeFilter, $assignedFilter, $deptFilter, $fromDate, $toDate, 'id', 'DESC', 1, 10000);
            $items = $res['items'] ?? [];

            if ($format === 'csv' || $format === 'excel') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=DSR_Register_' . date('Y-m-d_H-i') . '.csv');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Request ID', 'Subject Name', 'Subject Email', 'Department', 'Type', 'Request Type', 'Priority', 'Status', 'Due Date', 'Created At']);
                foreach ($items as $r) {
                    fputcsv($out, [
                        $r['request_id_code'],
                        $r['subject_name'] ?: 'N/A',
                        $r['subject_email'],
                        $r['subject_dept'] ?: 'N/A',
                        $r['subject_type'] ?: 'customer',
                        strtoupper($r['request_type']),
                        $r['priority'],
                        strtoupper($r['status']),
                        $r['due_date'],
                        $r['created_at']
                    ]);
                }
                fclose($out);
                exit;
            } else {
                // PDF / HTML Print Output
                header('Content-Type: text/html; charset=utf-8');
                echo '<!DOCTYPE html><html><head><title>DSR Register Report</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:12px;text-align:left;} th{background:#f3f4f6;}</style></head><body>';
                echo '<h2>PrivacyHQ - DSR Register Report</h2>';
                echo '<p>Generated on: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($items) . '</p>';
                echo '<table><thead><tr><th>Request ID</th><th>Subject Name</th><th>Email</th><th>Dept</th><th>Request Type</th><th>Priority</th><th>Status</th><th>Due Date</th></tr></thead><tbody>';
                foreach ($items as $r) {
                    echo '<tr><td>' . htmlspecialchars($r['request_id_code']) . '</td><td>' . htmlspecialchars($r['subject_name'] ?: 'N/A') . '</td><td>' . htmlspecialchars($r['subject_email']) . '</td><td>' . htmlspecialchars($r['subject_dept'] ?: 'N/A') . '</td><td>' . htmlspecialchars(strtoupper($r['request_type'])) . '</td><td>' . htmlspecialchars($r['priority']) . '</td><td>' . htmlspecialchars(strtoupper($r['status'])) . '</td><td>' . htmlspecialchars($r['due_date']) . '</td></tr>';
                }
                echo '</tbody></table><script>window.print();</script></body></html>';
                exit;
            }
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
