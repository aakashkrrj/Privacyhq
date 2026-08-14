<?php
// governance/backend/models/Vendor.php

namespace Backend\Models;

class Vendor
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new vendor
     */
    public function create($name, $serviceType, $contactName = null, $contactEmail = null, $dpaStatus = 'Pending', $riskLevel = 'Low', $dataShared = null, $status = 'Active', $nextAssessmentDate = null, $contractExpiry = null, $notes = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO vendors 
                (name, service_type, contact_name, contact_email, dpa_status, risk_level, data_shared, status, next_assessment_date, contract_expiry, notes, created_at, updated_at) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $name,
            $serviceType,
            $contactName,
            $contactEmail,
            $dpaStatus,
            $riskLevel,
            $dataShared,
            $status,
            $nextAssessmentDate ?: null,
            $contractExpiry ?: null,
            $notes
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update existing vendor details
     */
    public function update($id, $name, $serviceType, $contactName = null, $contactEmail = null, $dpaStatus = 'Pending', $riskLevel = 'Low', $dataShared = null, $status = 'Active', $nextAssessmentDate = null, $contractExpiry = null, $notes = null)
    {
        $stmt = $this->pdo->prepare("
            UPDATE vendors 
            SET name = ?, 
                service_type = ?, 
                contact_name = ?, 
                contact_email = ?, 
                dpa_status = ?, 
                risk_level = ?, 
                data_shared = ?, 
                status = ?, 
                next_assessment_date = ?, 
                contract_expiry = ?, 
                notes = ?, 
                updated_at = NOW() 
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $name,
            $serviceType,
            $contactName,
            $contactEmail,
            $dpaStatus,
            $riskLevel,
            $dataShared,
            $status,
            $nextAssessmentDate ?: null,
            $contractExpiry ?: null,
            $notes,
            $id
        ]);
    }

    /**
     * Soft delete vendor
     */
    public function softDelete($id)
    {
        $stmt = $this->pdo->prepare("UPDATE vendors SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$id]);
    }

    /**
     * Find vendor by ID
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, 
                   v.name AS vendor_name, 
                   v.service_type AS category,
                   va.risk_score,
                   va.status AS assessment_status,
                   va.last_assessment_date
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.id = ? AND v.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Find vendor by name (for duplicate validation)
     */
    public function findByName($name, $excludeId = null)
    {
        $sql = "SELECT id FROM vendors WHERE LOWER(name) = LOWER(?) AND deleted_at IS NULL";
        $params = [trim($name)];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Search, filter, and paginate vendor list
     */
    public function getList($search = null, $categoryFilter = null, $riskFilter = null, $statusFilter = null, $limit = 10, $offset = 0)
    {
        $whereClauses = ["v.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(v.name LIKE ? OR v.service_type LIKE ? OR v.contact_name LIKE ? OR v.contact_email LIKE ? OR v.data_shared LIKE ?)";
            $term = "%" . trim($search) . "%";
            $params[] = $term;
            $params[] = $term;
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
        if (!empty($statusFilter)) {
            $whereClauses[] = "v.status = ?";
            $params[] = $statusFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching
        $countSql = "SELECT COUNT(*) FROM vendors v $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch paginated rows
        $sql = "
            SELECT v.id,
                   v.name AS vendor_name, 
                   v.service_type AS category, 
                   COALESCE(v.contact_name, '') AS contact_name,
                   COALESCE(v.contact_email, '') AS contact_email,
                   COALESCE(v.dpa_status, 'Pending') AS dpa_status, 
                   COALESCE(v.risk_level, 'Low') AS risk_level,
                   COALESCE(v.status, 'Active') AS status,
                   COALESCE(v.data_shared, '') AS data_shared,
                   v.next_assessment_date,
                   v.contract_expiry,
                   v.created_at,
                   va.risk_score
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            $whereSql
            ORDER BY v.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Get Telemetry KPIs & Summary Statistics
     */
    public function getKpis()
    {
        $kpiQuery = "
            SELECT 
                COUNT(*) AS total,
                SUM(IF(v.status = 'Active', 1, 0)) AS active,
                SUM(IF(v.status = 'Inactive', 1, 0)) AS inactive,
                SUM(IF(v.status = 'Under Review' OR v.status = 'Pending Review', 1, 0)) AS pending_review,
                SUM(IF(v.dpa_status = 'Pending', 1, 0)) AS pending_dpa,
                SUM(IF(v.risk_level = 'Critical', 1, 0)) AS critical_risk,
                SUM(IF(v.risk_level = 'High', 1, 0)) AS high_risk,
                SUM(IF(v.risk_level = 'Medium', 1, 0)) AS medium_risk,
                SUM(IF(v.risk_level = 'Low', 1, 0)) AS low_risk
            FROM vendors v
            WHERE v.deleted_at IS NULL
        ";
        $row = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);

        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'inactive' => (int)($row['inactive'] ?? 0),
            'pending_review' => (int)($row['pending_review'] ?? 0),
            'pending_dpa' => (int)($row['pending_dpa'] ?? 0),
            'critical_risk' => (int)($row['critical_risk'] ?? 0),
            'high_risk' => (int)($row['high_risk'] ?? 0),
            'medium_risk' => (int)($row['medium_risk'] ?? 0),
            'low_risk' => (int)($row['low_risk'] ?? 0)
        ];
    }
}
