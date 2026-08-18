// governance/assets/js/users.js
// User Management Module JS Controller

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function toggleUserExportDropdown() {
    const menu = document.getElementById('userExportDropdownMenu');
    if (menu) menu.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('userExportDropdownMenu');
    const btn = e.target.closest('button');
    if (menu && !menu.classList.contains('hidden') && (!btn || !btn.getAttribute('onclick')?.includes('toggleUserExportDropdown'))) {
        menu.classList.add('hidden');
    }
});

function switchUserTab(tabName) {
    const btnInv = document.getElementById('tabBtn-inventory');
    const btnMat = document.getElementById('tabBtn-matrix');
    const contentInv = document.getElementById('tabContent-inventory');
    const contentMat = document.getElementById('tabContent-matrix');

    if (tabName === 'matrix') {
        btnInv?.classList.remove('border-primary', 'text-primary');
        btnInv?.classList.add('border-transparent', 'text-on-surface-variant');
        btnMat?.classList.remove('border-transparent', 'text-on-surface-variant');
        btnMat?.classList.add('border-primary', 'text-primary');

        contentInv?.classList.add('hidden');
        contentMat?.classList.remove('hidden');

        loadRoleMatrix();
    } else {
        btnMat?.classList.remove('border-primary', 'text-primary');
        btnMat?.classList.add('border-transparent', 'text-on-surface-variant');
        btnInv?.classList.remove('border-transparent', 'text-on-surface-variant');
        btnInv?.classList.add('border-primary', 'text-primary');

        contentMat?.classList.add('hidden');
        contentInv?.classList.remove('hidden');
    }
}

// 1. Dashboard Telemetry & Visual Analytics (Row 132)
async function loadUserDashboard() {
    try {
        const res = await fetch('backend/api/users/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;

            // KPI Cards
            const kpiTotal = document.getElementById('kpi-total');
            const kpiActive = document.getElementById('kpi-active');
            const kpiSuspended = document.getElementById('kpi-suspended');
            const kpiLoggedIn = document.getElementById('kpi-logged-in');

            if (kpiTotal) kpiTotal.innerText = d.total_users || 0;
            if (kpiActive) kpiActive.innerText = d.active_users || 0;
            if (kpiSuspended) kpiSuspended.innerText = (d.suspended_users || 0) + (d.inactive_users || 0);
            if (kpiLoggedIn) kpiLoggedIn.innerText = d.logged_in_recently || 0;

            // Chart 1: Role Distribution
            const distRoles = document.getElementById('dist-roles');
            if (distRoles) {
                const roles = d.role_distribution || {};
                const keys = Object.keys(roles);
                const total = d.total_users || 1;

                if (keys.length === 0) {
                    distRoles.innerHTML = '<div class="text-caption text-gray-500">No role distribution data available.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = roles[k] || 0;
                        const pct = Math.round((count / total) * 100);
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span>${escapeHtml(k)}</span>
                                    <span class="font-bold text-on-surface">${count} (${pct}%)</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="bg-primary h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distRoles.innerHTML = html;
                }
            }

            // Chart 2: 14-Day Velocity Trend
            const distTrend = document.getElementById('dist-trend');
            if (distTrend) {
                const trend = d.daily_trend || {};
                const keys = Object.keys(trend);
                const maxVal = Math.max(...Object.values(trend), 1);

                if (keys.length === 0) {
                    distTrend.innerHTML = '<div class="text-caption text-gray-500">No 14-day creation velocity data recorded.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = trend[k] || 0;
                        const pct = Math.round((count / maxVal) * 100);
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span>${escapeHtml(k)}</span>
                                    <span class="font-bold text-on-surface">${count} Users</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distTrend.innerHTML = html;
                }
            }
        }
    } catch (e) {
        console.error('Failed loading user dashboard telemetry', e);
    }
}

