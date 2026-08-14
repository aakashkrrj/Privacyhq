<?php
namespace Backend\Services;

class DsrService {
    private $pdo;
    private $dsrModel;
    private $subjectModel;
    private $historyModel;

    public function __construct(\PDO $pdo, $dsrModel, $subjectModel, $historyModel) {
        $this->pdo = $pdo;
        $this->dsrModel = $dsrModel;
        $this->subjectModel = $subjectModel;
        $this->historyModel = $historyModel;
    }

    public function createRequest($name, $email, $phone, $department, $subjectType, $requestType, $priority, $dueDate, $description, $file, $userId) {
        if (empty($email) || empty($requestType)) {
            throw new \Exception("Email and Request Type are required.");
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Locate or Create Data Subject
            $subject = $this->subjectModel->findByEmail($email);
            if ($subject) {
                $subjectId = $subject['id'];
                $this->subjectModel->update($subjectId, $name ?: $subject['name'], $email, $phone ?: $subject['phone'], $department ?: $subject['department'], $subjectType ?: $subject['type']);
            } else {
                $subjectId = $this->subjectModel->create($email, $name, $phone, $department, $subjectType);
            }

            // 2. Create Data Request
            $requestId = $this->dsrModel->create($subjectId, $requestType, $priority ?: 'Medium', $dueDate, $description, $userId);
            $request = $this->dsrModel->findById($requestId);

            // 3. Handle File Upload Attachment
            if ($file && !empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
                $this->handleAttachmentUpload($requestId, $file, $userId);
            }

            // 4. Log History
            $this->historyModel->insert($requestId, $userId, null, 'open', 'Initial request logged');

            // 5. Audit Log
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Create Request', $userId, $requestId, null, json_encode(['email' => $email, 'type' => $requestType, 'code' => $request['request_id_code']]));
            }

            // 6. Send System Notification to Admin
            $this->createNotification($userId, "New DSR Request Created ({$request['request_id_code']})", "A new {$requestType} request was submitted for {$email}.", 'info');

            $this->pdo->commit();

            // Dispatch workflow event
            if (class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('dsr.created', [
                    'module' => 'DSR',
                    'record_id' => $requestId,
                    'subject_email' => $email,
                    'assigned_to' => 11, // DPO user ID
                    'created_by' => $userId,
                    'priority' => $priority
                ]);
            }

            return $requestId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateRequest($id, $name, $email, $phone, $department, $subjectType, $requestType, $priority, $dueDate, $description, $status, $assignedTo, $userId) {
        if (empty($id)) {
            throw new \Exception("Valid Request ID is required.");
        }

        $existing = $this->dsrModel->findById($id);
        if (!$existing) {
            throw new \Exception("Request not found.");
        }

        try {
            $this->pdo->beginTransaction();

            // Update Subject Info if provided
            if (!empty($existing['data_subject_id'])) {
                $this->subjectModel->update($existing['data_subject_id'], $name ?: $existing['subject_name'], $email ?: $existing['subject_email'], $phone ?: $existing['subject_phone'], $department ?: $existing['subject_dept'], $subjectType ?: $existing['subject_type']);
            }

            // Update Request Info
            $this->dsrModel->update($id, $priority ?: $existing['priority'], $dueDate ?: $existing['due_date'], $description ?: $existing['description'], $status ?: $existing['status'], $assignedTo);

            // Track Assignment change
            if ($assignedTo && (string)$assignedTo !== (string)$existing['assigned_to']) {
                $this->historyModel->insert($id, $userId, $existing['status'], $status ?: $existing['status'], "Assigned request to officer ID {$assignedTo}");
                $this->createNotification($assignedTo, "DSR Request Assigned ({$existing['request_id_code']})", "You have been assigned to handle DSR request {$existing['request_id_code']}.", 'warning');
            }

            // Track Status change
            if ($status && $status !== $existing['status']) {
                $this->historyModel->insert($id, $userId, $existing['status'], $status, "Status updated to {$status}");
            }

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Update Request', $userId, $id, json_encode(['priority' => $existing['priority'], 'status' => $existing['status']]), json_encode(['priority' => $priority, 'status' => $status]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function changeStatus($id, $newStatus, $comments, $userId) {
        $existing = $this->dsrModel->findById($id);
        if (!$existing) {
            throw new \Exception("Request not found.");
        }

        if ($existing['status'] === $newStatus) {
            throw new \Exception("Request is already in status '{$newStatus}'.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->dsrModel->updateStatus($id, $newStatus);
            $this->historyModel->insert($id, $userId, $existing['status'], $newStatus, $comments);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Change Status', $userId, $id, $existing['status'], $newStatus);
            }

            // Notification to Assigned Officer or Admin
            if (!empty($existing['assigned_to'])) {
                $this->createNotification($existing['assigned_to'], "DSR Status Changed ({$existing['request_id_code']})", "Status updated from {$existing['status']} to {$newStatus}.", 'info');
            }

            $this->pdo->commit();

            // Dispatch workflow events based on new status
            if (class_exists('\Backend\Services\WorkflowService')) {
                if ($newStatus === 'Verified') {
                    \Backend\Services\WorkflowService::dispatch('dsr.verified', [
                        'module' => 'DSR',
                        'record_id' => $id,
                        'subject_email' => $existing['subject_email'] ?? 'Subject',
                        'assigned_to' => 11,
                        'created_by' => $userId,
                        'priority' => $existing['priority'] ?? 'Medium',
                        'old_status' => $existing['status'],
                        'new_status' => $newStatus
                    ]);
                } elseif ($newStatus === 'Completed') {
                    \Backend\Services\WorkflowService::dispatch('dsr.completed', [
                        'module' => 'DSR',
                        'record_id' => $id,
                        'subject_email' => $existing['subject_email'] ?? 'Subject',
                        'assigned_to' => 11,
                        'created_by' => $userId,
                        'priority' => $existing['priority'] ?? 'Medium',
                        'old_status' => $existing['status'],
                        'new_status' => $newStatus
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteRequest($id, $userId) {
        $existing = $this->dsrModel->findById($id);
        if (!$existing) {
            throw new \Exception("Request not found.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->dsrModel->delete($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Delete Request', $userId, $id, json_encode(['code' => $existing['request_id_code']]), null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getList($search = '', $statusFilter = '', $priorityFilter = '', $typeFilter = '', $assignedFilter = '', $deptFilter = '', $fromDate = '', $toDate = '', $sortBy = 'id', $sortOrder = 'DESC', $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->dsrModel->getList($search, $statusFilter, $priorityFilter, $typeFilter, $assignedFilter, $deptFilter, $fromDate, $toDate, $sortBy, $sortOrder, $pageSize, $offset);
    }

    public function getDashboardMetrics() {
        return $this->dsrModel->getDashboardMetrics();
    }

    public function getDetails($id) {
        $data = $this->dsrModel->findById($id);
        if (!$data) throw new \Exception("Request not found");
        return $data;
    }

    public function verifyRequest($id, $status, $userId) {
        $existing = $this->dsrModel->findById($id);
        if (!$existing) throw new \Exception("Request not found");

        try {
            $this->pdo->beginTransaction();
            $this->dsrModel->updateVerification($id, $status);
            
            if ($status === 'verified' && ($existing['status'] === 'open' || $existing['status'] === 'pending')) {
                $this->dsrModel->updateStatus($id, 'verifying');
            }

            $this->historyModel->insert($id, $userId, $existing['status'], $status === 'verified' ? 'verifying' : $existing['status'], "Identity verification updated to " . $status);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Verify Subject', $userId, $id, $existing['verification_status'], $status);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function assignRequest($id, $assigneeId, $userId) {
        $existing = $this->dsrModel->findById($id);
        if (!$existing) throw new \Exception("Request not found");

        try {
            $this->pdo->beginTransaction();
            $this->dsrModel->updateAssignment($id, $assigneeId);
            
            if ($existing['status'] === 'open' || $existing['status'] === 'verifying') {
                $this->dsrModel->updateStatus($id, 'processing');
            }

            $this->historyModel->insert($id, $userId, $existing['status'], 'processing', "Assigned request to officer ID {$assigneeId}");

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Assign Request', $userId, $id, $existing['assigned_to'], $assigneeId);
            }

            if ($assigneeId) {
                $this->createNotification($assigneeId, "DSR Assigned ({$existing['request_id_code']})", "You have been assigned to handle DSR request {$existing['request_id_code']}.", 'warning');
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getPendingAction() {
        return $this->dsrModel->getPendingAction();
    }

    // Notes Management
    public function addNote($requestId, $noteText, $isPublic, $userId) {
        if (empty($requestId) || empty(trim($noteText))) {
            throw new \Exception("Note text cannot be empty.");
        }
        $res = $this->dsrModel->addNote($requestId, $userId, trim($noteText), $isPublic);
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'DSR Management', 'Add Note', $userId, $requestId, null, substr($noteText, 0, 100));
        }
        return $res;
    }

    // Attachments Management
    public function handleAttachmentUpload($requestId, $file, $userId) {
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'txt', 'csv', 'xlsx'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            throw new \Exception("Invalid file extension '.{$ext}'. Allowed: " . implode(', ', $allowedExts));
        }
        if ($fileSize > $maxSize) {
            throw new \Exception("File size exceeds maximum allowed limit of 10MB.");
        }

        $uploadDir = __DIR__ . '/../../uploads/dsr/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uniqueName = 'dsr_' . $requestId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $uniqueName;
        $relativePath = 'uploads/dsr/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to save uploaded file.");
        }

        $res = $this->dsrModel->addAttachment($requestId, $fileName, $relativePath, $fileSize, $fileType, $userId);
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'DSR Management', 'Upload Attachment', $userId, $requestId, null, $fileName);
        }
        return $res;
    }

    public function deleteAttachment($attachmentId, $userId) {
        $res = $this->dsrModel->deleteAttachment($attachmentId);
        if ($res && function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'DSR Management', 'Delete Attachment', $userId, $attachmentId, null, null);
        }
        return $res;
    }

    private function createNotification($userId, $title, $message, $type = 'info') {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            $stmt->execute([$userId, $title, $message, $type]);
        } catch (\Throwable $e) {}
    }
}
