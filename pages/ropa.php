<?php
// governance/pages/ropa.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ropa'])) {
    $process_name     = trim($_POST['process_name'] ?? '');
    $data_controller  = trim($_POST['data_controller'] ?? '');
    $purpose          = trim($_POST['purpose'] ?? '');
    $data_categories  = trim($_POST['data_categories'] ?? '');
    $data_subjects    = trim($_POST['data_subjects'] ?? '');
    $recipients       = trim($_POST['recipients'] ?? '');
    $retention_period = trim($_POST['retention_period'] ?? '');

    if (!empty($process_name) && !empty($purpose)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO ropa_records (process_name, data_controller, purpose, data_categories, data_subjects, recipients, retention_period) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$process_name, $data_controller, $purpose, $data_categories, $data_subjects, $recipients, $retention_period])) {
                $message = "ROPA Record added successfully!";
            } else {
                $message = "Failed to record ROPA entry.";
            }
        } else {
            $message = "Database connection error.";
        }
    }
}

// Fetch Existing Records
// Fetch Existing Records
$records = [];
try {
    if (isset($pdo)) {
        /** @var PDO $pdo */
        $stmt = $pdo->query("SELECT * FROM ropa_records ORDER BY id DESC");
        if ($stmt) {
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Graceful fallback if table doesn't exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROPA Management - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 20px; font-family: system-ui, sans-serif; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #2563eb; color: white; padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
        th { background-color: #f9fafb; font-weight: 600; }
        .alert { padding: 10px 15px; background: #d1fae5; color: #065f46; border-radius: 4px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Article 30: Records of Processing Activities (ROPA)</h2>
        <p>Maintain up-to-date documentation of personal data processing operations across your organization.</p>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Add ROPA Form -->
        <div class="card">
            <h3>Add Processing Activity</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Process Name / Activity</label>
                        <input type="text" name="process_name" placeholder="e.g., Customer Onboarding" required>
                    </div>
                    <div class="form-group">
                        <label>Data Controller / Joint Controller</label>
                        <input type="text" name="data_controller" placeholder="e.g., HR Dept / PrivacyHQ Ltd">
                    </div>
                    <div class="form-group">
                        <label>Data Categories</label>
                        <input type="text" name="data_categories" placeholder="e.g., Contact Info, Financial, IP">
                    </div>
                    <div class="form-group">
                        <label>Data Subjects</label>
                        <input type="text" name="data_subjects" placeholder="e.g., Employees, Customers">
                    </div>
                    <div class="form-group">
                        <label>Recipients / Third Parties</label>
                        <input type="text" name="data_recipients" placeholder="e.g., Stripe, AWS, Marketing Vendor">
                    </div>
                    <div class="form-group">
                        <label>Retention Period</label>
                        <input type="text" name="retention_period" placeholder="e.g., 7 Years post-termination">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Purpose of Processing</label>
                    <textarea name="purpose" rows="2" placeholder="Describe the legal basis and purpose..." required></textarea>
                </div>
                <button type="submit" name="add_ropa" class="btn">+ Add ROPA Entry</button>
            </form>
        </div>

        <!-- ROPA Table -->
        <div class="card">
            <h3>ROPA Registry Index</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Process Name</th>
                            <th>Controller</th>
                            <th>Purpose</th>
                            <th>Categories</th>
                            <th>Subjects</th>
                            <th>Retention</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $row): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['process_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['data_controller'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                                    <td><?= htmlspecialchars($row['data_categories']) ?></td>
                                    <td><?= htmlspecialchars($row['data_subjects']) ?></td>
                                    <td><?= htmlspecialchars($row['retention_period']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #6b7280;">No ROPA processing activities logged yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>