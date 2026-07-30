<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class RopaController extends BaseController {
    private $ropaService;

    public function __construct($ropaService) {
        $this->ropaService = $ropaService;
    }

    public function create() {
        try {
            $activityName = trim($_POST['activity_name'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $dataController = trim($_POST['data_controller'] ?? '');
            $dataCategories = trim($_POST['data_categories'] ?? '');
            $dataSubjects = trim($_POST['data_subjects'] ?? '');
            $recipients = trim($_POST['recipients'] ?? '');
            $retentionPeriod = trim($_POST['retention_period'] ?? '');

            $this->ropaService->createRopa($activityName, $purpose, $department, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod, $this->getUserId());
            ApiResponse::success('ROPA Record added successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update() {
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid request ID");

            $activityName = trim($_POST['activity_name'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $status = trim($_POST['status'] ?? 'active');
            $dataController = trim($_POST['data_controller'] ?? '');
            $dataCategories = trim($_POST['data_categories'] ?? '');
            $dataSubjects = trim($_POST['data_subjects'] ?? '');
            $recipients = trim($_POST['recipients'] ?? '');
            $retentionPeriod = trim($_POST['retention_period'] ?? '');

            $this->ropaService->updateRopa($id, $activityName, $purpose, $department, $status, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod, $this->getUserId());
            ApiResponse::success('ROPA Record updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete() {
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid request ID");

            $this->ropaService->deleteRopa($id, $this->getUserId());
            ApiResponse::success('ROPA Record deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get() {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new \Exception("Invalid request ID");

            $data = $this->ropaService->getRopa($id);
            if (!$data) throw new \Exception("ROPA Record not found");

            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listRecords() {
        try {
            $search = trim($_GET['search'] ?? '');
            $statusFilter = trim($_GET['status'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->ropaService->getList($search, $statusFilter, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        try {
            $data = $this->ropaService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
