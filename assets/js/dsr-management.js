// assets/js/dsr-management.js

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadDashboard() {
    try {
        const res = await fetch('backend/api/dsr/dashboard.php');
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            document.getElementById('kpi-total').innerText = data.data.total;
            document.getElementById('kpi-pending').innerText = data.data.pending;
            document.getElementById('kpi-completed').innerText = data.data.completed;
            
            document.getElementById('kpi-sla').innerText = data.data.sla_compliance;
            document.getElementById('kpi-avg-res').innerText = data.data.avg_resolution;
            document.getElementById('kpi-high-priority').innerText = data.data.open_high_priority;

            const dist = data.data.distribution;
            document.getElementById('dist-access').innerText = dist.access + '%';
            document.getElementById('bar-access').style.width = dist.access + '%';
            
            document.getElementById('dist-erasure').innerText = dist.erasure + '%';
            document.getElementById('bar-erasure').style.width = dist.erasure + '%';

            document.getElementById('dist-portability').innerText = dist.portability + '%';
            document.getElementById('bar-portability').style.width = dist.portability + '%';

            document.getElementById('dist-rectification').innerText = dist.rectification + '%';
            document.getElementById('bar-rectification').style.width = dist.rectification + '%';

            const perf = data.data.performance;
            document.getElementById('perf-verified').innerText = perf.verified;
            document.getElementById('perf-completed').innerText = perf.completed;
            document.getElementById('perf-pending').innerText = perf.pending;
            document.getElementById('perf-escalated').innerText = perf.escalated;
        }
    } catch (e) {
        console.error('Failed to load DSR dashboard', e);
    }
}

