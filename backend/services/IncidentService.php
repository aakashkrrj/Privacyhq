<?php
// governance/backend/services/IncidentService.php

namespace Backend\Services;

class IncidentService
{
    private $pdo;
    private $incidentModel;

    public function __construct(\PDO $pdo, $incidentModel)
    {
        $this->pdo = $pdo;
        $this->incidentModel = $incidentModel;
    }

    public function getDashboardMetrics()
    {
        return $this->incidentModel->getDashboardMetrics();
    }

    public function getList($search, $statusFilter, $severityFilter, $typeFilter = null, $assignedTo = null, $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        return $this->incidentModel->getList($search, $statusFilter, $severityFilter, $typeFilter, $assignedTo, $pageSize, $offset);
    }

    public function findById($id)
    {
        if (empty($id)) {
            throw new \Exception("Valid Incident ID is required.");
        }
        $incident = $this->incidentModel->findById($id);
        if (!$incident) {
            throw new \Exception("Incident not found.");
        }
        return $incident;
    }

    public function create($summary, $description, $incidentType, $severity, $priority, $impactedRecords, $affectedSystem, $dueDate, $assignedTo, $assignedTeam, $userId)
    {
        if (empty($summary)) {
            throw new \Exception("Incident summary is required.");
        }

        try {
            $this->pdo->beginTransaction();

            $id = $this->incidentModel->create(
                $summary,
                $description,
                $incidentType ?: 'Data Privacy',
                $severity ?: 'Medium',
                $priority ?: 'Medium',
                $impactedRecords ?: 0,
                $affectedSystem ?: 'Core System',
                $dueDate,
                $assignedTo,
                $assignedTeam ?: 'Response Team',
                $userId
            );

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Incident Management',
                    'Create Incident',
                    $userId,
                    $id,
                    null,
                    json_encode(['summary' => $summary, 'severity' => $severity, 'status' => 'Open'])
                );
            }

            $this->pdo->commit();

            // Workflow dispatch
            if (class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('incident.created', [
                    'module' => 'Incident Management',
                    'record_id' => $id,
                    'summary' => $summary,
                    'priority' => $priority ?: 'Medium',
                    'assigned_to' => $assignedTo ?: 1,
                    'created_by' => $userId
                ]);
            }

