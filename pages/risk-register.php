<?php
// governance/pages/risk-register.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

/** @var PDO $pdo */

// Handle Risk Registration
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_risk'])) {
    $title           = trim($_POST['title'] ?? '');
    $category        = trim($_POST['category'] ?? '');
    $likelihood      = trim($_POST['likelihood'] ?? 'Medium');
    $impact          = trim($_POST['impact'] ?? 'Medium');
    $mitigation      = trim($_POST['mitigation'] ?? '');
    $owner           = trim($_POST['owner'] ?? '');
    $status          = trim($_POST['status'] ?? 'Open');

    if (!empty($title) && !empty($category)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO risk_register (title, category, likelihood, impact, mitigation, owner, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $category, $likelihood, $impact, $mitigation, $owner, $status])) {
                $message = "Risk item registered successfully!";
            } else {
                $message = "Failed to register risk item.";
            }
        } else {
            $message = "Database connection error.";
        }
    }
}

// Fetch Existing Risks
$risks = [];
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT * FROM risk_register ORDER BY id DESC");
        if ($stmt) {
            $risks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Graceful fallback
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risk Register - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 20px; font-family: system-ui, sans-serif; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #dc2626; color: white; padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #b91c1c; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
        th { background-color: #f9fafb; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-high { background: #fee2e2; color: #991b1b; }
        .badge-status-open { background: #fee2e2; color: #991b1b; }
        .badge-status-mitigated { background: #d1fae5; color: #065f46; }
        .badge-status-in-review { background: #e0e7ff; color: #3730a3; }
        .alert { padding: 10px 15px; background: #d1fae5; color: #065f46; border-radius: 4px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Privacy & Compliance Risk Register</h2>
        <p>Log, assess, and manage privacy risks, impact assessments, and remediation plans.</p>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Create Risk Entry Form -->
        <div class="card">
            <h3>Log New Risk Item</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Risk Title / Description</label>
                        <input type="text" name="title" placeholder="e.g., Unencrypted S3 bucket storing user backups" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="Data Transfer">Data Transfer</option>
                            <option value="Access Control">Access Control</option>
                            <option value="Third-Party Vendor">Third-Party Vendor</option>
                            <option value="Data Retention">Data Retention</option>
                            <option value="Security Governance">Security Governance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Likelihood</label>
                        <select name="likelihood">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Impact Rating</label>
                        <select name="impact">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Risk Owner / Lead</label>
                        <input type="text" name="owner" placeholder="e.g., DevOps / DPO Team">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Open">Open</option>
                            <option value="In Review">In Review</option>
                            <option value="Mitigated">Mitigated</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Mitigation Strategy / Action Plan</label>
                    <textarea name="mitigation" rows="2" placeholder="Describe controls, policies, or technical fixes to resolve this risk..."></textarea>
                </div>
                <button type="submit" name="add_risk" class="btn">+ Log Risk Entry</button>
            </form>
        </div>

        <!-- Risk Register Table -->
        <div class="card">
            <h3>Active Risk Matrix</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Risk Title</th>
                            <th>Category</th>
                            <th>Likelihood</th>
                            <th>Impact</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Mitigation Strategy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($risks)): ?>
                            <?php foreach ($risks as $risk): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($risk['title']) ?></strong></td>
                                    <td><?= htmlspecialchars($risk['category']) ?></td>
                                    <td>
                                        <?php 
                                            $lh = strtolower($risk['likelihood'] ?? 'medium');
                                            $badge_lh = ($lh === 'high') ? 'badge-high' : (($lh === 'medium') ? 'badge-medium' : 'badge-low');
                                        ?>
                                        <span class="badge <?= $badge_lh ?>"><?= htmlspecialchars($risk['likelihood']) ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $imp = strtolower($risk['impact'] ?? 'medium');
                                            $badge_imp = ($imp === 'high') ? 'badge-high' : (($imp === 'medium') ? 'badge-medium' : 'badge-low');
                                        ?>
                                        <span class="badge <?= $badge_imp ?>"><?= htmlspecialchars($risk['impact']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($risk['owner'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <?php 
                                            $st = strtolower($risk['status'] ?? 'open');
                                            $badge_st = ($st === 'mitigated') ? 'badge-status-mitigated' : (($st === 'in review') ? 'badge-status-in-review' : 'badge-status-open');
                                        ?>
                                        <span class="badge <?= $badge_st ?>"><?= htmlspecialchars($risk['status']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($risk['mitigation']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #6b7280;">No risks logged in the matrix yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>