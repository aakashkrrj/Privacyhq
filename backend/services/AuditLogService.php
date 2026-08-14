<?php
namespace Backend\Services;

class AuditLogService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get a list of audit logs with filters and pagination.
     */
    public function getLogs($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        if (!empty($filters['date'])) {
            $where[] = "DATE(a.created_at) = ?";
            $params[] = $filters['date'];
        }
        if (!empty($filters['user'])) {
            $where[] = "u.email LIKE ?";
            $params[] = "%" . $filters['user'] . "%";
        }
        if (!empty($filters['module'])) {
            $where[] = "a.module = ?";
            $params[] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $where[] = "a.action LIKE ?";
            $params[] = "%" . $filters['action'] . "%";
        }
        if (!empty($filters['search'])) {
            $where[] = "(a.module LIKE ? OR a.action LIKE ? OR u.email LIKE ?)";
            $term = "%" . $filters['search'] . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Total count
        $countSql = "SELECT COUNT(*) FROM audit_logs a JOIN users u ON a.user_id = u.id $whereSql";
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Query
        $sql = "SELECT a.*, u.email as user_email 
                FROM audit_logs a 
                JOIN users u ON a.user_id = u.id 
                $whereSql 
                ORDER BY a.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'items' => $items
        ];
    }
}
