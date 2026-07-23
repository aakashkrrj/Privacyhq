<?php
// governance/pages/user-management.php
require_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/bottom-nav.php';

/** @var PDO $pdo */

// Handle User Creation
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = trim($_POST['role'] ?? 'Viewer');
    $status    = trim($_POST['status'] ?? 'Active');

    if (!empty($full_name) && !empty($email)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO system_users (full_name, email, role, status) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $email, $role, $status])) {
                $message = "User account registered successfully!";
            } else {
                $message = "Failed to register user account.";
            }
        } else {
            $message = "Database connection error.";
        }
    }
}

// Fetch Existing Users
$users = [];
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT * FROM system_users ORDER BY id DESC");
        if ($stmt) {
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>User & Roles Management - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 20px; font-family: system-ui, sans-serif; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { background: #0891b2; color: white; padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #0e7490; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
        th { background-color: #f9fafb; font-weight: 600; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #f3f4f6; color: #4b5563; }
        .badge-role { background: #e0e7ff; color: #3730a3; }
        .alert { padding: 10px 15px; background: #d1fae5; color: #065f46; border-radius: 4px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Management & Access Controls (RBAC)</h2>
        <p>Manage system access, assign roles, and grant permissions to platform users.</p>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Create User Form -->
        <div class="card">
            <h3>Add New System User</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="e.g., Alex Mercer" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g., alex@organization.com" required>
                    </div>
                    <div class="form-group">
                        <label>Assign Role</label>
                        <select name="role">
                            <option value="Admin">Admin</option>
                            <option value="DPO">Data Protection Officer (DPO)</option>
                            <option value="Auditor">Auditor</option>
                            <option value="Viewer">Viewer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn">+ Create User Account</button>
            </form>
        </div>

        <!-- User Registry Table -->
        <div class="card">
            <h3>Registered Team Members</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Assigned Role</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><span class="badge badge-role"><?= htmlspecialchars($user['role']) ?></span></td>
                                    <td>
                                        <?php 
                                            $st = strtolower($user['status'] ?? 'active');
                                            $badge_class = ($st === 'active') ? 'badge-active' : 'badge-inactive';
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($user['status']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #6b7280;">No users registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>