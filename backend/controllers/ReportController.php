<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class ReportController extends BaseController {
    private $reportService;

    public function __construct($reportService) {
        $this->reportService = $reportService;
    }

    public function summary() {
        try {
            $data = $this->reportService->getSummary();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function vendorRisk() {
        try {
            return $this->reportService->getVendorRiskReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function riskRegister() {
        try {
            return $this->reportService->getRiskRegisterReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function ropa() {
        try {
            return $this->reportService->getRopaReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function policies() {
        try {
            return $this->reportService->getPoliciesReport();
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}


