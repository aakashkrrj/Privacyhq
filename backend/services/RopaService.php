<?php
// governance/backend/services/RopaService.php

namespace Backend\Services;

class RopaService
{
    private $pdo;
    private $ropaModel;

    public function __construct(\PDO $pdo, $ropaModel)
    {
        $this->pdo = $pdo;
        $this->ropaModel = $ropaModel;
    }

    public function getDashboardMetrics()
    {
        return $this->ropaModel->getDashboardMetrics();
    }

    public function getList($search = null, $status = null, $department = null, $lawfulBasis = null, $controllerRole = null, $overdue = null, $page = 1, $pageSize = 10, $sortField = 'created_at', $sortDir = 'DESC')
    {
        $page = max(1, (int)$page);
        $pageSize = max(1, min(100, (int)$pageSize));
        $offset = ($page - 1) * $pageSize;

        return $this->ropaModel->getList($search, $status, $department, $lawfulBasis, $controllerRole, $overdue, $pageSize, $offset, $sortField, $sortDir);
    }

    public function findById($id)
    {
        if (empty($id) || !is_numeric($id)) {
            throw new \Exception("Valid ROPA record ID is required.");
        }
        $ropa = $this->ropaModel->findById((int)$id);
        if (!$ropa) {
            throw new \Exception("ROPA processing activity record not found.");
        }
        return $ropa;
    }

