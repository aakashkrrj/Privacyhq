<?php
namespace Backend\Models;

class Consent {
    public const STATUS_OPT_IN = 'opt_in';
    public const STATUS_OPT_OUT = 'opt_out';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_EXPIRED = 'expired';

    public static function getValidStatuses(): array {
        return [
            self::STATUS_OPT_IN,
            self::STATUS_OPT_OUT,
            self::STATUS_WITHDRAWN,
            self::STATUS_EXPIRED
        ];
    }

    public static function getStatusMap(): array {
        return [
            'Granted'   => self::STATUS_OPT_IN,
            'Pending'   => self::STATUS_OPT_OUT,
            'Revoked'   => self::STATUS_WITHDRAWN,
            'Expired'   => self::STATUS_EXPIRED,
            self::STATUS_OPT_IN    => self::STATUS_OPT_IN,
            self::STATUS_OPT_OUT   => self::STATUS_OPT_OUT,
            self::STATUS_WITHDRAWN => self::STATUS_WITHDRAWN,
            self::STATUS_EXPIRED   => self::STATUS_EXPIRED,
        ];
    }

    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($subjectId, $purposeId, $policyId, $status, $source, $collectionMethod = 'web_portal', $ipAddress = null, $userAgent = null, $expiresAt = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO consents (data_subject_id, consent_purpose_id, policy_id, status, source, collection_method, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$subjectId, $purposeId, $policyId, $status, $source, $collectionMethod, $ipAddress, $userAgent, $expiresAt]);
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
            SELECT c.id, c.status, c.source, c.collection_method, c.ip_address, c.user_agent, c.created_at, c.granted_at, c.expires_at, 
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

    public function getExportList($search, $statusFilter, $categoryFilter) {
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

        $sql = "
            SELECT c.id, c.status, c.source, c.collection_method, c.ip_address, c.user_agent, c.created_at, c.granted_at, c.expires_at, 
                   ds.identifier_hash as subject_email, cp.purpose_name as category
            FROM consents c
            LEFT JOIN data_subjects ds ON c.data_subject_id = ds.id
            LEFT JOIN consent_purposes cp ON c.consent_purpose_id = cp.id
            $whereSql
            ORDER BY c.id DESC
        ";
            
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

    public function getHistory($consentId) {
        $sql = "
            SELECT ch.id, ch.previous_status, ch.new_status, ch.reason, ch.created_at,
                   COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System / Self') AS changed_by
            FROM consent_history ch
            LEFT JOIN users u ON ch.changed_by = u.id
            WHERE ch.consent_id = ?
            ORDER BY ch.id DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$consentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
