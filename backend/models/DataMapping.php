<?php
namespace Backend\Models;

class DataMapping {
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
        // Total Activities
        $actSql = "SELECT COUNT(*) FROM processing_activities WHERE deleted_at IS NULL";
        $totalActivities = (int)($this->pdo ? $this->pdo->query($actSql)->fetchColumn() : 0);

        // Data Flows Metrics
        $flowSql = "
            SELECT 
                COUNT(*) as total_flows,
                SUM(IF(risk_level = 'High' OR risk_level = 'Critical', 1, 0)) as high_risk_flows,
                SUM(IF(encryption_status LIKE 'Encrypted%', 1, 0)) as encrypted_flows,
                SUM(IF(encryption_status LIKE 'In Transit%', 1, 0)) as in_transit_flows,
                SUM(IF(encryption_status LIKE 'None%' OR encryption_status LIKE 'Plaintext%', 1, 0)) as plaintext_flows
            FROM data_flows
            WHERE deleted_at IS NULL
        ";
        $flowMetrics = $this->pdo ? $this->pdo->query($flowSql)->fetch(\PDO::FETCH_ASSOC) : [];
        $totalFlows = (int)($flowMetrics['total_flows'] ?? 0);
        $highRiskFlows = (int)($flowMetrics['high_risk_flows'] ?? 0);
        $encryptedFlows = (int)($flowMetrics['encrypted_flows'] ?? 0);
        $inTransitFlows = (int)($flowMetrics['in_transit_flows'] ?? 0);
        $plaintextFlows = (int)($flowMetrics['plaintext_flows'] ?? 0);

        $encryptedPct = $totalFlows > 0 ? round(($encryptedFlows / $totalFlows) * 100) : 100;
        $inTransitPct = $totalFlows > 0 ? round(($inTransitFlows / $totalFlows) * 100) : 0;
        $plaintextPct = $totalFlows > 0 ? round(($plaintextFlows / $totalFlows) * 100) : 0;

        // Connected Systems (Unique Sources + Targets)
        $sysSql = "
            SELECT COUNT(DISTINCT sys) FROM (
                SELECT source_system AS sys FROM data_flows WHERE deleted_at IS NULL AND source_system IS NOT NULL AND source_system != ''
                UNION
                SELECT target_system AS sys FROM data_flows WHERE deleted_at IS NULL AND target_system IS NOT NULL AND target_system != ''
            ) AS combined_systems
        ";
        $connectedSystems = (int)($this->pdo ? $this->pdo->query($sysSql)->fetchColumn() : 0);

        // Recent Mapping Activity
        $recentSql = "
            SELECT df.*, pa.activity_name 
            FROM data_flows df
            LEFT JOIN processing_activities pa ON df.processing_activity_id = pa.id
            WHERE df.deleted_at IS NULL
            ORDER BY df.id DESC LIMIT 5
        ";
        $recentActivity = $this->pdo ? $this->pdo->query($recentSql)->fetchAll(\PDO::FETCH_ASSOC) : [];

