<?php
// governance/pages/data-mapping.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_flow'])) {
    $source_system     = trim($_POST['source_system'] ?? '');
    $target_system     = trim($_POST['target_system'] ?? '');
    $data_type         = trim($_POST['data_type'] ?? '');
    $transfer_method   = trim($_POST['transfer_method'] ?? '');
    $encryption_status = trim($_POST['encryption_status'] ?? '');
    $risk_level        = trim($_POST['risk_level'] ?? '');

   if (!empty($source_system) && !empty($target_system)) {
        if (isset($pdo)) {
            /** @var PDO $pdo */
            $stmt = $pdo->prepare("INSERT INTO data_flows (source_system, target_system, data_type, transfer_method, encryption_status, risk_level) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$source_system, $target_system, $data_type, $transfer_method, $encryption_status, $risk_level])) {
                $message = "Data Flow added successfully!";
            } else {
                $message = "Failed to record data flow.";
            }
        } else {
            $message = "Database connection error.";
        }
    }
}

// Fetch Existing Flows
// Fetch Existing Flows
$flows = [];
try {
    if (isset($pdo)) {
        /** @var PDO $pdo */
        $stmt = $pdo->query("SELECT * FROM data_flows ORDER BY id DESC");
        if ($stmt) {
            $flows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // Fallback if table is empty or missing
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mapping & Flows - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 20px; font-family: system-ui, sans-serif; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #059669; color: white; padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #047857; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
        th { background-color: #f9fafb; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-high { background: #fee2e2; color: #991b1b; }
        .alert { padding: 10px 15px; background: #d1fae5; color: #065f46; border-radius: 4px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Data Flow & Inventory Mapping</h2>
        <p>Track how personal data travels across internal services, databases, and third-party API destinations.</p>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Add Data Mapping Flow Form -->
        <div class="card">
            <h3>Register Data Pipeline / Flow</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Source System</label>
                        <input type="text" name="source_system" placeholder="e.g., Web App Frontend" required>
                    </div>
                    <div class="form-group">
                        <label>Target / Destination System</label>
                        <input type="text" name="target_system" placeholder="e.g., PostgreSQL DB / Hubspot" required>
                    </div>
                    <div class="form-group">
                        <label>Data Type / Payload</label>
                        <input type="text" name="data_type" placeholder="e.g., User Email, PII, Billing Data">
                    </div>
                    <div class="form-group">
                        <label>Transfer Protocol</label>
                        <input type="text" name="transfer_method" placeholder="e.g., REST API (HTTPS), SFTP">
                    </div>
                    <div class="form-group">
                        <label>Encryption Status</label>
                        <select name="encryption_status">
                            <option value="Encrypted in Transit & Rest">Encrypted in Transit & Rest</option>
                            <option value="In Transit Only">In Transit Only</option>
                            <option value="None / Plaintext">None / Plaintext</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Risk Rating</label>
                        <select name="risk_level">
                            <option value="Low">Low Risk</option>
                            <option value="Medium">Medium Risk</option>
                            <option value="High">High Risk</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_flow" class="btn">+ Map Data Flow</button>
            </form>
        </div>

        <!-- Data Mapping Inventory -->
        <div class="card">
            <h3>Data Pipeline Registry</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Data Types</th>
                            <th>Protocol</th>
                            <th>Security</th>
                            <th>Risk Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($flows)): ?>
                            <?php foreach ($flows as $flow): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($flow['source_system']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($flow['target_system']) ?></strong></td>
                                    <td><?= htmlspecialchars($flow['data_type']) ?></td>
                                    <td><?= htmlspecialchars($flow['transfer_method']) ?></td>
                                    <td><?= htmlspecialchars($flow['encryption_status']) ?></td>
                                    <td>
                                        <?php 
                                            $risk = strtolower($flow['risk_level'] ?? 'low');
                                            $badge_class = 'badge-low';
                                            if ($risk === 'medium') $badge_class = 'badge-medium';
                                            if ($risk === 'high') $badge_class = 'badge-high';
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($flow['risk_level']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #6b7280;">No data flows mapped yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>