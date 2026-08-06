// assets/js/user-management.js

// Switching between tabs: Users List vs Permission Matrix
function switchTab(tabId) {
    const tabs = ['users', 'matrix'];
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

// Inline role changer event listener
document.querySelectorAll('.role-selector').forEach(select => {
    select.addEventListener('change', function() {
        const userId = this.getAttribute('data-user-id');
        const roleId = this.value;

        const formData = new FormData();
        formData.append('action', 'update_role');
        formData.append('user_id', userId);
        formData.append('role_id', roleId);

        fetch('backend/api/users/update.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessNotification(data.message);
            } else {
                alert('Failed to update role: ' + data.message);
                location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to update role. System error.');
        });
    });
});

// Toggle active/suspended user status
function toggleUserStatus(userId) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('user_id', userId);

    fetch('backend/api/users/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessNotification(data.message);
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (row) {
                const badge = row.querySelector('.status-badge');
                const btnIcon = row.querySelector('button[title="Toggle Status"] span');
                if (data.status === 'active') {
                    badge.className = "status-badge inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200";
                    badge.textContent = "Active";
                    btnIcon.textContent = "block";
                } else {
                    badge.className = "status-badge inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border bg-red-50 text-red-700 border-red-200";
                    badge.textContent = "Suspended";
                    btnIcon.textContent = "check_circle";
                }
            }
        } else {
            alert('Failed to update status: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to update status. System error.');
    });
}

// Password Reset Modal Controls
function openResetPasswordModal(userId, email) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_user_email').value = email;
    document.getElementById('new_password').value = '';
    document.getElementById('resetPasswordModal').classList.remove('hidden');
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
}

document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const userId = document.getElementById('reset_user_id').value;
    const password = document.getElementById('new_password').value;

    const formData = new FormData();
    formData.append('action', 'reset_password');
    formData.append('user_id', userId);
    formData.append('password', password);

    fetch('backend/api/users/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeResetPasswordModal();
            showSuccessNotification(data.message);
        } else {
            alert('Failed to reset password: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to reset password. System error.');
    });
});

// Create User Modal Controls
function openCreateUserModal() {
    document.getElementById('first_name').value = '';
    document.getElementById('last_name').value = '';
    document.getElementById('create_email').value = '';
    document.getElementById('create_phone').value = '';
    document.getElementById('create_role_id').selectedIndex = 0;
    document.getElementById('create_password').value = '';
    document.getElementById('createUserModal').classList.remove('hidden');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.add('hidden');
}

document.getElementById('createUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('backend/api/users/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeCreateUserModal();
            showSuccessNotification(data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Failed to create user: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Failed to create user. System error.');
    });
});


// Toggle Role Permission Checkbox Mapping
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
            showSuccessNotification(data.message);
        } else {
            checkbox.checked = !checkbox.checked; // Revert checkbox check state on failure
            alert('Failed to modify permission mapping: ' + data.message);
        }
    })
    .catch(err => {
        checkbox.disabled = false;
        checkbox.checked = !checkbox.checked;
        console.error(err);
        alert('Failed to modify permission mapping. System error.');
    });
}

// Fluent toast notification helper
function showSuccessNotification(message) {
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
