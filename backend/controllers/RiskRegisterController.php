<?php
// governance/backend/controllers/RiskRegisterController.php

namespace Backend\Controllers;

use Backend\Core\BaseController;
use Backend\Core\ApiResponse;

class RiskRegisterController extends BaseController
{
    private $riskService;

    public function __construct($riskService)
    {
        $this->riskService = $riskService;
    }

    public function dashboard()
    {
        $this->checkPermission('view_dashboard');
        try {
            $data = $this->riskService->getDashboardMetrics();
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
            $riskLevel = trim($_GET['risk_level'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $treatment = trim($_GET['treatment_strategy'] ?? '');
            $owner = trim($_GET['owner'] ?? '');
            $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT) ?: 1;

            $data = $this->riskService->getList($search, $category, $riskLevel, $status, $treatment, $owner, $page);
            ApiResponse::success('Success', $data);
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
                throw new \Exception("Invalid Risk ID.");
            }
            $data = $this->riskService->findById($id);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function create()
    {
        $this->checkPermission('view_dashboard');
        try {
            $data = [
                'assessment_id' => filter_input(INPUT_POST, 'assessment_id', FILTER_VALIDATE_INT) ?: 1,
                'title' => trim($_POST['title'] ?? ($_POST['description'] ?? '')),
                'category' => trim($_POST['category'] ?? 'Data Privacy'),
                'risk_source' => trim($_POST['risk_source'] ?? 'Internal Audit'),
                'affected_asset' => trim($_POST['affected_asset'] ?? 'Core System'),
                'owner' => trim($_POST['owner'] ?? 'Compliance Team'),
                'department' => trim($_POST['department'] ?? 'Privacy Governance'),
                'inherent_likelihood' => filter_input(INPUT_POST, 'inherent_likelihood', FILTER_VALIDATE_INT) ?: 3,
                'inherent_impact' => filter_input(INPUT_POST, 'inherent_impact', FILTER_VALIDATE_INT) ?: 3,
                'residual_likelihood' => filter_input(INPUT_POST, 'residual_likelihood', FILTER_VALIDATE_INT) ?: (filter_input(INPUT_POST, 'likelihood', FILTER_VALIDATE_INT) ?: 2),
                'residual_impact' => filter_input(INPUT_POST, 'residual_impact', FILTER_VALIDATE_INT) ?: (filter_input(INPUT_POST, 'impact', FILTER_VALIDATE_INT) ?: 2),
                'treatment_strategy' => trim($_POST['treatment_strategy'] ?? 'Mitigate / Reduce'),
                'target_date' => trim($_POST['target_date'] ?? ''),
                'status' => trim($_POST['status'] ?? 'open'),
                'mitigation' => trim($_POST['mitigation'] ?? ($_POST['mitigation_plan'] ?? ''))
            ];

            $id = $this->riskService->createRisk($data, $this->getUserId());
            ApiResponse::success('Risk record created successfully', ['id' => $id]);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function update()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Risk ID.");
            }

            $data = [
                'title' => trim($_POST['title'] ?? ($_POST['description'] ?? '')),
                'category' => trim($_POST['category'] ?? 'Data Privacy'),
                'risk_source' => trim($_POST['risk_source'] ?? 'Internal Audit'),
                'affected_asset' => trim($_POST['affected_asset'] ?? 'Core System'),
                'owner' => trim($_POST['owner'] ?? 'Compliance Team'),
                'department' => trim($_POST['department'] ?? 'Privacy Governance'),
                'inherent_likelihood' => filter_input(INPUT_POST, 'inherent_likelihood', FILTER_VALIDATE_INT) ?: 3,
                'inherent_impact' => filter_input(INPUT_POST, 'inherent_impact', FILTER_VALIDATE_INT) ?: 3,
                'residual_likelihood' => filter_input(INPUT_POST, 'residual_likelihood', FILTER_VALIDATE_INT) ?: 2,
                'residual_impact' => filter_input(INPUT_POST, 'residual_impact', FILTER_VALIDATE_INT) ?: 2,
                'treatment_strategy' => trim($_POST['treatment_strategy'] ?? 'Mitigate / Reduce'),
                'target_date' => trim($_POST['target_date'] ?? ''),
                'status' => trim($_POST['status'] ?? 'open'),
                'mitigation' => trim($_POST['mitigation'] ?? '')
            ];

            $success = $this->riskService->updateRisk($id, $data, $this->getUserId());
            ApiResponse::success('Risk record updated successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function delete()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: (int)($_POST['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Risk ID.");
            }

            $success = $this->riskService->deleteRisk($id, $this->getUserId());
            ApiResponse::success('Risk record deleted successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function matrix()
    {
        $this->checkPermission('view_dashboard');
        try {
            $type = strtolower(trim($_GET['type'] ?? 'residual'));
            $data = $this->riskService->getMatrixData($type);
            ApiResponse::success('Success', $data);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function saveMitigation()
    {
        $this->checkPermission('view_dashboard');
        try {
            $riskId = filter_input(INPUT_POST, 'risk_id', FILTER_VALIDATE_INT) ?: (int)($_POST['risk_id'] ?? 0);
            if (!$riskId) {
                throw new \Exception("Invalid Risk ID.");
            }

            $title = trim($_POST['mitigation_title'] ?? 'Risk Mitigation Action');
            $details = trim($_POST['implementation_details'] ?? ($_POST['mitigation'] ?? ''));
            $owner = trim($_POST['mitigation_owner'] ?? 'Compliance Officer');
            $targetDate = trim($_POST['target_date'] ?? '');
            $progress = filter_input(INPUT_POST, 'progress', FILTER_VALIDATE_INT) ?: 0;
            $status = trim($_POST['status'] ?? 'In Progress');
            $controlDetails = trim($_POST['control_details'] ?? '');

            $success = $this->riskService->saveMitigation($riskId, $title, $details, $owner, $targetDate, $progress, $status, $controlDetails, $this->getUserId());
            ApiResponse::success('Mitigation plan saved successfully');
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }

    public function history()
    {
        $this->checkPermission('view_dashboard');
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new \Exception("Invalid Risk ID.");
            }

            $data = $this->riskService->getHistory($id);
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
            $riskLevel = trim($_GET['risk_level'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $treatment = trim($_GET['treatment_strategy'] ?? '');
            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            $this->riskService->exportReport($search, $category, $riskLevel, $status, $treatment, $format);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage());
        }
    }
}
