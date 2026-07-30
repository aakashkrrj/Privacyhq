<?php
namespace Backend\Models;

class VendorAssessment {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($vendorId, $riskScore, $status) {
        $stmt = $this->pdo->prepare("INSERT INTO vendor_assessments (vendor_id, risk_score, status) VALUES (?, ?, ?)");
        return $stmt->execute([$vendorId, $riskScore, $status]);
    }

    public function updateByVendorId($vendorId, $riskScore, $status) {
        $stmt = $this->pdo->prepare("UPDATE vendor_assessments SET risk_score = ?, status = ? WHERE vendor_id = ?");
        return $stmt->execute([$riskScore, $status, $vendorId]);
    }
}
