<?php
namespace Backend\Services;

class DataDiscoveryService {
    private $pdo;
    private $model;

    public function __construct(\PDO $pdo, $model = null) {
        $this->pdo = $pdo;
        $this->model = $model ?: new \Backend\Models\DataDiscovery($pdo);
    }

    public function getDashboard() {
        return $this->model->getDashboardMetrics();
    }

    public function getSources($search = '', $type = '', $risk = '', $status = '', $sortBy = 'id', $sortOrder = 'DESC', $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->model->getSources($search, $type, $risk, $status, $sortBy, $sortOrder, $pageSize, $offset);
    }

    public function getSource($id) {
        $source = $this->model->getSourceById($id);
        if (!$source) {
            throw new \Exception("Data Source record not found.");
        }
        return $source;
    }

    public function createSource($data, $userId) {
        $name = trim($data['name'] ?? '');
        $sourceType = trim($data['source_type'] ?? 'database');
        $connectionUri = trim($data['connection_uri'] ?? '');
        $hostPort = trim($data['host_port'] ?? '');
        $environment = trim($data['environment'] ?? 'production');
        $riskLevel = trim($data['risk_level'] ?? 'medium');
        $status = trim($data['status'] ?? 'active');
        $description = trim($data['description'] ?? '');
        $piiTypes = $data['pii_types'] ?? ['Email', 'PAN'];

        if (empty($name) || empty($connectionUri)) {
            throw new \Exception("Data Source Name and Connection URI are required.");
        }

        try {
            $this->pdo->beginTransaction();
            $sourceId = $this->model->createSource($name, $sourceType, $connectionUri, $hostPort, $environment, $riskLevel, $status, $description, $piiTypes);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Discovery', 'Create Source', $userId, $sourceId, null, json_encode(['name' => $name, 'type' => $sourceType]));
            }

            $this->pdo->commit();
            return $sourceId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateSource($id, $data, $userId) {
        $name = trim($data['name'] ?? '');
        $sourceType = trim($data['source_type'] ?? 'database');
        $connectionUri = trim($data['connection_uri'] ?? '');
        $hostPort = trim($data['host_port'] ?? '');
        $environment = trim($data['environment'] ?? 'production');
        $riskLevel = trim($data['risk_level'] ?? 'medium');
        $status = trim($data['status'] ?? 'active');
        $description = trim($data['description'] ?? '');
        $piiTypes = $data['pii_types'] ?? [];

        if (empty($id) || empty($name) || empty($connectionUri)) {
            throw new \Exception("Source ID, Name, and Connection URI are required.");
        }

        $existing = $this->model->getSourceById($id);
        if (!$existing) {
            throw new \Exception("Data Source not found.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->model->updateSource($id, $name, $sourceType, $connectionUri, $hostPort, $environment, $riskLevel, $status, $description, $piiTypes);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Discovery', 'Update Source', $userId, $id, json_encode(['name' => $existing['name']]), json_encode(['name' => $name]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteSource($id, $userId) {
        $existing = $this->model->getSourceById($id);
        if (!$existing) {
            throw new \Exception("Data Source not found.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->model->deleteSource($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Discovery', 'Delete Source', $userId, $id, json_encode(['name' => $existing['name']]), null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function triggerScan($sourceId, $scanType = 'full', $userId = null) {
        $source = $this->model->getSourceById($sourceId);
        if (!$source) {
            throw new \Exception("Selected Data Source does not exist.");
        }

        $activeScan = $this->model->getActiveScanForSource($sourceId);
        if ($activeScan) {
            throw new \Exception("A discovery scan is already running for this source.");
        }

        try {
            $this->pdo->beginTransaction();
            $scanId = $this->model->createScan($sourceId, $scanType, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Discovery', 'Trigger Scan', $userId, $scanId, null, json_encode(['source' => $source['name'], 'type' => $scanType]));
            }

            $this->pdo->commit();
            return $scanId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function controlScan($scanId, $action, $userId = null) {
        try {
            $this->pdo->beginTransaction();

            if ($action === 'pause') {
                $this->model->updateScanStatus($scanId, 'paused');
            } else if ($action === 'resume') {
                $this->model->updateScanStatus($scanId, 'scanning', 50);
            } else if ($action === 'cancel') {
                $this->model->updateScanStatus($scanId, 'cancelled', 0, 0, 0, 0, 0, 'Cancelled by user');
            } else if ($action === 'complete') {
                $this->model->updateScanStatus($scanId, 'completed', 100, 15400, 245000, 48, 180);
            }

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Discovery', 'Control Scan', $userId, $scanId, null, json_encode(['action' => $action]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getScanHistory($sourceId = null, $status = '', $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->model->getScans($sourceId, $status, 'id', 'DESC', $pageSize, $offset);
    }

    public function getFindings($search = '', $category = '', $severity = '', $sourceId = null, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        return $this->model->getFindings($search, $category, $severity, $sourceId, $pageSize, $offset);
    }

    public function exportReports($format = 'csv', $reportType = 'summary', $search = '', $category = '', $sourceId = null) {
        $findings = $this->model->getFindings($search, $category, '', $sourceId, 10000, 0)['items'] ?? [];

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Data_Discovery_' . ucfirst($reportType) . '_' . date('Y-m-d_H-i') . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Finding ID', 'Source Name', 'Data Element Name', 'Classification Category', 'Location Path', 'Record Count', 'Risk Severity', 'Confidence Score %']);
            foreach ($findings as $r) {
                fputcsv($out, [
                    $r['id'],
                    $r['source_name'],
                    $r['data_element_name'],
                    $r['classification_category'],
                    $r['location_path'],
                    $r['record_count'],
                    strtoupper($r['risk_severity']),
                    $r['confidence_score'] . '%'
                ]);
            }
            fclose($out);
            exit;
        } else {
            // PDF / Print HTML Format
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Data Discovery Report - ' . ucfirst($reportType) . '</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:12px;text-align:left;} th{background:#f3f4f6;}</style></head><body>';
            echo '<h2>PrivacyHQ - Personal Data Discovery & DSPM Report</h2>';
            echo '<p>Generated on: ' . date('Y-m-d H:i:s') . ' | Total Findings: ' . count($findings) . '</p>';
            echo '<table><thead><tr><th>Element</th><th>Source</th><th>Category</th><th>Location</th><th>Records</th><th>Severity</th></tr></thead><tbody>';
            foreach ($findings as $r) {
                echo '<tr><td><code>' . htmlspecialchars($r['data_element_name']) . '</code></td><td>' . htmlspecialchars($r['source_name']) . '</td><td>' . htmlspecialchars($r['classification_category']) . '</td><td><code>' . htmlspecialchars($r['location_path']) . '</code></td><td>' . number_format($r['record_count']) . '</td><td>' . htmlspecialchars(strtoupper($r['risk_severity'])) . '</td></tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }
}
