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

    public function createConsent($email, $category, $status, $userId, $collectionMethod = 'web_portal', $source = 'Manual', $ipAddress = null, $userAgent = null, $expiresAt = null) {
        if (empty($email) || empty($category)) {
            throw new \Exception("Email and Category are required.");
        }

        $statusMap = \Backend\Models\Consent::getStatusMap();
        $dbStatus = $statusMap[$status] ?? \Backend\Models\Consent::STATUS_OPT_IN;

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
            $consentId = $this->consentModel->create($subjectId, $purposeId, 1, $dbStatus, $source, $collectionMethod, $ipAddress, $userAgent, $expiresAt);

            // Log History
            $this->historyModel->insert($consentId, null, $dbStatus, $userId, 'Initial manual entry');

            // Audit Log
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Consent Management', 'Create', $userId, $consentId, null, json_encode(['email' => $email, 'category' => $category, 'status' => $dbStatus]));
            }

            $this->pdo->commit();
            return $consentId;
        } catch (\Throwable $t) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $t;
        }
    }

    public function revokeConsent($id, $userId, $reason = "Manual Revocation") {
        $existing = $this->consentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Consent not found.");
        }

        if ($existing['status'] === \Backend\Models\Consent::STATUS_WITHDRAWN) {
            throw new \Exception("Consent is already withdrawn.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->consentModel->updateStatus($id, \Backend\Models\Consent::STATUS_WITHDRAWN);
            $this->historyModel->insert($id, $existing['status'], \Backend\Models\Consent::STATUS_WITHDRAWN, $userId, $reason);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Consent Management', 'Revoke', $userId, $id, $existing['status'], \Backend\Models\Consent::STATUS_WITHDRAWN);
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $t) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $t;
        }
    }

    public function getList($search, $statusFilter, $categoryFilter, $dateFilter, $page, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->consentModel->getList($search, $statusFilter, $categoryFilter, $dateFilter, $pageSize, $offset);
    }

    public function getExportList($search, $statusFilter, $categoryFilter, $dateFilter) {
        return $this->consentModel->getExportList($search, $statusFilter, $categoryFilter, $dateFilter);
    }

    public function getDashboardMetrics() {
        return $this->consentModel->getDashboardMetrics();
    }

    public function getConsentHistory($consentId) {
        $existing = $this->consentModel->findById($consentId);
        if (!$existing) {
            throw new \Exception("Consent record not found.");
        }
        return $this->consentModel->getHistory($consentId);
    }

    public function updatePreference($id, $status, $userId, $reason) {
        $reason = trim($reason ?? '');
        if (empty($reason)) {
            throw new \Exception("A valid reason is required for status modification.");
        }

        $existing = $this->consentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Consent record not found.");
        }

        $statusMap = \Backend\Models\Consent::getStatusMap();

        if (!isset($statusMap[$status])) {
            throw new \Exception("Invalid consent status provided.");
        }

        $targetStatus = $statusMap[$status];
        $prevStatus = $existing['status'];

        if ($prevStatus === $targetStatus) {
            throw new \Exception("Consent record is already set to status '{$targetStatus}'.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->consentModel->updateStatus($id, $targetStatus);
            $this->historyModel->insert($id, $prevStatus, $targetStatus, $userId, $reason);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Consent Management',
                    'Update Preference',
                    $userId,
                    $id,
                    $prevStatus,
                    $targetStatus . " (Reason: $reason)"
                );
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $t) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \Exception("Failed to update consent preference: " . $t->getMessage());
        }
    }
}
