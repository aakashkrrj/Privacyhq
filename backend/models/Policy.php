<?php
// governance/backend/models/Policy.php

namespace Backend\Models;

class Policy
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new policy record
     */
    public function create($data, $userId = 1)
    {
        $title = trim($data['title'] ?? $data['policy_name'] ?? '');
        $category = trim($data['category'] ?? 'Data Privacy');
        $description = trim($data['description'] ?? '');
        $policyOwner = trim($data['policy_owner'] ?? 'DPO / Compliance Team');
        $department = trim($data['department'] ?? 'Legal & Governance');
        $version = trim($data['version'] ?? '1.0');
        $effectiveDate = !empty($data['effective_date']) ? $data['effective_date'] : date('Y-m-d');
        $reviewDate = !empty($data['review_date']) ? $data['review_date'] : date('Y-m-d', strtotime('+1 year'));
        $expiryDate = !empty($data['expiry_date']) ? $data['expiry_date'] : date('Y-m-d', strtotime('+2 years'));
        $status = strtolower(trim($data['status'] ?? 'draft'));
        $approvalStatus = strtolower(trim($data['approval_status'] ?? ($status === 'active' ? 'approved' : 'draft')));

        $documentPath = $data['document_path'] ?? null;
        $fileName = $data['file_name'] ?? ($documentPath ? basename($documentPath) : null);
        $fileType = $data['file_type'] ?? ($documentPath ? pathinfo($documentPath, PATHINFO_EXTENSION) : null);
        $fileSize = (int)($data['file_size'] ?? 0);
        $changeSummary = trim($data['change_summary'] ?? 'Initial policy creation.');

        // Auto-generate policy code
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM privacy_policies");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $policyCode = 'POL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO privacy_policies 
                (policy_code, policy_name, category, description, policy_owner, department, version, 
                 effective_date, review_date, expiry_date, status, approval_status, document_path, 
                 file_name, file_type, file_size, uploaded_by, created_at, updated_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $policyCode,
            $title,
            $category,
            $description,
            $policyOwner,
            $department,
            $version,
            $effectiveDate,
            $reviewDate,
            $expiryDate,
            $status,
            $approvalStatus,
            $documentPath,
            $fileName,
            $fileType,
            $fileSize,
            $userId
        ]);

        $policyId = $this->pdo->lastInsertId();

        // Insert initial entry in policy_versions
        $stmtVer = $this->pdo->prepare("
            INSERT INTO policy_versions 
                (policy_id, version_number, document_path, file_name, file_type, file_size, change_summary, uploaded_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtVer->execute([
            $policyId,
            $version,
            $documentPath,
            $fileName,
            $fileType,
            $fileSize,
            $changeSummary,
            $userId,
            $status
        ]);
        $versionId = $this->pdo->lastInsertId();

        // Insert audit entry in policy_approvals
        $this->logApprovalAction($policyId, $versionId, 'Created Policy', $userId, 'Author', null, $status, "Policy document created with code {$policyCode}.");

        return $policyId;
    }

    /**
     * Upload a new version for an existing policy
     */
    public function uploadVersion($policyId, $data, $userId = 1)
    {
        $existing = $this->findById($policyId);
        if (!$existing) {
            throw new \Exception("Policy record not found.");
        }

        $version = trim($data['version'] ?? '1.1');
        $documentPath = $data['document_path'] ?? $existing['document_path'];
        $fileName = $data['file_name'] ?? ($documentPath ? basename($documentPath) : $existing['file_name']);
        $fileType = $data['file_type'] ?? ($documentPath ? pathinfo($documentPath, PATHINFO_EXTENSION) : $existing['file_type']);
        $fileSize = (int)($data['file_size'] ?? $existing['file_size']);
        $changeSummary = trim($data['change_summary'] ?? 'Updated policy document version.');
        $status = strtolower(trim($data['status'] ?? $existing['status']));
        $approvalStatus = strtolower(trim($data['approval_status'] ?? 'pending_review'));

        $reviewDate = !empty($data['review_date']) ? $data['review_date'] : $existing['review_date'];
        $expiryDate = !empty($data['expiry_date']) ? $data['expiry_date'] : $existing['expiry_date'];
        $category = trim($data['category'] ?? $existing['category']);
        $department = trim($data['department'] ?? $existing['department']);
        $policyOwner = trim($data['policy_owner'] ?? $existing['policy_owner']);
        $description = trim($data['description'] ?? $existing['description']);

        // Update privacy_policies table
        $stmt = $this->pdo->prepare("
            UPDATE privacy_policies 
            SET version = ?,
                category = ?,
                description = ?,
                policy_owner = ?,
                department = ?,
                document_path = ?,
                file_name = ?,
                file_type = ?,
                file_size = ?,
                review_date = ?,
                expiry_date = ?,
                status = ?,
                approval_status = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");

        $stmt->execute([
            $version,
            $category,
            $description,
            $policyOwner,
            $department,
            $documentPath,
            $fileName,
            $fileType,
            $fileSize,
            $reviewDate,
            $expiryDate,
            $status,
            $approvalStatus,
            $userId,
            $policyId
        ]);

        // Insert new record into policy_versions
        $stmtVer = $this->pdo->prepare("
            INSERT INTO policy_versions 
                (policy_id, version_number, document_path, file_name, file_type, file_size, change_summary, uploaded_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtVer->execute([
            $policyId,
            $version,
            $documentPath,
            $fileName,
            $fileType,
            $fileSize,
            $changeSummary,
            $userId,
            $status
        ]);
        $versionId = $this->pdo->lastInsertId();

        $this->logApprovalAction($policyId, $versionId, 'Uploaded New Version', $userId, 'Author', $existing['status'], $status, "Uploaded version {$version}: {$changeSummary}");

        return $versionId;
    }

    /**
     * Get paginated and filtered policy list
     */
    public function getList($search = null, $statusFilter = null, $categoryFilter = null, $departmentFilter = null, $approvalFilter = null, $ownerFilter = null, $expiredFilter = null, $reviewDueFilter = null, $limit = 10, $offset = 0, $sortField = 'updated_at', $sortDir = 'DESC')
    {
        $whereClauses = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(policy_code LIKE ? OR policy_name LIKE ? OR description LIKE ? OR policy_owner LIKE ? OR department LIKE ? OR category LIKE ?)";
            $term = "%" . trim($search) . "%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $term;
            }
        }

        if (!empty($statusFilter)) {
            $whereClauses[] = "status = ?";
            $params[] = strtolower(trim($statusFilter));
        }

        if (!empty($categoryFilter)) {
            $whereClauses[] = "category = ?";
            $params[] = trim($categoryFilter);
        }

        if (!empty($departmentFilter)) {
            $whereClauses[] = "department = ?";
            $params[] = trim($departmentFilter);
        }

        if (!empty($approvalFilter)) {
            $whereClauses[] = "approval_status = ?";
            $params[] = strtolower(trim($approvalFilter));
        }

        if (!empty($ownerFilter)) {
            $whereClauses[] = "policy_owner = ?";
            $params[] = trim($ownerFilter);
        }

        if ($expiredFilter === '1' || $expiredFilter === true || $expiredFilter === 'true') {
            $whereClauses[] = "expiry_date IS NOT NULL AND expiry_date < CURDATE() AND status != 'archived'";
        }

        if ($reviewDueFilter === '1' || $reviewDueFilter === true || $reviewDueFilter === 'true') {
            $whereClauses[] = "review_date IS NOT NULL AND review_date < CURDATE() AND status != 'archived'";
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching records
        $countSql = "SELECT COUNT(*) FROM privacy_policies $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Allowed sort fields whitelist
        $allowedSorts = [
            'id' => 'id',
            'policy_code' => 'policy_code',
            'policy_name' => 'policy_name',
            'category' => 'category',
            'department' => 'department',
            'version' => 'version',
            'status' => 'status',
            'approval_status' => 'approval_status',
            'effective_date' => 'effective_date',
            'review_date' => 'review_date',
            'expiry_date' => 'expiry_date',
            'updated_at' => 'updated_at'
        ];
        $orderBy = $allowedSorts[$sortField] ?? 'updated_at';
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        // Fetch paginated items
        $sql = "
            SELECT 
                id,
                COALESCE(policy_code, CONCAT('POL-', LPAD(id, 4, '0'))) AS policy_code,
                policy_name,
                category,
                description,
                policy_owner,
                department,
                version,
                effective_date,
                review_date,
                expiry_date,
                status,
                COALESCE(approval_status, status) AS approval_status,
                document_path,
                file_name,
                file_type,
                file_size,
                created_at,
                updated_at,
                (expiry_date IS NOT NULL AND expiry_date < CURDATE() AND status != 'archived') AS is_expired,
                (review_date IS NOT NULL AND review_date < CURDATE() AND status != 'archived') AS is_review_due
            FROM privacy_policies
            $whereSql
            ORDER BY $orderBy $direction
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Fetch single policy by ID
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*,
                   COALESCE(p.policy_code, CONCAT('POL-', LPAD(p.id, 4, '0'))) AS policy_code,
                   COALESCE(p.approval_status, p.status) AS approval_status,
                   u.email AS creator_email, u.first_name AS creator_first, u.last_name AS creator_last
            FROM privacy_policies p
            LEFT JOIN users u ON p.uploaded_by = u.id
            WHERE p.id = ? AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get complete version history timeline for a policy
     */
    public function getVersions($policyId)
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, u.email AS uploader_email, u.first_name AS uploader_first, u.last_name AS uploader_last
            FROM policy_versions v
            LEFT JOIN users u ON v.uploaded_by = u.id
            WHERE v.policy_id = ?
            ORDER BY v.id DESC
        ");
        $stmt->execute([(int)$policyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Record approval action and update status
     */
    public function recordApprovalAction($policyId, $action, $newStatus, $newApprovalStatus, $comments = '', $actorId = 1, $actorRole = 'Compliance Officer')
    {
        $existing = $this->findById($policyId);
        if (!$existing) {
            throw new \Exception("Policy record not found.");
        }

        $oldStatus = $existing['status'];
        $oldApproval = $existing['approval_status'];

        $stmt = $this->pdo->prepare("
            UPDATE privacy_policies 
            SET status = ?,
                approval_status = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([strtolower($newStatus), strtolower($newApprovalStatus), $actorId, $policyId]);

        $this->logApprovalAction($policyId, null, $action, $actorId, $actorRole, $oldApproval, $newApprovalStatus, $comments);

        return true;
    }

    /**
     * Get approval history logs
     */
    public function getApprovalHistory($policyId)
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.email, u.first_name, u.last_name
            FROM policy_approvals a
            LEFT JOIN users u ON a.actor_id = u.id
            WHERE a.policy_id = ?
            ORDER BY a.id DESC
        ");
        $stmt->execute([(int)$policyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Log workflow action to policy_approvals
     */
    public function logApprovalAction($policyId, $versionId, $action, $actorId = 1, $actorRole = 'Compliance Admin', $oldStatus = null, $newStatus = null, $comments = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO policy_approvals (policy_id, version_id, action, actor_id, actor_role, old_status, new_status, comments, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$policyId, $versionId, $action, $actorId, $actorRole, $oldStatus, $newStatus, $comments]);
        } catch (\Throwable $e) {}
    }

    /**
     * Soft delete policy
     */
    public function delete($id, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE privacy_policies SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([(int)$id]);
        if ($success) {
            $this->logApprovalAction($id, null, 'Deleted Policy', $userId, 'Compliance Admin', null, 'archived', 'Policy document soft-deleted.');
        }
        return $success;
    }

    /**
     * Live database dashboard metrics (Row 109)
     */
    public function getDashboardMetrics()
    {
        $kpiSql = "
            SELECT 
                COUNT(*) AS total_policies,
                SUM(IF(status = 'active', 1, 0)) AS active_policies,
                SUM(IF(status = 'draft', 1, 0)) AS draft_policies,
                SUM(IF(status = 'archived', 1, 0)) AS archived_policies,
                SUM(IF(approval_status IN ('pending_review', 'under_review'), 1, 0)) AS pending_review_policies,
                SUM(IF(approval_status = 'pending_approval', 1, 0)) AS pending_approval_policies,
                SUM(IF(approval_status = 'approved' OR status = 'active', 1, 0)) AS approved_policies,
                SUM(IF(expiry_date IS NOT NULL AND expiry_date < CURDATE() AND status != 'archived', 1, 0)) AS expired_policies,
                SUM(IF(review_date IS NOT NULL AND review_date < CURDATE() AND status != 'archived', 1, 0)) AS review_due_policies
            FROM privacy_policies
            WHERE deleted_at IS NULL
        ";

        $data = $this->pdo->query($kpiSql)->fetch(\PDO::FETCH_ASSOC);

        // Category distribution
        $catSql = "
            SELECT category, COUNT(*) AS count 
            FROM privacy_policies 
            WHERE deleted_at IS NULL AND category IS NOT NULL AND category != ''
            GROUP BY category
            ORDER BY count DESC
        ";
        $catDist = $this->pdo->query($catSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Department distribution
        $deptSql = "
            SELECT department, COUNT(*) AS count 
            FROM privacy_policies 
            WHERE deleted_at IS NULL AND department IS NOT NULL AND department != ''
            GROUP BY department
            ORDER BY count DESC
        ";
        $deptDist = $this->pdo->query($deptSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Approval status distribution
        $apprSql = "
            SELECT COALESCE(approval_status, status) AS approval_status, COUNT(*) AS count 
            FROM privacy_policies 
            WHERE deleted_at IS NULL
            GROUP BY COALESCE(approval_status, status)
            ORDER BY count DESC
        ";
        $apprDist = $this->pdo->query($apprSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Recent policy updates
        $recentSql = "
            SELECT id, COALESCE(policy_code, CONCAT('POL-', LPAD(id, 4, '0'))) AS policy_code, policy_name, category, version, status, updated_at
            FROM privacy_policies
            WHERE deleted_at IS NULL
            ORDER BY updated_at DESC
            LIMIT 5
        ";
        $recent = $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total_policies' => (int)($data['total_policies'] ?? 0),
            'active_policies' => (int)($data['active_policies'] ?? 0),
            'draft_policies' => (int)($data['draft_policies'] ?? 0),
            'archived_policies' => (int)($data['archived_policies'] ?? 0),
            'pending_review_policies' => (int)($data['pending_review_policies'] ?? 0),
            'pending_approval_policies' => (int)($data['pending_approval_policies'] ?? 0),
            'approved_policies' => (int)($data['approved_policies'] ?? 0),
            'expired_policies' => (int)($data['expired_policies'] ?? 0),
            'review_due_policies' => (int)($data['review_due_policies'] ?? 0),
            'category_distribution' => $catDist ?: [],
            'department_distribution' => $deptDist ?: [],
            'approval_distribution' => $apprDist ?: [],
            'recent_policies' => $recent ?: []
        ];
    }
}
