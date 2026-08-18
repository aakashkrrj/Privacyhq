<?php
// governance/pages/user-management.php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header('Location: login.php');
    exit;
}

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="space-y-lg max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-md">
        <div>
            <div class="flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[32px]">manage_accounts</span>
                <h1 class="text-display font-display text-primary leading-tight">User &amp; Access Control Management</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Provision enterprise accounts, enforce role-based access control (RBAC), import bulk users, and monitor account security telemetry.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openAddUserModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm cursor-pointer">
                + Add New User
            </button>
            <button onclick="openImportModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">upload_file</span> Bulk CSV Import
            </button>
            <div class="relative inline-block text-left">
                <button onclick="toggleUserExportDropdown()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                    <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export Users
                </button>
                <div id="userExportDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-surface rounded-xl border border-outline-variant shadow-lg py-1 z-20">
                    <button onclick="exportUsers('pdf')" class="w-full text-left px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-high font-medium cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600 text-[18px]">picture_as_pdf</span> Genuine PDF (.pdf)
                    </button>
                    <button onclick="exportUsers('excel')" class="w-full text-left px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-high font-medium cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-[18px]">table_view</span> Genuine Excel (.xlsx)
                    </button>
                    <button onclick="exportUsers('csv')" class="w-full text-left px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-high font-medium cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600 text-[18px]">csv</span> CSV Spreadsheet (.csv)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Executive Dashboard Telemetry Cards (Row 132) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total User Accounts</span>
                <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-[24px]">group</span>
            </div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active Accounts</span>
                <div class="mt-base text-display font-bold text-emerald-600" id="kpi-active">...</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600 text-[24px]">check_circle</span>
            </div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Suspended / Inactive</span>
                <div class="mt-base text-display font-bold text-red-600" id="kpi-suspended">...</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600 text-[24px]">block</span>
            </div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm flex items-center justify-between">
            <div>
                <span class="text-caption font-semibold uppercase tracking-wider text-indigo-600">Logged In Recently (30d)</span>
                <div class="mt-base text-display font-bold text-indigo-600" id="kpi-logged-in">...</div>
            </div>
            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-indigo-600 text-[24px]">key</span>
            </div>
        </div>
    </div>

    <!-- Visual Analytics Section (Row 132) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <!-- Chart 1: Role Distribution -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">pie_chart</span>
                Users Distribution by Role
            </h3>
            <div id="dist-roles" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading role breakdown...</div>
            </div>
        </div>

        <!-- Chart 2: User Creation Velocity -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">trending_up</span>
                14-Day User Provisioning Velocity
            </h3>
            <div id="dist-trend" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading creation velocity trend...</div>
            </div>
        </div>
    </div>

    <!-- Main Tabs Card -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant bg-surface-container-low px-md flex gap-md">
            <button onclick="switchUserTab('inventory')" id="tabBtn-inventory" class="px-md py-4 font-title-md border-b-2 border-primary text-primary transition-all font-semibold focus:outline-none cursor-pointer">
                User Inventory Register
            </button>
            <button onclick="switchUserTab('matrix')" id="tabBtn-matrix" class="px-md py-4 font-title-md border-b-2 border-transparent text-on-surface-variant transition-all font-semibold focus:outline-none cursor-pointer">
                Role &amp; Permission Access Matrix
            </button>
        </div>

        <!-- Tab 1: Users Inventory -->
        <div id="tabContent-inventory">
            <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
                <h2 class="font-semibold text-on-surface text-title-md">Enterprise Accounts Register</h2>
                
                <!-- Search & Filters (Row 138) -->
                <form id="userSearchForm" class="flex flex-wrap items-center gap-sm">
                    <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search email, name, phone, ID...">
                    
                    <select id="filter-role" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="">All Roles</option>
                        <option value="1">Super Admin</option>
                        <option value="2">DPO / Privacy Officer</option>
                        <option value="3">Compliance Assessor</option>
                        <option value="4">Audit Specialist</option>
                        <option value="5">Business User</option>
                    </select>

                    <select id="filter-status" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>

                    <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">Search</button>
                    <button type="button" onclick="clearUserFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition cursor-pointer">Clear</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                            <th class="px-lg py-md">User ID</th>
                            <th class="px-lg py-md">Full Name &amp; Email</th>
                            <th class="px-lg py-md">Phone</th>
                            <th class="px-lg py-md">Assigned Role</th>
                            <th class="px-lg py-md">Status</th>
                            <th class="px-lg py-md">Last Login</th>
                            <th class="px-lg py-md text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <tr><td colspan="7" class="px-lg py-md text-center text-on-surface-variant">Loading user accounts...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div id="userPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
                <div class="text-caption text-on-surface-variant">Showing user records</div>
            </div>
        </div>

        <!-- Tab 2: Permission Matrix (Row 137) -->
        <div id="tabContent-matrix" class="hidden p-md space-y-md">
            <div class="flex justify-between items-center bg-surface-container-low p-md rounded-lg border border-outline-variant">
                <div>
                    <h3 class="font-bold text-on-surface text-title-md">Role-Based Access Control (RBAC) Matrix</h3>
                    <p class="text-caption text-on-surface-variant mt-0.5">Toggle granular module permissions for each platform role.</p>
                </div>
                <div class="flex gap-sm">
                    <select id="matrix-role-select" onchange="loadRoleMatrix()" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md bg-surface font-semibold text-primary">
                        <option value="1">Super Admin</option>
                        <option value="2">DPO / Privacy Officer</option>
                        <option value="3">Compliance Assessor</option>
                        <option value="4">Audit Specialist</option>
                        <option value="5">Business User</option>
                    </select>
                    <button onclick="saveRoleMatrix()" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">
                        Save Matrix Changes
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border border-outline-variant rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                            <th class="p-md w-12">Grant</th>
                            <th class="p-md">Module</th>
                            <th class="p-md">Permission Name</th>
                            <th class="p-md">Description</th>
                        </tr>
                    </thead>
                    <tbody id="matrixTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                        <tr><td colspan="4" class="p-md text-center text-gray-500">Loading permission matrix...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Add User Modal (Row 134) -->
