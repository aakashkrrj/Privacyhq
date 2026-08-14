<?php
namespace Backend\Services;

class ActivityService {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Log an activity to the timeline.
     */
    public function logActivity($module, $recordId, $performedBy, $action, $oldStatus = null, $newStatus = null, array $metadata = []) {
        try {
            $metadataJson = json_encode($metadata);
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_timeline (module, record_id, performed_by, action, old_status, new_status, metadata_json)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$module, $recordId, $performedBy, $action, $oldStatus, $newStatus, $metadataJson]);
            return $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get timeline activities for a module or overall.
     */
    public function getTimeline($module = null, $recordId = null, $limit = 30) {
        $sql = "SELECT a.*, u.email as user_email 
                FROM activity_timeline a 
                JOIN users u ON a.performed_by = u.id";
        
        $params = [];
        if ($module !== null) {
            $sql .= " WHERE a.module = ?";
            $params[] = $module;
            if ($recordId !== null) {
                $sql .= " AND a.record_id = ?";
                $params[] = $recordId;
            }
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT " . intval($limit);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
