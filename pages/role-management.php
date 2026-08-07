<?php
// governance/pages/role-management.php
require_once __DIR__ . '/../includes/db.php';

// Super Admin only
require_permission('manage_users');

/** @var PDO $pdo */

// Fetch Statistics counts
$totalRoles = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
$activeRoles = $pdo->query("SELECT COUNT(*) FROM roles WHERE status = 'active'")->fetchColumn();
$disabledRoles = $pdo->query("SELECT COUNT(*) FROM roles WHERE status = 'disabled'")->fetchColumn();

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
?>

<div class="space-y-lg">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-sm">
        <div>
            <h1 class="text-display font-display text-primary leading-tight">Role & Permission Policies</h1>
            <p class="text-body-md text-on-surface-variant">Configure enterprise access groups, clone security baselines, and define dynamic permission policies.</p>
        </div>
        <div class="flex gap-sm">
            <button onclick="openCreateRoleModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm">
                + Add Role
            </button>
            <button onclick="openCloneRoleModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-primary bg-primary/20 hover:bg-primary/30 text-primary rounded-xl transition shadow-sm">
                Clone Role
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Roles</span>
                <p class="text-display font-bold text-on-surface mt-base"><?= $totalRoles ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary">security</span>
            </div>
        </div>
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active Roles</span>
                <p class="text-display font-bold text-emerald-600 mt-base"><?= $activeRoles ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            </div>
        </div>
        <div class="bg-surface shadow-sm rounded-xl border border-outline-variant p-md flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Disabled</span>
                <p class="text-display font-bold text-red-600 mt-base"><?= $disabledRoles ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">block_external</span>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant bg-surface-container-low px-md flex gap-md">
            <button onclick="switchRoleTab('roles')" id="tabBtn-roles" class="px-md py-4 font-title-md border-b-2 border-primary text-primary transition-all font-semibold focus:outline-none">Roles Configuration</button>
            <button onclick="switchRoleTab('matrix')" id="tabBtn-matrix" class="px-md py-4 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none">Permission Matrix Policy</button>
        </div>

        <!-- Tab Content: Roles Configuration -->
        <div id="tabContent-roles" class="p-md space-y-md">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md">Role Name</th>
                            <th class="p-md">Description</th>
                            <th class="p-md">Status</th>
                            <th class="p-md text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <?php foreach ($roles as $r): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors" data-role-id="<?= $r['id'] ?>">
                                <td class="p-md">
                                    <div class="font-semibold text-on-surface"><?= htmlspecialchars($r['role_name']) ?></div>
                                    <div class="text-caption text-on-surface-variant">ID: <?= $r['id'] ?></div>
                                </td>
                                <td class="p-md text-on-surface-variant">
                                    <?= htmlspecialchars($r['description'] ?: 'No description provided') ?>
                                </td>
                                <td class="p-md">
                                    <?php $isActive = $r['status'] === 'active'; ?>
                                    <span class="status-badge inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border <?= $isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
                                        <?= htmlspecialchars(ucfirst($r['status'])) ?>
                                    </span>
                                </td>
                                <td class="p-md text-right space-x-base">
                                    <!-- Edit Description -->
                                    <button onclick="openEditRoleModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['role_name']) ?>', '<?= htmlspecialchars($r['description']) ?>')" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-primary transition-all" title="Edit Metadata">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <!-- Toggle Status -->
                                    <button onclick="toggleRoleStatus(<?= $r['id'] ?>)" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-on-surface transition-all" title="Toggle active/disabled">
                                        <span class="material-symbols-outlined text-[20px]"><?= $isActive ? 'block' : 'check_circle' ?></span>
                                    </button>
                                    <!-- Delete Role -->
                                    <button onclick="deleteRole(<?= $r['id'] ?>)" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-red-600 transition-all" title="Delete Role">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Permission Matrix -->
        <div id="tabContent-matrix" class="p-md hidden space-y-md">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md">Module / Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="p-md text-center <?= ($role['status'] === 'disabled') ? 'opacity-40' : '' ?>">
                                    <?= htmlspecialchars($role['role_name']) ?>
                                    <?php if ($role['status'] === 'disabled'): ?>
                                        <div class="text-[10px] text-red-500 font-bold">(Disabled)</div>
                                    <?php endif; ?>
                                </th>
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
                                               <?= ($role['status'] === 'disabled') ? 'disabled' : '' ?>
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

<!-- Modal: Create Role -->
<div id="createRoleModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Create Access Role</h3>
            <button onclick="closeCreateRoleModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="createRoleForm" class="p-md space-y-md">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="role_name">Role Name</label>
                <input type="text" name="role_name" id="role_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Lead Auditor">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="role_desc">Description</label>
                <textarea name="description" id="role_desc" rows="3" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Enter role capabilities and limitations..."></textarea>
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeCreateRoleModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Create Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Clone Role -->
<div id="cloneRoleModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Clone Security Baseline</h3>
            <button onclick="closeCloneRoleModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="cloneRoleForm" class="p-md space-y-md">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="source_role">Source Baseline Role</label>
                <select name="source_role_id" id="source_role" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="new_role_name">New Role Name</label>
                <input type="text" name="role_name" id="new_role_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Junior Auditor">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="clone_desc">Description</label>
                <textarea name="description" id="clone_desc" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Enter cloned role details..."></textarea>
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeCloneRoleModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Clone Base Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Role Metadata -->
<div id="editRoleModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Edit Role Metadata</h3>
            <button onclick="closeEditRoleModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="editRoleForm" class="p-md space-y-md">
            <input type="hidden" name="role_id" id="edit_role_id">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_role_name">Role Name</label>
                <input type="text" name="role_name" id="edit_role_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_role_desc">Description</label>
                <textarea name="description" id="edit_role_desc" rows="3" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeEditRoleModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/role-management.js"></script>
