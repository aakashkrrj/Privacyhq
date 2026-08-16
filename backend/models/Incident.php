<?php
// governance/backend/models/Incident.php

namespace Backend\Models;

class Incident
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new incident record
     */
    public function create($summary, $description, $incidentType = 'Data Privacy', $severity = 'Medium', $priority = 'Medium', $impactedRecords = 0, $affectedSystem = 'Core System', $dueDate = null, $assignedTo = null, $assignedTeam = 'Response Team', $reportedBy = 1)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO incidents 
                (summary, description, incident_type, severity, priority, impacted_records, affected_system, status, due_date, assigned_to, assigned_team, reported_by, created_at, updated_at) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, 'Open', ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $summary,
            $description,
            $incidentType,
            $severity,
            $priority,
            (int)$impactedRecords,
            $affectedSystem,
            $dueDate ?: null,
            $assignedTo ?: null,
            $assignedTeam,
            $reportedBy
        ]);
        $id = $this->pdo->lastInsertId();

        $this->logTimeline($id, 'Incident Reported', $reportedBy, null, 'Open', 'Incident initialized and logged.');

        return $id;
    }

    /**
     * Update an existing incident record
     */
    public function update($id, $summary, $description, $incidentType, $severity, $priority, $impactedRecords, $affectedSystem, $dueDate, $status, $userId = 1)
    {
        $old = $this->findById($id);
        if (!$old) {
            throw new \Exception("Incident not found.");
        }

        $resolvedAt = null;
        if (in_array($status, ['Resolved', 'Closed']) && !in_array($old['status'], ['Resolved', 'Closed'])) {
            $resolvedAt = date('Y-m-d H:i:s');
        } elseif ($old['resolved_at']) {
            $resolvedAt = $old['resolved_at'];
        }

        $stmt = $this->pdo->prepare("
            UPDATE incidents 
            SET summary = ?, 
                description = ?, 
                incident_type = ?,
                severity = ?, 
                priority = ?,
                impacted_records = ?, 
                affected_system = ?,
                due_date = ?,
                status = ?, 
                resolved_at = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $success = $stmt->execute([
            $summary,
            $description,
            $incidentType,
            $severity,
            $priority,
            (int)$impactedRecords,
            $affectedSystem,
            $dueDate ?: null,
            $status,
            $resolvedAt,
            $id
        ]);

        if ($success) {
            if ($old['status'] !== $status) {
                $this->logTimeline($id, 'Status Changed', $userId, $old['status'], $status, "Status updated from {$old['status']} to {$status}.");
            }
            if ($old['severity'] !== $severity) {
                $this->logTimeline($id, 'Severity Changed', $userId, $old['severity'], $severity, "Severity adjusted to {$severity}.");
            }
        }

        return $success;
    }

    /**
     * Soft delete incident
     */
    public function delete($id, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE incidents SET deleted_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            $this->logTimeline($id, 'Incident Deleted', $userId, null, 'Deleted', 'Incident moved to trash/soft-deleted.');
        }
        return $success;
    }

    /**
     * Assign incident to user / team
     */
    public function assign($id, $assignedTo = null, $assignedTeam = 'Response Team', $dueDate = null, $userId = 1)
    {
        $old = $this->findById($id);
        if (!$old) {
            throw new \Exception("Incident not found.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE incidents 
            SET assigned_to = ?, 
                assigned_team = ?, 
                due_date = ?,
                status = IF(status = 'Open', 'Investigating', status),
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $success = $stmt->execute([$assignedTo ?: null, $assignedTeam, $dueDate ?: null, $id]);

        if ($success) {
            $details = "Assigned to Team: {$assignedTeam}";
            if ($assignedTo) {
                $details .= " (User ID: {$assignedTo})";
            }
            if ($dueDate) {
                $details .= ", Due Date: {$dueDate}";
            }
            $this->logTimeline($id, 'Incident Assigned', $userId, $old['status'], ($old['status'] === 'Open' ? 'Investigating' : $old['status']), $details);
        }

        return $success;
    }

    /**
     * Find single incident by ID with user details
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, 
                   u1.email AS reporter_email, u1.first_name AS reporter_first, u1.last_name AS reporter_last,
                   u2.email AS assignee_email, u2.first_name AS assignee_first, u2.last_name AS assignee_last
            FROM incidents i
            LEFT JOIN users u1 ON i.reported_by = u1.id
            LEFT JOIN users u2 ON i.assigned_to = u2.id
            WHERE i.id = ? AND i.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated incident list
     */
    public function getList($search = null, $statusFilter = null, $severityFilter = null, $typeFilter = null, $assignedTo = null, $limit = 10, $offset = 0)
    {
        $whereClauses = ["i.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(summary LIKE ? OR description LIKE ? OR affected_system LIKE ?)";
            $term = "%" . trim($search) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($statusFilter)) {
            $whereClauses[] = "status = ?";
            $params[] = $statusFilter;
        }
        if (!empty($severityFilter)) {
            $whereClauses[] = "severity = ?";
            $params[] = $severityFilter;
        }
        if (!empty($typeFilter)) {
            $whereClauses[] = "incident_type = ?";
            $params[] = $typeFilter;
        }
        if (!empty($assignedTo)) {
            $whereClauses[] = "assigned_to = ?";
            $params[] = (int)$assignedTo;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching
        $countSql = "SELECT COUNT(*) FROM incidents i $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch paginated list
        $sql = "
            SELECT i.*, 
                   u1.email AS reporter_email,
                   u2.email AS assignee_email
            FROM incidents i
            LEFT JOIN users u1 ON i.reported_by = u1.id
            LEFT JOIN users u2 ON i.assigned_to = u2.id
            $whereSql
            ORDER BY i.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Dashboard live database metrics
     */
    public function getDashboardMetrics()
    {
        $kpiQuery = "
            SELECT 
                COUNT(*) as total,
                SUM(IF(status IN ('Open', 'Investigating', 'Contained'), 1, 0)) as active_incidents,
                SUM(IF(severity = 'Critical', 1, 0)) as crit_severity,
                SUM(IF(severity = 'High', 1, 0)) as high_severity,
                SUM(IF(severity = 'Medium', 1, 0)) as med_severity,
                SUM(IF(severity = 'Low', 1, 0)) as low_severity,
                SUM(IF(status IN ('Resolved', 'Closed'), 1, 0)) as resolved_incidents,
                SUM(IF(status = 'Open', 1, 0)) as open_count,
                SUM(IF(status = 'Investigating', 1, 0)) as investigating_count,
                SUM(IF(status = 'Contained', 1, 0)) as contained_count,
                SUM(IF(status = 'Closed', 1, 0)) as closed_count,
                SUM(IF(due_date IS NOT NULL AND due_date < CURDATE() AND status NOT IN ('Resolved', 'Closed'), 1, 0)) as overdue_incidents
            FROM incidents
            WHERE deleted_at IS NULL
        ";
        $kpiRes = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
        $total = (int)($kpiRes['total'] ?? 0);
        $resolved = (int)($kpiRes['resolved_incidents'] ?? 0);

        $resolutionRate = '0%';
        if ($total > 0) {
            $resolutionRate = round(($resolved / $total) * 100, 1) . '%';
        }

        $monthQuery = "
            SELECT COUNT(*) 
            FROM incidents 
            WHERE deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
        ";
        $newThisMonth = (int)$this->pdo->query($monthQuery)->fetchColumn();

        // Fetch recent incident activity
        $recentSql = "
            SELECT id, summary, severity, status, created_at 
            FROM incidents 
            WHERE deleted_at IS NULL 
            ORDER BY created_at DESC 
            LIMIT 5
        ";
        $recent = $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'active_incidents' => (int)($kpiRes['active_incidents'] ?? 0),
            'high_severity' => (int)($kpiRes['high_severity'] ?? 0) + (int)($kpiRes['crit_severity'] ?? 0),
            'resolved' => $resolved,
            'resolution_rate' => $resolutionRate,
            'new_this_month' => $newThisMonth,
            'overdue_incidents' => (int)($kpiRes['overdue_incidents'] ?? 0),
            'distribution' => [
                'critical' => (int)($kpiRes['crit_severity'] ?? 0),
                'high' => (int)($kpiRes['high_severity'] ?? 0),
                'medium' => (int)($kpiRes['med_severity'] ?? 0),
                'low' => (int)($kpiRes['low_severity'] ?? 0)
            ],
            'status_distribution' => [
                'open' => (int)($kpiRes['open_count'] ?? 0),
                'investigating' => (int)($kpiRes['investigating_count'] ?? 0),
                'contained' => (int)($kpiRes['contained_count'] ?? 0),
                'resolved' => $resolved,
                'closed' => (int)($kpiRes['closed_count'] ?? 0)
            ],
            'recent_activity' => $recent
        ];
    }

    /**
     * Update containment and remediation details
     */
    public function updateRemediation($id, $containment, $remediation, $rootCause = null, $preventiveActions = null, $userId = 1)
    {
        $old = $this->findById($id);
        if (!$old) {
            throw new \Exception("Incident not found.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE incidents 
            SET containment_actions = ?, 
                remediation_notes = ?,
                root_cause = ?,
                preventive_actions = ?,
                status = IF(status IN ('Open', 'Investigating'), 'Contained', status),
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $success = $stmt->execute([$containment, $remediation, $rootCause, $preventiveActions, $id]);

        if ($success) {
            $this->logTimeline($id, 'Containment & Remediation Logged', $userId, $old['status'], ($old['status'] === 'Open' ? 'Contained' : $old['status']), "Containment actions and remediation notes updated.");
        }

        return $success;
    }

    /**
     * Update escalation & regulatory notification settings
     */
    public function updateEscalation($id, $isEscalated, $dpoNotified, $regulatoryStatus, $userId = 1)
    {
        $old = $this->findById($id);
        if (!$old) {
            throw new \Exception("Incident not found.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE incidents 
            SET is_escalated = ?, 
                dpo_notified = ?, 
                regulatory_status = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $success = $stmt->execute([$isEscalated ? 1 : 0, $dpoNotified ? 1 : 0, $regulatoryStatus, $id]);

        if ($success) {
            $this->logTimeline($id, 'Escalation Updated', $userId, $old['status'], $old['status'], "Escalated: " . ($isEscalated ? 'Yes' : 'No') . ", DPO Notified: " . ($dpoNotified ? 'Yes' : 'No') . ", Regulatory: {$regulatoryStatus}");
        }

        return $success;
    }

    /**
     * Log timeline action entry
     */
    public function logTimeline($incidentId, $action, $userId = 1, $oldStatus = null, $newStatus = null, $details = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO incident_timeline (incident_id, action, performed_by, old_status, new_status, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$incidentId, $action, $userId, $oldStatus, $newStatus, $details]);
        } catch (\Throwable $e) {}
    }

    /**
     * Get incident timeline logs
     */
    public function getTimeline($incidentId)
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, u.email, u.first_name, u.last_name
            FROM incident_timeline t
            LEFT JOIN users u ON t.performed_by = u.id
            WHERE t.incident_id = ?
            ORDER BY t.id DESC
        ");
        $stmt->execute([$incidentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
