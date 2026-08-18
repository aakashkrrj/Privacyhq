// governance/assets/js/settings.js
// Settings Module JS Controller

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function switchSettingsTab(tabName) {
    const tabs = ['profile', 'edit-profile', 'password', '2fa', 'notifications', 'api-keys', 'permissions', 'documents'];
    
    tabs.forEach(t => {
        const btn = document.getElementById(`tabBtn-${t}`);
        const content = document.getElementById(`tabContent-${t}`);
        if (t === tabName) {
            btn?.classList.remove('border-transparent', 'text-on-surface-variant');
            btn?.classList.add('border-primary', 'text-primary');
            content?.classList.remove('hidden');
        } else {
            btn?.classList.remove('border-primary', 'text-primary');
            btn?.classList.add('border-transparent', 'text-on-surface-variant');
            content?.classList.add('hidden');
        }
    });

    if (tabName === 'profile' || tabName === 'edit-profile') loadProfile();
    if (tabName === '2fa') load2faStatus();
    if (tabName === 'notifications') loadNotificationPreferences();
    if (tabName === 'api-keys') loadApiKeys();
    if (tabName === 'permissions') loadSettingsRoleMatrix();
    if (tabName === 'documents') loadDocuments();
}

// 1. Profile Information (Row 141 & Row 142)
async function loadProfile() {
    try {
        const res = await fetch('backend/api/settings/profile.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const u = data.data;

            const name = `${u.first_name || ''} ${u.last_name || ''}`.trim() || 'User Account';
            document.getElementById('prof-name').innerText = name;
            document.getElementById('prof-email').innerText = u.email || '';
            document.getElementById('prof-role').innerText = u.role_name || `Role #${u.role_id}`;
            document.getElementById('prof-id').innerText = `#${u.id}`;
            document.getElementById('prof-phone').innerText = u.phone || 'N/A';
            document.getElementById('prof-status').innerText = u.status || 'Active';
            document.getElementById('prof-created').innerText = u.created_at || 'N/A';
            document.getElementById('prof-login').innerText = u.last_login_at || 'Never Logged In';
            document.getElementById('prof-2fa-status').innerText = u.two_factor_enabled ? 'Active / Enabled' : 'Disabled';

            // Edit Profile Form Fill
            const fName = document.getElementById('edit_prof_first_name');
            const lName = document.getElementById('edit_prof_last_name');
            const phone = document.getElementById('edit_prof_phone');

            if (fName) fName.value = u.first_name || '';
            if (lName) lName.value = u.last_name || '';
            if (phone) phone.value = u.phone || '';

            // Profile Avatar
            const avatarBox = document.getElementById('prof-avatar-box');
            if (avatarBox && u.profile_image) {
                avatarBox.innerHTML = `<img src="${u.profile_image}" class="w-full h-full object-cover">`;
            }
        }
    } catch (e) {
        console.error('Failed loading profile information', e);
    }
}

// 2. Two-Factor Authentication (Row 144)
async function load2faStatus() {
    try {
        const res = await fetch('backend/api/settings/2fa.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const isEnabled = !!data.data.enabled;
            const badge = document.getElementById('2fa-badge');
            const setupBox = document.getElementById('2fa-setup-box');
            const disableBox = document.getElementById('2fa-disable-box');

            if (isEnabled) {
                if (badge) {
                    badge.innerText = 'Status: Enabled';
                    badge.className = 'px-3 py-1 text-caption font-bold rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200';
                }
                setupBox?.classList.add('hidden');
                disableBox?.classList.remove('hidden');
            } else {
                if (badge) {
                    badge.innerText = 'Status: Disabled';
                    badge.className = 'px-3 py-1 text-caption font-bold rounded-full border bg-amber-50 text-amber-700 border-amber-200';
                }
                setupBox?.classList.remove('hidden');
                disableBox?.classList.add('hidden');
            }
        }
    } catch (e) {
        console.error('Failed loading 2FA status', e);
    }
}

