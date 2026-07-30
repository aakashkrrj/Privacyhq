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

    public function createRequest($email, $subjectType, $requestType, $priority, $userId) {
        if (empty($email) || empty($requestType)) {
            throw new \Exception("Email and Request Type are required.");
        }

        try {
            $this->pdo->beginTransaction();

            // Locate or Create Data Subject
            $subject = $this->subjectModel->findByEmail($email);
            if ($subject) {
                $subjectId = $subject['id'];
            } else {
                $subjectId = $this->subjectModel->create($email, $subjectType);
            }

            // Create Data Request
            $requestId = $this->dsrModel->create($subjectId, $requestType, $priority);

            // Log History
            $this->historyModel->insert($requestId, $userId, null, 'open', 'Initial request logged');

            // Audit Log
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Create', $userId, $requestId, null, json_encode(['email' => $email, 'type' => $requestType]));
            }

            $this->pdo->commit();
            return $requestId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateRequest($id, $priority, $userId) {
        if (empty($id) || empty($priority)) {
            throw new \Exception("Valid Request ID and Priority are required.");
        }

        $existing = $this->dsrModel->findById($id);
        if (!$existing) {
            throw new \Exception("Request not found.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->dsrModel->update($id, $priority, $existing['due_date']);
            
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Update', $userId, $id, $existing['priority'], $priority);
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
            throw new \Exception("Request is already in this status.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->dsrModel->updateStatus($id, $newStatus);
            $this->historyModel->insert($id, $userId, $existing['status'], $newStatus, $comments);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'DSR Management', 'Change Status', $userId, $id, $existing['status'], $newStatus);
            }

            $this->pdo->commit();
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
                log_audit_event($this->pdo, 'DSR Management', 'Delete', $userId, $id, null, null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getList($search, $statusFilter, $typeFilter, $page, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->dsrModel->getList($search, $statusFilter, $typeFilter, $pageSize, $offset);
    }

    public function getDashboardMetrics() {
        return $this->dsrModel->getDashboardMetrics();
    }

    public function getDetails($id) {
        $data = $this->dsrModel->findById($id);
        if (!$data) throw new \Exception("Request not found");
        return $data;
    }
}
