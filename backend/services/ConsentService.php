<?php
/**
 * Consent Management Service
 * 
 * Handles business logic, validation, and database operations for Consents.
 */

require_once __DIR__ . '/../../includes/db_helper.php';

class ConsentService {
    private $db;

    public const STATUS_ACTIVE = 'opt_in';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_EXPIRED = 'expired';

    public function __construct() {
        $this->db = new DBHelper();
    }

    /**
     * Get a paginated, filtered, sorted list of consents.
     */
    public function getConsents(array $filters = [], int $page = 1, int $limit = 10, string $sort = 'created_at', string $order = 'DESC'): array {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;
        
        $allowedSortCols = ['id', 'created_at', 'expires_at', 'status', 'purpose_name', 'user_email'];
        if (!in_array($sort, $allowedSortCols)) {
            $sort = 'created_at';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $whereClauses = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereClauses[] = "c.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['purpose_id'])) {
            $whereClauses[] = "c.consent_purpose_id = :purpose_id";
            $params['purpose_id'] = $filters['purpose_id'];
        }
        if (!empty($filters['created_date'])) {
            $whereClauses[] = "DATE(c.created_at) = :created_date";
            $params['created_date'] = $filters['created_date'];
        }
        if (!empty($filters['expiry_date'])) {
            $whereClauses[] = "DATE(c.expires_at) = :expiry_date";
            $params['expiry_date'] = $filters['expiry_date'];
        }

        if (!empty($filters['keyword'])) {
            $kw = '%' . $filters['keyword'] . '%';
            $whereClauses[] = "(c.id LIKE :kw1 OR ds.identifier_hash LIKE :kw2 OR p.purpose_name LIKE :kw5 OR c.status LIKE :kw6)";
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw5'] = $kw;
            $params['kw6'] = $kw;
        }

        $whereSql = empty($whereClauses) ? "" : "WHERE " . implode(" AND ", $whereClauses);
        
        $sql = "SELECT c.id, c.status, c.granted_at, c.expires_at, c.created_at, c.source, 
                       p.purpose_name as purpose_name, p.id as purpose_id,
                       ds.identifier_hash as user_email, SUBSTRING(ds.identifier_hash, 1, 8) as first_name, ds.type as last_name, ds.id as user_id
                FROM consents c
                JOIN consent_purposes p ON c.consent_purpose_id = p.id
                JOIN data_subjects ds ON c.data_subject_id = ds.id
                $whereSql
                ORDER BY $sort $order 
                LIMIT $limit OFFSET $offset";

        $results = $this->db->fetchAll($sql, $params);
        
        // Get total for pagination
        $countSql = "SELECT COUNT(*) as total
                     FROM consents c
                     JOIN consent_purposes p ON c.consent_purpose_id = p.id
                     JOIN data_subjects ds ON c.data_subject_id = ds.id
                     $whereSql";
        $countRow = $this->db->fetchOne($countSql, $params);
        $total = (int)($countRow['total'] ?? 0);

        return [
            'data' => $results ?: [],
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    /**
     * Search consents specifically
     */
    public function searchConsents(string $keyword): array {
        return $this->getConsents(['keyword' => $keyword], 1, 100);
    }

    /**
     * Filter consents specifically
     */
    public function filterConsents(array $filters): array {
        return $this->getConsents($filters, 1, 100);
    }

    /**
     * Get single consent by ID
     */
    public function getConsentById(int $id) {
        $sql = "SELECT c.*, p.purpose_name as purpose_name, ds.identifier_hash as user_email, SUBSTRING(ds.identifier_hash, 1, 8) as first_name, ds.type as last_name 
                FROM consents c
                JOIN consent_purposes p ON c.consent_purpose_id = p.id
                JOIN data_subjects ds ON c.data_subject_id = ds.id
                WHERE c.id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Create a new consent
     */
    public function createConsent(array $data, int $actorId): array {
        $this->validateData($data);

        // Check duplicates
        $dup = $this->db->fetchOne("SELECT id FROM consents WHERE data_subject_id = :u AND consent_purpose_id = :p AND status = :s", [
            'u' => $data['data_subject_id'], 'p' => $data['consent_purpose_id'], 's' => self::STATUS_ACTIVE
        ]);
        if ($dup) {
            throw new Exception("Active consent already exists for this subject and purpose.");
        }

        $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;

        $sql = "INSERT INTO consents (data_subject_id, consent_purpose_id, policy_id, status, source, expires_at) 
                VALUES (:u, :p, :pol, :s, :src, :e)";
        $params = [
            'u' => $data['data_subject_id'],
            'p' => $data['consent_purpose_id'],
            'pol' => $data['policy_id'] ?? 1, // Defaulting policy_id since it's required
            's' => self::STATUS_ACTIVE,
            'src' => $data['source'] ?? 'Manual',
            'e' => $expiresAt
        ];

        $this->db->execute($sql, $params);
        $newId = (int)$this->db->getLastInsertId();

        if (!$newId) {
            throw new Exception("Failed to create consent record.");
        }

        $this->logHistory($newId, null, self::STATUS_ACTIVE, $actorId, "Consent Created");
        $this->logAudit($actorId, 'CREATE', $newId);

        return $this->getConsentById($newId);
    }

    /**
     * Update an existing consent
     */
    public function updateConsent(int $id, array $data, int $actorId): array {
        $existing = $this->getConsentById($id);
        if (!$existing) {
            throw new Exception("Consent record not found.");
        }

        if (isset($data['expires_at']) && !empty($data['expires_at'])) {
            if (strtotime($data['expires_at']) < time()) {
                throw new Exception("Expiry date cannot be in the past.");
            }
            $sql = "UPDATE consents SET expires_at = :e WHERE id = :id";
            $this->db->execute($sql, ['e' => $data['expires_at'], 'id' => $id]);
        }

        if (isset($data['status']) && $data['status'] !== $existing['status']) {
            if (!in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_WITHDRAWN, self::STATUS_EXPIRED])) {
                throw new Exception("Invalid status.");
            }
            $sql = "UPDATE consents SET status = :s WHERE id = :id";
            $this->db->execute($sql, ['s' => $data['status'], 'id' => $id]);
            
            $this->logHistory($id, $existing['status'], $data['status'], $actorId, "Status updated to " . $data['status']);
            
            if ($data['status'] === self::STATUS_WITHDRAWN) {
                $this->logAudit($actorId, 'REVOKE', $id);
            }
        }
        
        $this->logAudit($actorId, 'UPDATE', $id);

        return $this->getConsentById($id);
    }

    /**
     * Delete a consent
     */
    public function deleteConsent(int $id, int $actorId): bool {
        $existing = $this->getConsentById($id);
        if (!$existing) {
            throw new Exception("Consent record not found.");
        }

        $this->logHistory($id, $existing['status'], 'Deleted', $actorId, "Consent deleted manually.");
        
        $sql = "DELETE FROM consents WHERE id = :id";
        $result = $this->db->execute($sql, ['id' => $id]);
        
        if ($result) {
            $this->logAudit($actorId, 'DELETE', $id);
        }
        
        return $result;
    }

    /**
     * Get consent history
     */
    public function getConsentHistory(int $consentId): array {
        $sql = "SELECT h.*, u.email as changed_by_email 
                FROM consent_history h
                LEFT JOIN users u ON h.changed_by = u.id
                WHERE h.consent_id = :id
                ORDER BY h.changed_at DESC";
        return $this->db->fetchAll($sql, ['id' => $consentId]) ?: [];
    }

    // --- Statistics Methods ---
    
    public function countConsents(): int {
        return (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM consents")['c'] ?? 0);
    }

    public function countActiveConsents(): int {
        return (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM consents WHERE status = :s", ['s' => self::STATUS_ACTIVE])['c'] ?? 0);
    }

    public function countExpiredConsents(): int {
        return (int)($this->db->fetchOne("SELECT COUNT(*) as c FROM consents WHERE status = :s", ['s' => self::STATUS_EXPIRED])['c'] ?? 0);
    }

    // --- Helper Methods ---

    private function validateData(array $data) {
        if (empty($data['data_subject_id']) || empty($data['consent_purpose_id'])) {
            throw new Exception("Data Subject ID and Consent Purpose ID are required.");
        }
        
        // Validate dates if present
        if (!empty($data['expires_at'])) {
            if (strtotime($data['expires_at']) === false) {
                throw new Exception("Invalid expiry date format.");
            }
            if (strtotime($data['expires_at']) < time()) {
                throw new Exception("Expiry date cannot be in the past.");
            }
        }
    }

    private function logHistory(int $consentId, ?string $oldStatus, string $newStatus, int $actorId, string $remarks) {
        // ENUM constraint in DB only supports Active, Withdrawn, Expired for new_status, but for 'Deleted' we might hit an issue.
        // Wait! The schema has `new_status` ENUM('Active', 'Withdrawn', 'Expired') NOT NULL.
        // So for 'Deleted', we shouldn't insert a status if it violates ENUM. Let's just use the closest or avoid inserting history for deleted since CASCADE takes care of it.
        if ($newStatus === 'Deleted') return;

        $sql = "INSERT INTO consent_history (consent_id, previous_status, new_status, changed_by, reason) 
                VALUES (:cid, :old, :new, :actor, :rem)";
        $this->db->execute($sql, [
            'cid' => $consentId,
            'old' => $oldStatus,
            'new' => $newStatus,
            'actor' => $actorId,
            'rem' => $remarks
        ]);
    }

    public function logAudit(int $actorId, string $action, int $recordId) {
        $sql = "INSERT INTO audit_logs (user_id, module, action, record_id) VALUES (:u, :m, :a, :r)";
        $this->db->execute($sql, [
            'u' => $actorId,
            'm' => 'ConsentManagement',
            'a' => $action,
            'r' => $recordId
        ]);
    }
}
