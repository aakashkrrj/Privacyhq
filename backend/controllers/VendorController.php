<?php
// governance/backend/controllers/VendorController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class VendorController extends BaseController
{
    private $vendorService;

    public function __construct($vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function create()
    {
        $this->checkPermission('manage_vendors');
        try {
            $name = trim($_POST['vendor_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $contactName = trim($_POST['contact_name'] ?? '');
            $contactEmail = trim($_POST['contact_email'] ?? '');
            $dpaStatus = trim($_POST['dpa_status'] ?? 'Pending');
            $riskLevel = trim($_POST['risk_level'] ?? 'Low');
            $status = trim($_POST['status'] ?? 'Active');
            $dataShared = trim($_POST['data_shared'] ?? '');
            $nextAssessmentDate = trim($_POST['next_assessment_date'] ?? '');
            $contractExpiry = trim($_POST['contract_expiry'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            $vendorId = $this->vendorService->createVendor(
                $name,
                $category,
                $contactName,
                $contactEmail,
                $dpaStatus,
                $riskLevel,
                $dataShared,
                $status,
                $nextAssessmentDate,
                $contractExpiry,
                $notes,
                $this->getUserId()
            );

            ApiResponse::success('Vendor created successfully', ['vendor_id' => $vendorId]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update()
    {
        $this->checkPermission('manage_vendors');
        try {
            $id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT) ?: (int)($_POST['vendor_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Vendor ID.");
            }

            $name = trim($_POST['vendor_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $contactName = trim($_POST['contact_name'] ?? '');
            $contactEmail = trim($_POST['contact_email'] ?? '');
            $dpaStatus = trim($_POST['dpa_status'] ?? 'Pending');
            $riskLevel = trim($_POST['risk_level'] ?? 'Low');
            $status = trim($_POST['status'] ?? 'Active');
            $dataShared = trim($_POST['data_shared'] ?? '');
            $nextAssessmentDate = trim($_POST['next_assessment_date'] ?? '');
            $contractExpiry = trim($_POST['contract_expiry'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            $this->vendorService->updateVendor(
                $id,
                $name,
                $category,
                $contactName,
                $contactEmail,
                $dpaStatus,
                $riskLevel,
                $dataShared,
                $status,
                $nextAssessmentDate,
                $contractExpiry,
                $notes,
                $this->getUserId()
            );

            ApiResponse::success('Vendor updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete()
    {
        $this->checkPermission('manage_vendors');
        try {
            $id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT) ?: (int)($_POST['vendor_id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Vendor ID.");
            }

            $this->vendorService->deleteVendor($id, $this->getUserId());
            ApiResponse::success('Vendor deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function get()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Vendor ID.");
            }

            $data = $this->vendorService->getVendorDetail($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listVendors()
    {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $risk = trim($_GET['risk_level'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->vendorService->getVendorsList($search, $category, $risk, $status, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function kpis()
    {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->vendorService->getVendorKpis();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function export()
    {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $risk = trim($_GET['risk_level'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->vendorService->exportVendors($search, $category, $risk, $status, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
