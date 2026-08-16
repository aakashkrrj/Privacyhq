<?php
// governance/backend/services/RiskRegisterService.php

namespace Backend\Services;

class RiskRegisterService
{
    private $pdo;
    private $riskModel;

    public function __construct(\PDO $pdo, $riskModel)
    {
        $this->pdo = $pdo;
        $this->riskModel = $riskModel;
    }

    public function getDashboardMetrics()
    {
        return $this->riskModel->getDashboardMetrics();
    }

    public function getList($search, $category, $riskLevel, $status, $treatment = null, $owner = null, $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        return $this->riskModel->getList($search, $category, $riskLevel, $status, $treatment, $owner, $pageSize, $offset);
    }

    public function findById($id)
    {
        if (empty($id)) {
            throw new \Exception("Valid Risk ID is required.");
        }
        $risk = $this->riskModel->findById($id);
        if (!$risk) {
            throw new \Exception("Risk record not found.");
        }
        return $risk;
    }

    public function createRisk($data, $userId = 1)
    {
        if (empty($data['title'])) {
            throw new \Exception("Risk title/summary is required.");
        }

        try {
            $this->pdo->beginTransaction();

            $riskId = $this->riskModel->createRisk($data, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Risk Register',
                    'Create Risk',
                    $userId,
                    $riskId,
                    null,
                    json_encode(['title' => $data['title'], 'category' => $data['category'] ?? 'Data Privacy'])
                );
            }

            $this->pdo->commit();

            // Workflow dispatch if available
            if (class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('risk.created', [
                    'module' => 'Risk Register',
                    'record_id' => $riskId,
                    'title' => $data['title'],
                    'assigned_to' => 1,
                    'created_by' => $userId
                ]);
            }

            return $riskId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateRisk($id, $data, $userId = 1)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->riskModel->updateRisk($id, $data, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Risk Register',
                    'Update Risk',
                    $userId,
                    $id,
                    json_encode(['title' => $existing['title'], 'residual_score' => $existing['residual_score']]),
                    json_encode(['title' => $data['title'] ?? $existing['title']])
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteRisk($id, $userId = 1)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->riskModel->deleteRisk($id, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Risk Register',
                    'Delete Risk',
                    $userId,
                    $id,
                    json_encode(['title' => $existing['title']]),
                    null
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getMatrixData($type = 'residual')
    {
        return $this->riskModel->getMatrixData($type);
    }

    public function saveMitigation($riskId, $title, $details, $owner, $targetDate, $progress, $status, $controlDetails, $userId = 1)
    {
        if (empty($riskId)) {
            throw new \Exception("Valid Risk ID is required.");
        }

        try {
            $this->pdo->beginTransaction();

            $success = $this->riskModel->saveMitigation($riskId, $title, $details, $owner, $targetDate, $progress, $status, $controlDetails, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Risk Register',
                    'Save Mitigation',
                    $userId,
                    $riskId,
                    null,
                    json_encode(['mitigation_title' => $title, 'progress' => $progress, 'status' => $status])
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getHistory($riskId)
    {
        if (empty($riskId)) {
            throw new \Exception("Valid Risk ID is required.");
        }
        return $this->riskModel->getHistory($riskId);
    }

    public function exportReport($search = null, $category = null, $riskLevel = null, $status = null, $treatment = null, $format = 'csv')
    {
        $data = $this->riskModel->getList($search, $category, $riskLevel, $status, $treatment, null, 10000, 0);
        $items = $data['items'];

        $filename = 'PrivacyHQ_Risk_Register_Report_' . date('Y-m-d');

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');

            fputcsv($out, ['PrivacyHQ Risk Register & Governance Matrix Report', 'Export Date: ' . date('Y-m-d H:i:s')]);
            fputcsv($out, []);
            fputcsv($out, ['Risk Code', 'Risk Title', 'Category', 'Risk Source', 'Affected Asset', 'Owner', 'Department', 'Inherent Score', 'Inherent Level', 'Residual Score', 'Residual Level', 'Treatment Strategy', 'Status', 'Target Date', 'Mitigation Plan']);

            foreach ($items as $r) {
                fputcsv($out, [
                    $r['risk_code'],
                    $r['title'],
                    $r['category'],
                    $r['risk_source'] ?? 'Internal Audit',
                    $r['affected_asset'] ?? 'Core System',
                    $r['owner'] ?? 'Unassigned',
                    $r['department'] ?? 'Privacy Governance',
                    $r['inherent_score'],
                    $r['inherent_level'],
                    $r['residual_score'],
                    $r['residual_level'],
                    $r['treatment_strategy'],
                    $r['status'],
                    $r['target_date'] ?? 'N/A',
                    $r['mitigation'] ?? 'No mitigation defined'
                ]);
            }

            fclose($out);
            exit;
        } else {
            // PDF / Printable HTML report
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Risk Register Report</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:11px;text-align:left;} th{background:#f3f4f6;} .header{border-bottom:2px solid #333;padding-bottom:10px;margin-bottom:20px;}</style></head><body>';
            echo '<div class="header"><h2>PrivacyHQ - Privacy & Compliance Risk Register Report</h2><p>Export Date: ' . date('Y-m-d H:i:s') . ' | Total Risks: ' . count($items) . '</p></div>';
            echo '<table><thead><tr><th>Code</th><th>Risk Title</th><th>Category</th><th>Owner</th><th>Inherent</th><th>Residual</th><th>Treatment</th><th>Status</th></tr></thead><tbody>';
            foreach ($items as $r) {
                echo '<tr><td><strong>' . htmlspecialchars($r['risk_code']) . '</strong></td><td>' . htmlspecialchars($r['title']) . '</td><td>' . htmlspecialchars($r['category']) . '</td><td>' . htmlspecialchars($r['owner']) . '</td><td>' . $r['inherent_score'] . ' (' . htmlspecialchars($r['inherent_level']) . ')</td><td><strong>' . $r['residual_score'] . ' (' . htmlspecialchars($r['residual_level']) . ')</strong></td><td>' . htmlspecialchars($r['treatment_strategy']) . '</td><td>' . htmlspecialchars($r['status']) . '</td></tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }
}
