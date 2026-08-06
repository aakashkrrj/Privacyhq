<?php
namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class RiskRegisterController extends BaseController {
    private $riskRegisterService;

    public function __construct($riskRegisterService) {
        $this->riskRegisterService = $riskRegisterService;
        $this->checkPermission('view_dashboard');
    }

    public function create() {
        try {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $likelihood = trim($_POST['likelihood'] ?? 'Medium');
            $impact = trim($_POST['impact'] ?? 'Medium');
            $mitigation = trim($_POST['mitigation'] ?? '');
            $status = trim($_POST['status'] ?? 'Open');

            $this->riskRegisterService->registerRisk($title, $category, $likelihood, $impact, $mitigation, $status, $this->getUserId());
            ApiResponse::success('Risk item registered successfully!');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function listRecords() {
        try {
            $data = $this->riskRegisterService->getList();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function dashboard() {
        try {
            $data = $this->riskRegisterService->getDashboardMetrics();
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
