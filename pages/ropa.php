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
<!-- ================= ROPA KPI DASHBOARD ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">ROPA Dashboard</h3>

    <div class="form-grid">

        <div style="background:#eef4ff;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">Total Processing Activities</small>
            <h2 style="margin:8px 0;color:#2563eb;"><?= count($records) ?></h2>
            <small style="color:#6b7280;">Registered activities</small>
        </div>

        <div style="background:#ecfdf5;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">Data Controllers</small>
            <h2 style="margin:8px 0;color:#059669;">12</h2>
            <small style="color:#6b7280;">Across departments</small>
        </div>

        <div style="background:#fff7ed;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">Active Activities</small>
            <h2 style="margin:8px 0;color:#ea580c;">24</h2>
            <small style="color:#6b7280;">Currently monitored</small>
        </div>

        <div style="background:#f3f4f6;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">Avg. Retention</small>
            <h2 style="margin:8px 0;color:#374151;">7 Years</h2>
            <small style="color:#6b7280;">Average record retention</small>
        </div>

    </div>

</div>

<!-- ================= SEARCH & FILTER ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Search & Filter Activities</h3>

    <div class="form-grid">

        <div class="form-group">
            <label>Search Activity</label>
            <input type="text" placeholder="Customer Onboarding">
        </div>

        <div class="form-group">
            <label>Controller</label>
            <select>
                <option>All Controllers</option>
                <option>HR</option>
                <option>Finance</option>
                <option>IT</option>
                <option>Marketing</option>
            </select>
        </div>

        <div class="form-group">
            <label>Data Subjects</label>
            <select>
                <option>All Subjects</option>
                <option>Customers</option>
                <option>Employees</option>
                <option>Vendors</option>
                <option>Partners</option>
            </select>
        </div>

        <div class="form-group">
            <label>Retention</label>
            <select>
                <option>All Periods</option>
                <option>1 Year</option>
                <option>3 Years</option>
                <option>5 Years</option>
                <option>7+ Years</option>
            </select>
        </div>

    </div>

    <button class="btn">
        Search Records
    </button>

</div>

<!-- ================= PROCESSING OVERVIEW ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Processing Activities Overview</h3>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;">

        <div>

            <p style="margin-bottom:8px;">Customer Operations (38%)</p>

            <div style="height:8px;background:#e5e7eb;border-radius:5px;">
                <div style="width:38%;height:8px;background:#2563eb;border-radius:5px;"></div>
            </div>

            <br>

            <p style="margin-bottom:8px;">HR Operations (27%)</p>

            <div style="height:8px;background:#e5e7eb;border-radius:5px;">
                <div style="width:27%;height:8px;background:#16a34a;border-radius:5px;"></div>
            </div>

            <br>

            <p style="margin-bottom:8px;">Finance (18%)</p>

            <div style="height:8px;background:#e5e7eb;border-radius:5px;">
                <div style="width:18%;height:8px;background:#ea580c;border-radius:5px;"></div>
            </div>

            <br>

            <p style="margin-bottom:8px;">Marketing (17%)</p>

            <div style="height:8px;background:#e5e7eb;border-radius:5px;">
                <div style="width:17%;height:8px;background:#9333ea;border-radius:5px;"></div>
            </div>

        </div>

        <div>

            <table style="width:100%;border-collapse:collapse;">

                <tr>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #ddd;">Category</th>
                    <th style="text-align:right;padding:10px;border-bottom:1px solid #ddd;">Activities</th>
                </tr>

                <tr>
                    <td style="padding:10px;">Customer Data</td>
                    <td style="text-align:right;">16</td>
                </tr>

                <tr>
                    <td style="padding:10px;">Employee Data</td>
                    <td style="text-align:right;">11</td>
                </tr>

                <tr>
                    <td style="padding:10px;">Financial Records</td>
                    <td style="text-align:right;">9</td>
                </tr>

                <tr>
                    <td style="padding:10px;">Marketing Data</td>
                    <td style="text-align:right;">7</td>
                </tr>

            </table>

        </div>

    </div>

