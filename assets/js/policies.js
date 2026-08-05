// assets/js/policies.js

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadDashboard() {
    try {
        const res = await fetch('backend/api/policies/dashboard.php');
        const data = await res.json();
        if (data.success && data.data) {
            document.getElementById('kpi-total').innerText = data.data.total_policies;
            document.getElementById('kpi-active').innerText = data.data.active_policies;
            document.getElementById('kpi-draft').innerText = data.data.draft_policies;
            document.getElementById('kpi-archived').innerText = data.data.archived_policies;
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics', e);
    }
}

async function loadRecords() {
    try {
        const res = await fetch('backend/api/policies/list.php');
        const data = await res.json();
        const tbody = document.getElementById('policyTableBody');
        
        if (data.success) {
            tbody.innerHTML = '';
            const items = data.data || [];
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No policy documents generated yet.</td></tr>';
            } else {
                items.forEach(p => {
                    const statusClass = p.status === 'active' ? 'bg-success' : (p.status === 'draft' ? 'bg-warning text-dark' : 'bg-dark');
                    const downloadLink = p.document_path 
                        ? `<a href="backend/api/policies/download.php?id=${p.id}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-download"></i> View</a>` 
                        : '<span class="text-muted small">No file</span>';

                    const row = `
                        <tr>
                            <td><strong>${escapeHtml(p.policy_name)}</strong></td>
                            <td><span class="badge bg-secondary">v${escapeHtml(p.version)}</span></td>
                            <td>
                                <span class="badge ${statusClass}">
                                    ${escapeHtml(p.status.toUpperCase())}
                                </span>
                            </td>
                            <td class="small text-muted">${escapeHtml(p.effective_date)}</td>
                            <td>
                                <button onclick="showVersionHistory('${escapeHtml(p.policy_name)}')" class="btn btn-sm btn-outline-secondary py-0 px-2"><i class="bi bi-clock-history"></i> History</button>
                                ${downloadLink}
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load records', e);
    }
}

async function showVersionHistory(policyName) {
    try {
        const res = await fetch(`backend/api/policies/history.php?name=${encodeURIComponent(policyName)}`);
        const data = await res.json();
        const tbody = document.getElementById('historyTableBody');
        
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data;
            
            items.forEach(h => {
                const statusClass = h.status === 'active' ? 'bg-success' : (h.status === 'draft' ? 'bg-warning text-dark' : 'bg-dark');
                const pathLink = h.document_path 
                    ? `<a href="backend/api/policies/download.php?id=${h.id}" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark-arrow-down"></i> Download</a>` 
                    : '<span class="text-muted">No file</span>';
                
                const row = `
                    <tr>
                        <td>v${escapeHtml(h.version)}</td>
                        <td><span class="badge ${statusClass}">${escapeHtml(h.status.toUpperCase())}</span></td>
                        <td>${escapeHtml(h.effective_date)}</td>
                        <td>${pathLink}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            
            const modal = new bootstrap.Modal(document.getElementById('versionHistoryModal'));
            modal.show();
        }
    } catch (e) {
        alert('Failed to load version history.');
    }
}

async function loadPendingApprovals() {
    try {
        const res = await fetch('backend/api/policies/list.php?status=draft');
        const data = await res.json();
        const tbody = document.getElementById('approvalTableBody');
        
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No pending approvals.</td></tr>';
            } else {
                items.forEach(p => {
                    const row = `
                        <tr>
                            <td><strong>${escapeHtml(p.policy_name)}</strong></td>
                            <td>v${escapeHtml(p.version)}</td>
                            <td><span class="badge bg-warning text-dark">DRAFT / PENDING</span></td>
                            <td class="text-end">
                                <button onclick="actionApproval(${p.id}, 'active')" class="btn btn-sm btn-success py-1"><i class="bi bi-check-lg"></i> Approve</button>
                                <button onclick="actionApproval(${p.id}, 'archived')" class="btn btn-sm btn-danger py-1"><i class="bi bi-x-lg"></i> Reject</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load approvals', e);
    }
}

async function actionApproval(policyId, status) {
    const fd = new FormData();
    fd.append('csrf_token', G_CSRF_TOKEN);
    fd.append('policy_id', policyId);
    fd.append('status', status);

    try {
        const res = await fetch('backend/api/policies/approve.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            loadPendingApprovals();
            loadRecords();
            loadDashboard();
        } else {
            alert(data.message || 'Error executing action.');
        }
    } catch (e) {
        alert('Network request failed.');
    }
}

// Bind to window for click triggers
window.showVersionHistory = showVersionHistory;
window.actionApproval = actionApproval;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRecords();

    // Quick Action button listeners
    document.getElementById('btn-create-policy-qa').addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('createPolicyModal'));
        modal.show();
    });

    document.getElementById('btn-upload-policy-qa').addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('uploadPolicyModal'));
        modal.show();
    });

    document.getElementById('btn-version-history-qa').addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('versionHistoryModal'));
        modal.show();
    });

    document.getElementById('btn-approval-workflow-qa').addEventListener('click', () => {
        loadPendingApprovals();
        const modal = new bootstrap.Modal(document.getElementById('approvalWorkflowModal'));
        modal.show();
    });

    // Create Form (Modal) Submit
    document.getElementById('modalCreatePolicyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('csrf_token', G_CSRF_TOKEN);

        try {
            const res = await fetch('backend/api/policies/create.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                bootstrap.Modal.getInstance(document.getElementById('createPolicyModal')).hide();
                this.reset();
                loadRecords();
                loadDashboard();
            } else {
                alert(data.message || 'Failed to create policy.');
            }
        } catch (e) {
            alert('Request failed.');
        }
    });

    // Create Form (Page-level) Submit AJAX fallback
    const pageForm = document.querySelector('form:not([id])');
    if (pageForm) {
        pageForm.removeAttribute('action');
        pageForm.removeAttribute('method');
        pageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append('csrf_token', G_CSRF_TOKEN);

            try {
                const res = await fetch('backend/api/policies/create.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    this.reset();
                    loadRecords();
                    loadDashboard();
                } else {
                    alert(data.message || 'Failed to create policy.');
                }
            } catch (e) {
                alert('Request failed.');
            }
        });
    }

    // Upload Form Submit
    document.getElementById('modalUploadPolicyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('csrf_token', G_CSRF_TOKEN);

        try {
            const res = await fetch('backend/api/policies/upload.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                bootstrap.Modal.getInstance(document.getElementById('uploadPolicyModal')).hide();
                this.reset();
                loadRecords();
                loadDashboard();
            } else {
                alert(data.message || 'Failed to upload policy.');
            }
        } catch (e) {
            alert('Upload failed.');
        }
    });
});
