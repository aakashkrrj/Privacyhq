<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class DataDiscoveryController extends BaseController {
    private $service;

    public function __construct($service) {
        $this->service = $service;
        $this->checkPermission('view_dashboard');
    }

    public function getDashboard() {
        try {
            $data = $this->service->getDashboard();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listSources() {
        try {
            $search = trim($_GET['search'] ?? '');
            $type = trim($_GET['type'] ?? '');
            $risk = trim($_GET['risk'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $sortBy = trim($_GET['sort_by'] ?? 'id');
            $sortOrder = trim($_GET['sort_order'] ?? 'DESC');
            $page = (int)($_GET['p'] ?? ($_GET['page'] ?? 1)) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? ($_GET['page_size'] ?? 10)) ?: 10;

            $data = $this->service->getSources($search, $type, $risk, $status, $sortBy, $sortOrder, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getSource() {
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Data Source ID");
            $data = $this->service->getSource($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function createSource() {
        try {
            $sourceId = $this->service->createSource($_POST, $this->getUserId());
            ApiResponse::success('Data Source connected successfully!', ['id' => $sourceId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function updateSource() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Data Source ID");
            $this->service->updateSource($id, $_POST, $this->getUserId());
            ApiResponse::success('Data Source updated successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteSource() {
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new \Exception("Invalid Data Source ID");
            $this->service->deleteSource($id, $this->getUserId());
            ApiResponse::success('Data Source deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function triggerScan() {
        try {
            $sourceId = (int)($_POST['source_id'] ?? 0);
            $scanType = trim($_POST['scan_type'] ?? 'full');
            if (!$sourceId) throw new \Exception("Please select a valid Data Source.");

            $scanId = $this->service->triggerScan($sourceId, $scanType, $this->getUserId());
            ApiResponse::success('Discovery scan initiated successfully!', ['scan_id' => $scanId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function controlScan() {
        try {
            $scanId = (int)($_POST['scan_id'] ?? 0);
            $action = trim($_POST['action'] ?? 'pause');
            if (!$scanId) throw new \Exception("Invalid Scan ID.");

            $this->service->controlScan($scanId, $action, $this->getUserId());
            ApiResponse::success('Scan status updated successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listScanHistory() {
        try {
            $sourceId = !empty($_GET['source_id']) ? (int)$_GET['source_id'] : null;
            $status = trim($_GET['status'] ?? '');
            $page = (int)($_GET['p'] ?? ($_GET['page'] ?? 1)) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? ($_GET['page_size'] ?? 10)) ?: 10;

            $data = $this->service->getScanHistory($sourceId, $status, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listFindings() {
        try {
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $severity = trim($_GET['severity'] ?? '');
            $sourceId = !empty($_GET['source_id']) ? (int)$_GET['source_id'] : null;
            $page = (int)($_GET['p'] ?? ($_GET['page'] ?? 1)) ?: 1;
            $pageSize = (int)($_GET['limit'] ?? ($_GET['page_size'] ?? 20)) ?: 20;

            $data = $this->service->getFindings($search, $category, $severity, $sourceId, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export() {
        try {
            $format = strtolower(trim($_GET['format'] ?? 'csv'));
            $reportType = strtolower(trim($_GET['type'] ?? 'summary'));
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $sourceId = !empty($_GET['source_id']) ? (int)$_GET['source_id'] : null;

            $this->service->exportReports($format, $reportType, $search, $category, $sourceId);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function setPdo(\PDO $pdo) {
        $this->pdo = $pdo;
    }
}
