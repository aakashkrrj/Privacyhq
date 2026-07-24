<?php
/**
 * AssessmentService
 * Handles business logic, CRUD operations, transactions, and auditing for Privacy Assessments.
 */
require_once __DIR__ . '/../../includes/db_helper.php';

class AssessmentService {
    private $db;

    public function __construct() {
        $this->db = new DBHelper();
    }

    /**
     * Get paginated and filtered assessments
     */
    public function getAssessments(array $filters = [], int $page = 1, int $limit = 50, string $sort = 'due_date', string $order = 'ASC'): array {
        $offset = ($page - 1) * $limit;
        
        $allowedSorts = ['id', 'title', 'due_date', 'created_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'due_date';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $whereParams = [];
        $whereSql = ["a.deleted_at IS NULL"];

        if (!empty($filters['status_id'])) {
            $whereSql[] = "a.status_id = :status_id";
            $whereParams['status_id'] = $filters['status_id'];
        }
        
        if (!empty($filters['keyword'])) {
            $whereSql[] = "(a.title LIKE :keyword1 OR pa.activity_name LIKE :keyword2)";
            $whereParams['keyword1'] = '%' . $filters['keyword'] . '%';
            $whereParams['keyword2'] = '%' . $filters['keyword'] . '%';
        }

        $whereClause = "WHERE " . implode(' AND ', $whereSql);

        $sql = "SELECT a.*, pa.activity_name, s.status_name, 
                       CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                       u.email as owner_email,
                       (SELECT MAX(rm.risk_score) 
                        FROM assessment_risks ar 
                        JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id 
                        WHERE ar.assessment_id = a.id AND ar.deleted_at IS NULL) as max_risk_score,
                       (SELECT rm.risk_level_name 
                        FROM assessment_risks ar 
                        JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id 
                        WHERE ar.assessment_id = a.id AND ar.deleted_at IS NULL
                        ORDER BY rm.risk_score DESC LIMIT 1) as risk_level_name,
                        
                       (SELECT COUNT(*) 
                        FROM assessment_responses r 
                        WHERE r.assessment_id = a.id AND r.response_text IS NOT NULL) as answered_questions,
                        
                       (SELECT COUNT(*) 
                        FROM assessment_questions q 
                        JOIN assessment_sections sec ON q.section_id = sec.id
                        WHERE sec.template_id = a.template_id AND q.deleted_at IS NULL) as total_questions
                       
                FROM privacy_assessments a
                JOIN processing_activities pa ON a.processing_activity_id = pa.id
                JOIN assessment_statuses s ON a.status_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                $whereClause
                ORDER BY a.$sort $order
                LIMIT $limit OFFSET $offset";

        $results = $this->db->fetchAll($sql, $whereParams);
        if (!$results) return [];

        // Post-process to attach calculated progress %
        foreach ($results as &$row) {
            $row['progress_percentage'] = $this->calculateProgress($row['answered_questions'], $row['total_questions']);
        }
        return $results;
    }

    /**
     * Get Assessment By ID
     */
    public function getAssessmentById(int $id): ?array {
        $sql = "SELECT a.*, pa.activity_name, s.status_name, 
                       CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                       u.email as owner_email
                FROM privacy_assessments a
                JOIN processing_activities pa ON a.processing_activity_id = pa.id
                JOIN assessment_statuses s ON a.status_id = s.id
                LEFT JOIN users u ON a.assigned_to = u.id
                WHERE a.id = :id AND a.deleted_at IS NULL";
        
        $res = $this->db->fetchOne($sql, ['id' => $id]);
        return $res ?: null;
    }

    /**
     * Count Assessments for pagination
     */
    public function countAssessments(array $filters = []): int {
        $whereParams = [];
        $whereSql = ["deleted_at IS NULL"];

        if (!empty($filters['status_id'])) {
            $whereSql[] = "status_id = :status_id";
            $whereParams['status_id'] = $filters['status_id'];
        }

        $whereClause = "WHERE " . implode(' AND ', $whereSql);
        
        $sql = "SELECT COUNT(*) as total FROM privacy_assessments $whereClause";
        $result = $this->db->fetchOne($sql, $whereParams);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Calculate Completion Progress %
     */
    private function calculateProgress($answered, $total): int {
        $answered = (int)$answered;
        $total = (int)$total;
        if ($total === 0) return 0;
        return (int)round(($answered / $total) * 100);
    }

    /**
     * Get aggregate statistics for Dashboard
     */
    public function getDashboardStats(): array {
        // Pending reviews
        $sqlPending = "SELECT COUNT(*) as total FROM privacy_assessments 
                       WHERE status_id IN (SELECT id FROM assessment_statuses WHERE status_name = 'Under Review')
                       AND deleted_at IS NULL";
        $pending = (int)($this->db->fetchOne($sqlPending)['total'] ?? 0);

        // Overall compliance %
        $sqlCompliance = "SELECT 
                            COUNT(*) as total_assessments,
                            SUM(CASE WHEN s.status_name = 'Approved' THEN 1 ELSE 0 END) as approved_assessments
                          FROM privacy_assessments a
                          JOIN assessment_statuses s ON a.status_id = s.id
                          WHERE a.deleted_at IS NULL";
        $compData = $this->db->fetchOne($sqlCompliance);
        
        $total = (int)($compData['total_assessments'] ?? 0);
        $approved = (int)($compData['approved_assessments'] ?? 0);
        $dpdpScore = $total > 0 ? (int)round(($approved / $total) * 100) : 100;

        return [
            'pending_reviews' => $pending,
            'compliance_percentage' => $dpdpScore
        ];
    }

    /**
     * Create Assessment
     */
    public function createAssessment(array $data, int $userId): int {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO privacy_assessments 
                    (processing_activity_id, template_id, status_id, title, assigned_to, due_date, created_by, updated_by, created_at, updated_at) 
                    VALUES (:pa_id, :template_id, :status_id, :title, :assigned, :due_date, :created_by, :updated_by, NOW(), NOW())";
            
            $res = $this->db->execute($sql, [
                'pa_id' => $data['processing_activity_id'],
                'template_id' => $data['template_id'],
                'status_id' => $data['status_id'],
                'title' => $data['title'],
                'assigned' => $data['assigned_to'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId
            ]);
            
            if (!$res) {
                throw new Exception("Database execution failed");
            }
            
            $id = $this->db->getLastInsertId();

            $this->logHistory($id, null, $data['status_id'], $userId, 'Assessment Created');
            $this->logAudit($userId, 'Create', $id);

            $this->db->commit();
            return (int)$id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update Assessment
     */
    public function updateAssessment(int $id, array $data, int $userId): void {
        $existing = $this->getAssessmentById($id);
        if (!$existing) {
            throw new Exception("Assessment not found");
        }

        try {
            $this->db->beginTransaction();
            
            $updates = [];
            $params = ['id' => $id];

            if (isset($data['status_id'])) {
                $updates[] = "status_id = :status_id";
                $params['status_id'] = $data['status_id'];
                
                if ($data['status_id'] != $existing['status_id']) {
                    $this->logHistory($id, $existing['status_id'], $data['status_id'], $userId, 'Status changed');
                }
            }

            if (isset($data['assigned_to'])) {
                $updates[] = "assigned_to = :assigned_to";
                $params['assigned_to'] = $data['assigned_to'];
            }
            
            if (isset($data['title'])) {
                $updates[] = "title = :title";
                $params['title'] = $data['title'];
            }

            if (isset($data['due_date'])) {
                $updates[] = "due_date = :due_date";
                $params['due_date'] = $data['due_date'];
            }

            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $updates[] = "updated_by = :updated_by";
                $params['updated_by'] = $userId;
                
                $sql = "UPDATE privacy_assessments SET " . implode(', ', $updates) . " WHERE id = :id";
                $res = $this->db->execute($sql, $params);
                if (!$res) {
                    throw new Exception("Database update failed");
                }
            }

            $this->logAudit($userId, 'Update', $id);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get Status History
     */
    public function getAssessmentHistory(int $id): array {
        $sql = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name,
                       s1.status_name as previous_status_name, s2.status_name as new_status_name
                FROM assessment_status_history h
                LEFT JOIN users u ON h.changed_by = u.id
                LEFT JOIN assessment_statuses s1 ON h.previous_status_id = s1.id
                LEFT JOIN assessment_statuses s2 ON h.new_status_id = s2.id
                WHERE h.assessment_id = :id
                ORDER BY h.changed_at DESC";
        $results = $this->db->fetchAll($sql, ['id' => $id]);
        return $results ?: [];
    }

    /**
     * Write history log
     */
    private function logHistory(int $assessmentId, ?int $oldStatus, int $newStatus, int $userId, string $reason): void {
        $sql = "INSERT INTO assessment_status_history 
                (assessment_id, previous_status_id, new_status_id, changed_by, reason, changed_at) 
                VALUES (:id, :old, :new, :user, :reason, NOW())";
        $this->db->execute($sql, [
            'id' => $assessmentId,
            'old' => $oldStatus,
            'new' => $newStatus,
            'user' => $userId,
            'reason' => $reason
        ]);
    }

    /**
     * Write audit log
     */
    private function logAudit(int $userId, string $action, int $recordId): void {
        $sql = "INSERT INTO audit_logs 
                (user_id, module, action, record_id, ip_address, user_agent, created_at) 
                VALUES (:user, 'PrivacyAssessments', :action, :record, :ip, :ua, NOW())";
        
        $this->db->execute($sql, [
            'user' => $userId,
            'action' => $action,
            'record' => $recordId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'System'
        ]);
    }
}
