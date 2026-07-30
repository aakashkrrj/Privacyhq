<?php
namespace Backend\Models;

class Incident {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($summary, $description, $severity, $impactedRecords, $assignedTo, $reportedBy) {
        $stmt = $this->pdo->prepare("
            INSERT INTO incidents (summary, description, severity, impacted_records, status, assigned_to, reported_by) 
            VALUES (?, ?, ?, ?, 'Open', ?, ?)
        ");
        $stmt->execute([$summary, $description, $severity, $impactedRecords, $assignedTo, $reportedBy]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $summary, $description, $severity, $impactedRecords, $status) {
        // Automatically set resolved_at if status changed to Resolved
        $resolvedAt = ($status === 'Resolved') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $this->pdo->prepare("
            UPDATE incidents 
            SET summary = ?, description = ?, severity = ?, impacted_records = ?, status = ?, resolved_at = IF(? IS NOT NULL AND status != 'Resolved', ?, resolved_at)
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$summary, $description, $severity, $impactedRecords, $status, $resolvedAt, $resolvedAt, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("UPDATE incidents SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM incidents WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getList($search, $statusFilter, $severityFilter, $limit, $offset) {
        $whereClauses = ["deleted_at IS NULL"];
        $params = [];

        if ($search) {
            $whereClauses[] = "(summary LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($statusFilter) {
            $whereClauses[] = "status = ?";
            $params[] = $statusFilter;
        }
        if ($severityFilter) {
            $whereClauses[] = "severity = ?";
            $params[] = $severityFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "SELECT COUNT(*) FROM incidents $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Fetch items
        $sql = "
            SELECT id, summary, severity, impacted_records, status, created_at, resolved_at 
            FROM incidents 
            $whereSql
            ORDER BY created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getDashboardMetrics() {
        $kpiQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(IF(severity = 'Critical', 1, 0)) as crit_severity,
                SUM(IF(severity = 'High', 1, 0)) as high_severity,
                SUM(IF(severity = 'Medium', 1, 0)) as med_severity,
                SUM(IF(severity = 'Low', 1, 0)) as low_severity,
                SUM(IF(status = 'Resolved', 1, 0)) as resolved_incidents,
                SUM(IF(status = 'Open' OR status = 'Investigating', 1, 0)) as active_incidents
            FROM incidents
            WHERE deleted_at IS NULL
        ";
        $kpiRes = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
        $total = $kpiRes['total'] ?? 0;
        $resolved = $kpiRes['resolved_incidents'] ?? 0;

        $resolutionRate = '0%';
        if ($total > 0) {
            $resolutionRate = round(($resolved / $total) * 100, 1) . '%';
        }

        $monthQuery = "
            SELECT COUNT(*) 
            FROM incidents 
            WHERE deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
        ";
        $newThisMonth = $this->pdo->query($monthQuery)->fetchColumn();

        return [
            'total' => $total,
            'active_incidents' => $kpiRes['active_incidents'] ?? 0,
            'high_severity' => ($kpiRes['high_severity'] ?? 0) + ($kpiRes['crit_severity'] ?? 0),
            'resolved' => $resolved,
            'resolution_rate' => $resolutionRate,
            'new_this_month' => $newThisMonth ?? 0,
            'distribution' => [
                'critical' => $kpiRes['crit_severity'] ?? 0,
                'high' => $kpiRes['high_severity'] ?? 0,
                'medium' => $kpiRes['med_severity'] ?? 0,
                'low' => $kpiRes['low_severity'] ?? 0
            ]
        ];
    }
}
