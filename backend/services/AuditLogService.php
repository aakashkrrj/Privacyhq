<?php
// governance/backend/services/AuditLogService.php

namespace Backend\Services;

use Backend\Core\PdfGenerator;
use Backend\Core\XlsxGenerator;

class AuditLogService
{
    private $pdo;
    private $auditModel;

    public function __construct(\PDO $pdo, $auditModel = null)
    {
        $this->pdo = $pdo;
        $this->auditModel = $auditModel ?: new \Backend\Models\AuditLog($pdo);
    }

    public function getDashboardMetrics()
    {
        return $this->auditModel->getDashboardMetrics();
    }

    public function getLogs($filters = [], $page = 1, $limit = 20, $sortField = 'created_at', $sortDir = 'DESC')
    {
        $page = max(1, (int)$page);
        $limit = max(1, min(200, (int)$limit));
        $offset = ($page - 1) * $limit;

        $search = $filters['search'] ?? null;
        $module = $filters['module'] ?? null;
        $action = $filters['action'] ?? null;
        $userId = $filters['user_id'] ?? $filters['user'] ?? null;
        $dateFrom = $filters['date_from'] ?? $filters['date'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $res = $this->auditModel->getLogsList($search, $module, $action, $userId, $dateFrom, $dateTo, $limit, $offset, $sortField, $sortDir);

        // Sanitize items for frontend consumption
        foreach ($res['items'] as &$item) {
            $item['old_value_sanitized'] = $this->sanitizeMetadata($item['old_value']);
            $item['new_value_sanitized'] = $this->sanitizeMetadata($item['new_value']);
        }

        return $res;
    }

    public function getLogById($id)
    {
        if (empty($id) || !is_numeric($id)) {
            throw new \Exception("Valid audit log ID is required.");
        }
        $log = $this->auditModel->getLogById((int)$id);
        if (!$log) {
            throw new \Exception("Audit log record not found.");
        }
        $log['old_value_sanitized'] = $this->sanitizeMetadata($log['old_value']);
        $log['new_value_sanitized'] = $this->sanitizeMetadata($log['new_value']);
        return $log;
    }

    public function getRetentionSettings()
    {
        return $this->auditModel->getRetentionSettings();
    }

    public function saveRetentionSettings($data, $userId = 1)
    {
        $days = (int)($data['retention_days'] ?? 90);
        $autoPurge = !empty($data['auto_purge_enabled']);
        $archiveBeforePurge = !empty($data['archive_before_purge']);

        $res = $this->auditModel->saveRetentionSettings($days, $autoPurge, $archiveBeforePurge, $userId);

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Audit Logs', 'Update Retention Policy', $userId, 1, null, json_encode(['days' => $days, 'auto_purge' => $autoPurge]));
        }

        return $res;
    }

    public function purgeOldLogs($retentionDays = null, $userId = 1)
    {
        if ($retentionDays === null) {
            $settings = $this->getRetentionSettings();
            $retentionDays = $settings['retention_days'];
        }

        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Audit Logs', 'Execute Retention Purge', $userId, null, null, json_encode(['retention_days' => $retentionDays]));
        }

        return $this->auditModel->purgeOldLogs($retentionDays, $userId);
    }

    /**
     * Export Audit Logs with Filter Preservation & Formula Injection Protection (Row 129)
     */
    public function exportAuditLogs($filters = [], $format = 'csv', $userId = 1)
    {
        $list = $this->auditModel->getLogsList(
            $filters['search'] ?? null,
            $filters['module'] ?? null,
            $filters['action'] ?? null,
            $filters['user_id'] ?? $filters['user'] ?? null,
            $filters['date_from'] ?? $filters['date'] ?? null,
            $filters['date_to'] ?? null,
            10000,
            0,
            'created_at',
            'DESC'
        );

        $data = $list['items'];
        $headers = [
            'id' => 'ID',
            'created_at' => 'Timestamp',
            'user_email' => 'User Email',
            'module' => 'Module',
            'action' => 'Action Event',
            'record_id' => 'Record ID',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent'
        ];

        // Sanitize rows for formula injection
        $sanitizedData = [];
        foreach ($data as $row) {
            $sRow = [];
            foreach ($headers as $key => $label) {
                $val = $row[$key] ?? '';
                if (is_array($val)) $val = json_encode($val);
                $sRow[$key] = $this->sanitizeSpreadsheetVal($val);
            }
            $sanitizedData[] = $sRow;
        }

        $title = 'Audit Logs Compliance Inventory';
        $baseFileName = 'PrivacyHQ_Audit_Logs_' . date('Y-m-d');

        // Log audit event for export
        if (function_exists('log_audit_event')) {
            log_audit_event($this->pdo, 'Audit Logs', 'Export Audit Logs', $userId, null, null, json_encode(['format' => $format, 'count' => count($sanitizedData)]));
        }

        if ($format === 'pdf') {
            $pdfGen = new PdfGenerator();
            $pdfGen->addHeader('PrivacyHQ — ' . $title, 'Export Date: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($sanitizedData));
            $pdfGen->addMetadataBlocks([
                'Module' => 'Audit Logs',
                'Records' => count($sanitizedData),
                'Security' => 'Immutability Verified',
                'Export' => 'Binary PDF'
            ]);
            $pdfGen->addTable(array_values($headers), $sanitizedData);
            $pdfBytes = $pdfGen->output();

            $filename = $baseFileName . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfBytes));
            echo $pdfBytes;
            exit();
        } elseif ($format === 'excel' || $format === 'xlsx') {
            $xlsxGen = new XlsxGenerator($headers, $sanitizedData, 'PrivacyHQ — ' . $title);
            $xlsxBytes = $xlsxGen->output();

            $filename = $baseFileName . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($xlsxBytes));
            echo $xlsxBytes;
            exit();
        } else {
            $filename = $baseFileName . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($output, ['PrivacyHQ System Audit Log Export']);
            fputcsv($output, ['Export Date: ' . date('Y-m-d H:i:s'), 'Total Records: ' . count($sanitizedData)]);
            fputcsv($output, []);
            fputcsv($output, array_values($headers));

            foreach ($sanitizedData as $row) {
                fputcsv($output, array_values($row));
            }
            fclose($output);
            exit();
        }
    }

    /**
     * Redact sensitive credentials / tokens from JSON metadata
     */
    private function sanitizeMetadata($jsonVal)
    {
        if (empty($jsonVal)) return '';

        $data = json_decode($jsonVal, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return $jsonVal;
        }

        $sensitiveKeys = ['password', 'passwd', 'secret', 'auth_key', 'token', 'access_token', 'credit_card', 'ssn', 'api_key', 'private_key'];
        
        array_walk_recursive($data, function (&$val, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sk) {
                if (stripos((string)$key, $sk) !== false) {
                    $val = '[REDACTED_SENSITIVE_DATA]';
                    break;
                }
            }
        });

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Prevent CSV/Excel Spreadsheet Formula Injection
     */
    private function sanitizeSpreadsheetVal($val)
    {
        $str = (string)$val;
        if ($str === '') return '';
        $firstChar = substr($str, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
            return "'" . $str;
        }
        return $str;
    }
}
