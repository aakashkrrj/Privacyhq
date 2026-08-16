// governance/assets/js/policies.js
// Policy Library, Governance & Version Control JS Controller

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 KB';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// 1. Live Dashboard Telemetry Metrics & Distribution Analytics (Row 109)
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/policies/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;

            // KPI Cards
            const kpiTotal = document.getElementById('kpi-total');
            const kpiActive = document.getElementById('kpi-active');
            const kpiDraft = document.getElementById('kpi-draft');
            const kpiPending = document.getElementById('kpi-pending');
            const kpiApproved = document.getElementById('kpi-approved');
            const kpiExpired = document.getElementById('kpi-expired');

            if (kpiTotal) kpiTotal.innerText = d.total_policies || 0;
            if (kpiActive) kpiActive.innerText = d.active_policies || 0;
            if (kpiDraft) kpiDraft.innerText = d.draft_policies || 0;
            if (kpiPending) kpiPending.innerText = (d.pending_review_policies || 0) + (d.pending_approval_policies || 0);
            if (kpiApproved) kpiApproved.innerText = d.approved_policies || 0;
            if (kpiExpired) kpiExpired.innerText = (d.expired_policies || 0) + (d.review_due_policies || 0);

            // Card 1: Category Distribution Breakdown
            const distCategory = document.getElementById('dist-category');
            if (distCategory) {
                const cats = d.category_distribution || {};
                const keys = Object.keys(cats);
                const total = d.total_policies || 1;

                if (keys.length === 0) {
                    distCategory.innerHTML = '<div class="text-caption text-gray-500">No policy category records found.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = cats[k] || 0;
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
                    distCategory.innerHTML = html;
                }
            }

            // Card 2: Department Breakdown
            const distDept = document.getElementById('dist-department');
            if (distDept) {
                const depts = d.department_distribution || {};
                const keys = Object.keys(depts);
                const total = d.total_policies || 1;

                if (keys.length === 0) {
                    distDept.innerHTML = '<div class="text-caption text-gray-500">No department data recorded.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = depts[k] || 0;
                        const pct = Math.round((count / total) * 100);
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span>${escapeHtml(k)}</span>
                                    <span class="font-bold text-on-surface">${count} (${pct}%)</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distDept.innerHTML = html;
                }
            }

            // Card 3: Approval Lifecycle Breakdown
            const distApproval = document.getElementById('dist-approval');
            if (distApproval) {
                const apprs = d.approval_distribution || {};
                const keys = Object.keys(apprs);
                const total = d.total_policies || 1;

                if (keys.length === 0) {
                    distApproval.innerHTML = '<div class="text-caption text-gray-500">No approval lifecycle records.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = apprs[k] || 0;
                        const pct = Math.round((count / total) * 100);
                        let barColor = 'bg-indigo-500';
                        if (k === 'approved' || k === 'active') barColor = 'bg-emerald-500';
                        else if (k === 'pending_approval' || k === 'pending_review') barColor = 'bg-amber-500';
                        else if (k === 'rejected') barColor = 'bg-red-500';

                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span class="capitalize">${escapeHtml(k.replace('_', ' '))}</span>
                                    <span class="font-bold text-on-surface">${count} (${pct}%)</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="${barColor} h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distApproval.innerHTML = html;
                }
            }
        }
    } catch (e) {
        console.error('Failed to load Policy dashboard metrics', e);
    }
}

