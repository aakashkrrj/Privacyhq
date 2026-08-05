<?php
namespace Backend\Services;

class PolicyService {
    private $pdo;
    private $policyModel;

    public function __construct(\PDO $pdo, $policyModel) {
        $this->pdo = $pdo;
        $this->policyModel = $policyModel;
    }

    public function createPolicy($name, $version, $status, $documentPath, $userId) {
        if (empty($name) || empty($version)) {
            throw new \Exception("Policy Name and Version are required.");
        }

        try {
            $this->pdo->beginTransaction();

            $id = $this->policyModel->create($name, $version, $status, $documentPath);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Policies', 'Create', $userId, $id, null, json_encode(['name' => $name, 'version' => $version]));
            }

            $this->pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateStatus($id, $status, $userId) {
        try {
            $this->pdo->beginTransaction();

            $this->policyModel->updateStatus($id, $status);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Policies', 'Update Status', $userId, $id, null, $status);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getList($search = '', $status = '') {
        return $this->policyModel->getList($search, $status);
    }

    public function getPolicyById($id) {
        return $this->policyModel->findById($id);
    }

    public function getHistory($policyName) {
        return $this->policyModel->getHistory($policyName);
    }

    public function getDashboardMetrics() {
        return $this->policyModel->getDashboardMetrics();
    }
}