        return [
            'metrics' => [
                'total_activities' => $totalActivities,
                'total_flows' => $totalFlows,
                'connected_systems' => $connectedSystems,
                'high_risk_flows' => $highRiskFlows,
                'encrypted_pct' => $encryptedPct,
                'in_transit_pct' => $inTransitPct,
                'plaintext_pct' => $plaintextPct,
                'compliance_coverage' => 96
            ],
            'recent_activity' => $recentActivity
        ];
    }

    /**
     * Processing Activities CRUD & Search
     */
    public function getActivities($search = '', $department = '', $riskLevel = '', $status = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 10, $offset = 0) {
        $where = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(activity_name LIKE ? OR purpose LIKE ? OR data_categories LIKE ? OR data_subjects LIKE ? OR data_controller LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if (!empty($department)) {
            $where[] = "department = ?";
            $params[] = $department;
        }
        if (!empty($riskLevel)) {
            $where[] = "risk_level = ?";
            $params[] = $riskLevel;
        }
        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM processing_activities $whereSql");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $allowedSort = ['id', 'activity_name', 'department', 'risk_level', 'status', 'created_at'];
        $sortCol = in_array($sortBy, $allowedSort) ? $sortBy : 'id';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM processing_activities $whereSql ORDER BY $sortCol $sortDir LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function getActivityById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM processing_activities WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createActivity($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO processing_activities 
            (activity_name, purpose, department, data_controller, processor, data_categories, data_subjects, recipients, legal_basis, retention_period, storage_location, safeguards, risk_level, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            trim($data['activity_name'] ?? ''),
            trim($data['purpose'] ?? ''),
            trim($data['department'] ?? 'Engineering'),
            trim($data['data_controller'] ?? 'PrivacyHQ Core'),
            trim($data['processor'] ?? ''),
            trim($data['data_categories'] ?? ''),
            trim($data['data_subjects'] ?? ''),
            trim($data['recipients'] ?? ''),
            trim($data['legal_basis'] ?? 'Legitimate Interest'),
            trim($data['retention_period'] ?? '3 Years'),
            trim($data['storage_location'] ?? 'AWS Cloud'),
            trim($data['safeguards'] ?? 'AES-256 Encryption'),
            trim($data['risk_level'] ?? 'Medium'),
            trim($data['status'] ?? 'active')
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateActivity($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE processing_activities 
            SET activity_name = ?, purpose = ?, department = ?, data_controller = ?, processor = ?, data_categories = ?, data_subjects = ?, recipients = ?, legal_basis = ?, retention_period = ?, storage_location = ?, safeguards = ?, risk_level = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([
            trim($data['activity_name'] ?? ''),
            trim($data['purpose'] ?? ''),
            trim($data['department'] ?? 'Engineering'),
            trim($data['data_controller'] ?? 'PrivacyHQ Core'),
            trim($data['processor'] ?? ''),
            trim($data['data_categories'] ?? ''),
            trim($data['data_subjects'] ?? ''),
            trim($data['recipients'] ?? ''),
            trim($data['legal_basis'] ?? 'Legitimate Interest'),
            trim($data['retention_period'] ?? '3 Years'),
            trim($data['storage_location'] ?? 'AWS Cloud'),
            trim($data['safeguards'] ?? 'AES-256 Encryption'),
            trim($data['risk_level'] ?? 'Medium'),
            trim($data['status'] ?? 'active'),
            $id
        ]);
    }

    public function deleteActivity($id) {
        $stmt = $this->pdo->prepare("UPDATE processing_activities SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Data Flows CRUD & Search
     */
    public function getFlows($search = '', $source = '', $risk = '', $encryption = '', $sortBy = 'id', $sortOrder = 'DESC', $limit = 20, $offset = 0) {
        $where = ["df.deleted_at IS NULL"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(df.source_system LIKE ? OR df.target_system LIKE ? OR df.data_type LIKE ? OR df.flow_name LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }
        if (!empty($source)) {
            $where[] = "df.source_system = ?";
            $params[] = $source;
        }
        if (!empty($risk)) {
            $where[] = "df.risk_level = ?";
            $params[] = $risk;
        }
        if (!empty($encryption)) {
            $where[] = "df.encryption_status LIKE ?";
            $params[] = "%$encryption%";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM data_flows df $whereSql");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $allowedSort = ['id', 'source_system', 'target_system', 'risk_level', 'created_at'];
        $sortCol = in_array($sortBy, $allowedSort) ? "df.$sortBy" : 'df.id';
        $sortDir = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "
            SELECT df.*, pa.activity_name 
            FROM data_flows df
            LEFT JOIN processing_activities pa ON df.processing_activity_id = pa.id
            $whereSql 
            ORDER BY $sortCol $sortDir 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['total' => $total, 'items' => $items];
    }

    public function createFlow($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO data_flows 
            (processing_activity_id, flow_name, source_system, target_system, data_type, data_subject_category, transfer_method, encryption_status, risk_level, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            !empty($data['processing_activity_id']) ? (int)$data['processing_activity_id'] : null,
            trim($data['flow_name'] ?? ''),
            trim($data['source_system'] ?? ''),
            trim($data['target_system'] ?? ''),
            trim($data['data_type'] ?? ''),
            trim($data['data_subject_category'] ?? 'Customers'),
            trim($data['transfer_method'] ?? 'REST API (HTTPS)'),
            trim($data['encryption_status'] ?? 'Encrypted in Transit & Rest'),
            trim($data['risk_level'] ?? 'Low'),
            trim($data['description'] ?? '')
        ]);
        return $this->pdo->lastInsertId();
    }

    public function deleteFlow($id) {
        $stmt = $this->pdo->prepare("UPDATE data_flows SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Topology Nodes & Connections for Flow Diagram
     */
    public function getFlowTopology() {
        $sql = "
            SELECT df.id, df.source_system, df.target_system, df.data_type, df.transfer_method, df.encryption_status, df.risk_level, pa.activity_name
            FROM data_flows df
            LEFT JOIN processing_activities pa ON df.processing_activity_id = pa.id
            WHERE df.deleted_at IS NULL
            ORDER BY df.id ASC
        ";
        return $this->pdo ? $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC) : [];
    }
}
