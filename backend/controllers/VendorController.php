<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class VendorController extends BaseController {
    private $vendorService;

    public function __construct($vendorService) {
        $this->vendorService = $vendorService;
    }



    public function create() {
        $this->checkPermission('manage_vendors');
        try {
            $name = trim($_POST['vendor_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $dpaStatus = trim($_POST['dpa_status'] ?? '');
            $riskLevel = trim($_POST['risk_level'] ?? '');

            $this->vendorService->createVendor($name, $category, $dpaStatus, $riskLevel, $this->getUserId());
            ApiResponse::success('Vendor added successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update() {
        $this->checkPermission('manage_vendors');
        try {
            $id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);
            $name = trim($_POST['vendor_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $dpaStatus = trim($_POST['dpa_status'] ?? '');
            $riskLevel = trim($_POST['risk_level'] ?? '');

            $this->vendorService->updateVendor($id, $name, $category, $dpaStatus, $riskLevel, $this->getUserId());
            ApiResponse::success('Vendor updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete() {
        $this->checkPermission('manage_vendors');
        try {
            $id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);
            $this->vendorService->deleteVendor($id, $this->getUserId());
            ApiResponse::success('Vendor deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listVendors() {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $risk = trim($_GET['risk_level'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->vendorService->getVendorsList($search, $category, $risk, $page);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function kpis() {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->vendorService->getVendorKpis();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
