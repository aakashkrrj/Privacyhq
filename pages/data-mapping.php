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
        <!-- ================= KPI DASHBOARD ================= -->

<div class="row" style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:25px;">

    <div class="card" style="flex:1;min-width:220px;">
        <small style="color:#6b7280;">Total Data Flows</small>
        <h2 style="margin-top:8px;color:#2563eb;">148</h2>
    </div>

    <div class="card" style="flex:1;min-width:220px;">
        <small style="color:#6b7280;">Connected Systems</small>
        <h2 style="margin-top:8px;color:#10b981;">24</h2>
    </div>

    <div class="card" style="flex:1;min-width:220px;">
        <small style="color:#6b7280;">High Risk Flows</small>
        <h2 style="margin-top:8px;color:#ef4444;">18</h2>
    </div>

    <div class="card" style="flex:1;min-width:220px;">
        <small style="color:#6b7280;">Encrypted Flows</small>
        <h2 style="margin-top:8px;color:#7c3aed;">92%</h2>
    </div>

</div>

<!-- ================= SEARCH & FILTER ================= -->

<div class="card">

    <h3 style="margin-bottom:18px;">Search & Filter Data Flows</h3>

    <div style="display:grid;
                grid-template-columns:2fr 1fr 1fr 1fr auto;
                gap:15px;">

        <input
            type="text"
            placeholder="Search Source or Destination">

        <select>
            <option>All Sources</option>
            <option>Web App</option>
            <option>CRM</option>
            <option>Database</option>
            <option>Cloud</option>
        </select>

        <select>
            <option>Risk Level</option>
            <option>High</option>
            <option>Medium</option>
            <option>Low</option>
        </select>

        <select>
            <option>Status</option>
            <option>Encrypted</option>
            <option>In Transit</option>
            <option>Plaintext</option>
        </select>

        <button class="btn">
            Search
        </button>

    </div>

</div>

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
        <!-- ================= DATA FLOW VISUALIZATION ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Data Flow Visualization</h3>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;text-align:center;gap:15px;">

        <div style="flex:1;">
            <div style="padding:18px;background:#eef2ff;border-radius:10px;">
                <strong>🌐 Web Portal</strong>
            </div>
        </div>

        <div style="font-size:28px;color:#6b7280;">➜</div>

        <div style="flex:1;">
            <div style="padding:18px;background:#ecfeff;border-radius:10px;">
                <strong>⚙ API Gateway</strong>
            </div>
        </div>

        <div style="font-size:28px;color:#6b7280;">➜</div>

        <div style="flex:1;">
            <div style="padding:18px;background:#ecfccb;border-radius:10px;">
                <strong>🗄 PostgreSQL</strong>
            </div>
        </div>

        <div style="font-size:28px;color:#6b7280;">➜</div>

        <div style="flex:1;">
            <div style="padding:18px;background:#fff7ed;border-radius:10px;">
                <strong>☁ Salesforce CRM</strong>
            </div>
        </div>

    </div>

</div>

<!-- ================= MAPPING STATISTICS ================= -->

<div style="display:grid;
            grid-template-columns:repeat(auto-fit,minmax(340px,1fr));
            gap:20px;
            margin-bottom:25px;">

    <!-- Encryption Coverage -->

    <div class="card">

        <h3>Encryption Coverage</h3>

        <br>

        <p>Encrypted</p>

        <div style="background:#e5e7eb;border-radius:10px;height:10px;overflow:hidden;">
            <div style="width:92%;height:10px;background:#10b981;"></div>
        </div>

        <small>92%</small>

        <br><br>

        <p>In Transit Only</p>

        <div style="background:#e5e7eb;border-radius:10px;height:10px;overflow:hidden;">
            <div style="width:6%;height:10px;background:#f59e0b;"></div>
        </div>

        <small>6%</small>

        <br><br>

        <p>Plaintext</p>

        <div style="background:#e5e7eb;border-radius:10px;height:10px;overflow:hidden;">
            <div style="width:2%;height:10px;background:#ef4444;"></div>
        </div>

        <small>2%</small>

    </div>

    <!-- Flow Distribution -->

    <div class="card">

        <h3>Flow Distribution</h3>

        <table style="margin-top:18px;">

            <tr>
                <td>Internal Systems</td>
                <td><strong>64%</strong></td>
            </tr>

            <tr>
                <td>Cloud Services</td>
                <td><strong>22%</strong></td>
            </tr>

            <tr>
                <td>Third-Party APIs</td>
                <td><strong>14%</strong></td>
            </tr>

        </table>

    </div>

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
        <!-- ================= RECENT MAPPING ACTIVITY ================= -->

<div class="card">

    <h3>Recent Mapping Activity</h3>

    <table style="margin-top:18px;">

        <thead>
            <tr>
                <th>Date</th>
                <th>Source</th>
                <th>Destination</th>
                <th>Action</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

            <tr>
                <td>27 Jul 2026</td>
                <td>Customer Portal</td>
                <td>CRM</td>
                <td>New Flow Added</td>
                <td><span class="badge badge-low">Completed</span></td>
            </tr>

            <tr>
                <td>26 Jul 2026</td>
                <td>Website</td>
                <td>PostgreSQL</td>
                <td>Mapping Updated</td>
                <td><span class="badge badge-medium">Review</span></td>
            </tr>

            <tr>
                <td>25 Jul 2026</td>
                <td>HRMS</td>
                <td>AWS S3</td>
                <td>Encryption Enabled</td>
                <td><span class="badge badge-low">Completed</span></td>
            </tr>

            <tr>
                <td>24 Jul 2026</td>
                <td>Finance App</td>
                <td>Analytics</td>
                <td>Risk Assessment</td>
                <td><span class="badge badge-high">High Risk</span></td>
            </tr>

        </tbody>

    </table>

</div>

<!-- ================= QUICK ACTIONS ================= -->

<div class="card">

    <h3>Quick Actions</h3>

    <div style="display:flex;
                flex-wrap:wrap;
                gap:15px;
                margin-top:20px;">

        <button onclick="alert('Coming Soon: Feature under development.');" class="btn">+ Create Mapping</button>

        <button onclick="alert('Coming Soon: Feature under development.');" class="btn">Import CSV</button>

        <button onclick="alert('Coming Soon: Feature under development.');" class="btn">Export Inventory</button>

        <button onclick="alert('Coming Soon: Feature under development.');" class="btn">Generate Report</button>

    </div>

</div>

<!-- ================= OVERALL MAPPING HEALTH ================= -->

<div class="card">

    <h3>Overall Mapping Health</h3>

    <div style="display:grid;
                grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
                gap:20px;
                margin-top:20px;">

        <div style="padding:18px;background:#ecfdf5;border-radius:10px;">
            <strong>Compliance Coverage</strong>
            <h2 style="color:#059669;margin-top:10px;">96%</h2>
        </div>

        <div style="padding:18px;background:#eff6ff;border-radius:10px;">
            <strong>Mapped Systems</strong>
            <h2 style="color:#2563eb;margin-top:10px;">24</h2>
        </div>

        <div style="padding:18px;background:#fff7ed;border-radius:10px;">
            <strong>Pending Reviews</strong>
            <h2 style="color:#d97706;margin-top:10px;">7</h2>
        </div>

        <div style="padding:18px;background:#fef2f2;border-radius:10px;">
            <strong>Critical Flows</strong>
            <h2 style="color:#dc2626;margin-top:10px;">3</h2>
        </div>

    </div>

</div>
    </div>
</body>
</html>