<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class DataMappingController extends BaseController {
    private $service;

    public function __construct($service) {
        $this->service = $service;
        $this->checkPermission('view_dashboard');
    }

    public function setPdo(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getDashboard() {
        try {
            $data = $this->service->getDashboard();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listActivities() {
        try {
            $search = trim($_GET['search'] ?? '');
            $department = trim($_GET['department'] ?? '');
            $risk = trim($_GET['risk'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $sortBy = trim($_GET['sort_by'] ?? 'id');
            $sortOrder = trim($_GET['sort_order'] ?? 'DESC');
            $page = (int)($_GET['p'] ?? ($_GET['page'] ?? 1)) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? ($_GET['page_size'] ?? 10)) ?: 10;

            $data = $this->service->getActivities($search, $department, $risk, $status, $sortBy, $sortOrder, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getActivity() {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Processing Activity ID");
            $data = $this->service->getActivity($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function createActivity() {
        try {
            $id = $this->service->createActivity($_POST, $this->getUserId());
            ApiResponse::success('Processing Activity registered successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateActivity() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Activity ID");
            $this->service->updateActivity($id, $_POST, $this->getUserId());
            ApiResponse::success('Processing Activity updated successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteActivity() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Activity ID");
            $this->service->deleteActivity($id, $this->getUserId());
            ApiResponse::success('Processing Activity deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listFlows() {
        try {
            $search = trim($_GET['search'] ?? '');
            $source = trim($_GET['source'] ?? '');
            $risk = trim($_GET['risk'] ?? '');
            $encryption = trim($_GET['encryption'] ?? '');
            $page = (int)($_GET['p'] ?? ($_GET['page'] ?? 1)) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? ($_GET['page_size'] ?? 20)) ?: 20;

            $data = $this->service->getFlows($search, $source, $risk, $encryption, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function createFlow() {
        try {
            $id = $this->service->createFlow($_POST, $this->getUserId());
            ApiResponse::success('Data Flow mapped successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteFlow() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Data Flow ID");
            $this->service->deleteFlow($id, $this->getUserId());
            ApiResponse::success('Data Flow deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getTopology() {
        try {
            $data = $this->service->getTopology();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export() {
        try {
            $format = strtolower(trim($_GET['format'] ?? 'csv'));
            $reportType = strtolower(trim($_GET['type'] ?? 'flows'));
            $search = trim($_GET['search'] ?? '');
            $department = trim($_GET['department'] ?? '');
            $risk = trim($_GET['risk'] ?? '');

            $this->service->exportReports($format, $reportType, $search, $department, $risk);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
