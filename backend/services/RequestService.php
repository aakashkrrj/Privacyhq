<?php
/**
 * Data Requests Service
 * 
 * Handles business logic, validation, and database operations for Data Subject Access Requests (DSAR).
 */

require_once __DIR__ . '/../../includes/db_helper.php';

class RequestService {
    private $db;

    // ENUM Constants for Request Type
    public const TYPE_ACCESS = 'access';
    public const TYPE_ERASURE = 'erasure';
    public const TYPE_RECTIFICATION = 'rectification';
    public const TYPE_PORTABILITY = 'portability';
    public const TYPE_OBJECTION = 'objection';

    // ENUM Constants for Status
    public const STATUS_OPEN = 'open';
    public const STATUS_VERIFYING = 'verifying';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    // ENUM Constants for Priority
    public const PRIORITY_LOW = 'Low';
    public const PRIORITY_MEDIUM = 'Medium';
    public const PRIORITY_HIGH = 'High';
    public const PRIORITY_URGENT = 'Urgent';

    public function __construct() {
        $this->db = new DBHelper();
    }

    /**
     * Map DB Request Type to UI Label
     */
    public static function getTypeLabel(?string $type): string {
        $map = [
            self::TYPE_ACCESS => 'Access',
            self::TYPE_ERASURE => 'Deletion',
            self::TYPE_RECTIFICATION => 'Rectification',
            self::TYPE_PORTABILITY => 'Portability',
            self::TYPE_OBJECTION => 'Objection'
        ];
        return $map[$type] ?? 'Unknown';
    }

    /**
     * Map DB Status to UI Label
     */
    public static function getStatusLabel(?string $status): string {
        $map = [
            self::STATUS_OPEN => 'Pending',
            self::STATUS_VERIFYING => 'In Progress',
            self::STATUS_PROCESSING => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired'
        ];
        return $map[$status] ?? 'Unknown';
    }

    /**
     * Get a paginated, filtered, sorted list of requests.
     */
    public function getRequests(array $filters = [], int $page = 1, int $limit = 10, string $sort = 'created_at', string $order = 'DESC'): array {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;
        
        $allowedSortCols = ['id', 'request_id_code', 'created_at', 'due_date', 'status', 'priority', 'request_type'];
        if (!in_array($sort, $allowedSortCols)) {
            $sort = 'created_at';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['request_type'])) {
            $whereClauses[] = "r.request_type = :request_type";
            $params['request_type'] = $filters['request_type'];
        }
        if (!empty($filters['priority'])) {
            $whereClauses[] = "r.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['keyword'])) {
            $kw = '%' . $filters['keyword'] . '%';
            $whereClauses[] = "(r.request_id_code LIKE :kw1 OR ds.identifier_hash LIKE :kw2)";
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
        }

        $whereSql = '';
        if (count($whereClauses) > 0) {
            $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        $sql = "SELECT r.*, ds.identifier_hash as user_email, 
                       u.email as assigned_to_email, CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
                FROM data_requests r
                JOIN data_subjects ds ON r.data_subject_id = ds.id
                LEFT JOIN users u ON r.assigned_to = u.id
                $whereSql
                ORDER BY r.$sort $order 
                LIMIT $limit OFFSET $offset";

        $result = $this->db->fetchAll($sql, $params);
        return $result ?: [];
    }