async function start2faSetup() {
    try {
        const res = await fetch('backend/api/settings/2fa.php?action=setup');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const secret = data.data.secret;
            document.getElementById('2fa-secret-text').innerText = secret;
            document.getElementById('2fa_secret_input').value = secret;
            document.getElementById('2fa-qr-container')?.classList.remove('hidden');
        }
    } catch (e) {
        alert('Failed generating 2FA secret key.');
    }
}

// 3. Notification Preferences (Row 145)
async function loadNotificationPreferences() {
    try {
        const res = await fetch('backend/api/settings/notifications.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const p = data.data;
            document.getElementById('notif_email').checked = !!p.email_notifications;
            document.getElementById('notif_in_app').checked = !!p.in_app_notifications;
            document.getElementById('notif_incident').checked = !!p.privacy_incident_alerts;
            document.getElementById('notif_consent').checked = !!p.consent_updates;
            document.getElementById('notif_assessment').checked = !!p.assessment_reminders;
        }
    } catch (e) {
        console.error('Failed loading notification preferences', e);
    }
}

// 4. API Keys (Row 146)
async function loadApiKeys() {
    const tbody = document.getElementById('apiKeysTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="p-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading API keys...</td></tr>';
    }

    try {
        const res = await fetch('backend/api/settings/api-keys.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            if (!tbody) return;
            tbody.innerHTML = '';
            const items = data.data || [];
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-md text-center text-gray-500">No active API keys generated.</td></tr>';
            } else {
                items.forEach(k => {
                    const isRevoked = k.status === 'revoked';
                    const statusBadge = isRevoked ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    
                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="p-md font-semibold text-on-surface">${escapeHtml(k.key_name)}</td>
                            <td class="p-md font-mono text-caption text-primary font-bold">${escapeHtml(k.key_prefix)}...</td>
                            <td class="p-md font-mono text-caption text-on-surface-variant">${escapeHtml(k.scopes || 'read,write')}</td>
                            <td class="p-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border capitalize ${statusBadge}">
                                    ${escapeHtml(k.status)}
                                </span>
                            </td>
                            <td class="p-md font-mono text-caption text-on-surface-variant">${escapeHtml(k.created_at)}</td>
                            <td class="p-md text-right">
                                ${!isRevoked ? `
                                    <button onclick="revokeApiKey(${k.id})" class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 font-semibold text-xs rounded-lg transition cursor-pointer">
                                        Revoke
                                    </button>
                                ` : '<span class="text-caption text-gray-400">Revoked</span>'}
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed loading API keys', e);
    }
}

function openCreateApiKeyModal() {
    document.getElementById('createApiKeyModal')?.classList.remove('hidden');
}

function closeCreateApiKeyModal() {
    document.getElementById('createApiKeyModal')?.classList.add('hidden');
}

function closeShowApiKeyModal() {
    document.getElementById('showApiKeyModal')?.classList.add('hidden');
}

async function revokeApiKey(id) {
    if (!confirm('Are you sure you want to revoke this API key? Applications using this token will be denied access.')) return;
    try {
        const formData = new FormData();
        formData.append('key_id', id);
        formData.append('action', 'revoke');
        formData.append('csrf_token', G_CSRF_TOKEN);

        const res = await fetch('backend/api/settings/api-keys.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadApiKeys();
            alert(data.message || 'API key revoked successfully!');
        } else {
            alert('Error revoking API key: ' + (data.message || 'Failed.'));
        }
    } catch (e) {
        alert('Network error revoking API key.');
    }
}

// 5. Team Permissions Matrix (Row 147)
async function loadSettingsRoleMatrix() {
    const roleId = document.getElementById('set-matrix-role-select')?.value || 1;
    const tbody = document.getElementById('settingsMatrixTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading team permission matrix...</td></tr>';
    }

    try {
        const res = await fetch('backend/api/settings/team-permissions.php');
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
                            <input type="checkbox" class="set-perm-checkbox w-4 h-4 text-primary rounded border-outline-variant cursor-pointer" value="${p.id}" ${isChecked ? 'checked' : ''}>
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
        console.error('Failed loading team permission matrix', e);
    }
}

async function saveSettingsRoleMatrix() {
    const roleId = document.getElementById('set-matrix-role-select')?.value || 1;
    const checkboxes = document.querySelectorAll('.set-perm-checkbox:checked');
    const permissionIds = Array.from(checkboxes).map(c => parseInt(c.value));

    try {
        const formData = new FormData();
        formData.append('role_id', roleId);
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('permission_ids', JSON.stringify(permissionIds));

        const res = await fetch('backend/api/settings/team-permissions.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            alert('Team permission matrix updated successfully!');
        } else {
            alert('Error saving team permission matrix: ' + (data.message || 'Failed.'));
        }
    } catch (e) {
        alert('Network error saving team permission matrix.');
    }
}

// 6. Compliance Documents Vault (Row 148)
async function loadDocuments() {
    const tbody = document.getElementById('documentsTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="p-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading compliance documents...</td></tr>';
    }

    try {
        const res = await fetch('backend/api/settings/documents.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            if (!tbody) return;
            tbody.innerHTML = '';
            const items = data.data || [];

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-md text-center text-gray-500">No compliance documents uploaded in vault.</td></tr>';
            } else {
                items.forEach(d => {
                    const fileSizeKb = Math.round((d.file_size || 0) / 1024);
                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="p-md font-semibold text-on-surface">${escapeHtml(d.title)}</td>
                            <td class="p-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                    ${escapeHtml(d.category)}
                                </span>
                            </td>
                            <td class="p-md">
                                <div class="font-mono text-caption text-on-surface">${escapeHtml(d.original_name)}</div>
                                <div class="text-caption text-on-surface-variant">${fileSizeKb} KB</div>
                            </td>
                            <td class="p-md text-caption text-on-surface-variant">${escapeHtml(d.uploader_email || 'System')}</td>
                            <td class="p-md font-mono text-caption text-on-surface-variant">${escapeHtml(d.created_at)}</td>
                            <td class="p-md text-right whitespace-nowrap space-x-2">
                                <button onclick="downloadDocument(${d.id})" class="px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary/20 font-semibold text-xs rounded-lg transition cursor-pointer">
                                    Download
                                </button>
                                <button onclick="deleteDocument(${d.id}, '${escapeHtml(d.title)}')" class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 font-semibold text-xs rounded-lg transition cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed loading compliance documents', e);
    }
}

function openUploadDocModal() {
    document.getElementById('uploadDocModal')?.classList.remove('hidden');
}

function closeUploadDocModal() {
    document.getElementById('uploadDocModal')?.classList.add('hidden');
}

function downloadDocument(id) {
    if (!id) return;
    window.open(`backend/api/settings/documents.php?action=download&id=${id}`, '_blank');
}

async function deleteDocument(id, title) {
    if (!confirm(`Are you sure you want to delete compliance document '${title}'?`)) return;
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'delete');
        formData.append('csrf_token', G_CSRF_TOKEN);

        const res = await fetch('backend/api/settings/documents.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadDocuments();
            alert(data.message || 'Compliance document deleted successfully!');
        } else {
            alert('Error deleting document: ' + (data.message || 'Failed.'));
        }
    } catch (e) {
        alert('Network error deleting document.');
    }
}

// Global Scope Exports
window.switchSettingsTab = switchSettingsTab;
window.loadProfile = loadProfile;
window.load2faStatus = load2faStatus;
window.start2faSetup = start2faSetup;
window.loadNotificationPreferences = loadNotificationPreferences;
window.loadApiKeys = loadApiKeys;
window.openCreateApiKeyModal = openCreateApiKeyModal;
window.closeCreateApiKeyModal = closeCreateApiKeyModal;
window.closeShowApiKeyModal = closeShowApiKeyModal;
window.revokeApiKey = revokeApiKey;
window.loadSettingsRoleMatrix = loadSettingsRoleMatrix;
window.saveSettingsRoleMatrix = saveSettingsRoleMatrix;
window.loadDocuments = loadDocuments;
window.openUploadDocModal = openUploadDocModal;
window.closeUploadDocModal = closeUploadDocModal;
window.downloadDocument = downloadDocument;
window.deleteDocument = deleteDocument;

document.addEventListener('DOMContentLoaded', () => {
    loadProfile();

    // Edit Profile Form Submit
    const editProfForm = document.getElementById('editProfileForm');
    if (editProfForm) {
        editProfForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/settings/update-profile.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    loadProfile();
                    alert(data.message || 'Profile information updated successfully!');
                } else {
                    alert('Error updating profile: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error updating profile.');
            }
        });
    }

    // Profile Avatar Form Submit
    const avatarForm = document.getElementById('avatarForm');
    if (avatarForm) {
        avatarForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/settings/update-profile.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    loadProfile();
                    alert(data.message || 'Profile avatar updated successfully!');
                } else {
                    alert('Error updating avatar: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error uploading avatar.');
            }
        });
    }

    // Change Password Form Submit
    const passForm = document.getElementById('changePasswordForm');
    if (passForm) {
        passForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/settings/change-password.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    passForm.reset();
                    alert(data.message || 'Password updated successfully!');
                } else {
                    alert('Error changing password: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error changing password.');
            }
        });
    }

    // Enable 2FA Form Submit
    const enable2faForm = document.getElementById('enable2faForm');
    if (enable2faForm) {
        enable2faForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'enable');
            try {
                const res = await fetch('backend/api/settings/2fa.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    load2faStatus();
                    alert(data.message || '2FA activated successfully!');
                } else {
                    alert('Error activating 2FA: ' + (data.message || 'Invalid code.'));
                }
            } catch (err) {
                alert('Network error activating 2FA.');
            }
        });
    }

    // Disable 2FA Form Submit
    const disable2faForm = document.getElementById('disable2faForm');
    if (disable2faForm) {
        disable2faForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'disable');
            try {
                const res = await fetch('backend/api/settings/2fa.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    disable2faForm.reset();
                    load2faStatus();
                    alert(data.message || '2FA disabled successfully.');
                } else {
                    alert('Error disabling 2FA: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error disabling 2FA.');
            }
        });
    }

    // Notification Preferences Form Submit
    const notifForm = document.getElementById('notificationForm');
    if (notifForm) {
        notifForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/settings/notifications.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    alert(data.message || 'Notification preferences saved successfully!');
                } else {
                    alert('Error saving notification preferences: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error saving notification preferences.');
            }
        });
    }

    // Create API Key Form Submit
    const apiKeyForm = document.getElementById('createApiKeyForm');
    if (apiKeyForm) {
        apiKeyForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create');
            try {
                const res = await fetch('backend/api/settings/api-keys.php', { method: 'POST', body: formData });
                const data = await res.json();
                if ((data.status === 'success' || data.success) && data.data) {
                    closeCreateApiKeyModal();
                    apiKeyForm.reset();
                    loadApiKeys();

                    document.getElementById('rawApiKeyDisplay').innerText = data.data.raw_api_key;
                    document.getElementById('showApiKeyModal')?.classList.remove('hidden');
                } else {
                    alert('Error generating API key: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error generating API key.');
            }
        });
    }

    // Upload Compliance Document Form Submit
    const uploadDocForm = document.getElementById('uploadDocForm');
    if (uploadDocForm) {
        uploadDocForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'upload');
            try {
                const res = await fetch('backend/api/settings/documents.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeUploadDocModal();
                    uploadDocForm.reset();
                    loadDocuments();
                    alert(data.message || 'Compliance document uploaded successfully!');
                } else {
                    alert('Error uploading document: ' + (data.message || 'Failed.'));
                }
            } catch (err) {
                alert('Network error uploading document.');
            }
        });
    }
});
