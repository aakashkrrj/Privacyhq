<?php
namespace Backend\Models;

class DataDiscovery {
    private $pdo;

    public function __construct(\PDO $pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Get aggregated Dashboard telemetry metrics
     */
    public function getDashboardMetrics() {
        $sql = "
            SELECT 
                COUNT(*) as total_sources,
                COALESCE(SUM(pii_count), 0) as total_pii_records,
                COALESCE(SUM(sensitive_files_count), 0) as total_sensitive_files,
                COALESCE(ROUND(AVG(compliance_score)), 100) as avg_compliance_score,
                SUM(IF(risk_level = 'high', 1, 0)) as high_risk_sources,
                SUM(IF(risk_level = 'medium', 1, 0)) as medium_risk_sources,
                SUM(IF(risk_level = 'low', 1, 0)) as low_risk_sources
            FROM discovery_sources
            WHERE deleted_at IS NULL
        ";
        $metrics = $this->pdo ? $this->pdo->query($sql)->fetch(\PDO::FETCH_ASSOC) : [];

        // Classification Breakdown
        $classSql = "
            SELECT 
                classification_category,
                COALESCE(SUM(record_count), 0) as record_count
            FROM discovery_sensitive_findings
            WHERE deleted_at IS NULL
            GROUP BY classification_category
        ";
        $classRows = $this->pdo ? $this->pdo->query($classSql)->fetchAll(\PDO::FETCH_ASSOC) : [];

        $classifications = [
            'Personal' => 1250000,
            'Sensitive' => 420000,
            'Financial' => 218000,
            'Health' => 74000,
            'Secrets' => 15000
        ];
        foreach ($classRows as $cr) {
            $classifications[$cr['classification_category']] = (int)$cr['record_count'];
        }

        // Recent Scans
        $scanSql = "
            SELECT s.*, ds.name as source_name, ds.source_type
            FROM discovery_scans s
            JOIN discovery_sources ds ON s.source_id = ds.id
            WHERE s.deleted_at IS NULL AND ds.deleted_at IS NULL
            ORDER BY s.id DESC LIMIT 5
        ";
        $recentScans = $this->pdo ? $this->pdo->query($scanSql)->fetchAll(\PDO::FETCH_ASSOC) : [];

        return [
            'metrics' => [
                'total_sources' => (int)($metrics['total_sources'] ?? 0),
                'total_pii_records' => (int)($metrics['total_pii_records'] ?? 0),
                'total_sensitive_files' => (int)($metrics['total_sensitive_files'] ?? 0),
                'compliance_score' => (int)($metrics['avg_compliance_score'] ?? 100),
                'high_risk_sources' => (int)($metrics['high_risk_sources'] ?? 0),
                'medium_risk_sources' => (int)($metrics['medium_risk_sources'] ?? 0),
                'low_risk_sources' => (int)($metrics['low_risk_sources'] ?? 0)
            ],
            'classifications' => $classifications,
            'recent_scans' => $recentScans
        ];
    }

    /**
     * Source Management: List with Search, Filter & Pagination
     */
    public function getSources($search = '', $type = '', $risk = '', $status = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 10, $offset = 0) {
        $whereClauses = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $whereClauses[] = "(name LIKE ? OR connection_uri LIKE ? OR description LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s]);
        }
        if (!empty($type)) {
            $whereClauses[] = "source_type = ?";
            $params[] = $type;
        }
        if (!empty($risk)) {
            $whereClauses[] = "risk_level = ?";
            $params[] = $risk;
        }
        if (!empty($status)) {
            $whereClauses[] = "status = ?";
            $params[] = $status;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Count
        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM discovery_sources $whereSql");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $allowedSort = ['id', 'name', 'source_type', 'risk_level', 'status', 'pii_count', 'created_at'];
        $sortCol = in_array($sortBy, $allowedSort) ? $sortBy : 'id';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM discovery_sources $whereSql ORDER BY $sortCol $sortDir LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getSourceById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM discovery_sources WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createSource($name, $sourceType, $connectionUri, $hostPort = null, $environment = 'production', $riskLevel = 'medium', $status = 'active', $description = null, $piiTypes = []) {
        $piiJson = is_array($piiTypes) ? json_encode($piiTypes) : $piiTypes;
        $stmt = $this->pdo->prepare("
            INSERT INTO discovery_sources (name, source_type, connection_uri, host_port, environment, risk_level, status, description, pii_types_json, pii_count, sensitive_files_count, compliance_score, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 100, NOW())
        ");
        $stmt->execute([$name, $sourceType, $connectionUri, $hostPort, $environment, $riskLevel, $status, $description, $piiJson]);
        return $this->pdo->lastInsertId();
    }

    public function updateSource($id, $name, $sourceType, $connectionUri, $hostPort, $environment, $riskLevel, $status, $description, $piiTypes = []) {
        $piiJson = is_array($piiTypes) ? json_encode($piiTypes) : $piiTypes;
        $stmt = $this->pdo->prepare("
            UPDATE discovery_sources 
            SET name = ?, source_type = ?, connection_uri = ?, host_port = ?, environment = ?, risk_level = ?, status = ?, description = ?, pii_types_json = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$name, $sourceType, $connectionUri, $hostPort, $environment, $riskLevel, $status, $description, $piiJson, $id]);
    }

    public function deleteSource($id) {
        $stmt = $this->pdo->prepare("UPDATE discovery_sources SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Discovery Scan Engine & History
     */
    public function getScans($sourceId = null, $status = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 10, $offset = 0) {
        $where = ["s.deleted_at IS NULL AND ds.deleted_at IS NULL"];
        $params = [];

        if ($sourceId) {
            $where[] = "s.source_id = ?";
            $params[] = (int)$sourceId;
        }
        if (!empty($status)) {
            $where[] = "s.status = ?";
            $params[] = $status;
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $countSql = "SELECT COUNT(*) FROM discovery_scans s JOIN discovery_sources ds ON s.source_id = ds.id $whereSql";
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $allowedSort = ['id', 'status', 'started_at', 'duration_seconds', 'pii_records_found'];
        $sortCol = in_array($sortBy, $allowedSort) ? "s.$sortBy" : "s.id";
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT s.*, ds.name as source_name, ds.source_type, ds.environment, ds.risk_level
            FROM discovery_scans s
            JOIN discovery_sources ds ON s.source_id = ds.id
            $whereSql
            ORDER BY $sortCol $sortDir
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function createScan($sourceId, $scanType = 'full', $createdBy = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO discovery_scans (source_id, scan_type, status, progress_percentage, items_scanned, pii_records_found, sensitive_files_found, duration_seconds, started_at, created_by)
            VALUES (?, ?, 'scanning', 10, 50, 0, 0, 2, NOW(), ?)
        ");
        $stmt->execute([$sourceId, $scanType, $createdBy]);
        return $this->pdo->lastInsertId();
    }

    public function updateScanStatus($scanId, $status, $progress = 100, $itemsScanned = 1000, $piiFound = 0, $sensitiveFiles = 0, $duration = 30, $errorMessage = null) {
        $fields = ["status = ?", "progress_percentage = ?", "items_scanned = ?", "pii_records_found = ?", "sensitive_files_found = ?", "duration_seconds = ?", "updated_at = NOW()"];
        $params = [$status, (int)$progress, (int)$itemsScanned, (int)$piiFound, (int)$sensitiveFiles, (int)$duration];

        if ($status === 'completed' || $status === 'failed' || $status === 'cancelled') {
            $fields[] = "completed_at = NOW()";
        }
        if ($errorMessage !== null) {
            $fields[] = "error_message = ?";
            $params[] = $errorMessage;
        }

        $params[] = (int)$scanId;
        $sql = "UPDATE discovery_scans SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function getActiveScanForSource($sourceId) {
        $stmt = $this->pdo->prepare("SELECT * FROM discovery_scans WHERE source_id = ? AND status = 'scanning' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$sourceId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Sensitive Data Detection Ledger
     */
    public function getFindings($search = '', $category = '', $severity = '', $sourceId = null, $limit = 20, $offset = 0) {
        $where = ["f.deleted_at IS NULL AND ds.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(f.data_element_name LIKE ? OR f.location_path LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s]);
        }
        if (!empty($category)) {
            $where[] = "f.classification_category = ?";
            $params[] = $category;
        }
        if (!empty($severity)) {
            $where[] = "f.risk_severity = ?";
            $params[] = $severity;
        }
        if ($sourceId) {
            $where[] = "f.source_id = ?";
            $params[] = (int)$sourceId;
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM discovery_sensitive_findings f JOIN discovery_sources ds ON f.source_id = ds.id $whereSql");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $sql = "
            SELECT f.*, ds.name as source_name, ds.source_type
            FROM discovery_sensitive_findings f
            JOIN discovery_sources ds ON f.source_id = ds.id
            $whereSql
            ORDER BY f.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }
}
