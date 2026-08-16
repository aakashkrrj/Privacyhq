<?php
// governance/backend/services/PolicyService.php

namespace Backend\Services;

class PolicyService
{
    private $pdo;
    private $policyModel;

    public function __construct(\PDO $pdo, $policyModel)
    {
        $this->pdo = $pdo;
        $this->policyModel = $policyModel;
    }

    public function getDashboardMetrics()
    {
        return $this->policyModel->getDashboardMetrics();
    }

    public function getList($search = null, $status = null, $category = null, $department = null, $approvalStatus = null, $owner = null, $expired = null, $reviewDue = null, $page = 1, $pageSize = 10, $sortField = 'updated_at', $sortDir = 'DESC')
    {
        $page = max(1, (int)$page);
        $pageSize = max(1, min(100, (int)$pageSize));
        $offset = ($page - 1) * $pageSize;

        return $this->policyModel->getList($search, $status, $category, $department, $approvalStatus, $owner, $expired, $reviewDue, $pageSize, $offset, $sortField, $sortDir);
    }

    public function getPolicyById($id)
    {
        if (empty($id) || !is_numeric($id)) {
            throw new \Exception("Valid policy document ID is required.");
        }
        $policy = $this->policyModel->findById((int)$id);
        if (!$policy) {
            throw new \Exception("Policy document record not found.");
        }
        return $policy;
    }