<div id="addUserModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Add New User Account</h3>
            <button onclick="closeAddUserModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="addUserForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">First Name *</label>
                    <input type="text" name="first_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Nishtha">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Last Name *</label>
                    <input type="text" name="last_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Sharma">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Email Address *</label>
                    <input type="email" name="email" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. user@privacyhq.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Phone Number</label>
                    <input type="text" name="phone" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. +1 555-0192">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Assigned Role *</label>
                    <select name="role_id" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="1">Super Admin</option>
                        <option value="2">DPO / Privacy Officer</option>
                        <option value="3">Compliance Assessor</option>
                        <option value="4">Audit Specialist</option>
                        <option value="5" selected>Business User</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Account Status *</label>
                    <select name="status" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Password *</label>
                    <input type="password" name="password" required minlength="8" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Min 8 characters">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Confirm Password *</label>
                    <input type="password" name="confirm_password" required minlength="8" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Re-enter password">
                </div>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeAddUserModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit User Modal (Row 135) -->
<div id="editUserModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md" id="editModalTitle">Edit User Account</h3>
            <button onclick="closeEditUserModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="editUserForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="edit_user_id" value="">

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">First Name *</label>
                    <input type="text" name="first_name" id="edit_first_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Last Name *</label>
                    <input type="text" name="last_name" id="edit_last_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Email Address *</label>
                    <input type="email" name="email" id="edit_email" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Assigned Role *</label>
                    <select name="role_id" id="edit_role_id" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="1">Super Admin</option>
                        <option value="2">DPO / Privacy Officer</option>
                        <option value="3">Compliance Assessor</option>
                        <option value="4">Audit Specialist</option>
                        <option value="5">Business User</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Account Status *</label>
                    <select name="status" id="edit_status" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="p-md bg-surface-container-low border border-outline-variant rounded-lg space-y-2">
                <span class="text-caption font-semibold uppercase text-on-surface-variant block">Change Password (Optional)</span>
                <div class="grid grid-cols-2 gap-md">
                    <input type="password" name="password" minlength="8" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="New Password">
                    <input type="password" name="confirm_password" minlength="8" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Confirm New Password">
                </div>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Update Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Delete User Confirmation (Row 136) -->
<div id="deleteUserModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-red-50">
            <h3 class="font-bold text-red-800 text-title-md flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[22px]">warning</span>
                Confirm Soft Delete User Account
            </h3>
            <button onclick="closeDeleteUserModal()" class="text-red-700 hover:text-red-900 text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md space-y-md">
            <p class="text-body-md text-on-surface" id="deleteConfirmText">Are you sure you want to delete this user account?</p>
            <input type="hidden" id="delete_user_id" value="">
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end gap-sm bg-surface-container-low">
            <button type="button" onclick="closeDeleteUserModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
            <button type="button" onclick="executeDeleteUser()" class="px-4 py-2 text-body-md text-white bg-red-600 rounded-lg hover:bg-red-700 font-semibold cursor-pointer">Confirm Soft Delete</button>
        </div>
    </div>
</div>

<!-- Modal 4: Bulk CSV Import Modal (Row 139) -->
<div id="importModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Bulk CSV User Account Import</h3>
            <button onclick="closeImportModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="importForm" class="p-md space-y-md" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Select CSV File *</label>
                <input type="file" name="csv_file" accept=".csv" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
            </div>

            <div class="p-md bg-surface-container-low border border-outline-variant rounded-lg text-xs space-y-1">
                <strong>Required CSV Headers:</strong>
                <code class="block font-mono bg-surface p-2 rounded text-primary mt-1 border">first_name, last_name, email, phone, role, status</code>
                <p class="text-on-surface-variant mt-1">Imported users will receive an initial secure hashed password automatically.</p>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 font-semibold cursor-pointer">Start Bulk Import</button>
            </div>
        </form>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/users.js?v=<?= time() ?>"></script>