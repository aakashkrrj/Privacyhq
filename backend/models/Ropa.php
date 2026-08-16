<?php
// governance/backend/models/Ropa.php

namespace Backend\Models;

class Ropa
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new processing activity (ROPA record)
     */
    public function create($data, $userId = 1)
    {
        $activityName = trim($data['activity_name'] ?? '');
        $purpose = trim($data['purpose'] ?? '');
        $department = trim($data['department'] ?? 'General Privacy');
        $dataController = trim($data['data_controller'] ?? 'PrivacyHQ Inc');
        $businessOwner = trim($data['business_owner'] ?? 'Data Owner');
        $controllerRole = trim($data['controller_role'] ?? 'Controller');
        $legalBasis = trim($data['legal_basis'] ?? 'Legitimate Interest');
        $dataCategories = trim($data['data_categories'] ?? '');
        $dataSubjects = trim($data['data_subjects'] ?? '');
        $processingOperations = trim($data['processing_operations'] ?? 'Collection, Storage');
        $dataSource = trim($data['data_source'] ?? 'Direct Input');
        $recipients = trim($data['recipients'] ?? '');
        $thirdParties = trim($data['third_parties'] ?? '');
        $internationalTransfers = trim($data['international_transfers'] ?? 'No');
        $transferSafeguards = trim($data['transfer_safeguards'] ?? 'N/A');
        $retentionPeriod = trim($data['retention_period'] ?? '1 Year');
        $retentionBasis = trim($data['retention_basis'] ?? 'Legal Obligation');
        $disposalMechanism = trim($data['disposal_mechanism'] ?? 'Secure Erasure');
        $storageLocation = trim($data['storage_location'] ?? 'AWS Cloud');
        $safeguards = trim($data['safeguards'] ?? 'Encryption in transit & at rest');
        $technicalMeasures = trim($data['technical_measures'] ?? 'TLS 1.3, AES-256');
        $organizationalMeasures = trim($data['organizational_measures'] ?? 'RBAC Access Policies');
        $riskLevel = trim($data['risk_level'] ?? 'Medium');
        $status = trim($data['status'] ?? 'active');
        $reviewDate = !empty($data['review_date']) ? $data['review_date'] : date('Y-m-d', strtotime('+1 year'));

        // Generate ROPA code
        $stmtCount = $this->pdo->query("SELECT MAX(id) FROM processing_activities");
        $nextId = ((int)$stmtCount->fetchColumn()) + 1;
        $ropaCode = 'ROPA-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare("
            INSERT INTO processing_activities 
                (ropa_code, activity_name, purpose, department, data_controller, business_owner, controller_role, legal_basis,
                 data_categories, data_subjects, processing_operations, data_source, recipients, third_parties,
                 international_transfers, transfer_safeguards, retention_period, retention_basis, disposal_mechanism,
                 storage_location, safeguards, technical_measures, organizational_measures, risk_level, status, review_date,
                 created_by, created_at, updated_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $ropaCode,
            $activityName,
            $purpose,
            $department,
            $dataController,
            $businessOwner,
            $controllerRole,
            $legalBasis,
            $dataCategories,
            $dataSubjects,
            $processingOperations,
            $dataSource,
            $recipients,
            $thirdParties,
            $internationalTransfers,
            $transferSafeguards,
            $retentionPeriod,
            $retentionBasis,
            $disposalMechanism,
            $storageLocation,
            $safeguards,
            $technicalMeasures,
            $organizationalMeasures,
            $riskLevel,
            $status,
            $reviewDate,
            $userId
        ]);

        $ropaId = $this->pdo->lastInsertId();
        $this->logHistory($ropaId, 'ROPA Created', $userId, null, $status, "Processing activity record created with code {$ropaCode}.");

        return $ropaId;
    }

    /**
     * Update an existing processing activity
     */
    public function update($id, $data, $userId = 1)
    {
        $old = $this->findById($id);
        if (!$old) {
            throw new \Exception("ROPA record not found.");
        }

        $activityName = trim($data['activity_name'] ?? $old['activity_name']);
        $purpose = trim($data['purpose'] ?? $old['purpose']);
        $department = trim($data['department'] ?? $old['department']);
        $dataController = trim($data['data_controller'] ?? $old['data_controller']);
        $businessOwner = trim($data['business_owner'] ?? $old['business_owner']);
        $controllerRole = trim($data['controller_role'] ?? $old['controller_role']);
        $legalBasis = trim($data['legal_basis'] ?? $old['legal_basis']);
        $dataCategories = trim($data['data_categories'] ?? $old['data_categories']);
        $dataSubjects = trim($data['data_subjects'] ?? $old['data_subjects']);
        $processingOperations = trim($data['processing_operations'] ?? $old['processing_operations']);
        $dataSource = trim($data['data_source'] ?? $old['data_source']);
        $recipients = trim($data['recipients'] ?? $old['recipients']);
        $thirdParties = trim($data['third_parties'] ?? $old['third_parties']);
        $internationalTransfers = trim($data['international_transfers'] ?? $old['international_transfers']);
        $transferSafeguards = trim($data['transfer_safeguards'] ?? $old['transfer_safeguards']);
        $retentionPeriod = trim($data['retention_period'] ?? $old['retention_period']);
        $retentionBasis = trim($data['retention_basis'] ?? $old['retention_basis']);
        $disposalMechanism = trim($data['disposal_mechanism'] ?? $old['disposal_mechanism']);
        $storageLocation = trim($data['storage_location'] ?? $old['storage_location']);
        $safeguards = trim($data['safeguards'] ?? $old['safeguards']);
        $technicalMeasures = trim($data['technical_measures'] ?? $old['technical_measures']);
        $organizationalMeasures = trim($data['organizational_measures'] ?? $old['organizational_measures']);
        $riskLevel = trim($data['risk_level'] ?? $old['risk_level']);
        $status = trim($data['status'] ?? $old['status']);
        $reviewDate = !empty($data['review_date']) ? $data['review_date'] : $old['review_date'];

        $stmt = $this->pdo->prepare("
            UPDATE processing_activities 
            SET activity_name = ?,
                purpose = ?,
                department = ?,
                data_controller = ?,
                business_owner = ?,
                controller_role = ?,
                legal_basis = ?,
                data_categories = ?,
                data_subjects = ?,
                processing_operations = ?,
                data_source = ?,
                recipients = ?,
                third_parties = ?,
                international_transfers = ?,
                transfer_safeguards = ?,
                retention_period = ?,
                retention_basis = ?,
                disposal_mechanism = ?,
                storage_location = ?,
                safeguards = ?,
                technical_measures = ?,
                organizational_measures = ?,
                risk_level = ?,
                status = ?,
                review_date = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");

        $success = $stmt->execute([
            $activityName,
            $purpose,
            $department,
            $dataController,
            $businessOwner,
            $controllerRole,
            $legalBasis,
            $dataCategories,
            $dataSubjects,
            $processingOperations,
            $dataSource,
            $recipients,
            $thirdParties,
            $internationalTransfers,
            $transferSafeguards,
            $retentionPeriod,
            $retentionBasis,
            $disposalMechanism,
            $storageLocation,
            $safeguards,
            $technicalMeasures,
            $organizationalMeasures,
            $riskLevel,
            $status,
            $reviewDate,
            $userId,
            $id
        ]);

        if ($success) {
            $changes = [];
            if ($old['status'] !== $status) {
                $changes[] = "Status: {$old['status']} -> {$status}";
            }
            if ($old['activity_name'] !== $activityName) {
                $changes[] = "Activity Name updated";
            }
            if ($old['legal_basis'] !== $legalBasis) {
                $changes[] = "Lawful Basis: {$old['legal_basis']} -> {$legalBasis}";
            }
            if ($old['review_date'] !== $reviewDate) {
                $changes[] = "Review Date: {$old['review_date']} -> {$reviewDate}";
            }

            $detailMsg = !empty($changes) ? implode(", ", $changes) : "Record details updated.";
            $this->logHistory($id, 'ROPA Updated', $userId, $old['status'], $status, $detailMsg);
        }

        return $success;
    }

    /**
     * Soft delete processing activity
     */
    public function delete($id, $userId = 1)
    {
        $stmt = $this->pdo->prepare("UPDATE processing_activities SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            $this->logHistory($id, 'ROPA Deleted', $userId, null, null, "ROPA record soft-deleted.");
        }
        return $success;
    }

    /**
     * Fetch single ROPA record by ID
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT pa.*,
                   COALESCE(pa.ropa_code, CONCAT('ROPA-', LPAD(pa.id, 4, '0'))) AS ropa_code,
                   u.email AS creator_email, u.first_name AS creator_first, u.last_name AS creator_last
            FROM processing_activities pa
            LEFT JOIN users u ON pa.created_by = u.id
            WHERE pa.id = ? AND pa.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated and filtered ROPA list
     */
    public function getList($search = null, $statusFilter = null, $departmentFilter = null, $lawfulBasisFilter = null, $controllerRoleFilter = null, $overdueFilter = null, $limit = 10, $offset = 0, $sortField = 'created_at', $sortDir = 'DESC')
    {
        $whereClauses = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(activity_name LIKE ? OR purpose LIKE ? OR ropa_code LIKE ? OR data_controller LIKE ? OR business_owner LIKE ? OR department LIKE ? OR legal_basis LIKE ? OR data_categories LIKE ? OR data_subjects LIKE ? OR storage_location LIKE ?)";
            $term = "%" . trim($search) . "%";
            for ($i = 0; $i < 10; $i++) {
                $params[] = $term;
            }
        }
        if (!empty($statusFilter)) {
            $whereClauses[] = "status = ?";
            $params[] = $statusFilter;
        }
        if (!empty($departmentFilter)) {
            $whereClauses[] = "department = ?";
            $params[] = $departmentFilter;
        }
        if (!empty($lawfulBasisFilter)) {
            $whereClauses[] = "legal_basis = ?";
            $params[] = $lawfulBasisFilter;
        }
        if (!empty($controllerRoleFilter)) {
            $whereClauses[] = "controller_role = ?";
            $params[] = $controllerRoleFilter;
        }
        if ($overdueFilter === '1' || $overdueFilter === true || $overdueFilter === 'true') {
            $whereClauses[] = "review_date IS NOT NULL AND review_date < CURDATE() AND status != 'inactive'";
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total matching
        $countSql = "SELECT COUNT(*) FROM processing_activities $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Whitelist allowed sort fields
        $allowedSorts = [
            'id' => 'id',
            'ropa_code' => 'ropa_code',
            'activity_name' => 'activity_name',
            'department' => 'department',
            'legal_basis' => 'legal_basis',
            'status' => 'status',
            'review_date' => 'review_date',
            'created_at' => 'created_at'
        ];
        $orderBy = $allowedSorts[$sortField] ?? 'created_at';
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        // Fetch paginated items
        $sql = "
            SELECT 
                id,
                COALESCE(ropa_code, CONCAT('ROPA-', LPAD(id, 4, '0'))) AS ropa_code,
                activity_name,
                purpose,
                department,
                data_controller,
                business_owner,
                controller_role,
                legal_basis,
                data_categories,
                data_subjects,
                processing_operations,
                data_source,
                recipients,
                third_parties,
                international_transfers,
                transfer_safeguards,
                retention_period,
                retention_basis,
                disposal_mechanism,
                storage_location,
                safeguards,
                technical_measures,
                organizational_measures,
                risk_level,
                status,
                review_date,
                created_at,
                (review_date IS NOT NULL AND review_date < CURDATE() AND status != 'inactive') AS is_overdue
            FROM processing_activities
            $whereSql
            ORDER BY $orderBy $direction
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * Live database-backed dashboard metrics
     */
    public function getDashboardMetrics()
    {
        $kpiSql = "
            SELECT 
                COUNT(*) AS total_activities,
                SUM(IF(status = 'active', 1, 0)) AS active_activities,
                SUM(IF(status = 'draft', 1, 0)) AS draft_activities,
                SUM(IF(status = 'under_review', 1, 0)) AS under_review_activities,
                SUM(IF(status = 'approved', 1, 0)) AS approved_activities,
                SUM(IF(status IN ('inactive', 'archived'), 1, 0)) AS inactive_activities,
                SUM(IF(review_date IS NOT NULL AND review_date < CURDATE() AND status != 'inactive', 1, 0)) AS overdue_reviews,
                SUM(IF(created_at >= DATE_FORMAT(NOW(), '%Y-%m-01'), 1, 0)) AS new_this_month,
                SUM(IF(international_transfers = 'Yes', 1, 0)) AS international_transfers,
                SUM(IF(risk_level = 'High', 1, 0)) AS high_risk_activities
            FROM processing_activities
            WHERE deleted_at IS NULL
        ";

        $data = $this->pdo->query($kpiSql)->fetch(\PDO::FETCH_ASSOC);

        // Lawful basis distribution
        $legalSql = "
            SELECT legal_basis, COUNT(*) AS count 
            FROM processing_activities 
            WHERE deleted_at IS NULL AND legal_basis IS NOT NULL AND legal_basis != ''
            GROUP BY legal_basis
            ORDER BY count DESC
        ";
        $legalDist = $this->pdo->query($legalSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Department distribution
        $deptSql = "
            SELECT department, COUNT(*) AS count 
            FROM processing_activities 
            WHERE deleted_at IS NULL AND department IS NOT NULL AND department != ''
            GROUP BY department
            ORDER BY count DESC
        ";
        $deptDist = $this->pdo->query($deptSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Controller vs Processor role distribution
        $roleSql = "
            SELECT controller_role, COUNT(*) AS count 
            FROM processing_activities 
            WHERE deleted_at IS NULL AND controller_role IS NOT NULL AND controller_role != ''
            GROUP BY controller_role
            ORDER BY count DESC
        ";
        $roleDist = $this->pdo->query($roleSql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Recent ROPA activity
        $recentSql = "
            SELECT id, COALESCE(ropa_code, CONCAT('ROPA-', LPAD(id, 4, '0'))) AS ropa_code, activity_name, department, status, created_at
            FROM processing_activities
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 5
        ";
        $recent = $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'total_activities' => (int)($data['total_activities'] ?? 0),
            'active_activities' => (int)($data['active_activities'] ?? 0),
            'draft_activities' => (int)($data['draft_activities'] ?? 0),
            'under_review_activities' => (int)($data['under_review_activities'] ?? 0),
            'approved_activities' => (int)($data['approved_activities'] ?? 0),
            'inactive_activities' => (int)($data['inactive_activities'] ?? 0),
            'overdue_reviews' => (int)($data['overdue_reviews'] ?? 0),
            'new_this_month' => (int)($data['new_this_month'] ?? 0),
            'international_transfers' => (int)($data['international_transfers'] ?? 0),
            'high_risk_activities' => (int)($data['high_risk_activities'] ?? 0),
            'lawful_basis_distribution' => $legalDist ?: [],
            'department_distribution' => $deptDist ?: [],
            'controller_role_distribution' => $roleDist ?: [],
            'recent_activity' => $recent ?: []
        ];
    }

    /**
     * Log history event to ropa_history
     */
    public function logHistory($ropaId, $action, $userId = 1, $oldStatus = null, $newStatus = null, $details = null)
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ropa_history (ropa_id, action, performed_by, old_status, new_status, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ropaId, $action, $userId, $oldStatus, $newStatus, $details]);
        } catch (\Throwable $e) {}
    }

    /**
     * Get ROPA audit history logs
     */
    public function getHistory($ropaId)
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, u.email, u.first_name, u.last_name
            FROM ropa_history h
            LEFT JOIN users u ON h.performed_by = u.id
            WHERE h.ropa_id = ?
            ORDER BY h.id DESC
        ");
        $stmt->execute([$ropaId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
