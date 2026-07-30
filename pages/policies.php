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

        <!-- Policy Repository Table -->
        <div class="card p-4 border-0 shadow-sm">
            <h5 class="fw-bold mb-3">Policy Repository</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Document Title</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Last Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($policies)): ?>
                            <?php foreach ($policies as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                                    <td><span class="badge bg-secondary">v<?= htmlspecialchars($p['version']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $p['status'] === 'Active' ? 'success' : ($p['status'] === 'Draft' ? 'warning' : 'dark') ?>">
                                            <?= htmlspecialchars($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($p['updated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">No policy documents generated yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>