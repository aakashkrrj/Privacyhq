<?php
// governance/backend/controllers/ReportController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class ReportController extends BaseController
{
    private $reportService;

    public function __construct($reportService)
    {
        $this->reportService = $reportService;
    }

    public function summary()
    {
        $this->checkPermission('view_reports');
        try {
            $data = $this->reportService->getDashboardMetrics();
            ApiResponse::success('Reports summary telemetry loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard()
    {
        $this->summary();
    }

    public function listExecutions()
    {
        $this->checkPermission('view_reports');
        try {
            $search = trim($_GET['search'] ?? '');
            $reportType = trim($_GET['report_type'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $executionType = trim($_GET['execution_type'] ?? '');
            $dateFrom = trim($_GET['date_from'] ?? '');
            $dateTo = trim($_GET['date_to'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
            $sortField = trim($_GET['sort'] ?? 'created_at');
            $sortDir = trim($_GET['dir'] ?? 'DESC');

            $data = $this->reportService->getExecutionsList($search, $reportType, $status, $executionType, $dateFrom, $dateTo, $page, $pageSize, $sortField, $sortDir);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function generate()
    {
        $this->checkPermission('view_reports');
        try {
            $data = [
                'report_type' => trim($_POST['report_type'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'file_format' => trim($_POST['file_format'] ?? 'pdf'),
                'execution_type' => 'manual',
                'filters' => [
                    'date_from' => trim($_POST['date_from'] ?? ''),
                    'date_to' => trim($_POST['date_to'] ?? ''),
                    'department' => trim($_POST['department'] ?? ''),
                    'status' => trim($_POST['status_filter'] ?? '')
                ]
            ];

            $id = $this->reportService->generateReport($data, $this->getUserId());
            ApiResponse::success('Report generated successfully!', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getExecution()
    {
        $this->checkPermission('view_reports');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid report execution ID is required.");
            }
            $data = $this->reportService->getExecutionById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteExecution()
    {
        $this->checkPermission('view_reports');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid execution ID is required.");
            }
            $this->reportService->deleteExecution($id, $this->getUserId());
            ApiResponse::success('Report execution log deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listSchedules()
    {
        $this->checkPermission('view_reports');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $reportType = trim($_GET['report_type'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;

            $data = $this->reportService->getSchedulesList($search, $status, $reportType, $page, $pageSize);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveSchedule()
    {
        $this->checkPermission('view_reports');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $data = [
                'report_type' => trim($_POST['report_type'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'frequency' => trim($_POST['frequency'] ?? 'weekly'),
                'export_format' => trim($_POST['export_format'] ?? 'pdf'),
                'recipients' => trim($_POST['recipients'] ?? ''),
                'status' => trim($_POST['status'] ?? 'active'),
                'filters' => [
                    'department' => trim($_POST['department'] ?? ''),
                    'status' => trim($_POST['status_filter'] ?? '')
                ]
            ];

            if ($id > 0) {
                $this->reportService->updateSchedule($id, $data, $this->getUserId());
                ApiResponse::success('Report schedule updated successfully!');
            } else {
                $newId = $this->reportService->createSchedule($data, $this->getUserId());
                ApiResponse::success('Report schedule created successfully!', ['id' => $newId]);
            }
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function toggleSchedule()
    {
        $this->checkPermission('view_reports');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'active');

            if (!$id) {
                throw new \Exception("Valid schedule ID is required.");
            }

            $this->reportService->toggleScheduleStatus($id, $status, $this->getUserId());
            ApiResponse::success("Report schedule status updated to '{$status}'!");
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function deleteSchedule()
    {
        $this->checkPermission('view_reports');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid schedule ID is required.");
            }

            $this->reportService->deleteSchedule($id, $this->getUserId());
            ApiResponse::success('Report schedule deleted successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function runDueSchedules()
    {
        if (php_sapi_name() !== 'cli') {
            $this->checkPermission('view_reports');
        }
        try {
            $res = $this->reportService->runDueSchedules();
            if (php_sapi_name() === 'cli') {
                echo json_encode(['status' => 'success', 'data' => $res]) . "\n";
            } else {
                ApiResponse::success('Scheduled report execution runner executed', $res);
            }
        } catch (\Exception $e) {
            if (php_sapi_name() === 'cli') {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]) . "\n";
            } else {
                ApiResponse::error($e->getMessage());
            }
        }
    }

    public function vendorRisk()
    {
        $this->checkPermission('view_reports');
        try {
            return $this->reportService->getVendorRiskReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function riskRegister()
    {
        $this->checkPermission('view_reports');
        try {
            return $this->reportService->getRiskRegisterReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function ropa()
    {
        $this->checkPermission('view_reports');
        try {
            return $this->reportService->getRopaReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function policies()
    {
        $this->checkPermission('view_reports');
        try {
            return $this->reportService->getPoliciesReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dsr()
    {
        $this->checkPermission('view_reports');
        try {
            return $this->reportService->getDsrReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function incident()
    {
        $this->checkPermission('view_reports');
        try {
            return $this->reportService->getIncidentReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
