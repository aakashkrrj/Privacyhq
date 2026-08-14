<?php
// governance/backend/services/VendorService.php

namespace Backend\Services;

class VendorService
{
    private $pdo;
    private $vendorModel;
    private $assessmentModel;

    public function __construct(\PDO $pdo, $vendorModel, $assessmentModel)
    {
        $this->pdo = $pdo;
        $this->vendorModel = $vendorModel;
        $this->assessmentModel = $assessmentModel;
    }

    private function mapRiskScore($level)
    {
        return match ($level) {
            'Critical' => 95,
            'High' => 80,
            'Medium' => 50,
            default => 20
        };
    }

    private function mapStatus($dpaStatus)
    {
        return match ($dpaStatus) {
            'Signed' => 'Compliant',
            default => 'Under Audit'
        };
    }

    public function createVendor($name, $category, $contactName = null, $contactEmail = null, $dpaStatus = 'Pending', $riskLevel = 'Low', $dataShared = null, $status = 'Active', $nextAssessmentDate = null, $contractExpiry = null, $notes = null, $userId = 1)
    {
        if (empty($name) || empty($category)) {
            throw new \Exception("Vendor Name and Service Category are required.");
        }

        if (!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid Contact Email address.");
        }

        // Duplicate name check
        $duplicate = $this->vendorModel->findByName($name);
        if ($duplicate) {
            throw new \Exception("A vendor with the name '{$name}' already exists.");
        }

        try {
            $this->pdo->beginTransaction();

            $vendorId = $this->vendorModel->create(
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
                $notes
            );

            $riskScore = $this->mapRiskScore($riskLevel);
            $assessStatus = $this->mapStatus($dpaStatus);

            $this->assessmentModel->create($vendorId, $riskScore, $assessStatus);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Vendor Management', 'Create Vendor', $userId, $vendorId, null, json_encode([
                    'name' => $name,
                    'service_type' => $category,
                    'risk_level' => $riskLevel,
                    'dpa_status' => $dpaStatus
                ]));
            }

            $this->pdo->commit();

            // Dispatch workflow event
            if (class_exists('\Backend\Services\WorkflowService')) {
                \Backend\Services\WorkflowService::dispatch('vendor.created', [
                    'module' => 'Vendor',
                    'record_id' => $vendorId,
                    'name' => $name,
                    'assigned_to' => 11,
                    'created_by' => $userId
                ]);
            }

            return $vendorId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateVendor($id, $name, $category, $contactName = null, $contactEmail = null, $dpaStatus = 'Pending', $riskLevel = 'Low', $dataShared = null, $status = 'Active', $nextAssessmentDate = null, $contractExpiry = null, $notes = null, $userId = 1)
    {
        if (empty($id) || empty($name) || empty($category)) {
            throw new \Exception("Valid Vendor ID, Vendor Name, and Category are required.");
        }

        if (!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid Contact Email address.");
        }

        $existing = $this->vendorModel->findById($id);
        if (!$existing) {
            throw new \Exception("Vendor not found or already deleted.");
        }

        // Duplicate check excluding current ID
        $duplicate = $this->vendorModel->findByName($name, $id);
        if ($duplicate) {
            throw new \Exception("Another vendor with the name '{$name}' already exists.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->vendorModel->update(
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
                $notes
            );

            $riskScore = $this->mapRiskScore($riskLevel);
            $assessStatus = $this->mapStatus($dpaStatus);

            $this->assessmentModel->updateByVendorId($id, $riskScore, $assessStatus);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Vendor Management', 'Update Vendor', $userId, $id, json_encode(['name' => $existing['vendor_name']]), json_encode(['name' => $name, 'risk_level' => $riskLevel]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteVendor($id, $userId = 1)
    {
        if (empty($id)) {
            throw new \Exception("Valid Vendor ID required for deletion.");
        }

        $existing = $this->vendorModel->findById($id);
        if (!$existing) {
            throw new \Exception("Vendor not found or already deleted.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->vendorModel->softDelete($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Vendor Management', 'Soft Delete Vendor', $userId, $id, json_encode(['name' => $existing['vendor_name']]), null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getVendorDetail($id)
    {
        if (empty($id)) {
            throw new \Exception("Valid Vendor ID required.");
        }
        $vendor = $this->vendorModel->findById($id);
        if (!$vendor) {
            throw new \Exception("Vendor not found.");
        }
        return $vendor;
    }

    public function getVendorsList($search, $category, $risk, $status, $page, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        return $this->vendorModel->getList($search, $category, $risk, $status, $pageSize, $offset);
    }

    public function getVendorKpis()
    {
        return $this->vendorModel->getKpis();
    }

    public function exportVendors($search = null, $category = null, $risk = null, $status = null, $format = 'csv')
    {
        $data = $this->vendorModel->getList($search, $category, $risk, $status, 10000, 0);
        $items = $data['items'];

        $filename = 'Vendor_Inventory_' . date('Y-m-d');

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');

            fputcsv($out, ['PrivacyHQ Vendor Risk Inventory Report', 'Export Date: ' . date('Y-m-d H:i:s')]);
            fputcsv($out, []);
            fputcsv($out, ['ID', 'Vendor Name', 'Service Category', 'Contact Name', 'Contact Email', 'DPA Status', 'Risk Level', 'Status', 'Data Shared / Processed', 'Next Review Date']);

            foreach ($items as $v) {
                fputcsv($out, [
                    $v['id'],
                    $v['vendor_name'],
                    $v['category'],
                    $v['contact_name'],
                    $v['contact_email'],
                    $v['dpa_status'],
                    $v['risk_level'],
                    $v['status'],
                    $v['data_shared'],
                    $v['next_assessment_date'] ?? 'N/A'
                ]);
            }

            fclose($out);
            exit;
        } else {
            // Printable PDF HTML view
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Vendor Inventory Export</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:12px;text-align:left;} th{background:#f3f4f6;} .header{border-bottom:2px solid #333;padding-bottom:10px;margin-bottom:20px;}</style></head><body>';
            echo '<div class="header"><h2>PrivacyHQ - Vendor Inventory & Risk Assessment Report</h2><p>Export Date: ' . date('Y-m-d H:i:s') . ' | Total Vendors: ' . count($items) . '</p></div>';
            echo '<table><thead><tr><th>ID</th><th>Vendor Name</th><th>Category</th><th>Contact</th><th>DPA Status</th><th>Risk Level</th><th>Status</th></tr></thead><tbody>';
            foreach ($items as $v) {
                echo '<tr><td>#' . $v['id'] . '</td><td><strong>' . htmlspecialchars($v['vendor_name']) . '</strong></td><td>' . htmlspecialchars($v['category']) . '</td><td>' . htmlspecialchars($v['contact_email'] ?: $v['contact_name'] ?: 'N/A') . '</td><td>' . htmlspecialchars($v['dpa_status']) . '</td><td><strong>' . htmlspecialchars($v['risk_level']) . '</strong></td><td>' . htmlspecialchars($v['status']) . '</td></tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }
}