</div>
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
        <!-- ================= DATA LIFECYCLE ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Personal Data Lifecycle</h3>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;text-align:center;">

        <div style="flex:1;min-width:120px;">
            <div style="width:55px;height:55px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;font-weight:bold;color:#2563eb;">
                1
            </div>
            <p style="margin-top:12px;font-weight:600;">Collect</p>
        </div>

        <div style="font-size:28px;color:#9ca3af;">→</div>

        <div style="flex:1;min-width:120px;">
            <div style="width:55px;height:55px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;font-weight:bold;color:#d97706;">
                2
            </div>
            <p style="margin-top:12px;font-weight:600;">Store</p>
        </div>

        <div style="font-size:28px;color:#9ca3af;">→</div>

        <div style="flex:1;min-width:120px;">
            <div style="width:55px;height:55px;background:#ede9fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;font-weight:bold;color:#7c3aed;">
                3
            </div>
            <p style="margin-top:12px;font-weight:600;">Process</p>
        </div>

        <div style="font-size:28px;color:#9ca3af;">→</div>

        <div style="flex:1;min-width:120px;">
            <div style="width:55px;height:55px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;font-weight:bold;color:#16a34a;">
                4
            </div>
            <p style="margin-top:12px;font-weight:600;">Share</p>
        </div>

        <div style="font-size:28px;color:#9ca3af;">→</div>

        <div style="flex:1;min-width:120px;">
            <div style="width:55px;height:55px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;font-weight:bold;color:#374151;">
                5
            </div>
            <p style="margin-top:12px;font-weight:600;">Archive</p>
        </div>

        <div style="font-size:28px;color:#9ca3af;">→</div>

        <div style="flex:1;min-width:120px;">
            <div style="width:55px;height:55px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:auto;font-weight:bold;color:#dc2626;">
                6
            </div>
            <p style="margin-top:12px;font-weight:600;">Delete</p>
        </div>

    </div>

</div>

<!-- ================= COMPLIANCE SUMMARY ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Compliance Summary</h3>

    <div class="form-grid">

        <div style="background:#ecfdf5;padding:18px;border-radius:8px;">
            <small>GDPR Coverage</small>
            <h2 style="color:#16a34a;margin-top:8px;">98%</h2>
        </div>

        <div style="background:#fef2f2;padding:18px;border-radius:8px;">
            <small>High Risk Activities</small>
            <h2 style="color:#dc2626;margin-top:8px;">5</h2>
        </div>

        <div style="background:#eef4ff;padding:18px;border-radius:8px;">
            <small>Legal Basis Documented</small>
            <h2 style="color:#2563eb;margin-top:8px;">96%</h2>
        </div>

        <div style="background:#fff7ed;padding:18px;border-radius:8px;">
            <small>Review Due</small>
            <h2 style="color:#ea580c;margin-top:8px;">9</h2>
        </div>

    </div>

</div>

<!-- ================= RECENT ACTIVITY ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Recent Processing Activity</h3>

    <table>

        <tr>
            <th>Activity</th>
            <th>Status</th>
            <th>Time</th>
        </tr>

        <tr>
            <td>Customer Onboarding added</td>
            <td style="color:green;font-weight:bold;">Completed</td>
            <td>Today</td>
        </tr>

        <tr>
            <td>HR Processing updated</td>
            <td style="color:#2563eb;font-weight:bold;">Updated</td>
            <td>Today</td>
        </tr>

        <tr>
            <td>Marketing purpose modified</td>
            <td style="color:#d97706;font-weight:bold;">Review</td>
            <td>Yesterday</td>
        </tr>

        <tr>
            <td>Finance retention changed</td>
            <td style="color:#16a34a;font-weight:bold;">Approved</td>
            <td>Yesterday</td>
        </tr>

    </table>

</div>

<!-- ================= QUICK ACTIONS ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Quick Actions</h3>

    <div class="form-grid">

        <button class="btn">+ Add Activity</button>

        <button class="btn" style="background:#059669;">
            Export Registry
        </button>

        <button class="btn" style="background:#7c3aed;">
            Generate Report
        </button>

        <button class="btn" style="background:#ea580c;">
            Review Processing
        </button>

    </div>

</div>
    </div>
</body>
</html>