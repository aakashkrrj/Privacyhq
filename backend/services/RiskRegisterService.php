<?php
namespace Backend\Services;

class RiskRegisterService {
    private $pdo;
    private $riskRegisterModel;

    public function __construct(\PDO $pdo, $riskRegisterModel) {
        $this->pdo = $pdo;
        $this->riskRegisterModel = $riskRegisterModel;
    }

    public function registerRisk($title, $category, $likelihood, $impact, $mitigation, $statusUi, $userId) {
        if (empty($title) || empty($category)) {
            throw new \Exception("Title and Category are required.");
        }

        $st = strtolower($statusUi);
        $statusDb = 'open';
        if ($st === 'mitigated') $statusDb = 'mitigated';
        else if ($st === 'in review' || $st === 'accepted') $statusDb = 'open'; // Preserving arbitrary UI fallback

        try {
            $this->pdo->beginTransaction();

            $assessmentId = $this->riskRegisterModel->resolveAssessmentId();
            $categoryId = $this->riskRegisterModel->getOrCreateCategory($category);
            $matrixId = $this->riskRegisterModel->getOrCreateMatrix($likelihood, $impact);

            $riskId = $this->riskRegisterModel->createRisk($assessmentId, $categoryId, $matrixId, $title, $statusDb, $userId, $mitigation);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Risk Register', 'Create', $userId, $riskId, null, json_encode(['title' => $title, 'status' => $statusDb]));
            }

            $this->pdo->commit();

            // Dispatch workflow event
            if (class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('risk.created', [
                    'module' => 'Risk',
                    'record_id' => $riskId,
                    'title' => $title,
                    'assigned_to' => 11, // DPO user ID
                    'created_by' => $userId,
                    'priority' => 'High'
                ]);
            }

            return $riskId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getList() {
        $data = $this->riskRegisterModel->getList();
        
        // Map DB back to UI
        $mappedItems = [];
        foreach ($data['items'] as $row) {
            $st = $row['status_db'];
            $status_ui = 'Open';
            if ($st === 'mitigated') $status_ui = 'Mitigated';
            else if ($st === 'accepted') $status_ui = 'In Review';
            
            $row['status'] = $status_ui;
            $row['owner'] = 'Admin User';
            $mappedItems[] = $row;
        }

        return ['total' => $data['total'], 'items' => $mappedItems];
    }

    public function getDashboardMetrics() {
        return $this->riskRegisterModel->getDashboardMetrics();
    }
}
