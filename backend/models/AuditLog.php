<?php
// governance/backend/models/AuditLog.php

namespace Backend\Models;

class AuditLog
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Dashboard Telemetry Metrics (Row 124)
     */
    public function getDashboardMetrics()
    {
        $totalEvents = (int)$this->pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        $eventsToday = (int)$this->pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURRENT_DATE()")->fetchColumn();
        $periodEvents = (int)$this->pdo->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();

        $loginEvents = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action LIKE '%Login%' OR action LIKE '%Auth%' OR module LIKE '%Auth%' OR action LIKE '%Logout%'
        ")->fetchColumn();

        $mutationEvents = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action LIKE '%Create%' OR action LIKE '%Update%' OR action LIKE '%Delete%' OR action LIKE '%Generate%' OR action LIKE '%Purge%'
        ")->fetchColumn();

        $securityEvents = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action LIKE '%Security%' OR action LIKE '%Denied%' OR action LIKE '%Failed%' OR action LIKE '%Retention%' OR action LIKE '%Permission%'
        ")->fetchColumn();

        $failedEvents = (int)$this->pdo->query("
            SELECT COUNT(*) FROM audit_logs 
            WHERE action LIKE '%Failed%' OR action LIKE '%Denied%' OR new_value LIKE '%failed%' OR old_value LIKE '%failed%'
        ")->fetchColumn();

        // Module Distribution
        $modStmt = $this->pdo->query("
            SELECT IFNULL(NULLIF(module, ''), 'General System') AS module_name, COUNT(*) AS count
            FROM audit_logs
            GROUP BY module_name
            ORDER BY count DESC
        ");
        $moduleDist = [];
        while ($row = $modStmt->fetch(\PDO::FETCH_ASSOC)) {
            $moduleDist[$row['module_name']] = (int)$row['count'];
        }

        // Top Active Users
        $userStmt = $this->pdo->query("
            SELECT 
                COALESCE(u.email, CONCAT('User #', a.user_id)) AS user_identifier,
                COUNT(*) AS count
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            GROUP BY a.user_id
            ORDER BY count DESC
            LIMIT 5
        ");
        $topUsers = $userStmt->fetchAll(\PDO::FETCH_ASSOC);

        // 14-Day Velocity Trend
        $trendStmt = $this->pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS log_date, COUNT(*) AS count
            FROM audit_logs
            WHERE created_at >= NOW() - INTERVAL 14 DAY
            GROUP BY log_date
            ORDER BY log_date ASC
        ");
        $dailyTrend = [];
        while ($row = $trendStmt->fetch(\PDO::FETCH_ASSOC)) {
            $dailyTrend[$row['log_date']] = (int)$row['count'];
        }

        return [
            'total_events' => $totalEvents,
            'events_today' => $eventsToday,
            'period_events' => $periodEvents,
            'login_events' => $loginEvents,
            'mutation_events' => $mutationEvents,
            'security_events' => $securityEvents,
            'failed_events' => $failedEvents,
            'module_distribution' => $moduleDist,
            'top_active_users' => $topUsers,
            'daily_trend' => $dailyTrend
        ];
    }

    /**
     * Server-Side Paginated Log Inventory (Rows 125, 126, 127)
     */
    public function getLogsList($search = null, $module = null, $action = null, $userId = null, $dateFrom = null, $dateTo = null, $limit = 20, $offset = 0, $sortField = 'created_at', $sortDir = 'DESC')
    {
        $whereClauses = [];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(a.module LIKE ? OR a.action LIKE ? OR u.email LIKE ? OR a.ip_address LIKE ? OR a.user_agent LIKE ? OR CAST(a.record_id AS CHAR) LIKE ?)";
            $term = "%" . trim($search) . "%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $term;
            }
        }

        if (!empty($module)) {
            $whereClauses[] = "a.module = ?";
            $params[] = trim($module);
        }

        if (!empty($action)) {
            $whereClauses[] = "a.action LIKE ?";
            $params[] = "%" . trim($action) . "%";
        }

        if (!empty($userId)) {
            $whereClauses[] = "a.user_id = ?";
            $params[] = (int)$userId;
        }

        if (!empty($dateFrom)) {
            $whereClauses[] = "a.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $whereClauses[] = "a.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereSql = count($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        // Count Total
        $countSql = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $whereSql";
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Allowed Sorts
        $allowedSorts = [
            'id' => 'a.id',
            'created_at' => 'a.created_at',
            'module' => 'a.module',
            'action' => 'a.action',
            'user_id' => 'a.user_id',
            'ip_address' => 'a.ip_address'
        ];
        $orderBy = $allowedSorts[$sortField] ?? 'a.created_at';
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT 
                a.*, 
                u.email AS user_email,
                CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) AS user_full_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            $whereSql
            ORDER BY $orderBy $direction
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Get Single Audit Log Details by ID (Row 128)
     */
    public function getLogById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                a.*, 
                u.email AS user_email,
                CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) AS user_full_name,
                u.role_id
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        $rec = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$rec) {
            return null;
        }

        return $rec;
    }

    /**
     * Get Retention Settings (Row 130)
     */
    public function getRetentionSettings()
    {
        $stmt = $this->pdo->query("SELECT * FROM audit_retention_settings ORDER BY id ASC LIMIT 1");
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$res) {
            return [
                'id' => 1,
                'retention_days' => 90,
                'auto_purge_enabled' => 1,
                'archive_before_purge' => 0,
                'last_purge_at' => null,
                'updated_by' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        return $res;
    }

    /**
     * Save Retention Settings (Row 130)
     */
    public function saveRetentionSettings($days, $autoPurge, $archiveBeforePurge = 0, $userId = 1)
    {
        $days = max(1, min(3650, (int)$days));
        $autoPurge = $autoPurge ? 1 : 0;
        $archiveBeforePurge = $archiveBeforePurge ? 1 : 0;

        $stmt = $this->pdo->prepare("
            UPDATE audit_retention_settings 
            SET retention_days = ?, auto_purge_enabled = ?, archive_before_purge = ?, updated_by = ?, updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute([$days, $autoPurge, $archiveBeforePurge, $userId]);
        return true;
    }

    /**
     * Purge Expired Audit Logs (Row 130)
     */
    public function purgeOldLogs($retentionDays = 90, $userId = 1)
    {
        $retentionDays = max(1, (int)$retentionDays);

        // Count logs eligible for purge
        $stmtCount = $this->pdo->prepare("
            SELECT COUNT(*) FROM audit_logs 
            WHERE created_at < NOW() - INTERVAL ? DAY
        ");
        $stmtCount->execute([$retentionDays]);
        $purgeCount = (int)$stmtCount->fetchColumn();

        if ($purgeCount > 0) {
            // Delete expired logs
            $stmtDel = $this->pdo->prepare("
                DELETE FROM audit_logs 
                WHERE created_at < NOW() - INTERVAL ? DAY
            ");
            $stmtDel->execute([$retentionDays]);
        }

        // Update last_purge_at
        $stmtUpdate = $this->pdo->prepare("
            UPDATE audit_retention_settings 
            SET last_purge_at = NOW(), updated_by = ? 
            WHERE id = 1
        ");
        $stmtUpdate->execute([$userId]);

        return ['purged_count' => $purgeCount, 'retention_days' => $retentionDays];
    }
}
