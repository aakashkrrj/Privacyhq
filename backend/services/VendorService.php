<?php
namespace Backend\Services;

class VendorService {
    private $pdo;
    private $vendorModel;
    private $assessmentModel;

    public function __construct(\PDO $pdo, $vendorModel, $assessmentModel) {
        $this->pdo = $pdo;
        $this->vendorModel = $vendorModel;
        $this->assessmentModel = $assessmentModel;
    }

    private function mapRiskScore($level) {
        return match($level) {
            'High', 'Critical' => 90,
            'Medium' => 50,
            default => 10
        };
    }

    private function mapStatus($dpaStatus) {
        return match($dpaStatus) {
            'Signed' => 'Compliant',
            default => 'Under Audit'
        };
    }

    public function createVendor($name, $category, $dpaStatus, $riskLevel, $userId) {
        if (empty($name) || empty($category)) {
            throw new \Exception("Vendor Name and Category are required.");
        }

        try {
            $this->pdo->beginTransaction();

            $vendorId = $this->vendorModel->create($name, $category);
            
            $riskScore = $this->mapRiskScore($riskLevel);
            $status = $this->mapStatus($dpaStatus);
            
            $this->assessmentModel->create($vendorId, $riskScore, $status);
            
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Vendor Management', 'Create', $userId, $vendorId, null, json_encode(['name' => $name, 'service_type' => $category]));
            }

            $this->pdo->commit();
            return $vendorId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateVendor($id, $name, $category, $dpaStatus, $riskLevel, $userId) {
        if (empty($id) || empty($name) || empty($category)) {
            throw new \Exception("Valid Vendor ID, Name, and Category are required.");
        }

        $existing = $this->vendorModel->findById($id);
        if (!$existing) {
            throw new \Exception("Vendor not found or already deleted.");
        }

        try {
            $this->pdo->beginTransaction();

            $this->vendorModel->update($id, $name, $category);
            
            $riskScore = $this->mapRiskScore($riskLevel);
            $status = $this->mapStatus($dpaStatus);
            
            $this->assessmentModel->updateByVendorId($id, $riskScore, $status);
            
            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Vendor Management', 'Update', $userId, $id, null, json_encode(['name' => $name, 'service_type' => $category]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteVendor($id, $userId) {
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
                log_audit_event($this->pdo, 'Vendor Management', 'Soft Delete', $userId, $id, null, null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getVendorsList($search, $category, $risk, $page, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->vendorModel->getList($search, $category, $risk, $pageSize, $offset);
    }

    public function getVendorKpis() {
        return $this->vendorModel->getKpis();
    }
}