    public function createRopa($data, $userId = 1)
    {
        if (empty(trim($data['activity_name'] ?? ''))) {
            throw new \Exception("Processing activity name is required.");
        }
        if (empty(trim($data['purpose'] ?? ''))) {
            throw new \Exception("Purpose of processing is required.");
        }
        if (empty(trim($data['department'] ?? ''))) {
            throw new \Exception("Department selection is required.");
        }
        if (empty(trim($data['legal_basis'] ?? ''))) {
            throw new \Exception("Lawful basis (Article 6) selection is required.");
        }

        try {
            $this->pdo->beginTransaction();

            $ropaId = $this->ropaModel->create($data, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'ROPA',
                    'Create ROPA Record',
                    $userId,
                    $ropaId,
                    null,
                    json_encode([
                        'activity_name' => $data['activity_name'],
                        'department' => $data['department'] ?? 'General',
                        'legal_basis' => $data['legal_basis'] ?? 'Legitimate Interest'
                    ])
                );
            }

            $this->pdo->commit();

            // Workflow dispatch if available
            if (class_exists('\Backend\Services\WorkflowService')) {
                try {
                    \Backend\Services\WorkflowService::dispatch('ropa.created', [
                        'module' => 'ROPA',
                        'record_id' => $ropaId,
                        'title' => $data['activity_name'],
                        'assigned_to' => 1,
                        'created_by' => $userId
                    ]);
                } catch (\Throwable $wt) {}
            }

            return $ropaId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateRopa($id, $data, $userId = 1)
    {
        $existing = $this->findById($id);

        if (empty(trim($data['activity_name'] ?? ''))) {
            throw new \Exception("Processing activity name cannot be empty.");
        }
        if (empty(trim($data['purpose'] ?? ''))) {
            throw new \Exception("Purpose of processing cannot be empty.");
        }

        try {
            $this->pdo->beginTransaction();

            $success = $this->ropaModel->update($id, $data, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'ROPA',
                    'Update ROPA Record',
                    $userId,
                    $id,
                    json_encode(['activity_name' => $existing['activity_name'], 'status' => $existing['status']]),
                    json_encode(['activity_name' => $data['activity_name'] ?? $existing['activity_name'], 'status' => $data['status'] ?? $existing['status']])
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteRopa($id, $userId = 1)
    {
        $existing = $this->findById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->ropaModel->delete($id, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'ROPA',
                    'Delete ROPA Record',
                    $userId,
                    $id,
                    json_encode(['activity_name' => $existing['activity_name']]),
                    null
                );
            }

            $this->pdo->commit();
            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getHistory($ropaId)
    {
        if (empty($ropaId) || !is_numeric($ropaId)) {
            throw new \Exception("Valid ROPA ID is required.");
        }
        return $this->ropaModel->getHistory((int)$ropaId);
    }

    public function exportReport($search = null, $status = null, $department = null, $lawfulBasis = null, $controllerRole = null, $overdue = null, $format = 'csv')
    {
        $data = $this->ropaModel->getList($search, $status, $department, $lawfulBasis, $controllerRole, $overdue, 10000, 0, 'id', 'ASC');
        $items = $data['items'];

        $filename = 'PrivacyHQ_ROPA_Article30_Report_' . date('Y-m-d');

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($out, "\EF\xBB\xBF");

            fputcsv($out, ['PrivacyHQ Article 30 Records of Processing Activities (ROPA) Report']);
            fputcsv($out, ['Export Date: ' . date('Y-m-d H:i:s'), 'Total Activities: ' . count($items)]);
            fputcsv($out, []);
            fputcsv($out, [
                'ROPA Code', 'Activity Name', 'Purpose of Processing', 'Department', 'Data Controller', 
                'Business Owner', 'Role', 'Lawful Basis', 'Data Categories', 'Data Subject Categories', 
                'Processing Operations', 'Data Source', 'Recipients', 'Third Parties', 'International Transfers', 
                'Transfer Safeguards', 'Retention Period', 'Retention Basis', 'Disposal Mechanism', 
                'Storage Location', 'Technical Safeguards', 'Organizational Safeguards', 'Risk Level', 'Status', 'Review Date'
            ]);

            foreach ($items as $r) {
                fputcsv($out, [
                    $r['ropa_code'],
                    $r['activity_name'],
                    $r['purpose'],
                    $r['department'],
                    $r['data_controller'],
                    $r['business_owner'] ?? 'Data Owner',
                    $r['controller_role'] ?? 'Controller',
                    $r['legal_basis'] ?? 'Legitimate Interest',
                    $r['data_categories'] ?? 'N/A',
                    $r['data_subjects'] ?? 'N/A',
                    $r['processing_operations'] ?? 'Collection, Storage',
                    $r['data_source'] ?? 'Direct Input',
                    $r['recipients'] ?? 'N/A',
                    $r['third_parties'] ?? 'N/A',
                    $r['international_transfers'] ?? 'No',
                    $r['transfer_safeguards'] ?? 'N/A',
                    $r['retention_period'] ?? 'N/A',
                    $r['retention_basis'] ?? 'Legal Obligation',
                    $r['disposal_mechanism'] ?? 'Secure Erasure',
                    $r['storage_location'] ?? 'AWS Cloud',
                    $r['technical_measures'] ?? $r['safeguards'] ?? 'TLS 1.3, AES-256',
                    $r['organizational_measures'] ?? 'RBAC Access Policies',
                    $r['risk_level'] ?? 'Medium',
                    $r['status'],
                    $r['review_date'] ?? 'N/A'
                ]);
            }

            fclose($out);
            exit;
        } else {
            // PDF / Printable HTML report
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Article 30 ROPA Report</title>';
            echo '<style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px; color: #111827; }
                .header { border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
                h1 { font-size: 20px; font-weight: 700; color: #1e3a8a; margin: 0; }
                .meta { font-size: 12px; color: #4b5563; margin-top: 4px; }
                table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 11px; }
                th { background-color: #f3f4f6; color: #1f2937; text-align: left; padding: 8px; border: 1px solid #d1d5db; font-weight: 600; text-transform: uppercase; font-size: 10px; }
                td { padding: 8px; border: 1px solid #e5e7eb; vertical-align: top; }
                tr:nth-child(even) { background-color: #f9fafb; }
                .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
                .badge-active { background: #d1fae5; color: #065f46; }
                .badge-under_review { background: #e0e7ff; color: #3730a3; }
                .badge-approved { background: #dbeafe; color: #1e40af; }
                .badge-inactive { background: #fef3c7; color: #92400e; }
                @media print { .no-print { display: none; } body { padding: 0; } }
            </style></head><body>';

            echo '<div class="header">
                <div>
                    <h1>PrivacyHQ — Article 30 Records of Processing Activities (ROPA) Report</h1>
                    <div class="meta">Export Date: ' . date('Y-m-d H:i:s') . ' | Total Activities: ' . count($items) . '</div>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-weight:600;cursor:pointer;">Print / Save PDF</button>
                </div>
            </div>';

            echo '<table>
                <thead>
                    <tr>
                        <th>ROPA Code</th>
                        <th>Activity Name & Purpose</th>
                        <th>Controller & Role</th>
                        <th>Dept</th>
                        <th>Lawful Basis</th>
                        <th>Data Categories & Subjects</th>
                        <th>Transfers & Safeguards</th>
                        <th>Retention</th>
                        <th>Status</th>
                        <th>Review Date</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($items as $r) {
                $stClass = 'badge-active';
                if ($r['status'] === 'under_review') $stClass = 'badge-under_review';
                else if ($r['status'] === 'approved') $stClass = 'badge-approved';
                else if ($r['status'] === 'inactive') $stClass = 'badge-inactive';

                echo '<tr>
                    <td style="font-family:monospace;font-weight:bold;color:#2563eb;">' . htmlspecialchars($r['ropa_code']) . '</td>
                    <td><strong>' . htmlspecialchars($r['activity_name']) . '</strong><br><span style="color:#6b7280;font-size:10px;">' . htmlspecialchars($r['purpose']) . '</span></td>
                    <td>' . htmlspecialchars($r['data_controller']) . '<br><span style="color:#6b7280;font-size:10px;">' . htmlspecialchars($r['controller_role'] ?? 'Controller') . '</span></td>
                    <td>' . htmlspecialchars($r['department']) . '</td>
                    <td>' . htmlspecialchars($r['legal_basis'] ?? 'Legitimate Interest') . '</td>
                    <td>' . htmlspecialchars($r['data_categories'] ?? 'N/A') . '<br><span style="color:#6b7280;font-size:10px;">Subjects: ' . htmlspecialchars($r['data_subjects'] ?? 'N/A') . '</span></td>
                    <td>Transfer: ' . htmlspecialchars($r['international_transfers'] ?? 'No') . '<br><span style="color:#6b7280;font-size:10px;">' . htmlspecialchars($r['transfer_safeguards'] ?? 'N/A') . '</span></td>
                    <td>' . htmlspecialchars($r['retention_period'] ?? 'N/A') . '</td>
                    <td><span class="badge ' . $stClass . '">' . htmlspecialchars($r['status']) . '</span></td>
                    <td style="font-family:monospace;">' . htmlspecialchars($r['review_date'] ?? 'N/A') . '</td>
                </tr>';
            }

            echo '</tbody></table>
            <script>window.onload = function() { window.print(); };</script>
            </body></html>';
            exit;
        }
    }
}
