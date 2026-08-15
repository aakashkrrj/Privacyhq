<?php
// governance/backend/models/VendorRisk.php

namespace Backend\Models;

class VendorRisk
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Map numerical risk score to categorical risk level
     */
    public function mapScoreToLevel($score)
    {
        $score = (int)$score;
        if ($score >= 80) return 'Critical';
        if ($score >= 60) return 'High';
        if ($score >= 40) return 'Medium';
        return 'Low';
    }

    /**
     * Get Dashboard Live Telemetry Metrics
     */
    public function getDashboardTelemetry()
    {
        $kpiSql = "
            SELECT 
                COUNT(v.id) AS total_vendors,
                SUM(IF(v.risk_level = 'Critical', 1, 0)) AS critical_risk,
                SUM(IF(v.risk_level = 'High', 1, 0)) AS high_risk,
                SUM(IF(v.risk_level = 'Medium', 1, 0)) AS medium_risk,
                SUM(IF(v.risk_level = 'Low', 1, 0)) AS low_risk,
                SUM(IF(va.compliance_status = 'Compliant' OR v.dpa_status = 'Signed', 1, 0)) AS compliant_count,
                SUM(IF(va.compliance_status = 'Non-Compliant' OR va.compliance_status = 'Critical Audit', 1, 0)) AS non_compliant_count,
                SUM(IF(va.compliance_status = 'Under Review' OR va.compliance_status IS NULL, 1, 0)) AS pending_count,
                AVG(COALESCE(va.risk_score, IF(v.risk_level = 'Critical', 90, IF(v.risk_level = 'High', 75, IF(v.risk_level = 'Medium', 50, 20))))) AS avg_risk_score,
                AVG(COALESCE(va.privacy_score, 20)) AS avg_privacy_score,
                AVG(COALESCE(va.security_score, 20)) AS avg_security_score,
                AVG(COALESCE(va.operational_score, 20)) AS avg_operational_score,
                AVG(COALESCE(va.legal_score, 20)) AS avg_legal_score
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.deleted_at IS NULL
        ";

        $data = $this->pdo->query($kpiSql)->fetch(\PDO::FETCH_ASSOC);

        // Fetch recent risk assessments activity
        $recentSql = "
            SELECT 
                v.id AS vendor_id,
                v.name AS vendor_name,
                v.service_type AS category,
                v.risk_level,
                COALESCE(va.risk_score, 0) AS risk_score,
                COALESCE(va.compliance_status, 'Under Review') AS compliance_status,
                va.updated_at AS last_assessed_at
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.deleted_at IS NULL
            ORDER BY COALESCE(va.updated_at, v.created_at) DESC
            LIMIT 5
        ";
        $recent = $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total_vendors' => (int)($data['total_vendors'] ?? 0),
            'critical_risk' => (int)($data['critical_risk'] ?? 0),
            'high_risk' => (int)($data['high_risk'] ?? 0),
            'medium_risk' => (int)($data['medium_risk'] ?? 0),
            'low_risk' => (int)($data['low_risk'] ?? 0),
            'compliant_count' => (int)($data['compliant_count'] ?? 0),
            'non_compliant_count' => (int)($data['non_compliant_count'] ?? 0),
            'pending_count' => (int)($data['pending_count'] ?? 0),
            'avg_risk_score' => round((float)($data['avg_risk_score'] ?? 0), 1),
            'categories' => [
                'privacy' => round((float)($data['avg_privacy_score'] ?? 0), 1),
                'security' => round((float)($data['avg_security_score'] ?? 0), 1),
                'operational' => round((float)($data['avg_operational_score'] ?? 0), 1),
                'legal' => round((float)($data['avg_legal_score'] ?? 0), 1)
            ],
            'recent_activity' => $recent
        ];
    }

    /**
     * Get specific vendor risk assessment details
     */
    public function getAssessment($vendorId)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                v.id AS vendor_id,
                v.name AS vendor_name,
                v.service_type AS category,
                v.contact_name,
                v.contact_email,
                v.dpa_status,
                v.risk_level,
                v.status AS vendor_status,
                v.data_shared,
                v.next_assessment_date,
                v.contract_expiry,
                COALESCE(va.risk_score, 0) AS risk_score,
                COALESCE(va.privacy_score, 20) AS privacy_score,
                COALESCE(va.security_score, 20) AS security_score,
                COALESCE(va.operational_score, 20) AS operational_score,
                COALESCE(va.legal_score, 20) AS legal_score,
                COALESCE(va.compliance_status, 'Under Review') AS compliance_status,
                COALESCE(va.status, 'Under Audit') AS assessment_status,
                va.assessment_notes,
                va.last_assessment_date,
                va.assessed_by,
                u.email AS assessor_email,
                u.first_name AS assessor_first,
                u.last_name AS assessor_last
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            LEFT JOIN users u ON va.assessed_by = u.id
            WHERE v.id = ? AND v.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$vendorId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Save/update vendor risk assessment and recalculate score deterministically
     */
    public function saveAssessment($vendorId, $privacyScore, $securityScore, $operationalScore, $legalScore, $complianceStatus = 'Under Review', $notes = null, $userId = 1)
    {
        // Fetch existing record for history logging
        $existing = $this->getAssessment($vendorId);
        if (!$existing) {
            throw new \Exception("Vendor not found.");
        }

        // Clamp category scores between 0 and 100
        $privacyScore = max(0, min(100, (int)$privacyScore));
        $securityScore = max(0, min(100, (int)$securityScore));
        $operationalScore = max(0, min(100, (int)$operationalScore));
        $legalScore = max(0, min(100, (int)$legalScore));

        // Deterministic backend-authoritative risk score calculation (Average of categories)
        $calculatedScore = (int)round(($privacyScore + $securityScore + $operationalScore + $legalScore) / 4);
        $newRiskLevel = $this->mapScoreToLevel($calculatedScore);

        $prevScore = (int)($existing['risk_score'] ?? 0);
        $prevLevel = $existing['risk_level'] ?? 'Low';
        $prevStatus = $existing['compliance_status'] ?? 'Under Review';

        // Check if assessment row exists in vendor_assessments
        $checkStmt = $this->pdo->prepare("SELECT id FROM vendor_assessments WHERE vendor_id = ?");
        $checkStmt->execute([$vendorId]);
        $assessmentId = $checkStmt->fetchColumn();

        if ($assessmentId) {
            $stmt = $this->pdo->prepare("
                UPDATE vendor_assessments 
                SET risk_score = ?,
                    privacy_score = ?,
                    security_score = ?,
                    operational_score = ?,
                    legal_score = ?,
                    compliance_status = ?,
                    assessment_notes = ?,
                    assessed_by = ?,
                    last_assessment_date = CURDATE(),
                    updated_at = NOW()
                WHERE vendor_id = ?
            ");
            $stmt->execute([
                $calculatedScore,
                $privacyScore,
                $securityScore,
                $operationalScore,
                $legalScore,
                $complianceStatus,
                $notes,
                $userId,
                $vendorId
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO vendor_assessments 
                    (vendor_id, risk_score, privacy_score, security_score, operational_score, legal_score, compliance_status, assessment_notes, assessed_by, last_assessment_date, created_at, updated_at)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW(), NOW())
            ");
            $stmt->execute([
                $vendorId,
                $calculatedScore,
                $privacyScore,
                $securityScore,
                $operationalScore,
                $legalScore,
                $complianceStatus,
                $notes,
                $userId
            ]);
        }

        // Synchronize vendors table risk level
        $stmtVendor = $this->pdo->prepare("
            UPDATE vendors 
            SET risk_level = ?, 
                dpa_status = IF(? = 'Compliant', 'Signed', dpa_status),
                next_assessment_date = DATE_ADD(CURDATE(), INTERVAL 6 MONTH),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmtVendor->execute([$newRiskLevel, $complianceStatus, $vendorId]);

        // Log entry to vendor_risk_history
        $this->addHistory(
            $vendorId,
            $prevScore,
            $calculatedScore,
            $prevLevel,
            $newRiskLevel,
            $prevStatus,
            $complianceStatus,
            $userId,
            $notes ?: 'Risk assessment audit saved'
        );

        return [
            'vendor_id' => $vendorId,
            'risk_score' => $calculatedScore,
            'risk_level' => $newRiskLevel,
            'compliance_status' => $complianceStatus,
            'privacy_score' => $privacyScore,
            'security_score' => $securityScore,
            'operational_score' => $operationalScore,
            'legal_score' => $legalScore
        ];
    }

    /**
     * Add entry to vendor_risk_history
     */
    public function addHistory($vendorId, $prevScore, $newScore, $prevLevel, $newLevel, $prevStatus, $newStatus, $userId, $notes = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO vendor_risk_history 
                (vendor_id, previous_risk_score, new_risk_score, previous_risk_level, new_risk_level, previous_status, new_status, changed_by, notes, changed_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $vendorId,
            $prevScore,
            $newScore,
            $prevLevel,
            $newLevel,
            $prevStatus,
            $newStatus,
            $userId,
            $notes
        ]);
    }

    /**
     * Get chronological risk history for vendor
     */
    public function getHistory($vendorId)
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, 
                   u.email, u.first_name, u.last_name
            FROM vendor_risk_history h
            LEFT JOIN users u ON h.changed_by = u.id
            WHERE h.vendor_id = ?
            ORDER BY h.id DESC
        ");
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search, filter, and paginate vendor risk records
     */
    public function getRiskList($search = null, $categoryFilter = null, $riskFilter = null, $complianceFilter = null, $limit = 10, $offset = 0)
    {
        $whereClauses = ["v.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(v.name LIKE ? OR v.service_type LIKE ? OR v.contact_name LIKE ?)";
            $term = "%" . trim($search) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($categoryFilter)) {
            $whereClauses[] = "v.service_type = ?";
            $params[] = $categoryFilter;
        }
        if (!empty($riskFilter)) {
            $whereClauses[] = "v.risk_level = ?";
            $params[] = $riskFilter;
        }
        if (!empty($complianceFilter)) {
            $whereClauses[] = "COALESCE(va.compliance_status, 'Under Review') = ?";
            $params[] = $complianceFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching
        $countSql = "SELECT COUNT(*) FROM vendors v LEFT JOIN vendor_assessments va ON v.id = va.vendor_id $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch paginated items
        $sql = "
            SELECT 
                v.id AS vendor_id,
                v.name AS vendor_name,
                v.service_type AS category,
                v.contact_name,
                v.contact_email,
                v.dpa_status,
                v.risk_level,
                v.status AS vendor_status,
                COALESCE(va.risk_score, 0) AS risk_score,
                COALESCE(va.privacy_score, 20) AS privacy_score,
                COALESCE(va.security_score, 20) AS security_score,
                COALESCE(va.operational_score, 20) AS operational_score,
                COALESCE(va.legal_score, 20) AS legal_score,
                COALESCE(va.compliance_status, 'Under Review') AS compliance_status,
                va.last_assessment_date,
                v.next_assessment_date
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            $whereSql
            ORDER BY COALESCE(va.risk_score, 0) DESC, v.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }
}
