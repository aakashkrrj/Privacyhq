<?php
// governance/pages/risk-register.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

// Handle Risk Registration
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_risk'])) {
    $title           = trim($_POST['title'] ?? '');
    $category        = trim($_POST['category'] ?? '');
    $likelihood      = trim($_POST['likelihood'] ?? 'Medium');
    $impact          = trim($_POST['impact'] ?? 'Medium');
    $mitigation      = trim($_POST['mitigation'] ?? '');
    $status_ui       = trim($_POST['status'] ?? 'Open');
    
    // Map UI Status to DB ENUM ('open','mitigated','accepted')
    $st = strtolower($status_ui);
    $status_db = 'open';
    if ($st === 'mitigated') $status_db = 'mitigated';
    else if ($st === 'in review') $status_db = 'open';

    if (!empty($title) && !empty($category)) {
        if (isset($conn) && !$conn->connect_error) {
            
            // 1. Get Assessment ID (Reuse first valid assessment)
            $assessment_id = 1;
            $res = $conn->query("SELECT id FROM privacy_assessments LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $assessment_id = $res->fetch_assoc()['id'];
            }

            // 2. Get User ID (First valid user)
            $created_by = 1;
            $res = $conn->query("SELECT id FROM users LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $created_by = $res->fetch_assoc()['id'];
            }

            // 3. Get or Create Category
            $category_id = null;
            $stmt = $conn->prepare("SELECT id FROM risk_categories WHERE category_name = ? LIMIT 1");
            $stmt->bind_param("s", $category);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $category_id = $res->fetch_assoc()['id'];
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO risk_categories (category_name) VALUES (?)");
                $stmt_insert->bind_param("s", $category);
                $stmt_insert->execute();
                $category_id = $stmt_insert->insert_id;
                $stmt_insert->close();
            }
            $stmt->close();

            // 4. Get or Create Risk Matrix
            $matrix_id = null;
            $likelihood_level = ($likelihood === 'High') ? 3 : (($likelihood === 'Medium') ? 2 : 1);
            $impact_level = ($impact === 'High') ? 3 : (($impact === 'Medium') ? 2 : 1);

            $stmt = $conn->prepare("SELECT id FROM risk_matrix WHERE likelihood_level = ? AND impact_level = ? LIMIT 1");
            $stmt->bind_param("ii", $likelihood_level, $impact_level);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $matrix_id = $res->fetch_assoc()['id'];
            }
            $stmt->close();
            
            if (!$matrix_id) {
                $risk_score = $likelihood_level * $impact_level;
                $risk_level_name = ($risk_score >= 6) ? 'High' : (($risk_score >= 3) ? 'Medium' : 'Low');
                
                $stmt_insert = $conn->prepare("INSERT INTO risk_matrix (impact_level, likelihood_level, impact_name, likelihood_name, risk_score, risk_level_name) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("iissis", $impact_level, $likelihood_level, $impact, $likelihood, $risk_score, $risk_level_name);
                $stmt_insert->execute();
                $matrix_id = $stmt_insert->insert_id;
                $stmt_insert->close();
            }

            // 5. Insert Risk
            $stmt = $conn->prepare("INSERT INTO assessment_risks (assessment_id, risk_category_id, description, inherent_risk_matrix_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisisi", $assessment_id, $category_id, $title, $matrix_id, $status_db, $created_by);
            if ($stmt->execute()) {
                $risk_id = $stmt->insert_id;
                
                // 6. Insert Mitigation
                if (!empty($mitigation)) {
                    $stmt_mit = $conn->prepare("INSERT INTO risk_mitigations (risk_id, implementation_details) VALUES (?, ?)");
                    $stmt_mit->bind_param("is", $risk_id, $mitigation);
                    $stmt_mit->execute();
                    $stmt_mit->close();
                }

                $message = "Risk item registered successfully!";
            } else {
                $message = "Failed to register risk item: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Database connection error.";
        }
    }
}

