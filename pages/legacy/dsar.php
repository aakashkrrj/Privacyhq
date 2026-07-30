<?php
// governance/pages/dsar.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';
include_once __DIR__ . '/audit-logs.php';

/** @var PDO $pdo */

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_request'])) {
    $subject_email = trim($_POST['subject_email'] ?? '');
    $request_type  = trim($_POST['request_type'] ?? 'Data Export');
    $due_date      = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));

    if (!empty($subject_email)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO dsar_requests (subject_email, request_type, due_date) VALUES (?, ?, ?)");
            if (function_exists('log_audit_event')) {
    log_audit_event('admin@privacyhq.io', 'Created DSAR Request', 'DSAR', 'Info', "Subject: $subject_email");
}
        }
    }
}

$requests = [];
try {
    if (isset($pdo)) {
        $requests = $pdo->query("SELECT * FROM dsar_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Rights Requests (DSAR) - PrivacyHQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>body { background-color: #f8f9fa; padding-bottom: 80px; }</style>
</head>
<body>
    <div class="container my-4">
        <h2 class="fw-bold"><i class="bi bi-person-check text-primary me-2"></i>Subject Rights Requests (DSAR)</h2>
        <p class="text-muted">Track customer privacy rights requests (Data Exports, Right to Erasure, Access Requests).</p>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Log New Request Form -->
        <div class="card p-4 border-0 shadow-sm mb-4">
            <h5 class="fw-bold mb-3">+ Log Customer DSAR Request</h5>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Subject / User Email</label>
                        <input type="email" name="subject_email" class="form-control" placeholder="user@example.com" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Request Type</label>
                        <select name="request_type" class="form-select">
                            <option value="Data Export">Data Export</option>
                            <option value="Deletion / Right to be Forgotten">Deletion / Right to be Forgotten</option>
                            <option value="Access">Access Request</option>
                            <option value="Rectification">Rectification</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Compliance Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_request" class="btn btn-primary fw-semibold">Submit Request</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="card p-4 border-0 shadow-sm">
            <h5 class="fw-bold mb-3">Active & Historical DSAR Requests</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Subject Email</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Requested On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['subject_email']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['request_type']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $r['status'] === 'Completed' ? 'success' : ($r['status'] === 'New' ? 'primary' : 'warning') ?>">
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-danger fw-semibold"><?= htmlspecialchars($r['due_date']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($r['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No DSAR requests registered yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>