async function loadRequests() {
    const search = document.getElementById('filter-search').value;
    const status = document.getElementById('filter-status').value;
    const type = document.getElementById('filter-type').value;

    const url = `backend/api/dsr/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&type=${encodeURIComponent(type)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('dsrTableBody');
        
        if (data.status === 'success') {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No requests found.</td></tr>';
            } else {
                items.forEach(r => {
                    let statusClass = 'bg-gray-100 text-gray-800';
                    if (r.status === 'completed') statusClass = 'bg-green-100 text-green-800';
                    if (r.status === 'open' || r.status === 'processing' || r.status === 'verifying') statusClass = 'bg-yellow-100 text-yellow-800';
                    if (r.status === 'rejected') statusClass = 'bg-red-100 text-red-800';

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-4 font-medium text-gray-900">${escapeHtml(r.request_id_code)}</td>
                            <td class="p-4 text-gray-600">${escapeHtml(r.subject_email)}</td>
                            <td class="p-4 text-gray-600 uppercase text-xs font-semibold">${escapeHtml(r.request_type)}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                                    ${escapeHtml(r.status)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600 text-sm">${escapeHtml(r.due_date)}</td>
                            <td class="p-4 text-right">
                                <button onclick="openStatusModal(${r.id}, '${escapeHtml(r.status)}')" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm mx-1">Status</button>
                                <button onclick="deleteRequest(${r.id})" class="text-red-600 hover:text-red-900 font-medium text-sm mx-1">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination
            const totalPages = Math.ceil(total / 10);
            const controls = document.getElementById('paginationControls');
            if (totalPages > 1) {
                controls.classList.remove('hidden');
                document.getElementById('pageInfo').innerText = `Showing page ${currentPage} of ${totalPages}`;
                document.getElementById('btnPrev').style.display = currentPage > 1 ? 'block' : 'none';
                document.getElementById('btnNext').style.display = currentPage < totalPages ? 'block' : 'none';
            } else {
                controls.classList.add('hidden');
            }
        }
    } catch (e) {
        console.error('Failed to load requests', e);
    }
}

async function submitApi(formId, endpoint, modalIdToClose) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadRequests();
            loadDashboard();
            form.reset();
            if (modalIdToClose) {
                document.getElementById(modalIdToClose).classList.add('hidden');
            }
        } else {
            alert(data.message || 'Error occurred');
        }
    } catch (e) {
        alert('Request failed');
    }
}

async function deleteRequest(id) {
    if (confirm('Are you sure you want to delete this DSR?')) {
        const fd = new FormData();
        fd.append('request_id', id);
        fd.append('csrf_token', G_CSRF_TOKEN);
        
        try {
            const res = await fetch('backend/api/dsr/delete.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                loadRequests();
                loadDashboard();
            } else {
                alert(data.message || 'Error occurred');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

// Request Select Options Loader
async function loadRequestSelectOptions(selectElementId, preselectedId = '') {
    try {
        const res = await fetch('backend/api/dsr/list.php?p=1&limit=1000');
        const data = await res.json();
        const select = document.getElementById(selectElementId);
        if (data.status === 'success' && data.data && data.data.items) {
            select.innerHTML = '<option value="">Choose a request...</option>';
            data.data.items.forEach(r => {
                const isSel = String(r.id) === String(preselectedId) ? 'selected' : '';
                select.innerHTML += `<option value="${r.id}" ${isSel}>${escapeHtml(r.request_id_code)} (${escapeHtml(r.subject_email)})</option>`;
            });
        }
    } catch (e) {
        console.error('Failed to load request options', e);
    }
}

// Assignees Loader
async function loadAssignees(preselectedId = '') {
    try {
        const res = await fetch('backend/api/dsr/assignees.php');
        const data = await res.json();
        const select = document.getElementById('assignee_select');
        if (data.success && data.data) {
            select.innerHTML = '<option value="">Choose officer...</option>';
            data.data.forEach(u => {
                const isSel = String(u.id) === String(preselectedId) ? 'selected' : '';
                const name = `${u.first_name || ''} ${u.last_name || ''}`.trim() || u.email;
                select.innerHTML += `<option value="${u.id}" ${isSel}>${escapeHtml(name)}</option>`;
            });
        }
    } catch (e) {
        console.error('Failed to load assignees', e);
    }
}

// Review Pending loader
async function loadPendingRequests() {
    try {
        const res = await fetch('backend/api/dsr/pending.php');
        const data = await res.json();
        const tbody = document.getElementById('pendingTableBody');
        
        if (data.status === 'success' && data.data) {
            tbody.innerHTML = '';
            const items = data.data;
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-500">All requests are fully processed, verified, and assigned!</td></tr>';
            } else {
                items.forEach(r => {
                    let reasons = [];
                    let actionButton = '';

                    if (r.verification_status === 'pending') {
                        reasons.push("Identity Verification Required");
                        actionButton = `<button onclick="triggerVerifyFromReview(${r.id})" class="text-green-600 hover:text-green-900 font-semibold text-xs mx-1">Verify</button>`;
                    }
                    if (!r.assigned_to) {
                        reasons.push("Request Unassigned");
                        if (!actionButton) {
                            actionButton = `<button onclick="triggerAssignFromReview(${r.id})" class="text-blue-600 hover:text-blue-900 font-semibold text-xs mx-1">Assign</button>`;
                        }
                    }
                    if (r.status === 'open' || r.status === 'verifying') {
                        reasons.push("Needs Status Update");
                        if (!actionButton) {
                            actionButton = `<button onclick="triggerStatusFromReview(${r.id}, '${escapeHtml(r.status)}')" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs mx-1">Update</button>`;
                        }
                    }

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-3 font-semibold text-gray-900">${escapeHtml(r.request_id_code)}</td>
                            <td class="p-3 text-gray-600">${escapeHtml(r.subject_email)}</td>
                            <td class="p-3 text-red-600 text-xs font-semibold">${escapeHtml(reasons.join(' & '))}</td>
                            <td class="p-3 text-right">${actionButton}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load pending requests', e);
    }
}

// Modal actions
function openDsrModal() {
    document.getElementById('dsrModal').classList.remove('hidden');
}

function closeDsrModal() {
    document.getElementById('dsrModal').classList.add('hidden');
}

