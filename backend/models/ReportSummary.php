<?php
// governance/backend/models/ReportSummary.php

namespace Backend\Models;

class ReportSummary
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Executive Dashboard Telemetry & Distribution Analytics (Row 116 & Row 121)
     */
    public function getDashboardMetrics()
    {
        // 1. Executive Report KPIs
        $execSql = "
            SELECT 
                COUNT(*) AS total_reports,
                SUM(IF(status = 'completed', 1, 0)) AS generated_reports,
                SUM(IF(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY), 1, 0)) AS period_reports,
                SUM(IF(status = 'failed', 1, 0)) AS failed_reports,
                SUM(IF(status IN ('pending', 'queued'), 1, 0)) AS pending_reports
            FROM report_executions
            WHERE deleted_at IS NULL
        ";
        $execMetrics = $this->pdo->query($execSql)->fetch(\PDO::FETCH_ASSOC);

        // 2. Active Schedules Count
        $schedSql = "SELECT COUNT(*) FROM report_schedules WHERE status = 'active' AND deleted_at IS NULL";
        $scheduledCount = (int)$this->pdo->query($schedSql)->fetchColumn();

        // 3. Domain Overview Metrics (Audits, DSR, Risk)
        $auditsQuery = "SELECT COUNT(*) FROM privacy_assessments WHERE status_id != 3 AND deleted_at IS NULL";
        $activeAudits = (int)$this->pdo->query($auditsQuery)->fetchColumn();

        $dsarQuery = "SELECT COUNT(*) as total, SUM(IF(status = 'completed', 1, 0)) as completed FROM data_requests";
        $dsarRes = $this->pdo->query($dsarQuery)->fetch(\PDO::FETCH_ASSOC);
        $totalDsar = (int)($dsarRes['total'] ?? 0);
        $completedDsar = (int)($dsarRes['completed'] ?? 0);
        $dsarCompletion = $totalDsar > 0 ? (int)round(($completedDsar / $totalDsar) * 100) : 0;

        $riskQuery = "SELECT COUNT(*) as total, SUM(IF(status = 'mitigated', 1, 0)) as mitigated FROM assessment_risks";
        $riskRes = $this->pdo->query($riskQuery)->fetch(\PDO::FETCH_ASSOC);
        $totalRisks = (int)($riskRes['total'] ?? 0);
        $mitigatedRisks = (int)($riskRes['mitigated'] ?? 0);
        $riskMitigation = $totalRisks > 0 ? (int)round(($mitigatedRisks / $totalRisks) * 100) : 0;

        // 4. Distribution Visualizations (Row 121)
        // Category / Module Distribution
        $catSql = "
            SELECT report_type, COUNT(*) AS count 
            FROM report_executions 
            WHERE deleted_at IS NULL 
            GROUP BY report_type 
            ORDER BY count DESC
        ";
        $catDist = $this->pdo->query($catSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Execution Type Distribution (Manual vs Scheduled)
        $typeSql = "
            SELECT execution_type, COUNT(*) AS count 
            FROM report_executions 
            WHERE deleted_at IS NULL 
            GROUP BY execution_type 
            ORDER BY count DESC
        ";
        $typeDist = $this->pdo->query($typeSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Monthly Generation Trend
        $trendSql = "
            SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count 
            FROM report_executions 
            WHERE deleted_at IS NULL 
            GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
            ORDER BY created_at ASC 
            LIMIT 6
        ";
        $trendDist = $this->pdo->query($trendSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Recent Executions
        $recentSql = "
            SELECT id, report_code, report_type, title, execution_type, file_format, file_size, status, created_at
            FROM report_executions
            WHERE deleted_at IS NULL
            ORDER BY id DESC
            LIMIT 5
        ";
        $recent = $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total_reports' => (int)($execMetrics['total_reports'] ?? 0),
            'generated_reports' => (int)($execMetrics['generated_reports'] ?? 0),
            'period_reports' => (int)($execMetrics['period_reports'] ?? 0),
            'scheduled_reports' => $scheduledCount,
            'failed_reports' => (int)($execMetrics['failed_reports'] ?? 0),
            'pending_reports' => (int)($execMetrics['pending_reports'] ?? 0),
            'active_audits' => $activeAudits,
            'dsar_completion' => $dsarCompletion,
            'risk_mitigation' => $riskMitigation,
            'total_dsar' => $totalDsar,
            'total_risks' => $totalRisks,
            'category_distribution' => $catDist ?: [],
            'execution_type_distribution' => $typeDist ?: [],
            'monthly_trend' => $trendDist ?: [],
            'recent_executions' => $recent ?: []
        ];
    }

    public function getSummary()
    {
        return $this->getDashboardMetrics();
    }

    /**
     * Paginated Report Executions List (Row 120 Filters)
     */
    public function getExecutionsList($search = null, $reportType = null, $status = null, $executionType = null, $dateFrom = null, $dateTo = null, $limit = 10, $offset = 0, $sortField = 'created_at', $sortDir = 'DESC')
    {
        $whereClauses = ["e.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(e.report_code LIKE ? OR e.title LIKE ? OR e.report_type LIKE ? OR e.file_name LIKE ?)";
            $term = "%" . trim($search) . "%";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $term;
            }
        }

        if (!empty($reportType)) {
            $whereClauses[] = "e.report_type = ?";
            $params[] = trim($reportType);
        }

        if (!empty($status)) {
            $whereClauses[] = "e.status = ?";
            $params[] = strtolower(trim($status));
        }

        if (!empty($executionType)) {
            $whereClauses[] = "e.execution_type = ?";
            $params[] = strtolower(trim($executionType));
        }

        if (!empty($dateFrom)) {
            $whereClauses[] = "e.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $whereClauses[] = "e.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching records
        $countSql = "SELECT COUNT(*) FROM report_executions e $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Allowed sorts
        $allowedSorts = [
            'id' => 'id',
            'report_code' => 'report_code',
            'report_type' => 'report_type',
            'title' => 'title',
            'execution_type' => 'execution_type',
            'status' => 'status',
            'file_format' => 'file_format',
            'created_at' => 'created_at'
        ];
        $orderBy = $allowedSorts[$sortField] ?? 'created_at';
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT e.*, u.email AS generator_email, u.first_name AS generator_first, u.last_name AS generator_last
            FROM report_executions e
            LEFT JOIN users u ON e.generated_by = u.id
            $whereSql
            ORDER BY e.$orderBy $direction
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Create execution log
     */
    public function createExecution($data, $userId = 1)
    {
        $reportType = trim($data['report_type'] ?? 'General Compliance');
        $title = trim($data['title'] ?? ($reportType . ' Governance Report'));
        $executionType = strtolower(trim($data['execution_type'] ?? 'manual'));
        $scheduleId = !empty($data['schedule_id']) ? (int)$data['schedule_id'] : null;
        $filtersJson = is_array($data['filters'] ?? null) ? json_encode($data['filters']) : ($data['filters'] ?? json_encode([]));
        $status = strtolower(trim($data['status'] ?? 'completed'));
        $filePath = $data['file_path'] ?? null;
        $fileName = $data['file_name'] ?? ($filePath ? basename($filePath) : null);
        $fileSize = (int)($data['file_size'] ?? 0);
        $fileFormat = strtolower(trim($data['file_format'] ?? 'pdf'));
        $errorMessage = $data['error_message'] ?? null;
        $executionTimeMs = (int)($data['execution_time_ms'] ?? 150);

        // Auto-generate code
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM report_executions");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $reportCode = 'RPT-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO report_executions 
                (report_code, report_type, title, execution_type, schedule_id, filters, status, file_path, file_name, file_size, file_format, generated_by, error_message, execution_time_ms, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $reportCode,
            $reportType,
            $title,
            $executionType,
            $scheduleId,
            $filtersJson,
            $status,
            $filePath,
            $fileName,
            $fileSize,
            $fileFormat,
            $userId,
            $errorMessage,
            $executionTimeMs
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Get single execution record by ID
     */
    public function getExecutionById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT e.*, u.email AS generator_email, u.first_name AS generator_first, u.last_name AS generator_last
            FROM report_executions e
            LEFT JOIN users u ON e.generated_by = u.id
            WHERE e.id = ? AND e.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Soft delete execution
     */
    public function deleteExecution($id, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE report_executions SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    /**
     * Report Schedules Management (Row 122)
     */
    public function getSchedulesList($search = null, $status = null, $reportType = null, $limit = 10, $offset = 0)
    {
        $whereClauses = ["s.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(s.schedule_code LIKE ? OR s.title LIKE ? OR s.report_type LIKE ? OR s.recipients LIKE ?)";
            $term = "%" . trim($search) . "%";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $term;
            }
        }

        if (!empty($status)) {
            $whereClauses[] = "s.status = ?";
            $params[] = strtolower(trim($status));
        }

        if (!empty($reportType)) {
            $whereClauses[] = "s.report_type = ?";
            $params[] = trim($reportType);
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        $countSql = "SELECT COUNT(*) FROM report_schedules s $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $sql = "
            SELECT s.*, u.email AS creator_email, u.first_name AS creator_first, u.last_name AS creator_last
            FROM report_schedules s
            LEFT JOIN users u ON s.created_by = u.id
            $whereSql
            ORDER BY s.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Create report schedule
     */
    public function createSchedule($data, $userId = 1)
    {
        $reportType = trim($data['report_type'] ?? 'DSR Performance');
        $title = trim($data['title'] ?? ($reportType . ' Recurring Digest'));
        $frequency = strtolower(trim($data['frequency'] ?? 'weekly'));
        $filtersJson = is_array($data['filters'] ?? null) ? json_encode($data['filters']) : ($data['filters'] ?? json_encode([]));
        $exportFormat = strtolower(trim($data['export_format'] ?? 'pdf'));
        $recipients = trim($data['recipients'] ?? 'dpo@privacyhq.com');
        $status = strtolower(trim($data['status'] ?? 'active'));

        // Calculate next run date
        $nextRun = date('Y-m-d H:i:s', strtotime('+7 days'));
        if ($frequency === 'daily') {
            $nextRun = date('Y-m-d H:i:s', strtotime('+1 day'));
        } elseif ($frequency === 'monthly') {
            $nextRun = date('Y-m-d H:i:s', strtotime('+30 days'));
        }

        // Auto-generate code
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM report_schedules");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $scheduleCode = 'SCH-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO report_schedules 
                (schedule_code, report_type, title, frequency, filters, export_format, recipients, next_run_at, status, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $scheduleCode,
            $reportType,
            $title,
            $frequency,
            $filtersJson,
            $exportFormat,
            $recipients,
            $nextRun,
            $status,
            $userId
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update existing schedule
     */
    public function updateSchedule($id, $data, $userId = 1)
    {
        $existing = $this->getScheduleById($id);
        if (!$existing) {
            throw new \Exception("Report schedule not found.");
        }

        $reportType = trim($data['report_type'] ?? $existing['report_type']);
        $title = trim($data['title'] ?? $existing['title']);
        $frequency = strtolower(trim($data['frequency'] ?? $existing['frequency']));
        $filtersJson = is_array($data['filters'] ?? null) ? json_encode($data['filters']) : ($data['filters'] ?? $existing['filters']);
        $exportFormat = strtolower(trim($data['export_format'] ?? $existing['export_format']));
        $recipients = trim($data['recipients'] ?? $existing['recipients']);
        $status = strtolower(trim($data['status'] ?? $existing['status']));

        // Recalculate next run
        $nextRun = $existing['next_run_at'];
        if ($frequency !== $existing['frequency']) {
            if ($frequency === 'daily') {
                $nextRun = date('Y-m-d H:i:s', strtotime('+1 day'));
            } elseif ($frequency === 'weekly') {
                $nextRun = date('Y-m-d H:i:s', strtotime('+7 days'));
            } elseif ($frequency === 'monthly') {
                $nextRun = date('Y-m-d H:i:s', strtotime('+30 days'));
            }
        }

        $stmt = $this->pdo->prepare("
            UPDATE report_schedules 
            SET report_type = ?,
                title = ?,
                frequency = ?,
                filters = ?,
                export_format = ?,
                recipients = ?,
                next_run_at = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            $reportType,
            $title,
            $frequency,
            $filtersJson,
            $exportFormat,
            $recipients,
            $nextRun,
            $status,
            $id
        ]);
    }

    /**
     * Fetch schedule by ID
     */
    public function getScheduleById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM report_schedules WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Toggle schedule status (active / paused / disabled)
     */
    public function toggleScheduleStatus($id, $status, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE report_schedules SET status = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([strtolower($status), (int)$id]);
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule($id, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE report_schedules SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    /**
     * Fetch due schedules for cron runner
     */
    public function getDueSchedules()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM report_schedules 
            WHERE status = 'active' AND (next_run_at <= NOW() OR next_run_at IS NULL) AND deleted_at IS NULL
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update schedule after execution
     */
    public function updateScheduleAfterRun($id, $frequency)
    {
        $interval = '+7 days';
        if ($frequency === 'daily') $interval = '+1 day';
        if ($frequency === 'monthly') $interval = '+30 days';

        $nextRun = date('Y-m-d H:i:s', strtotime($interval));
        $stmt = $this->pdo->prepare("UPDATE report_schedules SET last_run_at = NOW(), next_run_at = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$nextRun, (int)$id]);
    }

    // Module-specific Report Generators for live database data
    public function getVendorRiskReport()
    {
        $kpiQuery = "
            SELECT 
                COUNT(v.id) as total,
                SUM(IF(va.risk_score >= 90, 1, 0)) as critical_risk,
                SUM(IF(va.risk_score >= 80 AND va.risk_score < 90, 1, 0)) as high_risk,
                SUM(IF(va.risk_score >= 50 AND va.risk_score < 80, 1, 0)) as medium_risk,
                SUM(IF(va.risk_score < 50 OR va.risk_score IS NULL, 1, 0)) as low_risk,
                SUM(IF(va.status = 'Compliant', 1, 0)) as compliant_count
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.deleted_at IS NULL
        ";
        $kpis = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);

        $listQuery = "
            SELECT v.id, v.name as vendor_name, v.service_type as category, va.status as dpa_status,
                   IF(va.risk_score >= 80, 'Critical', IF(va.risk_score >= 50, 'Medium', 'Low')) as risk_level
            FROM vendors v
            LEFT JOIN vendor_assessments va ON v.id = va.vendor_id
            WHERE v.deleted_at IS NULL
            ORDER BY v.id DESC
        ";
        $vendors = $this->pdo->query($listQuery)->fetchAll(\PDO::FETCH_ASSOC);

        return ['kpis' => $kpis, 'vendors' => $vendors];
    }

    public function getRiskRegisterReport()
    {
        $highQuery = "
            SELECT COUNT(*) 
            FROM assessment_risks ar
            JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
            WHERE rm.risk_level_name = 'High' AND ar.status != 'mitigated'
        ";
        $highRisks = $this->pdo->query($highQuery)->fetchColumn();
        $totalRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks")->fetchColumn();
        $mitigatedRisks = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'mitigated'")->fetchColumn();
        $needsAction = $this->pdo->query("SELECT COUNT(*) FROM assessment_risks WHERE status = 'open'")->fetchColumn();

        $listQuery = "
            SELECT 
                ar.id,
                ar.description as title,
                rc.category_name as category,
                rm.likelihood_name as likelihood,
                rm.impact_name as impact,
                rm.risk_level_name as risk_level,
                ar.status as status,
                rmit.implementation_details as mitigation
            FROM assessment_risks ar
            LEFT JOIN risk_categories rc ON ar.risk_category_id = rc.id
            LEFT JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
            LEFT JOIN risk_mitigations rmit ON ar.id = rmit.risk_id
            ORDER BY ar.id DESC
        ";
        $items = $this->pdo->query($listQuery)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total_risks' => $totalRisks ?? 0,
                'high_risks' => $highRisks ?? 0,
                'mitigated_risks' => $mitigatedRisks ?? 0,
                'needs_action' => $needsAction ?? 0
            ],
            'risks' => $items
        ];
    }

    public function getRopaReport()
    {
        $kpiQuery = "
            SELECT 
                COUNT(*) as total_activities,
                SUM(IF(status = 'active', 1, 0)) as active_activities,
                SUM(IF(status = 'inactive', 1, 0)) as inactive_activities
            FROM processing_activities
            WHERE deleted_at IS NULL
        ";
        $kpis = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);

        $listQuery = "
            SELECT *
            FROM processing_activities
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ";
        $items = $this->pdo->query($listQuery)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total_activities' => $kpis['total_activities'] ?? 0,
                'active_activities' => $kpis['active_activities'] ?? 0,
                'inactive_activities' => $kpis['inactive_activities'] ?? 0
            ],
            'activities' => $items
        ];
    }

    public function getPoliciesReport()
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE deleted_at IS NULL")->fetchColumn();
        $active = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn();
        $draft = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'draft' AND deleted_at IS NULL")->fetchColumn();
        $archived = $this->pdo->query("SELECT COUNT(*) FROM privacy_policies WHERE status = 'archived' AND deleted_at IS NULL")->fetchColumn();

        $sql = "SELECT * FROM privacy_policies WHERE deleted_at IS NULL ORDER BY updated_at DESC";
        $items = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total_policies' => $total ?? 0,
                'active_policies' => $active ?? 0,
                'draft_policies' => $draft ?? 0,
                'archived_policies' => $archived ?? 0
            ],
            'policies' => $items
        ];
    }

    public function getDsrReport()
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM data_requests")->fetchColumn();
        $pending = $this->pdo->query("SELECT COUNT(*) FROM data_requests WHERE status IN ('open','verifying','processing')")->fetchColumn();
        $completed = $this->pdo->query("SELECT COUNT(*) FROM data_requests WHERE status = 'completed'")->fetchColumn();

        $sql = "
            SELECT dr.*, ds.identifier_hash as subject_email 
            FROM data_requests dr
            LEFT JOIN data_subjects ds ON dr.data_subject_id = ds.id
            ORDER BY dr.id DESC
        ";
        $items = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total' => $total ?? 0,
                'pending' => $pending ?? 0,
                'completed' => $completed ?? 0
            ],
            'requests' => $items
        ];
    }

    public function getIncidentReport()
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE deleted_at IS NULL")->fetchColumn();
        $open = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'Open' AND deleted_at IS NULL")->fetchColumn();
        $resolved = $this->pdo->query("SELECT COUNT(*) FROM incidents WHERE status = 'Resolved' AND deleted_at IS NULL")->fetchColumn();

        $sql = "SELECT * FROM incidents WHERE deleted_at IS NULL ORDER BY created_at DESC";
        $items = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'kpis' => [
                'total' => $total ?? 0,
                'open' => $open ?? 0,
                'resolved' => $resolved ?? 0
            ],
            'incidents' => $items
        ];
    }
}
