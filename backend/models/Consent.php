<?php
namespace Backend\Models;

class Consent {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($subjectId, $purposeId, $policyId, $status, $source) {
        $stmt = $this->pdo->prepare("
            INSERT INTO consents (data_subject_id, consent_purpose_id, policy_id, status, source) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$subjectId, $purposeId, $policyId, $status, $source]);
        return $this->pdo->lastInsertId();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE consents SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, ds.identifier_hash as subject_email, cp.purpose_name as category
            FROM consents c
            LEFT JOIN data_subjects ds ON c.data_subject_id = ds.id
            LEFT JOIN consent_purposes cp ON c.consent_purpose_id = cp.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getList($search, $statusFilter, $categoryFilter, $limit, $offset) {
        $whereClauses = ["1=1"];
        $params = [];

        if ($search) {
            $whereClauses[] = "ds.identifier_hash LIKE ?";
            $params[] = "%$search%";
        }
        if ($statusFilter) {
            $whereClauses[] = "c.status = ?";
            $params[] = $statusFilter;
        }
        if ($categoryFilter) {
            $whereClauses[] = "cp.purpose_name = ?";
            $params[] = $categoryFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "
            SELECT COUNT(*) 
            FROM consents c 
            LEFT JOIN data_subjects ds ON c.data_subject_id = ds.id 
            LEFT JOIN consent_purposes cp ON c.consent_purpose_id = cp.id
            $whereSql
        ";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Fetch items
        $sql = "
            SELECT c.id, c.status, c.source, c.created_at, c.granted_at, 
                   ds.identifier_hash as subject_email, cp.purpose_name as category
            FROM consents c
            LEFT JOIN data_subjects ds ON c.data_subject_id = ds.id
            LEFT JOIN consent_purposes cp ON c.consent_purpose_id = cp.id
            $whereSql
            ORDER BY c.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getDashboardMetrics() {
        // Total Consents & breakdown
        $kpiQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(IF(status = 'opt_in', 1, 0)) as active_consents,
                SUM(IF(status = 'withdrawn', 1, 0)) as revoked_consents,
                SUM(IF(status = 'opt_out', 1, 0)) as opt_outs
            FROM consents
        ";
        $kpiRes = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
        $total = $kpiRes['total'] ?? 0;

        $optInRate = '0%';
        if ($total > 0) {
            $optInRate = round(($kpiRes['active_consents'] / $total) * 100, 1) . '%';
        }

        // New Consents This Month
        $monthQuery = "
            SELECT COUNT(*) 
            FROM consents 
            WHERE created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
        ";
        $newThisMonth = $this->pdo->query($monthQuery)->fetchColumn();

        return [
            'total' => $total,
            'active_consents' => $kpiRes['active_consents'] ?? 0,
            'revoked_consents' => $kpiRes['revoked_consents'] ?? 0,
            'opt_in_rate' => $optInRate,
            'new_this_month' => $newThisMonth ?? 0
        ];
    }
}
