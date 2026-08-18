<?php
// governance/backend/models/User.php

namespace Backend\Models;

class User
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Dashboard Telemetry Metrics (Row 132)
     */
    public function getDashboardMetrics()
    {
        $totalUsers = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $activeUsers = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();
        $inactiveUsers = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive' AND deleted_at IS NULL")->fetchColumn();
        $suspendedUsers = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended' AND deleted_at IS NULL")->fetchColumn();
        $recentUsers = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY AND deleted_at IS NULL")->fetchColumn();
        $loggedInRecently = (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE last_login_at >= NOW() - INTERVAL 30 DAY AND deleted_at IS NULL")->fetchColumn();

        // Role Distribution
        $roleStmt = $this->pdo->query("
            SELECT r.role_name, COUNT(u.id) AS count
            FROM roles r
            LEFT JOIN users u ON r.id = u.role_id AND u.deleted_at IS NULL
            GROUP BY r.id, r.role_name
            ORDER BY r.id ASC
        ");
        $roleDist = [];
        while ($row = $roleStmt->fetch(\PDO::FETCH_ASSOC)) {
            $roleDist[$row['role_name']] = (int)$row['count'];
        }

        // Status Distribution
        $statusStmt = $this->pdo->query("
            SELECT status, COUNT(*) AS count
            FROM users
            WHERE deleted_at IS NULL
            GROUP BY status
        ");
        $statusDist = [];
        while ($row = $statusStmt->fetch(\PDO::FETCH_ASSOC)) {
            $statusDist[$row['status']] = (int)$row['count'];
        }

        // 14-Day Registration Trend
        $trendStmt = $this->pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS reg_date, COUNT(*) AS count
            FROM users
            WHERE created_at >= NOW() - INTERVAL 14 DAY AND deleted_at IS NULL
            GROUP BY reg_date
            ORDER BY reg_date ASC
        ");
        $dailyTrend = [];
        while ($row = $trendStmt->fetch(\PDO::FETCH_ASSOC)) {
            $dailyTrend[$row['reg_date']] = (int)$row['count'];
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'suspended_users' => $suspendedUsers,
            'recent_users' => $recentUsers,
            'logged_in_recently' => $loggedInRecently,
            'role_distribution' => $roleDist,
            'status_distribution' => $statusDist,
            'daily_trend' => $dailyTrend
        ];
    }

    /**
     * Server-Side Paginated Users Register (Rows 133, 138)
     */
    public function getUsersList($search = null, $roleId = null, $status = null, $dateFrom = null, $dateTo = null, $limit = 20, $offset = 0, $sortField = 'id', $sortDir = 'DESC')
    {
        $whereClauses = ["u.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ? OR CAST(u.id AS CHAR) LIKE ?)";
            $term = "%" . trim($search) . "%";
            for ($i = 0; $i < 5; $i++) {
                $params[] = $term;
            }
        }

        if (!empty($roleId) && is_numeric($roleId)) {
            $whereClauses[] = "u.role_id = ?";
            $params[] = (int)$roleId;
        }

        if (!empty($status)) {
            $whereClauses[] = "u.status = ?";
            $params[] = strtolower(trim($status));
        }

        if (!empty($dateFrom)) {
            $whereClauses[] = "u.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $whereClauses[] = "u.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count Total
        $countSql = "SELECT COUNT(*) FROM users u $whereSql";
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Allowed Sorts
        $allowedSorts = [
            'id' => 'u.id',
            'first_name' => 'u.first_name',
            'last_name' => 'u.last_name',
            'email' => 'u.email',
            'role_id' => 'u.role_id',
            'status' => 'u.status',
            'created_at' => 'u.created_at',
            'last_login_at' => 'u.last_login_at'
        ];
        $orderBy = $allowedSorts[$sortField] ?? 'u.id';
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT 
                u.id, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone, 
                u.role_id, 
                r.role_name, 
                u.status, 
                u.profile_image,
                u.two_factor_enabled,
                u.last_login_at, 
                u.created_at, 
                u.updated_at
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            $whereSql
            ORDER BY $orderBy $direction
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Get User by ID (Row 141)
     */
    public function getUserById($id)
    {
        return $this->findById($id);
    }

    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone, 
                u.role_id, 
                r.role_name, 
                u.status, 
                u.profile_image,
                u.two_factor_enabled,
                u.two_factor_updated_at,
                u.last_login_at, 
                u.created_at, 
                u.updated_at
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUserWithAuth($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, email, role_id, status, password_hash 
            FROM users 
            WHERE LOWER(email) = LOWER(?) AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([trim($email)]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Profile Updates (Row 142)
     */
    public function updateProfile($userId, $firstName, $lastName, $phone)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([trim($firstName), trim($lastName), trim($phone), (int)$userId]);
    }

    public function updateProfileImage($userId, $imagePath)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET profile_image = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$imagePath, (int)$userId]);
    }

    /**
     * Password Change (Row 143)
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->getUserWithAuth($userId);
        if (!$user) {
            throw new \Exception("User account not found.");
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new \Exception("Incorrect current password.");
        }

        if (password_verify($newPassword, $user['password_hash'])) {
            throw new \Exception("New password cannot be identical to your current password.");
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET password_hash = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$newHash, (int)$userId]);
    }

    /**
     * Two-Factor Authentication (Row 144)
     */
    public function enable2fa($userId, $secret)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET two_factor_enabled = 1, two_factor_secret = ?, two_factor_updated_at = NOW(), updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$secret, (int)$userId]);
    }

    public function disable2fa($userId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET two_factor_enabled = 0, two_factor_secret = NULL, two_factor_updated_at = NOW(), updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([(int)$userId]);
    }

    /**
     * Notification Preferences (Row 145)
     */
    public function getNotificationPreferences($userId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ? LIMIT 1");
        $stmt->execute([(int)$userId]);
        $pref = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$pref) {
            return [
                'user_id' => (int)$userId,
                'email_notifications' => 1,
                'in_app_notifications' => 1,
                'privacy_incident_alerts' => 1,
                'consent_updates' => 1,
                'assessment_reminders' => 1,
                'risk_alerts' => 1,
                'system_announcements' => 1
            ];
        }
        return $pref;
    }

    public function updateNotificationPreferences($userId, $email, $inApp, $incident, $consent, $assessment, $risk, $announcements)
    {
        $existing = $this->getNotificationPreferences($userId);
        if (isset($existing['id'])) {
            $stmt = $this->pdo->prepare("
                UPDATE notification_preferences 
                SET email_notifications = ?, in_app_notifications = ?, privacy_incident_alerts = ?, consent_updates = ?, assessment_reminders = ?, risk_alerts = ?, system_announcements = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            return $stmt->execute([$email, $inApp, $incident, $consent, $assessment, $risk, $announcements, (int)$userId]);
        } else {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_preferences 
                    (user_id, email_notifications, in_app_notifications, privacy_incident_alerts, consent_updates, assessment_reminders, risk_alerts, system_announcements, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            return $stmt->execute([(int)$userId, $email, $inApp, $incident, $consent, $assessment, $risk, $announcements]);
        }
    }

    /**
     * API Key Management (Row 146)
     */
    public function createApiKey($userId, $keyName, $prefix, $hash, $scopes = 'read,write')
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO api_keys (user_id, key_name, key_prefix, key_hash, scopes, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([(int)$userId, trim($keyName), trim($prefix), trim($hash), trim($scopes)]);
        return $this->pdo->lastInsertId();
    }

    public function listApiKeys($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, key_name, key_prefix, scopes, last_used_at, expires_at, status, created_at, revoked_at
            FROM api_keys
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function revokeApiKey($userId, $keyId)
    {
        $stmt = $this->pdo->prepare("
            UPDATE api_keys 
            SET status = 'revoked', revoked_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([(int)$keyId, (int)$userId]);
    }

    /**
     * Compliance Documents Vault (Row 148)
     */
    public function createComplianceDocument($title, $category, $fileName, $origName, $filePath, $fileSize, $mimeType, $userId)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO compliance_documents (title, category, file_name, original_name, file_path, file_size, mime_type, uploaded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([trim($title), trim($category), $fileName, $origName, $filePath, (int)$fileSize, $mimeType, (int)$userId]);
        return $this->pdo->lastInsertId();
    }

    public function listComplianceDocuments()
    {
        $stmt = $this->pdo->query("
            SELECT 
                d.*,
                CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) AS uploader_name,
                u.email AS uploader_email
            FROM compliance_documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.deleted_at IS NULL
            ORDER BY d.created_at DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getComplianceDocumentById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.email AS uploader_email
            FROM compliance_documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.id = ? AND d.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function deleteComplianceDocument($id)
    {
        $stmt = $this->pdo->prepare("UPDATE compliance_documents SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    /**
     * Add New User (Row 134)
     */
    public function createUser($data)
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $roleId = max(1, (int)($data['role_id'] ?? 5));
        $status = strtolower(trim($data['status'] ?? 'active'));
        $password = $data['password'] ?? '';

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users 
                (first_name, last_name, email, phone, role_id, status, password_hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $firstName,
            $lastName,
            $email,
            $phone,
            $roleId,
            $status,
            $passwordHash
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update User (Row 135)
     */
    public function updateUser($id, $data)
    {
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $phone = trim($data['phone'] ?? '');
        $roleId = max(1, (int)($data['role_id'] ?? 5));
        $status = strtolower(trim($data['status'] ?? 'active'));

        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, role_id = ?, status = ?, password_hash = ?, updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ");
            return $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $phone,
                $roleId,
                $status,
                $passwordHash,
                (int)$id
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, role_id = ?, status = ?, updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ");
            return $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $phone,
                $roleId,
                $status,
                (int)$id
            ]);
        }
    }

    /**
     * Soft Delete User with Self-Deletion Protection (Row 136)
     */
    public function deleteUser($id, $currentUserId = 1)
    {
        $id = (int)$id;
        if ($id === (int)$currentUserId) {
            throw new \Exception("Security Violation: You cannot delete your own active administrator account.");
        }

        $stmt = $this->pdo->prepare("UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$id]);
    }

    /**
     * Roles & Permissions Matrix Management (Row 137)
     */
    public function getRolesAndPermissionsMatrix()
    {
        $roles = $this->pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $permissions = $this->pdo->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $mapping = [];
        $mapStmt = $this->pdo->query("SELECT role_id, permission_id FROM role_permissions");
        while ($row = $mapStmt->fetch(\PDO::FETCH_ASSOC)) {
            $mapping[$row['role_id']][$row['permission_id']] = true;
        }

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $mapping
        ];
    }

    public function saveRolePermissionsMatrix($roleId, array $permissionIds)
    {
        $roleId = (int)$roleId;
        if ($roleId <= 0) {
            throw new \Exception("Valid role ID is required.");
        }

        $this->pdo->beginTransaction();
        try {
            $stmtDel = $this->pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmtDel->execute([$roleId]);

            if (!empty($permissionIds)) {
                $stmtIns = $this->pdo->prepare("INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, NOW())");
                foreach ($permissionIds as $pId) {
                    $stmtIns->execute([$roleId, (int)$pId]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getAllRoles()
    {
        return $this->pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }
}