<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class IncidentController extends BaseController {
    private $incidentService;

    public function __construct($incidentService) {
        $this->incidentService = $incidentService;
    }

    private function respond($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    

    public function create() {
        try {
            $summary = trim($_POST['summary'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $severity = trim($_POST['severity'] ?? 'Medium');
            $impactedRecords = intval($_POST['impacted_records'] ?? 0);

            $this->incidentService->createIncident($summary, $description, $severity, $impactedRecords, $this->getUserId());
            ApiResponse::success('Incident logged successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update() {
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid request ID");

            $summary = trim($_POST['summary'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $severity = trim($_POST['severity'] ?? 'Medium');
            $impactedRecords = intval($_POST['impacted_records'] ?? 0);
            $status = trim($_POST['status'] ?? 'Open');

            $this->incidentService->updateIncident($id, $summary, $description, $severity, $impactedRecords, $status, $this->getUserId());
            ApiResponse::success('Incident updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete() {
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid request ID");

            $this->incidentService->deleteIncident($id, $this->getUserId());
            ApiResponse::success('Incident deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get() {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid request ID");

            $data = $this->incidentService->getIncident($id);
            if (!$data) throw new \Exception("Incident not found");

            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listIncidents() {
        try {
            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $severityFilter = trim($_GET['severity'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->incidentService->getList($search, $statusFilter, $severityFilter, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        try {
            $data = $this->incidentService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
