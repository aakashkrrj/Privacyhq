<?php
namespace Backend\Services;

class IncidentService {
    private $pdo;
    private $incidentModel;

    public function __construct(\PDO $pdo, $incidentModel) {
        $this->pdo = $pdo;
        $this->incidentModel = $incidentModel;
    }

    public function createIncident($summary, $description, $severity, $impactedRecords, $userId) {
        if (empty($summary)) {
            throw new \Exception("Summary is required.");
        }

        $validSeverities = ['Low', 'Medium', 'High', 'Critical'];
        if (!in_array($severity, $validSeverities)) {
            $severity = 'Medium';
        }

        try {
            $this->pdo->beginTransaction();

            // We leave assigned_to and reported_by as NULL/generic for now based on instructions
            $incidentId = $this->incidentModel->create($summary, $description, $severity, $impactedRecords, null, null);

            // Audit Log
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Incident Management', 'Create', $userId, $incidentId, null, json_encode(['summary' => $summary, 'severity' => $severity, 'impacted_records' => $impactedRecords]));
            }

            $this->pdo->commit();
            return $incidentId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateIncident($id, $summary, $description, $severity, $impactedRecords, $status, $userId) {
        $existing = $this->incidentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Incident not found.");
        }

        $validStatuses = ['Open', 'Investigating', 'Resolved'];
        if (!in_array($status, $validStatuses)) {
            $status = 'Open';
        }

        try {
            $this->pdo->beginTransaction();

            $this->incidentModel->update($id, $summary, $description, $severity, $impactedRecords, $status);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Incident Management', 'Update', $userId, $id, json_encode($existing), json_encode(['summary' => $summary, 'status' => $status, 'severity' => $severity]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteIncident($id, $userId) {
        $existing = $this->incidentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Incident not found.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->incidentModel->delete($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Incident Management', 'Delete', $userId, $id, json_encode($existing), 'Deleted');
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getIncident($id) {
        return $this->incidentModel->findById($id);
    }

    public function getList($search, $statusFilter, $severityFilter, $page, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->incidentModel->getList($search, $statusFilter, $severityFilter, $pageSize, $offset);
    }

    public function getDashboardMetrics() {
        return $this->incidentModel->getDashboardMetrics();
    }

    public function remediateIncident($id, $containment, $remediation, $userId) {
        $existing = $this->incidentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Incident not found.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->incidentModel->updateRemediation($id, $containment, $remediation);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Incident Management', 'Remediate', $userId, $id, null, json_encode(['containment' => $containment, 'remediation' => $remediation]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function escalateIncident($id, $isEscalated, $dpoNotified, $regulatoryStatus, $userId) {
        $existing = $this->incidentModel->findById($id);
        if (!$existing) {
            throw new \Exception("Incident not found.");
        }

        if ($isEscalated && !in_array($existing['severity'], ['High', 'Critical'])) {
            throw new \Exception("Escalation is only allowed for High and Critical incidents.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->incidentModel->updateEscalation($id, $isEscalated, $dpoNotified, $regulatoryStatus);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Incident Management', 'Escalate', $userId, $id, null, json_encode(['escalated' => $isEscalated, 'dpo_notified' => $dpoNotified, 'regulatory' => $regulatoryStatus]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

