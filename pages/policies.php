<?php
// governance/pages/policies.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';
include_once __DIR__ . '/audit-logs.php';

/** @var PDO $pdo */

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_policy'])) {
    $title   = trim($_POST['title'] ?? '');
    $version = trim($_POST['version'] ?? '1.0');
    $status  = trim($_POST['status'] ?? 'Draft');
    $content = trim($_POST['content'] ?? '');

    /**
 * Global Audit Log Helper
 */
if (!function_exists('log_audit_event')) {
    function log_audit_event($user, $action, $module, $severity = 'Info', $details = '') {
        global $pdo;
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO audit_logs (user_name, action, module, severity, details) VALUES (?, ?, ?, ?, ?)");
                return $stmt->execute([$user, $action, $module, $severity, $details]);
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}
        }

$policies = [];
try {
    if (isset($pdo)) {
        $policies = $pdo->query("SELECT * FROM policies ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Generator & Version Control - PrivacyHQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>body { background-color: #f8f9fa; padding-bottom: 80px; }</style>
</head>
<body>
    <div class="container my-4">
        <h2 class="fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Policy Generator & Version Control</h2>
        <p class="text-muted">Create, edit, and maintain version history for compliance policies (Privacy Policy, Incident Response Plan, Security Policy).</p>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Form: Create/Draft Policy -->
        <div class="card p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-3">+ Create New Policy / Document</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Policy Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Global Privacy Policy, Incident Response Plan" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Version</label>
                        <input type="text" name="version" class="form-control" value="1.0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Draft" selected>Draft</option>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Policy Document Content</label>
                        <textarea name="content" class="form-control" rows="5" placeholder="Insert policy text or legal framework guidelines here..."></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_policy" class="btn btn-primary fw-semibold">Save Policy Document</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ================= QUICK ACTIONS ================= -->
        <div class="card p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-3">Quick Actions</h5>
            <div class="d-flex flex-wrap gap-2">
                <button id="btn-create-policy-qa" class="btn btn-primary fw-semibold">+ Create Policy</button>
                <button id="btn-upload-policy-qa" class="btn btn-success fw-semibold"><i class="bi bi-upload me-1"></i> Upload Policy</button>
                <button id="btn-version-history-qa" class="btn btn-secondary fw-semibold"><i class="bi bi-history me-1"></i> Version History</button>
                <button id="btn-approval-workflow-qa" class="btn btn-warning fw-semibold"><i class="bi bi-check-circle me-1"></i> Approval Workflow</button>
            </div>
        </div>

        <!-- ================= KPI DASHBOARD ================= -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm bg-primary bg-opacity-10 text-primary">
                    <small class="fw-semibold">Total Documents</small>
                    <h2 class="fw-bold my-1" id="kpi-total">...</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm bg-success bg-opacity-10 text-success">
                    <small class="fw-semibold">Active Policies</small>
                    <h2 class="fw-bold my-1" id="kpi-active">...</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm bg-warning bg-opacity-10 text-warning">
                    <small class="fw-semibold">Draft Policies</small>
                    <h2 class="fw-bold my-1" id="kpi-draft">...</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 border-0 shadow-sm bg-secondary bg-opacity-10 text-secondary">
                    <small class="fw-semibold">Archived Policies</small>
                    <h2 class="fw-bold my-1" id="kpi-archived">...</h2>
                </div>
            </div>
        </div>

        <!-- Policy Repository Table -->
        <div class="card p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-3">Policy Repository</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Document Title</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Effective Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="policyTableBody">
                        <tr><td colspan="5" class="text-center text-muted">Loading policies...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Create Policy -->
    <div class="modal fade" id="createPolicyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="modalCreatePolicyForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Policy Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Incident Response Plan" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Version</label>
                                <input type="text" name="version" class="form-control" value="1.0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft" selected>Draft</option>
                                    <option value="Active">Active</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Policy Document Content</label>
                                <textarea name="content" class="form-control" rows="6" placeholder="Insert policy text here..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Policy Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Upload Policy -->
    <div class="modal fade" id="uploadPolicyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Policy Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="modalUploadPolicyForm" enctype="multipart/form-data">
                    <div class="modal-body space-y-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Policy Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Security Compliance Policy">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Version</label>
                                <input type="text" name="version" class="form-control" value="1.0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Draft" selected>Draft</option>
                                    <option value="Active">Active</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select File (PDF, TXT, DOCX - Max 5MB)</label>
                            <input type="file" name="policy_file" class="form-control" accept=".pdf,.txt,.docx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Upload File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Version History -->
    <div class="modal fade" id="versionHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Version History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Multi-version document history is simulated based on recorded entries with identical policy names.
                    </p>
                    <table class="table table-sm text-sm">
                        <thead>
                            <tr class="table-light">
                                <th>Version</th>
                                <th>Status</th>
                                <th>Effective Date</th>
                                <th>File Path / Content</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr><td colspan="4" class="text-center text-muted">Select a policy to view history.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Approval Workflow -->
    <div class="modal fade" id="approvalWorkflowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Approval Workflow (Pending Policies)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table align-middle">
                        <thead>
                            <tr class="table-light">
                                <th>Policy Title</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="approvalTableBody">
                            <tr><td colspan="4" class="text-center text-muted">No pending approvals.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const G_CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
    </script>
    <script src="assets/js/policies.js"></script>
</body>
</html>