    /**
     * Count total requests for pagination.
     */
    public function countRequests(array $filters = []): int {
        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['request_type'])) {
            $whereClauses[] = "r.request_type = :request_type";
            $params['request_type'] = $filters['request_type'];
        }
        if (!empty($filters['priority'])) {
            $whereClauses[] = "r.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['keyword'])) {
            $kw = '%' . $filters['keyword'] . '%';
            $whereClauses[] = "(r.request_id_code LIKE :kw1 OR ds.identifier_hash LIKE :kw2)";
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
        }

        $whereSql = '';
        if (count($whereClauses) > 0) {
            $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
        }

        $countSql = "SELECT COUNT(*) as total
                     FROM data_requests r
                     JOIN data_subjects ds ON r.data_subject_id = ds.id
                     $whereSql";

        $countRes = $this->db->fetchOne($countSql, $params);
        return (int)($countRes['total'] ?? 0);
    }

    /**
     * Get single request by ID.
     */
    public function getRequestById(int $id): ?array {
        $sql = "SELECT r.*, ds.identifier_hash as user_email,
                       u.email as assigned_to_email, CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
                FROM data_requests r
                JOIN data_subjects ds ON r.data_subject_id = ds.id
                LEFT JOIN users u ON r.assigned_to = u.id
                WHERE r.id = :id";
        $result = $this->db->fetchOne($sql, ['id' => $id]);
        return $result ?: null;
    }

    /**
     * Create a new data request.
     */
    public function createRequest(array $data, int $createdBy): int {
        $this->db->beginTransaction();
        try {
            // Generate request_id_code e.g. REQ-000004
            $lastReq = $this->db->fetchOne("SELECT id FROM data_requests ORDER BY id DESC LIMIT 1");
            $nextId = ($lastReq ? (int)$lastReq['id'] : 0) + 1;
            $reqCode = 'REQ-' . str_pad((string)$nextId, 6, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO data_requests (
                        request_id_code, data_subject_id, request_type, 
                        status, priority, assigned_to, due_date, 
                        progress_percentage, verification_status
                    ) VALUES (
                        :code, :ds, :type, :status, :priority, :assign, :due, 0, 'pending'
                    )";
            
            $status = $data['status'] ?? self::STATUS_OPEN;
            $this->db->execute($sql, [
                'code' => $reqCode,
                'ds' => $data['data_subject_id'],
                'type' => $data['request_type'],
                'status' => $status,
                'priority' => $data['priority'] ?? self::PRIORITY_MEDIUM,
                'assign' => $data['assigned_to'] ?? null,
                'due' => $data['due_date']
            ]);
            
            $reqId = $this->db->getLastInsertId();

            $this->logHistory($reqId, null, $status, $createdBy, $data['assigned_to'] ?? null, 'Request Created');
            $this->logAudit($createdBy, 'DataRequests', 'Create', $reqId);

            $this->db->commit();
            return $reqId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing request.
     */
    public function updateRequest(int $id, array $data, int $updatedBy): bool {
        $current = $this->getRequestById($id);
        if (!$current) {
            throw new Exception("Request not found");
        }

        $this->db->beginTransaction();
        try {
            $updates = [];
            $params = ['id' => $id];
            
            if (isset($data['status'])) {
                $updates[] = "status = :status";
                $params['status'] = $data['status'];
            }
            if (isset($data['priority'])) {
                $updates[] = "priority = :priority";
                $params['priority'] = $data['priority'];
            }
            if (isset($data['progress_percentage'])) {
                $updates[] = "progress_percentage = :progress";
                $params['progress'] = $data['progress_percentage'];
            }
            if (isset($data['assigned_to'])) {
                $updates[] = "assigned_to = :assigned";
                $params['assigned'] = $data['assigned_to'];
            }

            if (!empty($updates)) {
                $sql = "UPDATE data_requests SET " . implode(", ", $updates) . " WHERE id = :id";
                $this->db->execute($sql, $params);
            }

            // Log status change if status or assignee changed
            if (isset($data['status']) && $data['status'] !== $current['status']) {
                $this->logHistory($id, $current['status'], $data['status'], $updatedBy, $data['assigned_to'] ?? $current['assigned_to'], 'Status updated');
            }

            $this->logAudit($updatedBy, 'DataRequests', 'Update', $id);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a request (Hard Delete).
     * The schema does not have a deleted_at column for data_requests.
     */
    public function deleteRequest(int $id, int $deletedBy): bool {
        $this->db->beginTransaction();
        try {
            $this->db->execute("DELETE FROM request_history WHERE data_request_id = :id", ['id' => $id]);
            $this->db->execute("DELETE FROM data_requests WHERE id = :id", ['id' => $id]);
            
            $this->logAudit($deletedBy, 'DataRequests', 'Delete', $id);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get request history logs.
     */
    public function getRequestHistory(int $requestId): array {
        $sql = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name, u.email as changed_by_email,
                       CONCAT(a.first_name, ' ', a.last_name) as assigned_to_name
                FROM request_history h
                LEFT JOIN users u ON h.changed_by = u.id
                LEFT JOIN users a ON h.assigned_to = a.id
                WHERE h.data_request_id = :req
                ORDER BY h.changed_at DESC";
        $result = $this->db->fetchAll($sql, ['req' => $requestId]);
        return $result ?: [];
    }

    /**
     * Log to request_history.
     */
    private function logHistory(int $requestId, ?string $oldStatus, string $newStatus, int $changedBy, ?int $assignedTo, ?string $comments): void {
        $sql = "INSERT INTO request_history (data_request_id, changed_by, assigned_to, previous_status, new_status, comments) 
                VALUES (:req, :actor, :assign, :old, :new, :comments)";
        $this->db->execute($sql, [
            'req' => $requestId,
            'actor' => $changedBy,
            'assign' => $assignedTo,
            'old' => $oldStatus,
            'new' => $newStatus,
            'comments' => $comments
        ]);
    }

    /**
     * Log to audit_logs.
     */
    private function logAudit(int $userId, string $module, string $action, ?int $recordId = null): void {
        $sql = "INSERT INTO audit_logs (user_id, module, action, record_id) VALUES (:u, :m, :a, :r)";
        $this->db->execute($sql, [
            'u' => $userId,
            'm' => $module,
            'a' => $action,
            'r' => $recordId
        ]);
    }
}
