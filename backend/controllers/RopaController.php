<?php
// governance/backend/controllers/RopaController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class RopaController extends BaseController
{
    private $ropaService;

    public function __construct($ropaService)
    {
        $this->ropaService = $ropaService;
    }

    public function dashboard()
    {
        $this->checkPermission('manage_ropa');
        try {
            $data = $this->ropaService->getDashboardMetrics();
            ApiResponse::success('ROPA dashboard telemetry metrics loaded', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function list()
    {
        $this->checkPermission('manage_ropa');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $department = trim($_GET['department'] ?? '');
            $lawfulBasis = trim($_GET['legal_basis'] ?? '');
            $controllerRole = trim($_GET['controller_role'] ?? '');
            $overdue = trim($_GET['overdue'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;
            $pageSize = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
            $sortField = trim($_GET['sort'] ?? 'created_at');
            $sortDir = trim($_GET['dir'] ?? 'DESC');

            $data = $this->ropaService->getList($search, $status, $department, $lawfulBasis, $controllerRole, $overdue, $page, $pageSize, $sortField, $sortDir);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get()
    {
        $this->checkPermission('manage_ropa');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing ROPA record ID.");
            }
            $data = $this->ropaService->findById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function create()
    {
        $this->checkPermission('manage_ropa');
        try {
            $data = [
                'activity_name' => trim($_POST['activity_name'] ?? ''),
                'purpose' => trim($_POST['purpose'] ?? ''),
                'department' => trim($_POST['department'] ?? 'General Privacy'),
                'data_controller' => trim($_POST['data_controller'] ?? 'PrivacyHQ Inc'),
                'business_owner' => trim($_POST['business_owner'] ?? 'Data Owner'),
                'controller_role' => trim($_POST['controller_role'] ?? 'Controller'),
                'legal_basis' => trim($_POST['legal_basis'] ?? 'Legitimate Interest'),
                'data_categories' => trim($_POST['data_categories'] ?? ''),
                'data_subjects' => trim($_POST['data_subjects'] ?? ''),
                'processing_operations' => trim($_POST['processing_operations'] ?? 'Collection, Storage'),
                'data_source' => trim($_POST['data_source'] ?? 'Direct Input'),
                'recipients' => trim($_POST['recipients'] ?? ''),
                'third_parties' => trim($_POST['third_parties'] ?? ''),
                'international_transfers' => trim($_POST['international_transfers'] ?? 'No'),
                'transfer_safeguards' => trim($_POST['transfer_safeguards'] ?? 'N/A'),
                'retention_period' => trim($_POST['retention_period'] ?? '1 Year'),
                'retention_basis' => trim($_POST['retention_basis'] ?? 'Legal Obligation'),
                'disposal_mechanism' => trim($_POST['disposal_mechanism'] ?? 'Secure Erasure'),
                'storage_location' => trim($_POST['storage_location'] ?? 'AWS Cloud'),
                'safeguards' => trim($_POST['safeguards'] ?? 'TLS 1.3, AES-256 Encryption'),
                'technical_measures' => trim($_POST['technical_measures'] ?? 'TLS 1.3, AES-256'),
                'organizational_measures' => trim($_POST['organizational_measures'] ?? 'RBAC Access Policies'),
                'risk_level' => trim($_POST['risk_level'] ?? 'Medium'),
                'status' => trim($_POST['status'] ?? 'active'),
                'review_date' => trim($_POST['review_date'] ?? '')
            ];

            $id = $this->ropaService->createRopa($data, $this->getUserId());
            ApiResponse::success('ROPA processing activity created successfully', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update()
    {
        $this->checkPermission('manage_ropa');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing ROPA record ID.");
            }

            $data = [
                'activity_name' => trim($_POST['activity_name'] ?? ''),
                'purpose' => trim($_POST['purpose'] ?? ''),
                'department' => trim($_POST['department'] ?? 'General Privacy'),
                'data_controller' => trim($_POST['data_controller'] ?? 'PrivacyHQ Inc'),
                'business_owner' => trim($_POST['business_owner'] ?? 'Data Owner'),
                'controller_role' => trim($_POST['controller_role'] ?? 'Controller'),
                'legal_basis' => trim($_POST['legal_basis'] ?? 'Legitimate Interest'),
                'data_categories' => trim($_POST['data_categories'] ?? ''),
                'data_subjects' => trim($_POST['data_subjects'] ?? ''),
                'processing_operations' => trim($_POST['processing_operations'] ?? 'Collection, Storage'),
                'data_source' => trim($_POST['data_source'] ?? 'Direct Input'),
                'recipients' => trim($_POST['recipients'] ?? ''),
                'third_parties' => trim($_POST['third_parties'] ?? ''),
                'international_transfers' => trim($_POST['international_transfers'] ?? 'No'),
                'transfer_safeguards' => trim($_POST['transfer_safeguards'] ?? 'N/A'),
                'retention_period' => trim($_POST['retention_period'] ?? '1 Year'),
                'retention_basis' => trim($_POST['retention_basis'] ?? 'Legal Obligation'),
                'disposal_mechanism' => trim($_POST['disposal_mechanism'] ?? 'Secure Erasure'),
                'storage_location' => trim($_POST['storage_location'] ?? 'AWS Cloud'),
                'safeguards' => trim($_POST['safeguards'] ?? 'TLS 1.3, AES-256 Encryption'),
                'technical_measures' => trim($_POST['technical_measures'] ?? 'TLS 1.3, AES-256'),
                'organizational_measures' => trim($_POST['organizational_measures'] ?? 'RBAC Access Policies'),
                'risk_level' => trim($_POST['risk_level'] ?? 'Medium'),
                'status' => trim($_POST['status'] ?? 'active'),
                'review_date' => trim($_POST['review_date'] ?? '')
            ];

            $success = $this->ropaService->updateRopa($id, $data, $this->getUserId());
            ApiResponse::success('ROPA processing activity updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete()
    {
        $this->checkPermission('manage_ropa');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing ROPA record ID.");
            }

            $success = $this->ropaService->deleteRopa($id, $this->getUserId());
            ApiResponse::success('ROPA processing activity deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function history()
    {
        $this->checkPermission('manage_ropa');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid or missing ROPA record ID.");
            }

            $data = $this->ropaService->getHistory($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export()
    {
        $this->checkPermission('manage_ropa');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $department = trim($_GET['department'] ?? '');
            $lawfulBasis = trim($_GET['legal_basis'] ?? '');
            $controllerRole = trim($_GET['controller_role'] ?? '');
            $overdue = trim($_GET['overdue'] ?? '');
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->ropaService->exportReport($search, $status, $department, $lawfulBasis, $controllerRole, $overdue, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}

// Alias for PSR / class loader compatibility
if (!class_exists('\Backend\Controllers\ROPAController', false)) {
    class_alias('\Backend\Controllers\RopaController', '\Backend\Controllers\ROPAController');
}
