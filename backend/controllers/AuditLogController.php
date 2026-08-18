<?php
// governance/backend/controllers/AuditLogController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class AuditLogController extends BaseController
{
    private $auditService;

    public function __construct($auditService)
    {
        $this->auditService = $auditService;
    }

    public function dashboard()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $data = $this->auditService->getDashboardMetrics();
            ApiResponse::success('Audit dashboard metrics loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function list()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'module' => trim($_GET['module'] ?? ''),
                'action' => trim($_GET['action'] ?? ''),
                'user' => trim($_GET['user'] ?? ''),
                'date_from' => trim($_GET['date_from'] ?? $_GET['date'] ?? ''),
                'date_to' => trim($_GET['date_to'] ?? '')
            ];
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;
            $sortField = trim($_GET['sort'] ?? 'created_at');
            $sortDir = trim($_GET['dir'] ?? 'DESC');

            $data = $this->auditService->getLogs($filters, $page, $limit, $sortField, $sortDir);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Valid audit log ID is required.");
            }
            $data = $this->auditService->getLogById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $filters = [
                'search' => trim($_GET['search'] ?? ''),
                'module' => trim($_GET['module'] ?? ''),
                'action' => trim($_GET['action'] ?? ''),
                'user' => trim($_GET['user'] ?? ''),
                'date_from' => trim($_GET['date_from'] ?? $_GET['date'] ?? ''),
                'date_to' => trim($_GET['date_to'] ?? '')
            ];
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->auditService->exportAuditLogs($filters, $format, $this->getUserId());
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getRetention()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $data = $this->auditService->getRetentionSettings();
            ApiResponse::success('Retention settings loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveRetention()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $data = [
                'retention_days' => filter_input(INPUT_POST, 'retention_days', FILTER_VALIDATE_INT) ?: 90,
                'auto_purge_enabled' => !empty($_POST['auto_purge_enabled']),
                'archive_before_purge' => !empty($_POST['archive_before_purge'])
            ];

            $this->auditService->saveRetentionSettings($data, $this->getUserId());
            ApiResponse::success('Retention policy settings updated successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function purge()
    {
        $this->checkPermission('view_audit_logs');
        try {
            $days = filter_input(INPUT_POST, 'retention_days', FILTER_VALIDATE_INT) ?: null;
            $res = $this->auditService->purgeOldLogs($days, $this->getUserId());
            ApiResponse::success("Audit log retention purge executed! Purged {$res['purged_count']} expired log records older than {$res['retention_days']} days.", $res);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