            return $id;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function update($id, $summary, $description, $incidentType, $severity, $priority, $impactedRecords, $affectedSystem, $dueDate, $status, $userId)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->incidentModel->update(
                $id,
                $summary,
                $description,
                $incidentType ?: 'Data Privacy',
                $severity ?: 'Medium',
                $priority ?: 'Medium',
                $impactedRecords ?: 0,
                $affectedSystem ?: 'Core System',
                $dueDate,
                $status ?: 'Open',
                $userId
            );

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Incident Management',
                    'Update Incident',
                    $userId,
                    $id,
                    json_encode(['summary' => $existing['summary'], 'status' => $existing['status']]),
                    json_encode(['summary' => $summary, 'status' => $status])
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function delete($id, $userId)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->incidentModel->delete($id, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Incident Management',
                    'Delete Incident',
                    $userId,
                    $id,
                    json_encode(['summary' => $existing['summary']]),
                    null
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function assign($id, $assignedTo, $assignedTeam, $dueDate, $userId)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->incidentModel->assign($id, $assignedTo, $assignedTeam, $dueDate, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Incident Management',
                    'Assign Incident',
                    $userId,
                    $id,
                    json_encode(['assigned_to' => $existing['assigned_to'], 'assigned_team' => $existing['assigned_team']]),
                    json_encode(['assigned_to' => $assignedTo, 'assigned_team' => $assignedTeam, 'due_date' => $dueDate])
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateRemediation($id, $containment, $remediation, $rootCause, $preventiveActions, $userId)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->incidentModel->updateRemediation($id, $containment, $remediation, $rootCause, $preventiveActions, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Incident Management',
                    'Remediate Incident',
                    $userId,
                    $id,
                    json_encode(['status' => $existing['status']]),
                    json_encode(['containment' => $containment, 'remediation' => $remediation])
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateEscalation($id, $isEscalated, $dpoNotified, $regulatoryStatus, $userId)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->incidentModel->updateEscalation($id, $isEscalated, $dpoNotified, $regulatoryStatus, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Incident Management',
                    'Escalate Incident',
                    $userId,
                    $id,
                    json_encode(['is_escalated' => $existing['is_escalated']]),
                    json_encode(['is_escalated' => $isEscalated, 'dpo_notified' => $dpoNotified, 'regulatory_status' => $regulatoryStatus])
                );
            }

            $this->pdo->commit();

            if ($isEscalated && class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('incident.escalated', [
                    'module' => 'Incident Management',
                    'record_id' => $id,
                    'summary' => $existing['summary'],
                    'dpo_user_id' => 1
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getTimeline($incidentId)
    {
        if (empty($incidentId)) {
            throw new \Exception("Valid Incident ID is required.");
        }
        return $this->incidentModel->getTimeline($incidentId);
    }

    public function exportReport($search = null, $status = null, $severity = null, $format = 'csv')
    {
        $data = $this->incidentModel->getList($search, $status, $severity, null, null, 10000, 0);
        $items = $data['items'];

        $filename = 'PrivacyHQ_Incident_Management_Report_' . date('Y-m-d');

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');

            fputcsv($out, ['PrivacyHQ Incident Management & Response Audit Report', 'Export Date: ' . date('Y-m-d H:i:s')]);
            fputcsv($out, []);
            fputcsv($out, ['Incident ID', 'Summary', 'Incident Type', 'Severity', 'Priority', 'Impacted Records', 'Affected System', 'Status', 'Assigned Team', 'Assignee Email', 'Reporter Email', 'Due Date', 'Created At', 'Resolved At']);

            foreach ($items as $i) {
                fputcsv($out, [
                    $i['id'],
                    $i['summary'],
                    $i['incident_type'] ?? 'Data Privacy',
                    $i['severity'],
                    $i['priority'] ?? 'Medium',
                    $i['impacted_records'],
                    $i['affected_system'] ?? 'Core System',
                    $i['status'],
                    $i['assigned_team'] ?? 'Response Team',
                    $i['assignee_email'] ?? 'Unassigned',
                    $i['reporter_email'] ?? 'N/A',
                    $i['due_date'] ?? 'N/A',
                    $i['created_at'],
                    $i['resolved_at'] ?? 'N/A'
                ]);
            }

            fclose($out);
            exit;
        } else {
            // PDF / Printable HTML report
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Incident Management Report</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:11px;text-align:left;} th{background:#f3f4f6;} .header{border-bottom:2px solid #333;padding-bottom:10px;margin-bottom:20px;}</style></head><body>';
            echo '<div class="header"><h2>PrivacyHQ - Security & Privacy Incident Report</h2><p>Export Date: ' . date('Y-m-d H:i:s') . ' | Total Incidents: ' . count($items) . '</p></div>';
            echo '<table><thead><tr><th>ID</th><th>Summary</th><th>Type</th><th>Severity</th><th>Impacted Records</th><th>Status</th><th>Assignee</th><th>Created Date</th></tr></thead><tbody>';
            foreach ($items as $i) {
                echo '<tr><td>#' . $i['id'] . '</td><td><strong>' . htmlspecialchars($i['summary']) . '</strong></td><td>' . htmlspecialchars($i['incident_type'] ?? 'Data Privacy') . '</td><td><strong>' . htmlspecialchars($i['severity']) . '</strong></td><td>' . number_format($i['impacted_records']) . '</td><td>' . htmlspecialchars($i['status']) . '</td><td>' . htmlspecialchars($i['assignee_email'] ?? 'Unassigned') . '</td><td>' . htmlspecialchars($i['created_at']) . '</td></tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }
}
