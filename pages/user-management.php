<?php
// governance/pages/user-management.php
require_once __DIR__ . '/../includes/db.php';

// Super Admin only
require_permission('manage_users');

/** @var PDO $pdo */

// Fetch Statistics counts
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$suspendedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
$superAdminsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn();
$dpoCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
$assessorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
$auditorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 4")->fetchColumn();

// Fetch roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch permissions
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch role permissions mapping
$rolePermsMapping = [];
$mapRows = $pdo->query("SELECT role_id, permission_id FROM role_permissions")->fetchAll(PDO::FETCH_ASSOC);
foreach ($mapRows as $row) {
    $rolePermsMapping[$row['role_id']][$row['permission_id']] = true;
}

// Fetch all users with their roles
$users = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-lg">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-sm">
        <div>
            <h1 class="text-display font-display text-primary leading-tight">User & Roles Management</h1>
            <p class="text-body-md text-on-surface-variant">Configure enterprise accounts, assign security profiles, and define global access policies.</p>
        </div>
        <button onclick="openCreateUserModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:opacity-90 transition shadow-sm">
            + New User
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Users</span>
                <p class="text-display font-bold text-on-surface mt-base"><?= $totalUsers ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">group</span>
            </div>
        </div>
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active Accounts</span>
                <p class="text-display font-bold text-emerald-600 mt-base"><?= $activeUsers ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
        </div>
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Suspended</span>
                <p class="text-display font-bold text-red-600 mt-base"><?= $suspendedUsers ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">block</span>
            </div>
        </div>
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Compliance Roles</span>
                <p class="text-display font-bold text-amber-600 mt-base"><?= $dpoCount + $assessorCount ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-amber-600">shield_with_heart</span>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant bg-surface-container-low px-md flex gap-md">
            <button onclick="switchTab('users')" id="tabBtn-users" class="px-md py-4 font-title-md border-b-2 border-primary text-primary transition-all font-semibold focus:outline-none">Users List</button>
            <button onclick="switchTab('matrix')" id="tabBtn-matrix" class="px-md py-4 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none">Permission Matrix</button>
        </div>

        <!-- Tab Content: Users List -->
        <div id="tabContent-users" class="p-md space-y-md">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md">Name & Email</th>
                            <th class="p-md">Assigned Role</th>
                            <th class="p-md">Status</th>
                            <th class="p-md">Last Login</th>
                            <th class="p-md text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="p-lg text-center text-on-surface-variant">No user accounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-surface-container-lowest transition-colors" data-user-id="<?= $user['id'] ?>">
                                    <td class="p-md">
                                        <div class="font-semibold text-on-surface"><?= htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Unnamed User') ?></div>
                                        <div class="text-caption text-on-surface-variant"><?= htmlspecialchars($user['email']) ?></div>
                                    </td>
                                    <td class="p-md">
                                        <select class="role-selector border border-outline-variant rounded-lg p-base bg-surface text-body-md focus:outline-none focus:border-primary" data-user-id="<?= $user['id'] ?>">
                                            <?php foreach ($roles as $roleOption): ?>
                                                <option value="<?= $roleOption['id'] ?>" <?= ($user['role_id'] == $roleOption['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($roleOption['role_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="p-md">
                                        <?php $isActive = $user['status'] === 'active'; ?>
                                        <span class="status-badge inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border <?= $isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
                                            <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="p-md text-on-surface-variant font-mono text-caption">
                                        <?= $user['last_login_at'] ? htmlspecialchars($user['last_login_at']) : 'Never logged in' ?>
                                    </td>
                                    <td class="p-md text-right space-x-base">
                                        <button onclick="toggleUserStatus(<?= $user['id'] ?>)" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-on-surface transition-all" title="Toggle Status">
                                            <span class="material-symbols-outlined text-[20px]"><?= $isActive ? 'block' : 'check_circle' ?></span>
                                        </button>
                                        <button onclick="openResetPasswordModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['email']) ?>')" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-primary transition-all" title="Reset Password">
                                            <span class="material-symbols-outlined text-[20px]">key</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Permission Matrix -->
        <div id="tabContent-matrix" class="p-md hidden space-y-md">
            <div class="p-sm bg-surface-container-low text-on-surface-variant text-caption rounded-lg flex items-center gap-xs">
                <span class="material-symbols-outlined text-primary">info</span>
                <span>Note: Permission matrices define granular API and page-level route controls. Super Admin modifications are locked to prevent configuration failure.</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md">Module / Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="p-md text-center"><?= htmlspecialchars($role['role_name']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <?php foreach ($permissions as $perm): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="p-md">
                                    <div class="font-semibold text-on-surface"><?= htmlspecialchars($perm['permission_name']) ?></div>
                                    <div class="text-caption text-on-surface-variant"><?= htmlspecialchars($perm['description']) ?> (<?= htmlspecialchars($perm['module']) ?>)</div>
                                </td>
                                <?php foreach ($roles as $role): ?>
                                    <td class="p-md text-center">
                                        <input type="checkbox" 
                                               class="permission-toggle w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary"
                                               data-role-id="<?= $role['id'] ?>"
                                               data-permission-id="<?= $perm['id'] ?>"
                                               <?= ($role['id'] == 1) ? 'disabled checked' : '' ?>
                                               <?= isset($rolePermsMapping[$role['id']][$perm['id']]) ? 'checked' : '' ?>
                                               onclick="togglePermission(this, <?= $role['id'] ?>, <?= $perm['id'] ?>)">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Reset Password -->
<div id="resetPasswordModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Reset Password</h3>
            <button onclick="closeResetPasswordModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="resetPasswordForm" class="p-md space-y-md">
            <input type="hidden" name="user_id" id="reset_user_id">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Target Account</label>
                <input type="text" id="reset_user_email" class="w-full border border-outline-variant rounded-lg p-2.5 bg-surface-container-low text-on-surface font-mono text-caption focus:outline-none" readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="new_password">New Password</label>
                <input type="password" name="password" id="new_password" required minlength="6" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Min. 6 characters">
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeResetPasswordModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Update Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create User -->
<div id="createUserModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Create User Account</h3>
            <button onclick="closeCreateUserModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="createUserForm" class="p-md space-y-md">
            <div class="grid grid-cols-2 gap-sm">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Super">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Admin">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="create_email">Email Address</label>
                <input type="email" name="email" id="create_email" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="name@company.com">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="create_phone">Phone Number</label>
                <input type="text" name="phone" id="create_phone" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="+1-555-0199">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="create_role_id">Assign Role</label>
                <select name="role_id" id="create_role_id" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="create_password">Initial Password</label>
                <input type="password" name="password" id="create_password" required minlength="6" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Min. 6 characters">
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeCreateUserModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Create User</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/user-management.js"></script>