// 2. Paginated Policy Library Register (Row 110)
async function loadRecords() {
    const search = document.getElementById('filter-search')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const department = document.getElementById('filter-department')?.value || '';
    const approvalStatus = document.getElementById('filter-approval')?.value || '';
    const reviewDue = document.getElementById('filter-review-due')?.checked ? '1' : '';

    const url = `backend/api/policies/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}&department=${encodeURIComponent(department)}&approval_status=${encodeURIComponent(approvalStatus)}&review_due=${reviewDue}`;
    
    const tbody = document.getElementById('policyTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading policy library records...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500">No matching policy document records found.</td></tr>';
            } else {
                items.forEach(p => {
                    let statusBadge = 'bg-gray-100 text-gray-800 border-gray-200';
                    if (p.status === 'active') statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    else if (p.status === 'draft') statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    else if (p.status === 'archived') statusBadge = 'bg-gray-100 text-gray-600 border-gray-300';

                    let approvalBadge = 'bg-gray-100 text-gray-700 border-gray-200';
                    const appr = p.approval_status || p.status;
                    if (appr === 'approved' || appr === 'active') approvalBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    else if (appr === 'pending_approval' || appr === 'pending_review') approvalBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                    else if (appr === 'rejected') approvalBadge = 'bg-red-50 text-red-700 border-red-200';
                    else if (appr === 'draft') approvalBadge = 'bg-amber-50 text-amber-700 border-amber-200';

                    const isExpired = p.is_expired;
                    const isReviewDue = p.is_review_due;

                    const downloadBtn = p.document_path 
                        ? `<button onclick="downloadPolicy(${p.id})" title="Download Policy File" class="text-emerald-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Download</button>`
                        : '<span class="text-caption text-gray-400">No File</span>';

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-primary font-bold">${escapeHtml(p.policy_code)}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(p.policy_name)}</div>
                                <div class="text-caption text-on-surface-variant">${escapeHtml(p.category || 'Data Privacy')}</div>
                            </td>
                            <td class="px-lg py-md">
                                <div class="font-medium text-on-surface">${escapeHtml(p.policy_owner || 'Compliance Team')}</div>
                                <span class="text-caption text-on-surface-variant">${escapeHtml(p.department || 'Legal')}</span>
                            </td>
                            <td class="px-lg py-md font-mono text-caption font-semibold text-indigo-700">v${escapeHtml(p.version || '1.0')}</td>
                            <td class="px-lg py-md text-on-surface-variant font-mono text-caption">${escapeHtml(p.effective_date || 'N/A')}</td>
                            <td class="px-lg py-md font-mono text-caption">
                                ${escapeHtml(p.review_date || 'N/A')}
                                ${isReviewDue ? '<span class="ml-1 text-[10px] font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded">DUE</span>' : ''}
                                ${isExpired ? '<span class="ml-1 text-[10px] font-bold text-red-700 bg-red-200 px-1.5 py-0.5 rounded">EXPIRED</span>' : ''}
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${statusBadge}">
                                    ${escapeHtml(p.status.toUpperCase())}
                                </span>
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${approvalBadge}">
                                    ${escapeHtml(appr.replace('_', ' ').toUpperCase())}
                                </span>
                            </td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-1">
                                <button onclick="openPolicyDetailsModal(${p.id})" class="text-primary hover:underline font-semibold text-xs px-1 cursor-pointer">Details</button>
                                ${downloadBtn}
                                <button onclick="openUploadPolicyModal(${p.id}, '${escapeHtml(p.policy_name)}', '${escapeHtml(p.category)}', '${escapeHtml(p.version)}')" title="Upload New Version" class="text-emerald-700 hover:underline font-semibold text-xs px-1 cursor-pointer">+Version</button>
                                <button onclick="openVersionHistoryModal(${p.id})" class="text-purple-600 hover:underline font-semibold text-xs px-1 cursor-pointer">History</button>
                                <button onclick="openApprovalWorkflowModal(${p.id})" class="text-amber-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Approve</button>
                                <button onclick="deletePolicy(${p.id})" class="text-red-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10) || 1;
            const paginationDiv = document.getElementById('policyPagination');
            if (paginationDiv) {
                const startIdx = total === 0 ? 0 : (currentPage - 1) * 10 + 1;
                const endIdx = Math.min(currentPage * 10, total);
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${startIdx}–${endIdx}</strong> of <strong>${total}</strong> Policy Documents
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load Policy library records', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-red-600">Failed to load policy document inventory.</td></tr>';
        }
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadRecords();
}

// 3. Download Document (Row 112)
function downloadPolicy(id) {
    if (!id) return;
    const url = `backend/api/policies/download.php?id=${id}`;
    window.open(url, '_blank');
}

// 4. Policy Details Modal
async function openPolicyDetailsModal(id) {
    if (!id) return;
    const modal = document.getElementById('policyDetailsModal');
    const content = document.getElementById('policyDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading policy document profile...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/policies/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const p = data.data;
            content.innerHTML = `
                <div class="space-y-4 text-sm">
                    <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant flex justify-between items-center">
                        <div>
                            <span class="text-caption text-primary font-mono font-bold">${escapeHtml(p.policy_code)}</span>
                            <h4 class="font-bold text-on-surface text-title-md">${escapeHtml(p.policy_name)}</h4>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-3 py-1 text-caption font-semibold rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">
                                ${escapeHtml(p.status.toUpperCase())}
                            </span>
                            <span class="px-3 py-1 text-caption font-semibold rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200">
                                ${escapeHtml((p.approval_status || p.status).toUpperCase())}
                            </span>
                        </div>
                    </div>

                    ${p.description ? `
                    <div class="p-3 bg-surface-container-low rounded-lg border">
                        <span class="text-caption text-on-surface-variant block font-semibold uppercase">Description / Scope</span>
                        <p class="text-on-surface mt-1">${escapeHtml(p.description)}</p>
                    </div>` : ''}

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Category</span>
                            <strong class="text-primary">${escapeHtml(p.category || 'Data Privacy')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Version</span>
                            <strong class="text-indigo-700 font-mono">v${escapeHtml(p.version || '1.0')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Policy Owner</span>
                            <strong class="text-on-surface">${escapeHtml(p.policy_owner || 'DPO')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Department</span>
                            <strong class="text-on-surface">${escapeHtml(p.department || 'Legal')}</strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Effective Date</span>
                            <strong class="font-mono text-on-surface">${escapeHtml(p.effective_date || 'N/A')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Review Target Date</span>
                            <strong class="font-mono text-indigo-700">${escapeHtml(p.review_date || 'N/A')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Expiry Date</span>
                            <strong class="font-mono text-red-600">${escapeHtml(p.expiry_date || 'N/A')}</strong>
                        </div>
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-lg border space-y-1">
                        <h5 class="font-semibold text-xs uppercase text-primary mb-1">Associated Document File</h5>
                        <div><span class="text-on-surface-variant">File Name:</span> <strong>${escapeHtml(p.file_name || (p.document_path ? p.document_path.split('/').pop() : 'No file attached'))}</strong></div>
                        <div><span class="text-on-surface-variant">File Type:</span> <strong class="uppercase">${escapeHtml(p.file_type || 'N/A')}</strong></div>
                        <div><span class="text-on-surface-variant">File Size:</span> <strong>${formatBytes(p.file_size)}</strong></div>
                        ${p.document_path ? `
                        <div class="mt-2 pt-2 border-t border-outline-variant">
                            <button onclick="downloadPolicy(${p.id})" class="px-3 py-1.5 bg-emerald-600 text-white font-semibold text-xs rounded-lg hover:bg-emerald-700 cursor-pointer">
                                <span class="material-symbols-outlined text-[14px] align-middle mr-1">download</span> Download File Document
                            </button>
                        </div>` : ''}
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load policy profile details.</div>';
        }
    } catch (e) {
        console.error('Failed to load Policy details', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading policy document details.</div>';
    }
}

function closePolicyDetailsModal() {
    const modal = document.getElementById('policyDetailsModal');
    if (modal) modal.classList.add('hidden');
}

// 5. Version History Modal (Row 113)
async function openVersionHistoryModal(policyId) {
    if (!policyId) return;
    const modal = document.getElementById('versionHistoryModal');
    const content = document.getElementById('versionHistoryContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading version history timeline...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/policies/versions.php?id=${policyId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const items = data.data || [];
            if (items.length === 0) {
                content.innerHTML = '<div class="text-center py-6 text-gray-500">No version history recorded for this policy document.</div>';
            } else {
                let html = '<div class="space-y-3">';
                items.forEach(v => {
                    const uploader = (v.uploader_first ? v.uploader_first + ' ' + v.uploader_last : v.uploader_email) || 'Compliance Administrator';
                    html += `
                        <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant text-caption">
                            <div class="flex justify-between items-center mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 font-mono font-bold text-xs rounded">v${escapeHtml(v.version_number)}</span>
                                    <span class="font-semibold text-on-surface">${escapeHtml(v.file_name || 'Document Version')}</span>
                                </div>
                                <span class="text-on-surface-variant font-mono text-[11px]">${escapeHtml(v.created_at || '')}</span>
                            </div>
                            <div class="text-on-surface-variant">Uploaded By: <strong>${escapeHtml(uploader)}</strong> (${formatBytes(v.file_size)})</div>
                            ${v.change_summary ? `<div class="mt-1.5 p-2 bg-white rounded border border-gray-100 italic text-gray-700">"${escapeHtml(v.change_summary)}"</div>` : ''}
                            ${v.document_path ? `
                            <div class="mt-2">
                                <button onclick="downloadPolicy(${v.policy_id})" class="text-primary hover:underline font-semibold text-xs cursor-pointer">
                                    <span class="material-symbols-outlined text-[14px] align-middle mr-0.5">download</span> Download v${escapeHtml(v.version_number)}
                                </button>
                            </div>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            }
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load version history.</div>';
        }
    } catch (e) {
        console.error('Failed to load version history', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading version history.</div>';
    }
}

function closeVersionHistoryModal() {
    const modal = document.getElementById('versionHistoryModal');
    if (modal) modal.classList.add('hidden');
}

// 6. Approval Workflow Modal & Submit Actions (Row 114)
async function openApprovalWorkflowModal(policyId) {
    if (!policyId) return;
    const modal = document.getElementById('approvalWorkflowModal');
    if (!modal) return;

    try {
        const res = await fetch(`backend/api/policies/get.php?id=${policyId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const p = data.data;
            document.getElementById('approval_policy_id').value = p.id;
            document.getElementById('approval_policy_code').innerText = p.policy_code;
            document.getElementById('approval_policy_title').innerText = p.policy_name;
            document.getElementById('approval_policy_status').innerText = (p.approval_status || p.status).replace('_', ' ');
            document.getElementById('approval_comments').value = '';

            modal.classList.remove('hidden');
        } else {
            alert('Failed to load policy record for approval.');
        }
    } catch (e) {
        alert('Failed to load approval workflow modal.');
    }
}

function closeApprovalWorkflowModal() {
    const modal = document.getElementById('approvalWorkflowModal');
    if (modal) modal.classList.add('hidden');
}

async function submitApprovalAction(action) {
    const policyId = document.getElementById('approval_policy_id').value;
    const comments = document.getElementById('approval_comments').value.trim();

    if (!comments) {
        alert('Please enter review comments / audit notes before proceeding with approval action.');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', G_CSRF_TOKEN);
    formData.append('policy_id', policyId);
    formData.append('action', action);
    formData.append('comments', comments);

    try {
        const res = await fetch('backend/api/policies/approve.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            closeApprovalWorkflowModal();
            loadRecords();
            loadDashboard();
            alert(data.message || 'Approval action executed successfully!');
        } else {
            alert('Error: ' + (data.message || 'Approval action failed.'));
        }
    } catch (e) {
        alert('Network request failed executing approval action.');
    }
}

// 7. Delete Policy Workflow
async function deletePolicy(id) {
    if (confirm("Are you sure you want to soft-delete this compliance policy document from the policy library?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('id', id);

        try {
            const res = await fetch('backend/api/policies/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadRecords();
                loadDashboard();
                alert('Policy document soft-deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete policy document.');
            }
        } catch (e) {
            alert('Request failed. Connection error.');
        }
    }
}

// 8. Export Reports
function exportPolicies(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const department = document.getElementById('filter-department')?.value || '';
    const approvalStatus = document.getElementById('filter-approval')?.value || '';
    const reviewDue = document.getElementById('filter-review-due')?.checked ? '1' : '';

    const url = `backend/api/policies/export.php?format=${format}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}&department=${encodeURIComponent(department)}&approval_status=${encodeURIComponent(approvalStatus)}&review_due=${reviewDue}`;
    window.open(url, '_blank');
}

function clearPolicyFilters() {
    const s = document.getElementById('filter-search');
    const st = document.getElementById('filter-status');
    const c = document.getElementById('filter-category');
    const d = document.getElementById('filter-department');
    const a = document.getElementById('filter-approval');
    const r = document.getElementById('filter-review-due');

    if (s) s.value = '';
    if (st) st.value = '';
    if (c) c.value = '';
    if (d) d.value = '';
    if (a) a.value = '';
    if (r) r.checked = false;

    currentPage = 1;
    loadRecords();
}

// 9. Modals Open / Close Helpers
function openCreatePolicyModal() {
    const form = document.getElementById('createPolicyForm');
    if (form) form.reset();
    document.getElementById('createPolicyModal').classList.remove('hidden');
}

function closeCreatePolicyModal() {
    document.getElementById('createPolicyModal').classList.add('hidden');
}

function openUploadPolicyModal(policyId = '', title = '', category = 'Data Privacy', currentVer = '1.0') {
    const form = document.getElementById('uploadPolicyForm');
    if (form) form.reset();

    const titleEl = document.getElementById('uploadModalTitle');
    const idEl = document.getElementById('upload_policy_id');
    const nameEl = document.getElementById('upload_policy_title');
    const catEl = document.getElementById('upload_policy_category');
    const verEl = document.getElementById('upload_policy_version');

    if (policyId) {
        if (titleEl) titleEl.innerText = `Upload New Version (POL-${String(policyId).padStart(4, '0')})`;
        if (idEl) idEl.value = policyId;
        if (nameEl) nameEl.value = title;
        if (catEl) catEl.value = category;
        
        // Auto increment minor version
        const vParts = currentVer.split('.');
        if (vParts.length === 2 && !isNaN(vParts[1])) {
            verEl.value = `${vParts[0]}.${parseInt(vParts[1]) + 1}`;
        } else {
            verEl.value = '1.1';
        }
    } else {
        if (titleEl) titleEl.innerText = 'Upload Policy Document';
        if (idEl) idEl.value = '';
        if (verEl) verEl.value = '1.0';
    }

    document.getElementById('uploadPolicyModal').classList.remove('hidden');
}

function closeUploadPolicyModal() {
    document.getElementById('uploadPolicyModal').classList.add('hidden');
}

// Global Scope Exports
window.loadDashboard = loadDashboard;
window.loadRecords = loadRecords;
window.openCreatePolicyModal = openCreatePolicyModal;
window.closeCreatePolicyModal = closeCreatePolicyModal;
window.openUploadPolicyModal = openUploadPolicyModal;
window.closeUploadPolicyModal = closeUploadPolicyModal;
window.openPolicyDetailsModal = openPolicyDetailsModal;
window.closePolicyDetailsModal = closePolicyDetailsModal;
window.openVersionHistoryModal = openVersionHistoryModal;
window.closeVersionHistoryModal = closeVersionHistoryModal;
window.openApprovalWorkflowModal = openApprovalWorkflowModal;
window.closeApprovalWorkflowModal = closeApprovalWorkflowModal;
window.submitApprovalAction = submitApprovalAction;
window.downloadPolicy = downloadPolicy;
window.deletePolicy = deletePolicy;
window.exportPolicies = exportPolicies;
window.clearPolicyFilters = clearPolicyFilters;
window.changePage = changePage;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRecords();

    const searchForm = document.getElementById('policySearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadRecords();
        });
    }

    // Submit Create Policy Form
    const createForm = document.getElementById('createPolicyForm');
    if (createForm) {
        createForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch('backend/api/policies/create.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeCreatePolicyModal();
                    loadRecords();
                    loadDashboard();
                    alert(data.message || 'Policy document created successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                alert('Network error saving policy document.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // Submit Upload Policy Form
    const uploadForm = document.getElementById('uploadPolicyForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch('backend/api/policies/upload.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeUploadPolicyModal();
                    loadRecords();
                    loadDashboard();
                    alert(data.message || 'Policy document uploaded successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Upload failed'));
                }
            } catch (err) {
                alert('Network error uploading policy file.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
