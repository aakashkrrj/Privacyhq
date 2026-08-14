<?php
namespace Backend\Models;

class DataRequest {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function generateRequestIdCode() {
        return 'DSR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    public function create($dataSubjectId, $requestType, $priority = 'Medium', $dueDate = null, $description = null, $createdBy = null) {
        if (!$dueDate) {
            $dueDate = date('Y-m-d', strtotime('+30 days'));
        }
        
        $requestCode = $this->generateRequestIdCode();
        $stmt = $this->pdo->prepare("
            INSERT INTO data_requests (request_id_code, data_subject_id, request_type, status, priority, due_date, description, created_by)
            VALUES (?, ?, ?, 'open', ?, ?, ?, ?)
        ");
        $stmt->execute([$requestCode, $dataSubjectId, $requestType, $priority, $dueDate, $description, $createdBy]);
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, $priority, $dueDate = null, $description = null, $status = null, $assignedTo = null) {
        $fields = ["priority = ?"];
        $params = [$priority];

        if ($dueDate !== null) {
            $fields[] = "due_date = ?";
            $params[] = $dueDate;
        }
        if ($description !== null) {
            $fields[] = "description = ?";
            $params[] = $description;
        }
        if ($status !== null) {
            $fields[] = "status = ?";
            $params[] = $status;
            if ($status === 'completed' || $status === 'rejected') {
                $fields[] = "resolved_at = NOW()";
            }
        }
        if ($assignedTo !== null) {
            $fields[] = "assigned_to = ?";
            $params[] = $assignedTo ?: null;
        }

        $fields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE data_requests SET " . implode(", ", $fields) . " WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE data_requests SET status = ?, updated_at = NOW()";
        if ($status === 'completed' || $status === 'rejected') {
            $sql .= ", resolved_at = NOW()";
        }
        $sql .= " WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("UPDATE data_requests SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("
            SELECT dr.*, 
                   ds.name as subject_name, 
                   ds.email as subject_email, 
                   ds.phone as subject_phone, 
                   ds.department as subject_dept, 
                   ds.type as subject_type,
                   CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_user_name,
                   u.email as assigned_user_email,
                   CONCAT(COALESCE(cb.first_name, ''), ' ', COALESCE(cb.last_name, '')) as creator_user_name
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            LEFT JOIN users u ON dr.assigned_to = u.id
            LEFT JOIN users cb ON dr.created_by = cb.id
            WHERE dr.id = ? AND dr.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($request) {
            // Fetch Status History
            $stmtHist = $this->pdo->prepare("
                SELECT rh.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as changed_by_name
                FROM request_history rh
                LEFT JOIN users u ON rh.changed_by = u.id
                WHERE rh.data_request_id = ?
                ORDER BY rh.id ASC
            ");
            $stmtHist->execute([$id]);
            $request['history'] = $stmtHist->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch Notes
            $request['notes'] = $this->getNotes($id);

            // Fetch Attachments
            $request['attachments'] = $this->getAttachments($id);

            // Fetch Audit Logs
            $stmtAudit = $this->pdo->prepare("
                SELECT al.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.module = 'DSR Management' AND al.record_id = ?
                ORDER BY al.id DESC
            ");
            $stmtAudit->execute([$id]);
            $request['audit_logs'] = $stmtAudit->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $request;
    }

    public function getList($search = '', $statusFilter = '', $priorityFilter = '', $typeFilter = '', $assignedFilter = '', $deptFilter = '', $fromDate = '', $toDate = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 10, $offset = 0) {
        $whereClauses = ["dr.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(dr.request_id_code LIKE ? OR ds.name LIKE ? OR ds.email LIKE ? OR ds.identifier_hash LIKE ? OR ds.department LIKE ? OR dr.description LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s]);
        }
        if (!empty($statusFilter)) {
            $whereClauses[] = "dr.status = ?";
            $params[] = $statusFilter;
        }
        if (!empty($priorityFilter)) {
            $whereClauses[] = "dr.priority = ?";
            $params[] = $priorityFilter;
        }
        if (!empty($typeFilter)) {
            $whereClauses[] = "dr.request_type = ?";
            $params[] = $typeFilter;
        }
        if (!empty($assignedFilter)) {
            $whereClauses[] = "dr.assigned_to = ?";
            $params[] = $assignedFilter;
        }
        if (!empty($deptFilter)) {
            $whereClauses[] = "ds.department = ?";
            $params[] = $deptFilter;
        }
        if (!empty($fromDate)) {
            $whereClauses[] = "DATE(dr.created_at) >= ?";
            $params[] = $fromDate;
        }
        if (!empty($toDate)) {
            $whereClauses[] = "DATE(dr.created_at) <= ?";
            $params[] = $toDate;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "SELECT COUNT(*) FROM data_requests dr LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $allowedSort = ['id', 'request_id_code', 'status', 'priority', 'due_date', 'created_at'];
        $sortCol = in_array($sortBy, $allowedSort) ? "dr.$sortBy" : "dr.id";
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT dr.id, dr.request_id_code, dr.request_type, dr.status, dr.priority, dr.due_date, dr.description, dr.created_at,
                   ds.name as subject_name, 
                   ds.email as subject_email,
                   ds.phone as subject_phone,
                   ds.department as subject_dept,
                   ds.type as subject_type,
                   CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_user_name
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            LEFT JOIN users u ON dr.assigned_to = u.id
            $whereSql
            ORDER BY $sortCol $sortDir
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
                SUM(IF(status = 'open', 1, 0)) as open_count,
                SUM(IF(status IN ('processing', 'verifying', 'assigned', 'waiting'), 1, 0)) as pending,
                SUM(IF(status = 'completed', 1, 0)) as completed,
                SUM(IF(status = 'rejected', 1, 0)) as rejected,
                SUM(IF(DATE(created_at) = CURDATE() AND status != 'completed', 1, 0)) as pending_today
            FROM data_requests
            WHERE deleted_at IS NULL
        ";
        $kpiRes = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
        $total = (int)($kpiRes['total'] ?? 0);

        // SLA Compliance & Average Resolution Time
        $avgResQuery = "
            SELECT 
                AVG(DATEDIFF(COALESCE(resolved_at, updated_at), created_at)) as avg_days,
                COUNT(*) as total_completed,
                SUM(IF(DATE(COALESCE(resolved_at, updated_at)) <= due_date, 1, 0)) as in_sla
            FROM data_requests
            WHERE status = 'completed' AND deleted_at IS NULL
        ";
        $perfRes = $this->pdo->query($avgResQuery)->fetch(\PDO::FETCH_ASSOC);
        
        $avgDays = '0.0 Days';
        if ($perfRes['avg_days'] !== null) {
            $avgDays = round((float)$perfRes['avg_days'], 1) . ' Days';
        }

        $slaCompliance = '100%';
        if ($perfRes['total_completed'] > 0) {
            $slaCompliance = round(($perfRes['in_sla'] / $perfRes['total_completed']) * 100) . '%';
        }

        // Open High Priority
        $highPriorityQuery = "
            SELECT COUNT(*) 
            FROM data_requests 
            WHERE status IN ('open', 'assigned', 'verifying', 'processing', 'waiting') AND priority IN ('High', 'Urgent') AND deleted_at IS NULL
        ";
        $openHighPriority = (int)$this->pdo->query($highPriorityQuery)->fetchColumn();

        // Request Distribution %
        $distQuery = "
            SELECT request_type, COUNT(*) as count 
            FROM data_requests 
            WHERE deleted_at IS NULL
            GROUP BY request_type
        ";
        $distRes = $this->pdo->query($distQuery)->fetchAll(\PDO::FETCH_ASSOC);
        $distribution = [];
        foreach ($distRes as $row) {
            $pct = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
            $distribution[$row['request_type']] = $pct;
        }

        // Performance
        $perfQuery = "
            SELECT 
                SUM(IF(verification_status = 'verified', 1, 0)) as verified,
                SUM(IF(status = 'completed', 1, 0)) as completed,
                SUM(IF(status IN ('open','assigned','verifying','processing','waiting'), 1, 0)) as pending,
                SUM(IF(priority = 'Urgent', 1, 0)) as escalated
            FROM data_requests
            WHERE deleted_at IS NULL
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
            'open' => (int)($kpiRes['open_count'] ?? 0),
            'pending' => (int)($kpiRes['pending'] ?? 0),
            'completed' => (int)($kpiRes['completed'] ?? 0),
            'rejected' => (int)($kpiRes['rejected'] ?? 0),
            'pending_today' => (int)($kpiRes['pending_today'] ?? 0),
            'sla_compliance' => $slaCompliance,
            'avg_resolution' => $avgDays,
            'open_high_priority' => $openHighPriority,
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
        $stmt = $this->pdo->prepare("UPDATE data_requests SET verification_status = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$status, $id]);
    }

    public function updateAssignment($id, $userId) {
        $stmt = $this->pdo->prepare("UPDATE data_requests SET assigned_to = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$userId ?: null, $id]);
    }

    public function getPendingAction() {
        $sql = "
            SELECT dr.*, 
                   ds.name as subject_name, 
                   ds.email as subject_email, 
                   ds.type as subject_type 
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            WHERE dr.deleted_at IS NULL
              AND (dr.verification_status = 'pending' 
                   OR dr.assigned_to IS NULL 
                   OR dr.status IN ('open', 'verifying', 'assigned'))
            ORDER BY dr.id DESC
        ";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Notes
    public function addNote($requestId, $userId, $noteText, $isPublic = 0) {
        $stmt = $this->pdo->prepare("
            INSERT INTO dsr_notes (data_request_id, user_id, note_text, is_public) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$requestId, $userId, $noteText, $isPublic ? 1 : 0]);
    }

    public function getNotes($requestId) {
        $stmt = $this->pdo->prepare("
            SELECT n.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as author_name
            FROM dsr_notes n
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.data_request_id = ?
            ORDER BY n.id DESC
        ");
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Attachments
    public function addAttachment($requestId, $fileName, $filePath, $fileSize, $fileType, $userId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO dsr_attachments (data_request_id, file_name, file_path, file_size, file_type, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$requestId, $fileName, $filePath, $fileSize, $fileType, $userId]);
    }

    public function getAttachments($requestId) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as uploader_name
            FROM dsr_attachments a
            LEFT JOIN users u ON a.uploaded_by = u.id
            WHERE a.data_request_id = ?
            ORDER BY a.id DESC
        ");
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deleteAttachment($attachmentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM dsr_attachments WHERE id = ? LIMIT 1");
        $stmt->execute([$attachmentId]);
        $att = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($att) {
            if (file_exists(__DIR__ . '/../../' . $att['file_path'])) {
                @unlink(__DIR__ . '/../../' . $att['file_path']);
            }
            $del = $this->pdo->prepare("DELETE FROM dsr_attachments WHERE id = ?");
            return $del->execute([$attachmentId]);
        }
        return false;
    }
}
