<?php
// governance/backend/controllers/IncidentController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class IncidentController extends BaseController
{
    private $incidentService;

    public function __construct($incidentService)
    {
        $this->incidentService = $incidentService;
    }

    public function dashboard()
    {
        $this->checkPermission('manage_incidents');
        try {
            $data = $this->incidentService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function list()
    {
        $this->checkPermission('manage_incidents');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $severity = trim($_GET['severity'] ?? '');
            $type = trim($_GET['incident_type'] ?? '');
            $assignedTo = filter_input(INPUT_GET, 'assigned_to', FILTER_VALIDATE_INT) ?: null;
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->incidentService->getList($search, $status, $severity, $type, $assignedTo, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function details()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }
            $data = $this->incidentService->findById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function create()
    {
        $this->checkPermission('manage_incidents');
        try {
            $summary = trim($_POST['summary'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $incidentType = trim($_POST['incident_type'] ?? 'Data Privacy');
            $severity = trim($_POST['severity'] ?? 'Medium');
            $priority = trim($_POST['priority'] ?? 'Medium');
            $impactedRecords = filter_input(INPUT_POST, 'impacted_records', FILTER_VALIDATE_INT) ?: 0;
            $affectedSystem = trim($_POST['affected_system'] ?? 'Core System');
            $dueDate = trim($_POST['due_date'] ?? '');
            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT) ?: null;
            $assignedTeam = trim($_POST['assigned_team'] ?? 'Response Team');

            $id = $this->incidentService->create(
                $summary,
                $description,
                $incidentType,
                $severity,
                $priority,
                $impactedRecords,
                $affectedSystem,
                $dueDate,
                $assignedTo,
                $assignedTeam,
                $this->getUserId()
            );

            ApiResponse::success('Incident created successfully', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }

            $summary = trim($_POST['summary'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $incidentType = trim($_POST['incident_type'] ?? 'Data Privacy');
            $severity = trim($_POST['severity'] ?? 'Medium');
            $priority = trim($_POST['priority'] ?? 'Medium');
            $impactedRecords = filter_input(INPUT_POST, 'impacted_records', FILTER_VALIDATE_INT) ?: 0;
            $affectedSystem = trim($_POST['affected_system'] ?? 'Core System');
            $dueDate = trim($_POST['due_date'] ?? '');
            $status = trim($_POST['status'] ?? 'Open');

            $success = $this->incidentService->update(
                $id,
                $summary,
                $description,
                $incidentType,
                $severity,
                $priority,
                $impactedRecords,
                $affectedSystem,
                $dueDate,
                $status,
                $this->getUserId()
            );

            ApiResponse::success('Incident updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }

            $success = $this->incidentService->delete($id, $this->getUserId());
            ApiResponse::success('Incident deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function assign()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }

            $assignedTo = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT) ?: null;
            $assignedTeam = trim($_POST['assigned_team'] ?? 'Response Team');
            $dueDate = trim($_POST['due_date'] ?? '');

            $success = $this->incidentService->assign($id, $assignedTo, $assignedTeam, $dueDate, $this->getUserId());
            ApiResponse::success('Incident assigned successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function remediate()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }

            $containment = trim($_POST['containment_actions'] ?? '');
            $remediation = trim($_POST['remediation_notes'] ?? '');
            $rootCause = trim($_POST['root_cause'] ?? '');
            $preventiveActions = trim($_POST['preventive_actions'] ?? '');

            $success = $this->incidentService->updateRemediation($id, $containment, $remediation, $rootCause, $preventiveActions, $this->getUserId());
            ApiResponse::success('Remediation actions saved successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function escalate()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }

            $isEscalated = !empty($_POST['is_escalated']);
            $dpoNotified = !empty($_POST['dpo_notified']);
            $regulatoryStatus = trim($_POST['regulatory_status'] ?? 'Not Required');

            $success = $this->incidentService->updateEscalation($id, $isEscalated, $dpoNotified, $regulatoryStatus, $this->getUserId());
            ApiResponse::success('Incident escalation settings saved successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function timeline()
    {
        $this->checkPermission('manage_incidents');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Incident ID.");
            }

            $data = $this->incidentService->getTimeline($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export()
    {
        $this->checkPermission('manage_incidents');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $severity = trim($_GET['severity'] ?? '');
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->incidentService->exportReport($search, $status, $severity, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
