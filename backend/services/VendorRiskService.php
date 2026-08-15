<?php
// governance/backend/services/VendorRiskService.php

namespace Backend\Services;

class VendorRiskService
{
    private $pdo;
    private $vendorRiskModel;

    public function __construct(\PDO $pdo, $vendorRiskModel)
    {
        $this->pdo = $pdo;
        $this->vendorRiskModel = $vendorRiskModel;
    }

    public function getDashboardTelemetry()
    {
        return $this->vendorRiskModel->getDashboardTelemetry();
    }

    public function getAssessment($vendorId)
    {
        if (empty($vendorId)) {
            throw new \Exception("Valid Vendor ID is required.");
        }
        $assessment = $this->vendorRiskModel->getAssessment($vendorId);
        if (!$assessment) {
            throw new \Exception("Vendor not found.");
        }
        return $assessment;
    }

    public function saveAssessment($vendorId, $privacyScore, $securityScore, $operationalScore, $legalScore, $complianceStatus, $notes, $userId = 1)
    {
        if (empty($vendorId)) {
            throw new \Exception("Valid Vendor ID is required.");
        }

        $existing = $this->vendorRiskModel->getAssessment($vendorId);
        if (!$existing) {
            throw new \Exception("Vendor not found.");
        }

        try {
            $this->pdo->beginTransaction();

            $result = $this->vendorRiskModel->saveAssessment(
                $vendorId,
                $privacyScore,
                $securityScore,
                $operationalScore,
                $legalScore,
                $complianceStatus,
                $notes,
                $userId
            );

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Vendor Risk',
                    'Save Assessment',
                    $userId,
                    $vendorId,
                    json_encode(['risk_score' => $existing['risk_score'], 'risk_level' => $existing['risk_level']]),
                    json_encode(['risk_score' => $result['risk_score'], 'risk_level' => $result['risk_level'], 'compliance_status' => $complianceStatus])
                );
            }

            $this->pdo->commit();

            // Dispatch workflow event if available
            if (class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('vendor.assessed', [
                    'module' => 'Vendor Risk',
                    'record_id' => $vendorId,
                    'title' => $existing['vendor_name'] . ' Risk Assessment',
                    'assigned_to' => 11,
                    'created_by' => $userId
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getHistory($vendorId)
    {
        if (empty($vendorId)) {
            throw new \Exception("Valid Vendor ID is required.");
        }
        return $this->vendorRiskModel->getHistory($vendorId);
    }

    public function getRiskList($search, $category, $risk, $compliance, $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        return $this->vendorRiskModel->getRiskList($search, $category, $risk, $compliance, $pageSize, $offset);
    }

    public function exportRiskReport($search = null, $category = null, $risk = null, $compliance = null, $format = 'csv')
    {
        $data = $this->vendorRiskModel->getRiskList($search, $category, $risk, $compliance, 10000, 0);
        $items = $data['items'];

        $filename = 'Vendor_Risk_Assessment_Report_' . date('Y-m-d');

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');

            fputcsv($out, ['PrivacyHQ Vendor Risk Assessment & Compliance Report', 'Export Date: ' . date('Y-m-d H:i:s')]);
            fputcsv($out, []);
            fputcsv($out, ['Vendor ID', 'Vendor Name', 'Category', 'Contact Email', 'Risk Score', 'Risk Level', 'Privacy Score', 'Security Score', 'Operational Score', 'Legal Score', 'Compliance Status', 'Last Assessed']);

            foreach ($items as $v) {
                fputcsv($out, [
                    $v['vendor_id'],
                    $v['vendor_name'],
                    $v['category'],
                    $v['contact_email'] ?? 'N/A',
                    $v['risk_score'],
                    $v['risk_level'],
                    $v['privacy_score'],
                    $v['security_score'],
                    $v['operational_score'],
                    $v['legal_score'],
                    $v['compliance_status'],
                    $v['last_assessment_date'] ?? 'N/A'
                ]);
            }

            fclose($out);
            exit;
        } else {
            // Printable PDF HTML View
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Vendor Risk Assessment Report</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:11px;text-align:left;} th{background:#f3f4f6;} .header{border-bottom:2px solid #333;padding-bottom:10px;margin-bottom:20px;}</style></head><body>';
            echo '<div class="header"><h2>PrivacyHQ - Vendor Risk Assessment & Security Audit Report</h2><p>Export Date: ' . date('Y-m-d H:i:s') . ' | Total Assessed: ' . count($items) . '</p></div>';
            echo '<table><thead><tr><th>ID</th><th>Vendor Name</th><th>Category</th><th>Risk Score</th><th>Risk Level</th><th>Privacy</th><th>Security</th><th>Operational</th><th>Legal</th><th>Compliance</th></tr></thead><tbody>';
            foreach ($items as $v) {
                echo '<tr><td>#' . $v['vendor_id'] . '</td><td><strong>' . htmlspecialchars($v['vendor_name']) . '</strong></td><td>' . htmlspecialchars($v['category']) . '</td><td><strong>' . $v['risk_score'] . '%</strong></td><td>' . htmlspecialchars($v['risk_level']) . '</td><td>' . $v['privacy_score'] . '%</td><td>' . $v['security_score'] . '%</td><td>' . $v['operational_score'] . '%</td><td>' . $v['legal_score'] . '%</td><td>' . htmlspecialchars($v['compliance_status']) . '</td></tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }
}
