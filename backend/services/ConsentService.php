<?php
namespace Backend\Services;

class ConsentService {
    private $pdo;
    private $consentModel;
    private $subjectModel;
    private $purposeModel;
    private $historyModel;

    public function __construct(\PDO $pdo, $consentModel, $subjectModel, $purposeModel, $historyModel) {
        $this->pdo = $pdo;
        $this->consentModel = $consentModel;
        $this->subjectModel = $subjectModel;
        $this->purposeModel = $purposeModel;
        $this->historyModel = $historyModel;
    }

    public function createConsent($email, $category, $status, $userId) {
        if (empty($email) || empty($category)) {
            throw new \Exception("Email and Category are required.");
        }

        // Map status strings to DB enums
        $dbStatus = 'opt_in';
        if ($status === 'Revoked') $dbStatus = 'withdrawn';
        if ($status === 'Pending') $dbStatus = 'opt_out';

        try {
            $this->pdo->beginTransaction();

            // Locate or Create Data Subject
            $subject = $this->subjectModel->findByEmail($email);
            if ($subject) {
                $subjectId = $subject['id'];
            } else {
                $subjectId = $this->subjectModel->create($email, 'customer');
            }

            // Locate or Create Purpose
            $purpose = $this->purposeModel->findByName($category);
            if ($purpose) {
                $purposeId = $purpose['id'];
            } else {
                $purposeId = $this->purposeModel->create($category);
            }

            // Create Consent
            $consentId = $this->consentModel->create($subjectId, $purposeId, 1, $dbStatus, 'Manual');

            // Log History
            $this->historyModel->insert($consentId, null, $dbStatus, $userId, 'Initial manual entry');

            // Audit Log
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Consent Management', 'Create', $userId, $consentId, null, json_encode(['email' => $email, 'category' => $category, 'status' => $dbStatus]));
            }

            $this->pdo->commit();
            return $consentId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function revokeConsent($id, $userId, $reason = "Manual Revocation") {
        $existing = $this->consentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Consent not found.");
        }

        if ($existing['status'] === 'withdrawn') {
            throw new \Exception("Consent is already withdrawn.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->consentModel->updateStatus($id, 'withdrawn');
            $this->historyModel->insert($id, $existing['status'], 'withdrawn', $userId, $reason);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Consent Management', 'Revoke', $userId, $id, $existing['status'], 'withdrawn');
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getList($search, $statusFilter, $categoryFilter, $page, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->consentModel->getList($search, $statusFilter, $categoryFilter, $pageSize, $offset);
    }

    public function getDashboardMetrics() {
        return $this->consentModel->getDashboardMetrics();
    }
}
