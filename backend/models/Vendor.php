<?php
namespace Backend\Models;

class Vendor {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($name, $serviceType) {
        $stmt = $this->pdo->prepare("INSERT INTO vendors (name, service_type) VALUES (?, ?)");
        $stmt->execute([$name, $serviceType]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name, $serviceType) {
        $stmt = $this->pdo->prepare("UPDATE vendors SET name = ?, service_type = ? WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$name, $serviceType, $id]);
    }

    public function softDelete($id) {
        $stmt = $this->pdo->prepare("UPDATE vendors SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM vendors WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getList($search, $categoryFilter, $riskFilter, $limit, $offset) {
        $whereClauses = ["v.deleted_at IS NULL"];
        $params = [];

        if ($search) {
            $whereClauses[] = "(v.name LIKE ? OR v.service_type LIKE ? OR va.status LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($categoryFilter) {
            $whereClauses[] = "v.service_type = ?";
            $params[] = $categoryFilter;
        }
        if ($riskFilter) {
            if ($riskFilter === 'Critical' || $riskFilter === 'High') {
                $whereClauses[] = "va.risk_score >= 80";
            } elseif ($riskFilter === 'Medium') {
                $whereClauses[] = "va.risk_score >= 50 AND va.risk_score < 80";
            } elseif ($riskFilter === 'Low') {
                $whereClauses[] = "va.risk_score < 50";
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count
        $countSql = "SELECT COUNT(*) FROM vendors v LEFT JOIN vendor_assessments va ON v.id = va.vendor_id $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Fetch
        $sql = "
            SELECT v.id,
                   v.name AS vendor_name, 
                   v.service_type AS category, 
                   va.status AS dpa_status, 
                   IF(va.risk_score >= 80, 'Critical', IF(va.risk_score >= 50, 'Medium', 'Low')) AS risk_level,
                   '' AS data_shared
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

    public function getKpis() {
        $kpiQuery = "
            SELECT 
                SUM(IF(va.risk_score >= 80 AND va.risk_score < 90, 1, 0)) as high_risk,
                SUM(IF(va.status = 'Pending', 1, 0)) as pending_dpa,
                SUM(IF(va.risk_score >= 90, 1, 0)) as critical_risk,
                COUNT(v.id) as total
            FROM vendors v 
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id 
            WHERE v.deleted_at IS NULL
        ";
        return $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
    }
}