// 2. Paginated Users Inventory Register (Rows 133, 138)
async function loadUsersList() {
    const search = document.getElementById('filter-search')?.value || '';
    const roleId = document.getElementById('filter-role')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';

    const url = `backend/api/users/list.php?p=${currentPage}&limit=20&search=${encodeURIComponent(search)}&role_id=${encodeURIComponent(roleId)}&status=${encodeURIComponent(status)}`;
    
    const tbody = document.getElementById('userTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading user accounts...</td></tr>';
    }

    try {
        const res = await fetch(url);
        const data = await res.json();
        
        if ((data.status === 'success' || data.success) && data.data) {
            if (!tbody) return;
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-gray-500">No matching user accounts found.</td></tr>';
            } else {
                items.forEach(u => {
                    let statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (u.status === 'inactive') statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    if (u.status === 'suspended') statusBadge = 'bg-red-50 text-red-700 border-red-200';

                    const roleName = u.role_name || `Role #${u.role_id}`;

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-primary font-bold">#${u.id}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(u.first_name)} ${escapeHtml(u.last_name)}</div>
                                <div class="text-caption text-on-surface-variant">${escapeHtml(u.email)}</div>
                            </td>
                            <td class="px-lg py-md font-mono text-caption text-on-surface-variant">${escapeHtml(u.phone || 'N/A')}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                    ${escapeHtml(roleName)}
                                </span>
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border capitalize ${statusBadge}">
                                    ${escapeHtml(u.status)}
                                </span>
                            </td>
                            <td class="px-lg py-md font-mono text-caption text-on-surface-variant">${escapeHtml(u.last_login_at || 'Never Logged In')}</td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-2">
                                <button onclick="openEditUserModal(${u.id})" class="px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary/20 font-semibold text-xs rounded-lg transition cursor-pointer">
                                    Edit
                                </button>
                                <button onclick="openDeleteUserModal(${u.id}, '${escapeHtml(u.email)}')" class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 font-semibold text-xs rounded-lg transition cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 20) || 1;
            const paginationDiv = document.getElementById('userPagination');
            if (paginationDiv) {
                const startIdx = total === 0 ? 0 : (currentPage - 1) * 20 + 1;
                const endIdx = Math.min(currentPage * 20, total);
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${startIdx}–${endIdx}</strong> of <strong>${total}</strong> User Accounts
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changeUserPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Previous</button>
                        <button onclick="changeUserPage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed loading users list', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-red-600">Failed loading user accounts register.</td></tr>';
        }
    }
}

function changeUserPage(page) {
    if (page < 1) return;
    currentPage = page;
    loadUsersList();
}

function clearUserFilters() {
    const s = document.getElementById('filter-search');
    const r = document.getElementById('filter-role');
    const st = document.getElementById('filter-status');

    if (s) s.value = '';
    if (r) r.value = '';
    if (st) st.value = '';

    currentPage = 1;
    loadUsersList();
}

// 3. User CRUD Modals (Rows 134, 135, 136)
function openAddUserModal() {
    const modal = document.getElementById('addUserModal');
    if (modal) modal.classList.remove('hidden');
}

function closeAddUserModal() {
    const modal = document.getElementById('addUserModal');
    if (modal) modal.classList.add('hidden');
}

async function openEditUserModal(id) {
    if (!id) return;
    const modal = document.getElementById('editUserModal');
    if (!modal) return;

    try {
        const res = await fetch(`backend/api/users/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const u = data.data;
            document.getElementById('edit_user_id').value = u.id;
            document.getElementById('edit_first_name').value = u.first_name || '';
            document.getElementById('edit_last_name').value = u.last_name || '';
            document.getElementById('edit_email').value = u.email || '';
            document.getElementById('edit_phone').value = u.phone || '';
            document.getElementById('edit_role_id').value = u.role_id || 5;
            document.getElementById('edit_status').value = u.status || 'active';

            modal.classList.remove('hidden');
        } else {
            alert('Error loading user details: ' + (data.message || 'User not found.'));
        }
    } catch (e) {
        alert('Network error loading user details.');
    }
}

function closeEditUserModal() {
    const modal = document.getElementById('editUserModal');
    if (modal) modal.classList.add('hidden');
}

function openDeleteUserModal(id, email) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('deleteConfirmText').innerText = `Are you sure you want to soft-delete user account '${email}' (ID #${id})?`;
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.add('hidden');
}

async function executeDeleteUser() {
    const id = document.getElementById('delete_user_id').value;
    closeDeleteUserModal();
    if (!id) return;

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', G_CSRF_TOKEN);

        const res = await fetch('backend/api/users/delete.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadUsersList();
            loadUserDashboard();
            alert(data.message || 'User account soft-deleted successfully!');
        } else {
            alert('Error: ' + (data.message || 'Failed deleting user account.'));
        }
    } catch (e) {
        alert('Network error executing user deletion.');
    }
}

// 4. Role Permissions Matrix (Row 137)
async function loadRoleMatrix() {
    const roleId = document.getElementById('matrix-role-select')?.value || 1;
    const tbody = document.getElementById('matrixTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading permission matrix...</td></tr>';
    }

    try {
        const res = await fetch('backend/api/users/roles.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const perms = data.data.permissions || [];
            const matrix = data.data.matrix || {};
            const assigned = matrix[roleId] || {};

            if (!tbody) return;
            tbody.innerHTML = '';

            perms.forEach(p => {
                const isChecked = !!assigned[p.id];
                const row = `
                    <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                        <td class="p-md text-center">
                            <input type="checkbox" class="perm-checkbox w-4 h-4 text-primary rounded border-outline-variant cursor-pointer" value="${p.id}" ${isChecked ? 'checked' : ''}>
                        </td>
                        <td class="p-md font-semibold text-primary">${escapeHtml(p.module)}</td>
                        <td class="p-md font-mono text-caption text-on-surface">${escapeHtml(p.permission_name)}</td>
                        <td class="p-md text-caption text-on-surface-variant">${escapeHtml(p.description || '')}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    } catch (e) {
        console.error('Failed loading role matrix', e);
    }
}

async function saveRoleMatrix() {
    const roleId = document.getElementById('matrix-role-select')?.value || 1;
    const checkboxes = document.querySelectorAll('.perm-checkbox:checked');
    const permissionIds = Array.from(checkboxes).map(c => parseInt(c.value));

    try {
        const formData = new FormData();
        formData.append('role_id', roleId);
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('permission_ids', JSON.stringify(permissionIds));

        const res = await fetch('backend/api/users/roles.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            alert('Role permission matrix updated successfully!');
        } else {
            alert('Error saving permission matrix: ' + (data.message || 'Failed.'));
        }
    } catch (e) {
        alert('Network error saving permission matrix.');
    }
}

// 5. Bulk CSV Import & Export (Row 139)
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
}

function exportUsers(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const roleId = document.getElementById('filter-role')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';

    const url = `backend/api/users/export.php?format=${format}&search=${encodeURIComponent(search)}&role_id=${encodeURIComponent(roleId)}&status=${encodeURIComponent(status)}`;
    window.open(url, '_blank');
}

// Global Scope Exports
window.loadUserDashboard = loadUserDashboard;
window.loadUsersList = loadUsersList;
window.changeUserPage = changeUserPage;
window.clearUserFilters = clearUserFilters;
window.switchUserTab = switchUserTab;
window.openAddUserModal = openAddUserModal;
window.closeAddUserModal = closeAddUserModal;
window.openEditUserModal = openEditUserModal;
window.closeEditUserModal = closeEditUserModal;
window.openDeleteUserModal = openDeleteUserModal;
window.closeDeleteUserModal = closeDeleteUserModal;
window.executeDeleteUser = executeDeleteUser;
window.loadRoleMatrix = loadRoleMatrix;
window.saveRoleMatrix = saveRoleMatrix;
window.openImportModal = openImportModal;
window.closeImportModal = closeImportModal;
window.toggleUserExportDropdown = toggleUserExportDropdown;
window.exportUsers = exportUsers;

document.addEventListener('DOMContentLoaded', () => {
    loadUserDashboard();
    loadUsersList();

    const searchForm = document.getElementById('userSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadUsersList();
        });
    }

    // Add User Form
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/users/create.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeAddUserModal();
                    addForm.reset();
                    loadUsersList();
                    loadUserDashboard();
                    alert(data.message || 'User account created successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed creating user account.'));
                }
            } catch (err) {
                alert('Network error creating user account.');
            }
        });
    }

    // Edit User Form
    const editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/users/update.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeEditUserModal();
                    loadUsersList();
                    loadUserDashboard();
                    alert(data.message || 'User account updated successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed updating user account.'));
                }
            } catch (err) {
                alert('Network error updating user account.');
            }
        });
    }

    // Import Form
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/users/import.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeImportModal();
                    importForm.reset();
                    loadUsersList();
                    loadUserDashboard();
                    alert(data.message || 'Bulk CSV import completed successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed processing CSV import.'));
                }
            } catch (err) {
                alert('Network error processing CSV import.');
            }
        });
    }
});
