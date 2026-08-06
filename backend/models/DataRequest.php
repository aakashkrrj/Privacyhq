<?php
namespace Backend\Models;

class DataRequest {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function generateRequestIdCode() {
        return 'DSR-' . strtoupper(uniqid());
    }

    public function create($dataSubjectId, $requestType, $priority = 'Medium', $dueDate = null) {
        if (!$dueDate) {
            $dueDate = date('Y-m-d', strtotime('+30 days'));
        }
        
        $requestCode = $this->generateRequestIdCode();
        $stmt = $this->pdo->prepare("
            INSERT INTO data_requests (request_id_code, data_subject_id, request_type, status, priority, due_date)
            VALUES (?, ?, ?, 'open', ?, ?)
        ");
        $stmt->execute([$requestCode, $dataSubjectId, $requestType, $priority, $dueDate]);
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, $priority) {
        $stmt = $this->pdo->prepare("UPDATE data_requests SET priority = ? WHERE id = ?");
        return $stmt->execute([$priority, $id]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE data_requests SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM data_requests WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("
            SELECT dr.*, ds.identifier_hash as subject_email, ds.type as subject_type 
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            WHERE dr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getList($search, $statusFilter, $typeFilter, $limit, $offset) {
        $whereClauses = ["1=1"];
        $params = [];

        if ($search) {
            $whereClauses[] = "(dr.request_id_code LIKE ? OR ds.identifier_hash LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($statusFilter) {
            $whereClauses[] = "dr.status = ?";
            $params[] = $statusFilter;
        }
        if ($typeFilter) {
            $whereClauses[] = "dr.request_type = ?";
            $params[] = $typeFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "SELECT COUNT(*) FROM data_requests dr LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Fetch items
        $sql = "
            SELECT dr.id, dr.request_id_code, dr.request_type, dr.status, dr.priority, dr.due_date, dr.created_at, ds.identifier_hash as subject_email 
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            $whereSql
            ORDER BY dr.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getDashboardMetrics() {
        // Basic KPIs
        $kpiQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(IF(status IN ('open', 'verifying', 'processing'), 1, 0)) as pending,
                SUM(IF(status = 'completed', 1, 0)) as completed
            FROM data_requests
        ";
        $kpiRes = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
        $total = $kpiRes['total'] ?? 0;

        // SLA Compliance & Average Resolution (Days)
        $avgResQuery = "
            SELECT 
                AVG(DATEDIFF(updated_at, created_at)) as avg_days,
                COUNT(*) as total_completed,
                SUM(IF(DATE(updated_at) <= due_date, 1, 0)) as in_sla
            FROM data_requests
            WHERE status = 'completed'
        ";
        $perfRes = $this->pdo->query($avgResQuery)->fetch(\PDO::FETCH_ASSOC);
        
        $avgDays = 'N/A';
        if ($perfRes['avg_days'] !== null) {
            $avgDays = round((float)$perfRes['avg_days'], 1) . ' Days';
        }

        $slaCompliance = 'N/A';
        if ($perfRes['total_completed'] > 0) {
            $slaCompliance = round(($perfRes['in_sla'] / $perfRes['total_completed']) * 100) . '%';
        }

        // Open High Priority
        $highPriorityQuery = "
            SELECT COUNT(*) 
            FROM data_requests 
            WHERE status IN ('open', 'verifying', 'processing') AND priority IN ('High', 'Urgent')
        ";
        $openHighPriority = $this->pdo->query($highPriorityQuery)->fetchColumn();

        // Request Distribution
        $distQuery = "
            SELECT request_type, COUNT(*) as count 
            FROM data_requests 
            GROUP BY request_type
        ";
        $distRes = $this->pdo->query($distQuery)->fetchAll(\PDO::FETCH_ASSOC);
        $distribution = [];
        foreach ($distRes as $row) {
            $pct = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
            $distribution[$row['request_type']] = $pct;
        }

        // Processing Performance (Verification statuses)
        $perfQuery = "
            SELECT 
                SUM(IF(verification_status = 'verified', 1, 0)) as verified,
                SUM(IF(status = 'completed', 1, 0)) as completed,
                SUM(IF(status IN ('open','verifying','processing'), 1, 0)) as pending,
                SUM(IF(priority = 'Urgent', 1, 0)) as escalated
            FROM data_requests
        ";
        $perfRaw = $this->pdo->query($perfQuery)->fetch(\PDO::FETCH_ASSOC);
        
        $performance = [
            'verified' => $total > 0 ? round(($perfRaw['verified'] / $total) * 100) . '%' : '0%',
            'completed' => $total > 0 ? round(($perfRaw['completed'] / $total) * 100) . '%' : '0%',
            'pending' => $total > 0 ? round(($perfRaw['pending'] / $total) * 100) . '%' : '0%',
            'escalated' => $total > 0 ? round(($perfRaw['escalated'] / $total) * 100) . '%' : '0%'
        ];

        return [
            'total' => $total,
            'pending' => $kpiRes['pending'] ?? 0,
            'completed' => $kpiRes['completed'] ?? 0,
            'sla_compliance' => $slaCompliance,
            'avg_resolution' => $avgDays,
            'open_high_priority' => $openHighPriority ?? 0,
            'distribution' => [
                'access' => $distribution['access'] ?? 0,
                'erasure' => $distribution['erasure'] ?? 0,
                'portability' => $distribution['portability'] ?? 0,
                'rectification' => $distribution['rectification'] ?? 0,
                'objection' => $distribution['objection'] ?? 0
            ],
            'performance' => $performance
        ];
    }

    public function updateVerification($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE data_requests SET verification_status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function updateAssignment($id, $userId) {
        $stmt = $this->pdo->prepare("UPDATE data_requests SET assigned_to = ? WHERE id = ?");
        return $stmt->execute([$userId ?: null, $id]);
    }

    public function getPendingAction() {
        $sql = "
            SELECT dr.*, ds.identifier_hash as subject_email, ds.type as subject_type 
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            WHERE dr.verification_status = 'pending' 
               OR dr.assigned_to IS NULL 
               OR dr.status IN ('open', 'verifying')
            ORDER BY dr.id DESC
        ";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}

