<?php
// governance/backend/controllers/VendorRiskController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class VendorRiskController extends BaseController
{
    private $vendorRiskService;

    public function __construct($vendorRiskService)
    {
        $this->vendorRiskService = $vendorRiskService;
    }

    public function dashboard()
    {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->vendorRiskService->getDashboardTelemetry();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getAssessment()
    {
        $this->checkPermission('view_dashboard');
        try {
            $vendorId = filter_input(INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT) ?: (int)($_GET['vendor_id'] ?? 0);
            if (!$vendorId) {
                throw new \Exception("Invalid Vendor ID.");
            }
            $data = $this->vendorRiskService->getAssessment($vendorId);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveAssessment()
    {
        $this->checkPermission('manage_vendors');
        try {
            $vendorId = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT) ?: (int)($_POST['vendor_id'] ?? 0);
            if (!$vendorId) {
                throw new \Exception("Invalid Vendor ID.");
            }

            $privacyScore = filter_input(INPUT_POST, 'privacy_score', FILTER_VALIDATE_INT) ?? (int)($_POST['privacy_score'] ?? 0);
            $securityScore = filter_input(INPUT_POST, 'security_score', FILTER_VALIDATE_INT) ?? (int)($_POST['security_score'] ?? 0);
            $operationalScore = filter_input(INPUT_POST, 'operational_score', FILTER_VALIDATE_INT) ?? (int)($_POST['operational_score'] ?? 0);
            $legalScore = filter_input(INPUT_POST, 'legal_score', FILTER_VALIDATE_INT) ?? (int)($_POST['legal_score'] ?? 0);
            $complianceStatus = trim($_POST['compliance_status'] ?? 'Under Review');
            $notes = trim($_POST['assessment_notes'] ?? '');

            $result = $this->vendorRiskService->saveAssessment(
                $vendorId,
                $privacyScore,
                $securityScore,
                $operationalScore,
                $legalScore,
                $complianceStatus,
                $notes,
                $this->getUserId()
            );

            ApiResponse::success('Vendor risk assessment saved and score recalculated successfully!', $result);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function getHistory()
    {
        $this->checkPermission('view_dashboard');
        try {
            $vendorId = filter_input(INPUT_GET, 'vendor_id', FILTER_VALIDATE_INT) ?: (int)($_GET['vendor_id'] ?? 0);
            if (!$vendorId) {
                throw new \Exception("Invalid Vendor ID.");
            }
            $data = $this->vendorRiskService->getHistory($vendorId);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function list()
    {
        $this->checkPermission('view_dashboard');
        try {
            $search = trim($_GET['search'] ?? '');
            $category = trim($_GET['category'] ?? '');
            $risk = trim($_GET['risk_level'] ?? '');
            $compliance = trim($_GET['compliance_status'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->vendorRiskService->getRiskList($search, $category, $risk, $compliance, $page);
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
            $compliance = trim($_GET['compliance_status'] ?? '');
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->vendorRiskService->exportRiskReport($search, $category, $risk, $compliance, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
