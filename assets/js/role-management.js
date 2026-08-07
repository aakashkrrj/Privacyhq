// assets/js/role-management.js

// Switch tabs: Roles Configuration vs Permission Matrix Policy
function switchRoleTab(tabId) {
    const tabs = ['roles', 'matrix'];
    tabs.forEach(tab => {
        const btn = document.getElementById(`tabBtn-${tab}`);
        const content = document.getElementById(`tabContent-${tab}`);
        if (tab === tabId) {
            btn.classList.add('border-primary', 'text-primary');
            btn.classList.remove('border-transparent', 'text-on-surface-variant');
            content.classList.remove('hidden');
        } else {
            btn.classList.remove('border-primary', 'text-primary');
            btn.classList.add('border-transparent', 'text-on-surface-variant');
            content.classList.add('hidden');
        }
    });
}

// Modal open/close actions
function openCreateRoleModal() {
    document.getElementById('createRoleForm').reset();
    document.getElementById('createRoleModal').classList.remove('hidden');
}
function closeCreateRoleModal() {
    document.getElementById('createRoleModal').classList.add('hidden');
}

function openCloneRoleModal() {
    document.getElementById('cloneRoleForm').reset();
    document.getElementById('cloneRoleModal').classList.remove('hidden');
}
function closeCloneRoleModal() {
    document.getElementById('cloneRoleModal').classList.add('hidden');
}

function openEditRoleModal(id, name, desc) {
    document.getElementById('edit_role_id').value = id;
    document.getElementById('edit_role_name').value = name;
    document.getElementById('edit_role_desc').value = desc;
    document.getElementById('editRoleModal').classList.remove('hidden');
}
function closeEditRoleModal() {
    document.getElementById('editRoleModal').classList.add('hidden');
}

// AJAX forms submissions
document.getElementById('createRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitRoleAction('create', new FormData(this));
});

document.getElementById('cloneRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitRoleAction('clone', new FormData(this));
});

document.getElementById('editRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitRoleAction('update', new FormData(this));
});

function submitRoleAction(action, formData) {
    formData.append('action', action);
    fetch('backend/api/roles/roles-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('System error performing role updates.');
    });
}

// Toggle role status
function toggleRoleStatus(roleId) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('role_id', roleId);

    fetch('backend/api/roles/roles-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Failed to update status: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('System error updating role status.');
    });
}

// Delete role
function deleteRole(roleId) {
    if (!confirm('Are you sure you want to delete this role? This cannot be undone.')) {
        return;
    }
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('role_id', roleId);

    fetch('backend/api/roles/roles-api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Failed to delete role: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('System error deleting role.');
    });
}

// Toggle permission checkbox mapping
function togglePermission(checkbox, roleId, permissionId) {
    const formData = new FormData();
    formData.append('role_id', roleId);
    formData.append('permission_id', permissionId);

    checkbox.disabled = true;

    fetch('backend/api/roles/toggle-permission.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        checkbox.disabled = false;
        if (data.success) {
            showSuccessToast(data.message);
        } else {
            checkbox.checked = !checkbox.checked;
            alert('Failed to modify permission: ' + data.message);
        }
    })
    .catch(err => {
        checkbox.disabled = false;
        checkbox.checked = !checkbox.checked;
        console.error(err);
        alert('Failed to update permission mapping.');
    });
}

function showSuccessToast(message) {
    const toast = document.createElement('div');
    toast.className = "fixed bottom-5 right-5 z-50 p-4 bg-primary text-white text-body-md rounded-xl shadow-lg border border-primary/20 flex items-center gap-xs animate-fade-in";
    toast.innerHTML = `
        <span class="material-symbols-outlined">check_circle</span>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