// Fetch Existing Risks
$risks = [];
if (isset($conn) && !$conn->connect_error) {
    $query = "
        SELECT 
            ar.id,
            ar.description as title,
            rc.category_name as category,
            rm.likelihood_name as likelihood,
            rm.impact_name as impact,
            rm.risk_level_name as risk_level,
            ar.status as status_db,
            rmit.implementation_details as mitigation
        FROM assessment_risks ar
        LEFT JOIN risk_categories rc ON ar.risk_category_id = rc.id
        LEFT JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
        LEFT JOIN risk_mitigations rmit ON ar.id = rmit.risk_id
        ORDER BY ar.id DESC
    ";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Map DB back to UI
            $st = $row['status_db'];
            $status_ui = 'Open';
            if ($st === 'mitigated') $status_ui = 'Mitigated';
            else if ($st === 'accepted') $status_ui = 'In Review'; // arbitrary mapping for accepted

            $row['status'] = $status_ui;
            $row['owner'] = 'Admin User'; // Fallback for UI
            $risks[] = $row;
        }
    }
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
<!-- ================= RISK KPI DASHBOARD ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Risk Dashboard</h3>
    
    <?php
        $total_risks = count($risks);
        $high_risks = count(array_filter($risks, fn($r) => strtolower($r['risk_level'] ?? '') === 'high'));
        $in_review_risks = count(array_filter($risks, fn($r) => strtolower($r['status'] ?? '') === 'in review'));
        $mitigated_risks = count(array_filter($risks, fn($r) => strtolower($r['status'] ?? '') === 'mitigated'));
    ?>

    <div class="form-grid">

        <div style="background:#eef4ff;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">Total Risks</small>
            <h2 style="margin:8px 0;color:#2563eb;"><?= $total_risks ?></h2>
            <small style="color:#6b7280;">Registered risk items</small>
        </div>

        <div style="background:#fee2e2;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">High Risk Items</small>
            <h2 style="margin:8px 0;color:#dc2626;"><?= $high_risks ?></h2>
            <small style="color:#6b7280;">Require immediate action</small>
        </div>

        <div style="background:#fef3c7;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">In Review</small>
            <h2 style="margin:8px 0;color:#d97706;"><?= $in_review_risks ?></h2>
            <small style="color:#6b7280;">Under assessment</small>
        </div>

        <div style="background:#dcfce7;padding:18px;border-radius:8px;">
            <small style="color:#6b7280;">Mitigated Risks</small>
            <h2 style="margin:8px 0;color:#16a34a;"><?= $mitigated_risks ?></h2>
            <small style="color:#6b7280;">Successfully resolved</small>
        </div>

    </div>

</div>

<!-- ================= SEARCH & FILTER ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Search & Filter Risks</h3>

    <div class="form-grid">

        <div class="form-group">
            <label>Risk Title</label>
            <input type="text" placeholder="Search risk...">
        </div>

        <div class="form-group">
            <label>Category</label>
            <select>
                <option>All Categories</option>
                <option>Data Transfer</option>
                <option>Access Control</option>
                <option>Third-Party Vendor</option>
                <option>Data Retention</option>
                <option>Security Governance</option>
            </select>
        </div>

        <div class="form-group">
            <label>Likelihood</label>
            <select>
                <option>All</option>
                <option>High</option>
                <option>Medium</option>
                <option>Low</option>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select>
                <option>All</option>
                <option>Open</option>
                <option>In Review</option>
                <option>Mitigated</option>
            </select>
        </div>

    </div>

    <button class="btn">
        Search Risks
    </button>

</div>

<!-- ================= RISK DISTRIBUTION ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Risk Distribution Overview</h3>
    
    <?php
        $category_counts = [];
        $total_for_pct = $total_risks > 0 ? $total_risks : 1; // avoid division by zero
        foreach ($risks as $r) {
            $cat = trim($r['category'] ?? 'Uncategorized');
            if (!isset($category_counts[$cat])) $category_counts[$cat] = 0;
            $category_counts[$cat]++;
        }
        arsort($category_counts);
        $top_categories = array_slice($category_counts, 0, 5, true);
        $colors = ['#dc2626', '#2563eb', '#16a34a', '#d97706', '#7c3aed'];

        $priority_high = count(array_filter($risks, fn($r) => strtolower($r['risk_level'] ?? '') === 'high'));
        $priority_med = count(array_filter($risks, fn($r) => strtolower($r['risk_level'] ?? '') === 'medium'));
        $priority_low = count(array_filter($risks, fn($r) => strtolower($r['risk_level'] ?? '') === 'low'));
    ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;">

        <div>
            <?php if (empty($top_categories)): ?>
                <p style="color:#6b7280; font-size:0.9rem;">No data available.</p>
            <?php else: ?>
                <?php $cIdx = 0; foreach ($top_categories as $cat => $cCount): 
                    $pct = round(($cCount / $total_for_pct) * 100);
                    $color = $colors[$cIdx % count($colors)];
                ?>
                    <p style="margin-bottom:8px;"><?= htmlspecialchars($cat) ?> (<?= $pct ?>%)</p>
                    <div style="height:8px;background:#e5e7eb;border-radius:5px;">
                        <div style="width:<?= $pct ?>%;height:8px;background:<?= $color ?>;border-radius:5px;"></div>
                    </div>
                    <br>
                <?php $cIdx++; endforeach; ?>
            <?php endif; ?>
        </div>

        <div>

            <table style="width:100%;border-collapse:collapse;">

                <tr>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #ddd;">Priority</th>
                    <th style="text-align:right;padding:10px;border-bottom:1px solid #ddd;">Count</th>
                </tr>

                <tr>
                    <td>High</td>
                    <td style="text-align:right;color:#dc2626;font-weight:bold;"><?= $priority_high ?></td>
                </tr>

                <tr>
                    <td>Medium</td>
                    <td style="text-align:right;color:#d97706;font-weight:bold;"><?= $priority_med ?></td>
                </tr>

                <tr>
                    <td>Low</td>
                    <td style="text-align:right;color:#16a34a;font-weight:bold;"><?= $priority_low ?></td>
                </tr>

                <tr>
                    <td>Total Risks</td>
                    <td style="text-align:right;font-weight:bold;"><?= $total_risks ?></td>
                </tr>

            </table>

        </div>

    </div>

