<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class DsrController extends BaseController {
    private $dsrService;

    public function __construct($dsrService) {
        $this->dsrService = $dsrService;
        $this->checkPermission('manage_dsr');
    }

    private function respond($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    

    public function create() {
        try {
            $email = trim($_POST['subject_email'] ?? '');
            $subjectType = trim($_POST['subject_type'] ?? 'customer');
            $requestType = trim($_POST['request_type'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Medium');

            $this->dsrService->createRequest($email, $subjectType, $requestType, $priority, $this->getUserId());
            ApiResponse::success('Request created successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update() {
        try {
            $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
            $request = $this->dsrService->getDetails($id);
            $this->checkOwnershipOrPermission('manage_dsr', $request);
            $priority = trim($_POST['priority'] ?? '');

            $this->dsrService->updateRequest($id, $priority, $this->getUserId());
            ApiResponse::success('Request updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function changeStatus() {
        try {
            $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
            $request = $this->dsrService->getDetails($id);
            $this->checkOwnershipOrPermission('manage_dsr', $request);
            $newStatus = trim($_POST['status'] ?? '');
            $comments = trim($_POST['comments'] ?? '');

            $this->dsrService->changeStatus($id, $newStatus, $comments, $this->getUserId());
            ApiResponse::success('Status updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete() {
        try {
            $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
            $request = $this->dsrService->getDetails($id);
            $this->checkOwnershipOrPermission('manage_dsr', $request);
            $this->dsrService->deleteRequest($id, $this->getUserId());
            ApiResponse::success('Request deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listRequests() {
        try {
            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $typeFilter = trim($_GET['type'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->dsrService->getList($search, $statusFilter, $typeFilter, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        try {
            $data = $this->dsrService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function details() {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid ID");
            $data = $this->dsrService->getDetails($id);
            $this->checkOwnershipOrPermission('manage_dsr', $data);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function verify() {
        try {
            $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
            $status = trim($_POST['verification_status'] ?? '');
            if (!$id || empty($status)) {
                throw new \Exception("Invalid parameters");
            }
            $this->dsrService->verifyRequest($id, $status, $this->getUserId());
            ApiResponse::success('Verification status updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function assign() {
        try {
            $id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
            $assigneeId = filter_input(INPUT_POST, 'assigned_to', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new \Exception("Invalid parameters");
            }
            $this->dsrService->assignRequest($id, $assigneeId, $this->getUserId());
            ApiResponse::success('Assignment updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function pending() {
        try {
            $data = $this->dsrService->getPendingAction();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}