    public function createPolicy($data, $userId = 1)
    {
        $title = trim($data['title'] ?? $data['policy_name'] ?? '');
        if (empty($title)) {
            throw new \Exception("Policy title is required.");
        }
        if (empty(trim($data['category'] ?? ''))) {
            throw new \Exception("Policy category selection is required.");
        }

        try {
            $this->pdo->beginTransaction();

            $policyId = $this->policyModel->create($data, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Policies',
                    'Create Policy Document',
                    $userId,
                    $policyId,
                    null,
                    json_encode(['title' => $title, 'category' => $data['category'] ?? 'Data Privacy', 'version' => $data['version'] ?? '1.0'])
                );
            }

            $this->pdo->commit();

            // Dispatch workflow task if available
            if (class_exists('\Backend\Services\WorkflowService')) {
                try {
                    \Backend\Services\WorkflowService::dispatch('policy.created', [
                        'module' => 'Policy',
                        'record_id' => $policyId,
                        'title' => $title,
                        'assigned_to' => 1,
                        'created_by' => $userId
                    ]);
                } catch (\Throwable $wt) {}
            }

            return $policyId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function uploadPolicyVersion($policyId, $data, $userId = 1)
    {
        $existing = $this->getPolicyById($policyId);

        try {
            $this->pdo->beginTransaction();

            $versionId = $this->policyModel->uploadVersion($policyId, $data, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Policies',
                    'Upload Policy Version',
                    $userId,
                    $policyId,
                    json_encode(['version' => $existing['version']]),
                    json_encode(['version' => $data['version'] ?? '1.1', 'file_name' => $data['file_name'] ?? ''])
                );
            }

            $this->pdo->commit();

            return $versionId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function submitForApproval($policyId, $comments = '', $userId = 1)
    {
        $existing = $this->getPolicyById($policyId);
        if ($existing['approval_status'] === 'approved' && $existing['status'] === 'active') {
            throw new \Exception("This policy is already approved and active.");
        }

        try {
            $this->pdo->beginTransaction();

            $success = $this->policyModel->recordApprovalAction($policyId, 'Submitted for Approval', 'draft', 'pending_approval', $comments, $userId, 'Author');

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Policies',
                    'Submit Policy Approval',
                    $userId,
                    $policyId,
                    json_encode(['approval_status' => $existing['approval_status']]),
                    json_encode(['approval_status' => 'pending_approval', 'comments' => $comments])
                );
            }

            $this->pdo->commit();

            // Dispatch task for compliance approver
            if (class_exists('\Backend\Services\WorkflowService')) {
                try {
                    \Backend\Services\WorkflowService::dispatch('policy.submitted_for_approval', [
                        'module' => 'Policy',
                        'record_id' => $policyId,
                        'title' => $existing['policy_name'],
                        'assigned_to' => 11, // DPO User ID
                        'created_by' => $userId
                    ]);
                } catch (\Throwable $wt) {}
            }

            return $success;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function processApprovalAction($policyId, $action, $comments = '', $userId = 1, $userRole = 'Compliance Officer')
    {
        $existing = $this->getPolicyById($policyId);

        $newStatus = 'draft';
        $newApproval = 'pending_approval';

        if ($action === 'approve') {
            $newStatus = 'active';
            $newApproval = 'approved';
            $actionLabel = 'Approved Policy';
        } elseif ($action === 'reject') {
            $newStatus = 'draft';
            $newApproval = 'rejected';
            $actionLabel = 'Rejected Policy';
        } elseif ($action === 'request_changes') {
            $newStatus = 'draft';
            $newApproval = 'pending_review';
            $actionLabel = 'Requested Changes';
        } else {
            throw new \Exception("Invalid approval action specified.");
        }

        try {
            $this->pdo->beginTransaction();

            $success = $this->policyModel->recordApprovalAction($policyId, $actionLabel, $newStatus, $newApproval, $comments, $userId, $userRole);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Policies',
                    $actionLabel,
                    $userId,
                    $policyId,
                    json_encode(['approval_status' => $existing['approval_status']]),
                    json_encode(['approval_status' => $newApproval, 'comments' => $comments])
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

    public function getVersions($policyId)
    {
        if (empty($policyId) || !is_numeric($policyId)) {
            throw new \Exception("Valid policy ID is required.");
        }
        return $this->policyModel->getVersions((int)$policyId);
    }

    public function getHistory($policyName)
    {
        if (empty($policyName)) {
            throw new \Exception("Policy name is required.");
        }
        $stmt = $this->pdo->prepare("SELECT * FROM privacy_policies WHERE policy_name = ? AND deleted_at IS NULL ORDER BY id DESC");
        $stmt->execute([$policyName]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getApprovalHistory($policyId)
    {
        if (empty($policyId) || !is_numeric($policyId)) {
            throw new \Exception("Valid policy ID is required.");
        }
        return $this->policyModel->getApprovalHistory((int)$policyId);
    }

    public function deletePolicy($id, $userId = 1)
    {
        $existing = $this->getPolicyById($id);

        try {
            $this->pdo->beginTransaction();

            $success = $this->policyModel->delete($id, $userId);

            if (function_exists('log_audit_event')) {
                log_audit_event(
                    $this->pdo,
                    'Policies',
                    'Delete Policy Document',
                    $userId,
                    $id,
                    json_encode(['title' => $existing['policy_name']]),
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

    public function exportReport($search = null, $status = null, $category = null, $department = null, $approvalStatus = null, $owner = null, $expired = null, $reviewDue = null, $format = 'csv')
    {
        $data = $this->policyModel->getList($search, $status, $category, $department, $approvalStatus, $owner, $expired, $reviewDue, 10000, 0, 'id', 'ASC');
        $items = $data['items'];

        $filename = 'PrivacyHQ_Policy_Library_Report_' . date('Y-m-d');

        if ($format === 'csv' || $format === 'excel') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            $out = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($out, "\EF\xBB\xBF");

            fputcsv($out, ['PrivacyHQ Governance Policy Library Report']);
            fputcsv($out, ['Export Date: ' . date('Y-m-d H:i:s'), 'Total Documents: ' . count($items)]);
            fputcsv($out, []);
            fputcsv($out, [
                'Policy Code', 'Policy Title', 'Category', 'Description', 'Policy Owner', 
                'Department', 'Version', 'Effective Date', 'Review Date', 'Expiry Date', 
                'Status', 'Approval Status', 'File Name', 'File Type', 'File Size (KB)'
            ]);

            foreach ($items as $r) {
                fputcsv($out, [
                    $r['policy_code'],
                    $r['policy_name'],
                    $r['category'] ?? 'Data Privacy',
                    $r['description'] ?? 'N/A',
                    $r['policy_owner'] ?? 'Compliance Team',
                    $r['department'] ?? 'Legal & Compliance',
                    $r['version'] ?? '1.0',
                    $r['effective_date'] ?? 'N/A',
                    $r['review_date'] ?? 'N/A',
                    $r['expiry_date'] ?? 'N/A',
                    $r['status'],
                    $r['approval_status'] ?? $r['status'],
                    $r['file_name'] ?? 'N/A',
                    $r['file_type'] ?? 'N/A',
                    $r['file_size'] ? round($r['file_size'] / 1024, 1) : 0
                ]);
            }

            fclose($out);
            exit;
        } else {
            // Printable HTML PDF report
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Policy Library Governance Report</title>';
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
                .badge-draft { background: #fef3c7; color: #92400e; }
                .badge-approved { background: #dbeafe; color: #1e40af; }
                .badge-pending { background: #e0e7ff; color: #3730a3; }
                @media print { .no-print { display: none; } body { padding: 0; } }
            </style></head><body>';

            echo '<div class="header">
                <div>
                    <h1>PrivacyHQ — Policy Library Governance Report</h1>
                    <div class="meta">Export Date: ' . date('Y-m-d H:i:s') . ' | Total Documents: ' . count($items) . '</div>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-weight:600;cursor:pointer;">Print / Save PDF</button>
                </div>
            </div>';

            echo '<table>
                <thead>
                    <tr>
                        <th>Policy Code</th>
                        <th>Title & Category</th>
                        <th>Owner & Dept</th>
                        <th>Ver</th>
                        <th>Effective Date</th>
                        <th>Review Date</th>
                        <th>Status</th>
                        <th>Approval</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($items as $r) {
                $stClass = 'badge-active';
                if ($r['status'] === 'draft') $stClass = 'badge-draft';

                $apprClass = 'badge-approved';
                if ($r['approval_status'] === 'pending_review' || $r['approval_status'] === 'pending_approval') $apprClass = 'badge-pending';
                elseif ($r['approval_status'] === 'draft') $apprClass = 'badge-draft';

                echo '<tr>
                    <td style="font-family:monospace;font-weight:bold;color:#2563eb;">' . htmlspecialchars($r['policy_code']) . '</td>
                    <td><strong>' . htmlspecialchars($r['policy_name']) . '</strong><br><span style="color:#6b7280;font-size:10px;">Cat: ' . htmlspecialchars($r['category'] ?? 'Data Privacy') . '</span></td>
                    <td>' . htmlspecialchars($r['policy_owner'] ?? 'DPO') . '<br><span style="color:#6b7280;font-size:10px;">' . htmlspecialchars($r['department'] ?? 'Legal') . '</span></td>
                    <td>v' . htmlspecialchars($r['version'] ?? '1.0') . '</td>
                    <td style="font-family:monospace;">' . htmlspecialchars($r['effective_date'] ?? 'N/A') . '</td>
                    <td style="font-family:monospace;">' . htmlspecialchars($r['review_date'] ?? 'N/A') . '</td>
                    <td><span class="badge ' . $stClass . '">' . htmlspecialchars($r['status']) . '</span></td>
                    <td><span class="badge ' . $apprClass . '">' . htmlspecialchars($r['approval_status'] ?? $r['status']) . '</span></td>
                </tr>';
            }

            echo '</tbody></table>
            <script>window.onload = function() { window.print(); };</script>
            </body></html>';
            exit;
        }
    }
}