</div>
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
        <!-- ================= RISK HEAT MATRIX ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Risk Heat Matrix</h3>

    <div style="overflow-x:auto;">

        <table style="text-align:center;">

            <tr>
                <th></th>
                <th>Low Impact</th>
                <th>Medium Impact</th>
                <th>High Impact</th>
            </tr>

            <tr>
                <th>High Likelihood</th>
                <td style="background:#fde68a;">Monitor</td>
                <td style="background:#fb923c;color:white;">High</td>
                <td style="background:#dc2626;color:white;">Critical</td>
            </tr>

            <tr>
                <th>Medium Likelihood</th>
                <td style="background:#bbf7d0;">Low</td>
                <td style="background:#fde68a;">Medium</td>
                <td style="background:#fb923c;color:white;">High</td>
            </tr>

            <tr>
                <th>Low Likelihood</th>
                <td style="background:#dcfce7;">Minimal</td>
                <td style="background:#bbf7d0;">Low</td>
                <td style="background:#fde68a;">Medium</td>
            </tr>

        </table>

    </div>

</div>

<!-- ================= COMPLIANCE SUMMARY ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Compliance Summary</h3>

    <div class="form-grid">

        <div style="background:#eef4ff;padding:18px;border-radius:8px;">
            <small>Controls Implemented</small>
            <h2 style="margin-top:8px;color:#2563eb;">94%</h2>
        </div>

        <div style="background:#dcfce7;padding:18px;border-radius:8px;">
            <small>Mitigation Success</small>
            <h2 style="margin-top:8px;color:#16a34a;">89%</h2>
        </div>

        <div style="background:#fee2e2;padding:18px;border-radius:8px;">
            <small>Critical Risks</small>
            <h2 style="margin-top:8px;color:#dc2626;">3</h2>
        </div>

        <div style="background:#fef3c7;padding:18px;border-radius:8px;">
            <small>Pending Reviews</small>
            <h2 style="margin-top:8px;color:#d97706;">11</h2>
        </div>

    </div>

</div>

<!-- ================= RECENT RISK ACTIVITY ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Recent Risk Activity</h3>

    <table>

        <tr>
            <th>Risk Event</th>
            <th>Status</th>
            <th>Updated</th>
        </tr>

        <tr>
            <td>Encryption policy updated</td>
            <td style="color:#16a34a;font-weight:bold;">Mitigated</td>
            <td>Today</td>
        </tr>

        <tr>
            <td>Third-party vendor review</td>
            <td style="color:#2563eb;font-weight:bold;">In Review</td>
            <td>Today</td>
        </tr>

        <tr>
            <td>Access control assessment</td>
            <td style="color:#dc2626;font-weight:bold;">Open</td>
            <td>Yesterday</td>
        </tr>

        <tr>
            <td>Retention policy revised</td>
            <td style="color:#16a34a;font-weight:bold;">Mitigated</td>
            <td>Yesterday</td>
        </tr>

    </table>

</div>

<!-- ================= QUICK ACTIONS ================= -->

<div class="card">

    <h3 style="margin-bottom:20px;">Quick Actions</h3>

    <div class="form-grid">

        <button class="btn">
            + Log Risk
        </button>

        <button class="btn" style="background:#2563eb;">
            Export Register
        </button>

        <button class="btn" style="background:#16a34a;">
            Generate Report
        </button>

        <button class="btn" style="background:#7c3aed;">
            Review Controls
        </button>

    </div>

</div>
    </div>
</body>
</html>