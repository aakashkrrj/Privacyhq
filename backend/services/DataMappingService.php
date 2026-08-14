<?php
namespace Backend\Services;

class DataMappingService {
    private $pdo;
    private $model;

    public function __construct(\PDO $pdo, $model = null) {
        $this->pdo = $pdo;
        $this->model = $model ?: new \Backend\Models\DataMapping($pdo);
    }

    public function getDashboard() {
        return $this->model->getDashboardMetrics();
    }

    public function getActivities($search = '', $department = '', $riskLevel = '', $status = '', $sortBy = 'id', $sortOrder = 'DESC', $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        return $this->model->getActivities($search, $department, $riskLevel, $status, $sortBy, $sortOrder, $pageSize, $offset);
    }

    public function getActivity($id) {
        $activity = $this->model->getActivityById($id);
        if (!$activity) {
            throw new \Exception("Processing Activity record not found.");
        }
        return $activity;
    }

    public function createActivity($data, $userId) {
        $activityName = trim($data['activity_name'] ?? '');
        if (empty($activityName)) {
            throw new \Exception("Processing Activity Name is required.");
        }

        try {
            $this->pdo->beginTransaction();
            $id = $this->model->createActivity($data);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Mapping', 'Create Processing Activity', $userId, $id, null, json_encode(['name' => $activityName]));
            }

            $this->pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateActivity($id, $data, $userId) {
        $existing = $this->model->getActivityById($id);
        if (!$existing) {
            throw new \Exception("Processing Activity not found.");
        }

        $activityName = trim($data['activity_name'] ?? '');
        if (empty($activityName)) {
            throw new \Exception("Processing Activity Name is required.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->model->updateActivity($id, $data);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Mapping', 'Update Processing Activity', $userId, $id, json_encode(['name' => $existing['activity_name']]), json_encode(['name' => $activityName]));
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteActivity($id, $userId) {
        $existing = $this->model->getActivityById($id);
        if (!$existing) {
            throw new \Exception("Processing Activity not found.");
        }

        try {
            $this->pdo->beginTransaction();
            $this->model->deleteActivity($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Mapping', 'Delete Processing Activity', $userId, $id, json_encode(['name' => $existing['activity_name']]), null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getFlows($search = '', $source = '', $risk = '', $encryption = '', $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        return $this->model->getFlows($search, $source, $risk, $encryption, 'id', 'DESC', $pageSize, $offset);
    }

    public function createFlow($data, $userId) {
        $source = trim($data['source_system'] ?? '');
        $target = trim($data['target_system'] ?? '');
        if (empty($source) || empty($target)) {
            throw new \Exception("Source System and Target System are required.");
        }

        try {
            $this->pdo->beginTransaction();
            $id = $this->model->createFlow($data);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Mapping', 'Create Data Flow', $userId, $id, null, json_encode(['source' => $source, 'target' => $target]));
            }

            $this->pdo->commit();
            return $id;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteFlow($id, $userId) {
        try {
            $this->pdo->beginTransaction();
            $this->model->deleteFlow($id);

            if (function_exists('log_audit_event')) {
                log_audit_event($this->pdo, 'Data Mapping', 'Delete Data Flow', $userId, $id, null, null);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getTopology() {
        return $this->model->getFlowTopology();
    }

    public function exportReports($format = 'csv', $reportType = 'flows', $search = '', $department = '', $risk = '') {
        if ($reportType === 'activities') {
            $data = $this->model->getActivities($search, $department, $risk, '', 'id', 'DESC', 10000, 0)['items'] ?? [];
            $filename = 'Data_Mapping_Activities_' . date('Y-m-d_H-i');
            $headers = ['Activity ID', 'Activity Name', 'Department', 'Data Controller', 'Processor', 'Data Categories', 'Legal Basis', 'Retention', 'Risk Level', 'Status'];
            $rows = array_map(function($r) {
                return [
                    $r['id'],
                    $r['activity_name'],
                    $r['department'],
                    $r['data_controller'],
                    $r['processor'],
                    $r['data_categories'],
                    $r['legal_basis'],
                    $r['retention_period'],
                    strtoupper($r['risk_level']),
                    strtoupper($r['status'])
                ];
            }, $data);
        } else {
            $data = $this->model->getFlows($search, '', $risk, '', 'id', 'DESC', 10000, 0)['items'] ?? [];
            $filename = 'Data_Mapping_Flows_' . date('Y-m-d_H-i');
            $headers = ['Flow ID', 'Source System', 'Target System', 'Data Types', 'Transfer Method', 'Encryption Status', 'Risk Level', 'Activity Name'];
            $rows = array_map(function($r) {
                return [
                    $r['id'],
                    $r['source_system'],
                    $r['target_system'],
                    $r['data_type'],
                    $r['transfer_method'],
                    $r['encryption_status'],
                    strtoupper($r['risk_level']),
                    $r['activity_name'] ?? 'N/A'
                ];
            }, $data);
        }

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
            exit;
        } else {
            // PDF / Print HTML Format
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><title>Data Mapping Export - ' . ucfirst($reportType) . '</title><style>body{font-family:sans-serif;padding:20px;} table{width:100%;border-collapse:collapse;margin-top:20px;} th,td{border:1px solid #ccc;padding:8px;font-size:12px;text-align:left;} th{background:#f3f4f6;}</style></head><body>';
            echo '<h2>PrivacyHQ - Data Mapping & Flow Inventory Report</h2>';
            echo '<p>Generated on: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($rows) . '</p>';
            echo '<table><thead><tr>';
            foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
            echo '</tr></thead><tbody>';
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $val) echo '<td>' . htmlspecialchars((string)$val) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table><script>window.print();</script></body></html>';
            exit;
        }
    }
}
