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
        .bg-green-100 { background:#DCFCE7; }
        .text-green-700 { color:#15803D; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .bg-gray-100 { background:#F3F4F6; }
        .text-gray-700 { color:#374151; }
        .badge-inactive { background: #f3f4f6; color: #4b5563; }
        .badge-role { background: #e0e7ff; color: #3730a3; }
        .alert { padding: 10px 15px; background: #d1fae5; color: #065f46; border-radius: 4px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="max-w-screen-xl mt-6 mx-auto px-6 py-6">
        <h2 class="text-3xl font-bold text-on-surface">
    User Management & Access Control
</h2>
        <p class="text-on-surface-variant mt-1">
    Manage system users, roles and access permissions.
</p>

        <?php if ($message): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Total Users</h4>
<p class="text-3xl font-bold"><?= count($users) ?></p>
</div>

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Active</h4>
<p class="text-3xl font-bold text-green-600">12</p>
</div>

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Admins</h4>
<p class="text-3xl font-bold text-blue-600">3</p>
</div>

<div class="bg-surface-container-lowest rounded-xl border p-5">
<h4 class="text-sm text-on-surface-variant">Viewers</h4>
<p class="text-3xl font-bold text-orange-500">9</p>
</div>

</div>
        <!-- Create User Form -->
        <div class="bg-surface-container-lowest rounded-xl border border-[#EDEBE9] p-6 shadow-sm mb-6">
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
                <button
type="submit"
name="add_user"
class="bg-primary text-on-primary px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition">+ Create User Account</button>
            </form>
        </div>

        <!-- User Registry Table -->
        <div class="bg-surface-container-lowest rounded-xl border border-[#EDEBE9] p-6 shadow-sm mb-6">
            <h3>Registered Team Members</h3>
            <div style="overflow-x: auto;">
                <div class="flex gap-4 mb-5 flex-wrap">

<input
class="border rounded-xl px-4 py-3 flex-1"
placeholder="Search User">

<select class="border rounded-xl px-4 py-3">
<option>All Roles</option>
<option>Admin</option>
<option>DPO</option>
<option>Auditor</option>
<option>Viewer</option>
</select>

<select class="border rounded-xl px-4 py-3">
<option>All Status</option>
<option>Active</option>
<option>Inactive</option>
</select>

</div>
                <table class="w-full border-collapse">
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
                                    <td><span class="inline-flex px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-semibold"><?= htmlspecialchars($user['role']) ?></span></td>
                                    <td>
                                        <?php 
                                            $st = strtolower($user['status'] ?? 'active');
                                            $badge_class = ($st === 'active') ? 'badge-active' : 'badge-inactive';
                                        ?>
                                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $badge_class ?>"><?= htmlspecialchars($user['status']) ?></span>
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