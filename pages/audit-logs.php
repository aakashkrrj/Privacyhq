<?php
// governance/pages/audit-logs.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

/** @var PDO $pdo */

// Handle Manual Log Entry (Optional / Testing)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_log'])) {
    $user_name = trim($_POST['user_name'] ?? '');
    $action    = trim($_POST['action'] ?? '');
    $module    = trim($_POST['module'] ?? '');
    $severity  = trim($_POST['severity'] ?? 'Info');
    $details   = trim($_POST['details'] ?? '');

    if (!empty($user_name) && !empty($action)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_name, action, module, severity, details) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_name, $action, $module, $severity, $details])) {
                $message = "Audit log recorded successfully!";
            } else {
                $message = "Failed to record audit log.";
            }
        } else {
            $message = "Database connection error.";
        }
    }
}

// Fetch Existing Logs
$logs = [];
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC");
        if ($stmt) {
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Fallback if table doesn't exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 20px; font-family: system-ui, sans-serif; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #4f46e5; color: white; padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #4338ca; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
        th { background-color: #f9fafb; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .alert { padding: 10px 15px; background: #d1fae5; color: #065f46; border-radius: 4px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Audit Logs & Security Trail</h2>
        <p>Monitor system events, user actions, data modifications, and compliance activities in real-time.</p>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Log Event Form -->
        <div class="card">
            <h3>Record Custom Audit Event</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>User / Service</label>
                        <input type="text" name="user_name" placeholder="e.g., admin@privacyhq.io" required>
                    </div>
                    <div class="form-group">
                        <label>Action Performed</label>
                        <input type="text" name="action" placeholder="e.g., Exported ROPA Records" required>
                    </div>
                    <div class="form-group">
                        <label>Target Module</label>
                        <input type="text" name="module" placeholder="e.g., ROPA / User Access">
                    </div>
                    <div class="form-group">
                        <label>Severity Level</label>
                        <select name="severity">
                            <option value="Info">Info</option>
                            <option value="Warning">Warning</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Event Details / Metadata</label>
                    <textarea name="details" rows="2" placeholder="Provide extra details or metadata..."></textarea>
                </div>
                <button type="submit" name="add_log" class="btn">+ Log Event</button>
            </form>
        </div>

        <!-- Audit Log Table -->
        <div class="card">
            <h3>System Activity Log</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User / Actor</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Severity</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td><strong><?= htmlspecialchars($log['user_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['module']) ?></td>
                                    <td>
                                        <?php 
                                            $sev = strtolower($log['severity'] ?? 'info');
                                            $badge_class = 'badge-info';
                                            if ($sev === 'warning') $badge_class = 'badge-warning';
                                            if ($sev === 'critical') $badge_class = 'badge-critical';
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($log['severity']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($log['details']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #6b7280;">No audit logs recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>