function openStatusModal(id, currentStatus) {
    document.getElementById('status_request_id').value = id;
    document.getElementById('status_select').value = currentStatus;
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

function openVerifySubjectModal(preselectedId = '') {
    loadRequestSelectOptions('verify_request_select', preselectedId);
    document.getElementById('verifySubjectModal').classList.remove('hidden');
}

function closeVerifySubjectModal() {
    document.getElementById('verifySubjectModal').classList.add('hidden');
}

function openAssignRequestModal(preselectedId = '') {
    loadRequestSelectOptions('assign_request_select', preselectedId);
    loadAssignees();
    document.getElementById('assignRequestModal').classList.remove('hidden');
}

function closeAssignRequestModal() {
    document.getElementById('assignRequestModal').classList.add('hidden');
}

function openReviewPendingModal() {
    loadPendingRequests();
    document.getElementById('reviewPendingModal').classList.remove('hidden');
}

function closeReviewPendingModal() {
    document.getElementById('reviewPendingModal').classList.add('hidden');
}

// Quick triggers from review modal
function triggerVerifyFromReview(id) {
    closeReviewPendingModal();
    openVerifySubjectModal(id);
}

function triggerAssignFromReview(id) {
    closeReviewPendingModal();
    openAssignRequestModal(id);
}

function triggerStatusFromReview(id, status) {
    closeReviewPendingModal();
    openStatusModal(id, status);
}

// Bind to window for HTML triggers
window.deleteRequest = deleteRequest;
window.openStatusModal = openStatusModal;
window.closeStatusModal = closeStatusModal;
window.openDsrModal = openDsrModal;
window.closeDsrModal = closeDsrModal;
window.triggerVerifyFromReview = triggerVerifyFromReview;
window.triggerAssignFromReview = triggerAssignFromReview;
window.triggerStatusFromReview = triggerStatusFromReview;

// Bind DOM event listeners on load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRequests();

    document.getElementById('searchForm').addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        loadRequests();
    });

    document.getElementById('btnPrev').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadRequests();
        }
    });

    document.getElementById('btnNext').addEventListener('click', () => {
        currentPage++;
        loadRequests();
    });

    // Form submit handlers
    document.getElementById('addDsrForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('addDsrForm', 'backend/api/dsr/create.php', 'dsrModal');
    });

    document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('changeStatusForm', 'backend/api/dsr/change-status.php', 'statusModal');
    });

    document.getElementById('verifySubjectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('verifySubjectForm', 'backend/api/dsr/verify.php', 'verifySubjectModal');
    });

    document.getElementById('assignRequestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('assignRequestForm', 'backend/api/dsr/assign.php', 'assignRequestModal');
    });

    // Quick Action button listeners
    document.getElementById('btn-log-request-qa').addEventListener('click', openDsrModal);
    document.getElementById('btn-verify-subject-qa').addEventListener('click', () => openVerifySubjectModal());
    document.getElementById('btn-assign-request-qa').addEventListener('click', () => openAssignRequestModal());
    document.getElementById('btn-review-requests-qa').addEventListener('click', openReviewPendingModal);

    document.getElementById('btn-export-dsr-qa').addEventListener('click', () => {
        const search = document.getElementById('filter-search').value;
        const status = document.getElementById('filter-status').value;
        const type = document.getElementById('filter-type').value;
        window.open(`backend/api/dsr/export.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&type=${encodeURIComponent(type)}`, '_blank');
    });

    document.getElementById('btn-generate-report-qa').addEventListener('click', () => {
        window.open('backend/api/reports/dsr.php', '_blank');
    });

    // Modal closes
    document.getElementById('closeVerifySubjectModal').addEventListener('click', closeVerifySubjectModal);
    document.getElementById('btnCancelVerifySubject').addEventListener('click', closeVerifySubjectModal);

    document.getElementById('closeAssignRequestModal').addEventListener('click', closeAssignRequestModal);
    document.getElementById('btnCancelAssignRequest').addEventListener('click', closeAssignRequestModal);

    document.getElementById('closeReviewPendingModal').addEventListener('click', closeReviewPendingModal);
    document.getElementById('btnCloseReviewPendingModal').addEventListener('click', closeReviewPendingModal);
});
