<?php
namespace Backend\Models;

class Ropa {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create($activityName, $purpose, $department, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod) {
        $stmt = $this->pdo->prepare("
            INSERT INTO processing_activities (activity_name, purpose, department, data_controller, data_categories, data_subjects, recipients, retention_period, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$activityName, $purpose, $department, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $activityName, $purpose, $department, $status, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod) {
        $stmt = $this->pdo->prepare("
            UPDATE processing_activities 
            SET activity_name = ?, purpose = ?, department = ?, status = ?, data_controller = ?, data_categories = ?, data_subjects = ?, recipients = ?, retention_period = ?
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$activityName, $purpose, $department, $status, $dataController, $dataCategories, $dataSubjects, $recipients, $retentionPeriod, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("UPDATE processing_activities SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM processing_activities WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getList($search, $statusFilter, $limit, $offset) {
        $whereClauses = ["deleted_at IS NULL"];
        $params = [];

        if ($search) {
            $whereClauses[] = "(activity_name LIKE ? OR purpose LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($statusFilter) {
            $whereClauses[] = "status = ?";
            $params[] = $statusFilter;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count total
        $countSql = "SELECT COUNT(*) FROM processing_activities $whereSql";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Fetch items
        $sql = "
            SELECT * 
            FROM processing_activities 
            $whereSql
            ORDER BY created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getDashboardMetrics() {
        $kpiQuery = "
            SELECT 
                COUNT(*) as total_activities,
                SUM(IF(status = 'active', 1, 0)) as active_activities,
                SUM(IF(status = 'inactive', 1, 0)) as inactive_activities
            FROM processing_activities
            WHERE deleted_at IS NULL
        ";
        $kpiRes = $this->pdo->query($kpiQuery)->fetch(\PDO::FETCH_ASSOC);
        
        $monthQuery = "
            SELECT COUNT(*) 
            FROM processing_activities 
            WHERE deleted_at IS NULL AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
        ";
        $newThisMonth = $this->pdo->query($monthQuery)->fetchColumn();

        return [
            'total_activities' => $kpiRes['total_activities'] ?? 0,
            'active_activities' => $kpiRes['active_activities'] ?? 0,
            'inactive_activities' => $kpiRes['inactive_activities'] ?? 0,
            'new_this_month' => $newThisMonth ?? 0
        ];
    }

    public function getIncomplete() {
        $sql = "
            SELECT *
            FROM processing_activities
            WHERE deleted_at IS NULL
              AND (
                activity_name IS NULL OR TRIM(activity_name) = ''
                OR purpose IS NULL OR TRIM(purpose) = ''
                OR department IS NULL OR TRIM(department) = ''
                OR data_controller IS NULL OR TRIM(data_controller) = ''
                OR data_categories IS NULL OR TRIM(data_categories) = ''
                OR data_subjects IS NULL OR TRIM(data_subjects) = ''
                OR recipients IS NULL OR TRIM(recipients) = ''
                OR retention_period IS NULL OR TRIM(retention_period) = ''
              )
            ORDER BY id DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

