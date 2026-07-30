<?php
namespace Backend\Services;

class RopaService {
    private $pdo;
    private $ropaModel;

    public function __construct(\PDO $pdo, $ropaModel) {
        $this->pdo = $pdo;
        $this->ropaModel = $ropaModel;
    }

    public function createRopa($activityName, $purpose, $department, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod, $userId) {
        if (empty($activityName) || empty($purpose)) {
            throw new \Exception("Activity Name and Purpose are required.");
        }

        try {
            $this->pdo->beginTransaction();

            $id = $this->ropaModel->create($activityName, $purpose, $department, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'ROPA Management', 'Create', $userId, $id, null, json_encode(['activity_name' => $activityName]));
            }

            $this->pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateRopa($id, $activityName, $purpose, $department, $status, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod, $userId) {
        $existing = $this->ropaModel->findById($id);
        if (!$existing) {
            throw new \Exception("ROPA Record not found.");
        }

        if (empty($activityName) || empty($purpose)) {
            throw new \Exception("Activity Name and Purpose are required.");
        }

        $validStatuses = ['active', 'inactive'];
        if (!in_array($status, $validStatuses)) {
            $status = 'active';
        }

        try {
            $this->pdo->beginTransaction();

            $this->ropaModel->update($id, $activityName, $purpose, $department, $status, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'ROPA Management', 'Update', $userId, $id, json_encode($existing), json_encode(['activity_name' => $activityName, 'status' => $status]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteRopa($id, $userId) {
        $existing = $this->ropaModel->findById($id);
        if (!$existing) {
            throw new \Exception("ROPA Record not found.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->ropaModel->delete($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'ROPA Management', 'Delete', $userId, $id, json_encode($existing), 'Deleted');
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getRopa($id) {
        return $this->ropaModel->findById($id);
    }

    public function getList($search, $statusFilter, $page, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->ropaModel->getList($search, $statusFilter, $pageSize, $offset);
    }

    public function getDashboardMetrics() {
        return $this->ropaModel->getDashboardMetrics();
    }
}
