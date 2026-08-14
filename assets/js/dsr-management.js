// assets/js/dsr-management.js
// Enterprise Data Subject Requests (DSR) Management Frontend Engine

let currentPage = 1;
let currentLimit = 10;
let currentSortBy = 'id';
let currentSortOrder = 'DESC';
let activeDetailsRequestId = null;

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 1. Dashboard Metrics Loader
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/dsr/dashboard.php');
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            const d = data.data;
            document.getElementById('kpi-total').innerText = d.total || 0;
            document.getElementById('kpi-open').innerText = d.open || 0;
            document.getElementById('kpi-pending').innerText = d.pending || 0;
            document.getElementById('kpi-completed').innerText = d.completed || 0;
            document.getElementById('kpi-rejected').innerText = d.rejected || 0;
            document.getElementById('kpi-pending-today').innerText = d.pending_today || 0;
            document.getElementById('kpi-avg-res').innerText = d.avg_resolution || '0.0 Days';
            document.getElementById('kpi-sla').innerText = d.sla_compliance || '100%';
        }
    } catch (e) {
        console.error('Failed to load DSR dashboard metrics', e);
    }
}

// 2. Request Register DataTable Loader
async function loadRequests() {
    const search = document.getElementById('filter-search').value.trim();
    const status = document.getElementById('filter-status').value;
    const priority = document.getElementById('filter-priority').value;
    const type = document.getElementById('filter-type').value;
    const assigned = document.getElementById('filter-assigned').value;
    const fromDate = document.getElementById('filter-from-date').value;
    const toDate = document.getElementById('filter-to-date').value;

    const queryParams = new URLSearchParams({
        p: currentPage,
        limit: currentLimit,
        search: search,
        status: status,
        priority: priority,
        type: type,
        assigned_to: assigned,
        from_date: fromDate,
        to_date: toDate,
        sort_by: currentSortBy,
        sort_order: currentSortOrder
    });

    const tbody = document.getElementById('dsrTableBody');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-gray-500"><div class="flex flex-col items-center"><span class="material-symbols-outlined animate-spin text-2xl text-indigo-600 mb-2">sync</span>Loading DSR register...</div></td></tr>`;

    try {
        const res = await fetch(`backend/api/dsr/list.php?${queryParams.toString()}`);
        const data = await res.json();
        
        if (data.status === 'success' && data.data) {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            document.getElementById('registerCountInfo').innerText = `Total Records: ${total}`;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-gray-500"><span class="material-symbols-outlined text-3xl text-gray-400 block mb-1">folder_off</span>No matching DSR requests found.</td></tr>`;
            } else {
                items.forEach(r => {
                    let statusBadgeClass = 'bg-gray-100 text-gray-800';
                    if (r.status === 'completed') statusBadgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                    else if (r.status === 'processing' || r.status === 'assigned') statusBadgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';
                    else if (r.status === 'open' || r.status === 'verifying') statusBadgeClass = 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                    else if (r.status === 'rejected' || r.status === 'cancelled') statusBadgeClass = 'bg-rose-100 text-rose-800 border border-rose-200';

                    let priorityBadgeClass = 'bg-gray-100 text-gray-700';
                    if (r.priority === 'High') priorityBadgeClass = 'bg-orange-100 text-orange-800';
                    else if (r.priority === 'Urgent') priorityBadgeClass = 'bg-red-100 text-red-800 font-bold';

                    const row = `
                        <tr class="hover:bg-indigo-50/50 transition-colors border-b border-gray-100">
                            <td class="p-3.5 font-bold text-gray-900">
                                <a href="javascript:void(0)" onclick="viewRequestDetails(${r.id})" class="text-indigo-600 hover:underline">
                                    ${escapeHtml(r.request_id_code)}
                                </a>
                            </td>
                            <td class="p-3.5">
                                <div class="font-semibold text-gray-900">${escapeHtml(r.subject_name || 'N/A')}</div>
                                <div class="text-xs text-gray-500">${escapeHtml(r.subject_email)}</div>
                            </td>
                            <td class="p-3.5 text-gray-600 text-xs">${escapeHtml(r.subject_dept || 'N/A')}</td>
                            <td class="p-3.5 text-gray-700 uppercase font-semibold text-xs">${escapeHtml(r.request_type)}</td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full ${priorityBadgeClass}">
                                    ${escapeHtml(r.priority)}
                                </span>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full capitalize ${statusBadgeClass}">
                                    ${escapeHtml(r.status)}
                                </span>
                            </td>
                            <td class="p-3.5 text-gray-600 text-xs font-medium">${escapeHtml(r.due_date || 'N/A')}</td>
                            <td class="p-3.5 text-right whitespace-nowrap">
                                <button onclick="viewRequestDetails(${r.id})" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs mx-1">View</button>
                                <button onclick="openEditDsrModal(${r.id})" class="text-blue-600 hover:text-blue-900 font-semibold text-xs mx-1">Edit</button>
                                <button onclick="openStatusModal(${r.id}, '${escapeHtml(r.status)}')" class="text-amber-600 hover:text-amber-900 font-semibold text-xs mx-1">Status</button>
                                <button onclick="deleteRequest(${r.id})" class="text-rose-600 hover:text-rose-900 font-semibold text-xs mx-1">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / currentLimit) || 1;
            const startItem = total > 0 ? (currentPage - 1) * currentLimit + 1 : 0;
            const endItem = Math.min(currentPage * currentLimit, total);

            document.getElementById('pageInfo').innerText = `Showing ${startItem}-${endItem} of ${total} requests (Page ${currentPage} of ${totalPages})`;
            document.getElementById('btnPrev').disabled = (currentPage <= 1);
            document.getElementById('btnNext').disabled = (currentPage >= totalPages);
        }
    } catch (e) {
        console.error('Failed to load DSR requests', e);
    }
}

function executeSearch() {
    currentPage = 1;
    loadRequests();
}

function resetFilters() {
    document.getElementById('filter-search').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-priority').value = '';
    document.getElementById('filter-type').value = '';
    document.getElementById('filter-assigned').value = '';
    document.getElementById('filter-from-date').value = '';
    document.getElementById('filter-to-date').value = '';
    currentPage = 1;
    loadRequests();
}

function sortTable(column) {
    if (currentSortBy === column) {
        currentSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
    } else {
        currentSortBy = column;
        currentSortOrder = 'DESC';
    }
    loadRequests();
}

function changePage(direction) {
    currentPage += direction;
    if (currentPage < 1) currentPage = 1;
    loadRequests();
}

// 3. Assignees & Request Select Loader
async function loadAssignees() {
    try {
        const res = await fetch('backend/api/dsr/assignees.php');
        const data = await res.json();
        const selects = [document.getElementById('assignee_select'), document.getElementById('filter-assigned')];
        
        selects.forEach(select => {
            if (!select) return;
            select.innerHTML = '<option value="">All Assignees / Officers...</option>';
            if (data.success && data.data) {
                data.data.forEach(u => {
                    const name = `${u.first_name || ''} ${u.last_name || ''}`.trim() || u.email;
                    select.innerHTML += `<option value="${u.id}">${escapeHtml(name)}</option>`;
                });
            }
        });
    } catch (e) {
        console.error('Failed to load assignees', e);
    }
}

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

// 4. Form Submit Helper
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
            if (activeDetailsRequestId && (formId === 'addNoteForm' || formId === 'uploadAttachmentForm')) {
                viewRequestDetails(activeDetailsRequestId);
            }
        } else {
            alert(data.message || 'Action failed.');
        }
    } catch (e) {
        alert('Network request failed.');
    }
}

// 5. Delete Request
async function deleteRequest(id) {
    if (confirm('Are you sure you want to delete this DSR request?')) {
        const fd = new FormData();
        fd.append('request_id', id);
        fd.append('csrf_token', G_CSRF_TOKEN);
        
        try {
            const res = await fetch('backend/api/dsr/delete.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadRequests();
                loadDashboard();
            } else {
                alert(data.message || 'Delete failed');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

// 6. View Comprehensive Details View
async function viewRequestDetails(id) {
    activeDetailsRequestId = id;
    document.getElementById('note_request_id').value = id;
    document.getElementById('attachment_request_id').value = id;
    
    try {
        const res = await fetch(`backend/api/dsr/details.php?id=${id}`);
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            const req = data.data;

            document.getElementById('detailsRequestCode').innerText = req.request_id_code;
            document.getElementById('detailsMetaInfo').innerText = `Logged on ${req.created_at || '--'}`;

            // Badges
            document.getElementById('detailsBadges').innerHTML = `
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full uppercase bg-indigo-100 text-indigo-800">${escapeHtml(req.status)}</span>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full ${req.priority === 'Urgent' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}">${escapeHtml(req.priority)}</span>
            `;

            // Requester
            document.getElementById('detName').innerText = req.subject_name || 'N/A';
            document.getElementById('detEmail').innerText = req.subject_email || 'N/A';
            document.getElementById('detPhone').innerText = req.subject_phone || 'N/A';
            document.getElementById('detDept').innerText = req.subject_dept || 'N/A';
            document.getElementById('detType').innerText = req.subject_type || 'customer';

            // Metadata
            document.getElementById('detReqType').innerText = req.request_type || 'access';
            document.getElementById('detAssignee').innerText = req.assigned_user_name || 'Unassigned';
            document.getElementById('detDueDate').innerText = req.due_date || 'N/A';
            document.getElementById('detDescription').innerText = req.description || 'No description provided.';
            document.getElementById('detVerification').innerText = req.verification_status || 'pending';

            // SLA Timer
            if (req.due_date) {
                const diffTime = new Date(req.due_date) - new Date();
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                document.getElementById('detSlaTimer').innerText = diffDays > 0 ? `${diffDays} Days Remaining` : `SLA Overdue (${Math.abs(diffDays)} Days)`;
                document.getElementById('detSlaTimer').className = diffDays > 0 ? 'font-bold text-emerald-600' : 'font-bold text-rose-600';
            } else {
                document.getElementById('detSlaTimer').innerText = 'N/A';
            }

            // Populate Notes
            const notes = req.notes || [];
            document.getElementById('notesCount').innerText = notes.length;
            const notesBox = document.getElementById('notesContainer');
            notesBox.innerHTML = notes.length === 0 ? '<p class="text-xs text-gray-500 py-2 text-center">No notes added yet.</p>' : '';
            notes.forEach(n => {
                notesBox.innerHTML += `
                    <div class="p-3 bg-white border border-gray-200 rounded-lg">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-bold text-xs text-gray-900">${escapeHtml(n.author_name || 'Officer')} ${n.is_public == 1 ? '<span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">Public</span>' : '<span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">Internal</span>'}</span>
                            <span class="text-[11px] text-gray-400">${escapeHtml(n.created_at)}</span>
                        </div>
                        <p class="text-xs text-gray-700 whitespace-pre-wrap">${escapeHtml(n.note_text)}</p>
                    </div>
                `;
            });

            // Populate Attachments
            const attachments = req.attachments || [];
            document.getElementById('attachmentsCount').innerText = attachments.length;
            const attBox = document.getElementById('attachmentsContainer');
            attBox.innerHTML = attachments.length === 0 ? '<p class="text-xs text-gray-500 p-4 text-center">No attachments uploaded.</p>' : '';
            attachments.forEach(a => {
                attBox.innerHTML += `
                    <div class="p-3 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-indigo-600">attachment</span>
                            <div>
                                <a href="${escapeHtml(a.file_path)}" target="_blank" class="font-semibold text-xs text-indigo-600 hover:underline">${escapeHtml(a.file_name)}</a>
                                <div class="text-[10px] text-gray-400">${escapeHtml((a.file_size / 1024).toFixed(1))} KB | Uploaded by ${escapeHtml(a.uploader_name || 'System')}</div>
                            </div>
                        </div>
                        <button onclick="deleteAttachment(${a.id})" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">Delete</button>
                    </div>
                `;
            });

            // Populate Status History
            const history = req.history || [];
            const histBox = document.getElementById('historyContainer');
            histBox.innerHTML = history.length === 0 ? '<p class="text-xs text-gray-500 p-4 text-center">No history recorded.</p>' : '';
            history.forEach(h => {
                histBox.innerHTML += `
                    <div class="p-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-xs text-gray-900">${escapeHtml(h.previous_status || 'Start')} ➔ <span class="text-indigo-600 uppercase">${escapeHtml(h.new_status)}</span></div>
                            <div class="text-[11px] text-gray-500">${escapeHtml(h.comments || 'No comments')}</div>
                        </div>
                        <div class="text-[10px] text-gray-400 text-right">
                            <div>${escapeHtml(h.changed_by_name || 'System')}</div>
                            <div>${escapeHtml(h.changed_at)}</div>
                        </div>
                    </div>
                `;
            });

            // Populate Audit Log
            const audit = req.audit_logs || [];
            const auditBox = document.getElementById('auditContainer');
            auditBox.innerHTML = audit.length === 0 ? '<p class="text-xs text-gray-500 p-4 text-center">No audit activity logged.</p>' : '';
            audit.forEach(al => {
                auditBox.innerHTML += `
                    <div class="p-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-xs text-gray-900">${escapeHtml(al.action)}</div>
                            <div class="text-[11px] text-gray-500">By ${escapeHtml(al.user_name || 'User')} (${escapeHtml(al.ip_address || '127.0.0.1')})</div>
                        </div>
                        <div class="text-[10px] text-gray-400">${escapeHtml(al.created_at)}</div>
                    </div>
                `;
            });

            switchDetailsTab('overview');
            document.getElementById('requestDetailsModal').classList.remove('hidden');
        }
    } catch (e) {
        alert('Failed to load request details');
    }
}

async function deleteAttachment(attachmentId) {
    if (confirm('Delete this attachment file?')) {
        const fd = new FormData();
        fd.append('attachment_id', attachmentId);
        fd.append('csrf_token', G_CSRF_TOKEN);
        try {
            const res = await fetch('backend/api/dsr/attachments.php?action=delete', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                if (activeDetailsRequestId) viewRequestDetails(activeDetailsRequestId);
            } else {
                alert(data.message || 'Delete failed');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

function switchDetailsTab(tabName) {
    const tabs = ['overview', 'notes', 'attachments', 'history', 'audit'];
    tabs.forEach(t => {
        const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
        const div = document.getElementById('detailsTab' + t.charAt(0).toUpperCase() + t.slice(1));
        if (t === tabName) {
            if (btn) btn.className = 'px-4 py-2 text-xs font-bold border-b-2 border-indigo-600 text-indigo-600 whitespace-nowrap';
            if (div) div.classList.remove('hidden');
        } else {
            if (btn) btn.className = 'px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 whitespace-nowrap';
            if (div) div.classList.add('hidden');
        }
    });
}

function closeRequestDetailsModal() {
    document.getElementById('requestDetailsModal').classList.add('hidden');
}

// 7. Edit Modal Pre-fill
async function openEditDsrModal(id) {
    try {
        const res = await fetch(`backend/api/dsr/details.php?id=${id}`);
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            const r = data.data;
            document.getElementById('edit_request_id').value = r.id;
            document.getElementById('edit_subject_name').value = r.subject_name || '';
            document.getElementById('edit_subject_email').value = r.subject_email || '';
            document.getElementById('edit_subject_phone').value = r.subject_phone || '';
            document.getElementById('edit_subject_dept').value = r.subject_dept || '';
            document.getElementById('edit_subject_type').value = r.subject_type || 'customer';
            document.getElementById('edit_priority').value = r.priority || 'Medium';
            document.getElementById('edit_status').value = r.status || 'open';
            document.getElementById('edit_due_date').value = r.due_date || '';
            document.getElementById('edit_description').value = r.description || '';

            document.getElementById('editDsrModal').classList.remove('hidden');
        }
    } catch (e) {
        alert('Failed to load request details for editing');
    }
}

function closeEditDsrModal() {
    document.getElementById('editDsrModal').classList.add('hidden');
}

// 8. Modals controls
function openDsrModal() { document.getElementById('dsrModal').classList.remove('hidden'); }
function closeDsrModal() { document.getElementById('dsrModal').classList.add('hidden'); }

function openStatusModal(id, currentStatus) {
    document.getElementById('status_request_id').value = id;
    document.getElementById('status_select').value = currentStatus;
    document.getElementById('statusModal').classList.remove('hidden');
}
function closeStatusModal() { document.getElementById('statusModal').classList.add('hidden'); }

function openVerifySubjectModal(preselectedId = '') {
    loadRequestSelectOptions('verify_request_select', preselectedId);
    document.getElementById('verifySubjectModal').classList.remove('hidden');
}
function closeVerifySubjectModal() { document.getElementById('verifySubjectModal').classList.add('hidden'); }

function openAssignRequestModal(preselectedId = '') {
    loadRequestSelectOptions('assign_request_select', preselectedId);
    loadAssignees();
    document.getElementById('assignRequestModal').classList.remove('hidden');
}
function closeAssignRequestModal() { document.getElementById('assignRequestModal').classList.add('hidden'); }

function openReviewPendingModal() {
    loadPendingRequests();
    document.getElementById('reviewPendingModal').classList.remove('hidden');
}
function closeReviewPendingModal() { document.getElementById('reviewPendingModal').classList.add('hidden'); }

// Pending Requests Loader
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
                        reasons.push("Verification Needed");
                        actionButton = `<button onclick="triggerVerifyFromReview(${r.id})" class="text-green-600 hover:underline font-semibold text-xs mx-1">Verify</button>`;
                    }
                    if (!r.assigned_to) {
                        reasons.push("Unassigned");
                        if (!actionButton) actionButton = `<button onclick="triggerAssignFromReview(${r.id})" class="text-blue-600 hover:underline font-semibold text-xs mx-1">Assign</button>`;
                    }
                    if (r.status === 'open' || r.status === 'verifying') {
                        reasons.push("Needs Action");
                        if (!actionButton) actionButton = `<button onclick="triggerStatusFromReview(${r.id}, '${escapeHtml(r.status)}')" class="text-indigo-600 hover:underline font-semibold text-xs mx-1">Update</button>`;
                    }

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-3 font-semibold text-gray-900">${escapeHtml(r.request_id_code)}</td>
                            <td class="p-3 text-gray-600">${escapeHtml(r.subject_name || r.subject_email)}</td>
                            <td class="p-3 text-rose-600 text-xs font-semibold">${escapeHtml(reasons.join(' & '))}</td>
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

function triggerVerifyFromReview(id) { closeReviewPendingModal(); openVerifySubjectModal(id); }
function triggerAssignFromReview(id) { closeReviewPendingModal(); openAssignRequestModal(id); }
function triggerStatusFromReview(id, status) { closeReviewPendingModal(); openStatusModal(id, status); }

function triggerExport(format) {
    const search = document.getElementById('filter-search').value;
    const status = document.getElementById('filter-status').value;
    const priority = document.getElementById('filter-priority').value;
    const type = document.getElementById('filter-type').value;
    const assigned = document.getElementById('filter-assigned').value;
    const fromDate = document.getElementById('filter-from-date').value;
    const toDate = document.getElementById('filter-to-date').value;

    window.open(`backend/api/dsr/export.php?format=${format}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&priority=${encodeURIComponent(priority)}&type=${encodeURIComponent(type)}&assigned_to=${encodeURIComponent(assigned)}&from_date=${encodeURIComponent(fromDate)}&to_date=${encodeURIComponent(toDate)}`, '_blank');
}

// Window Exposures
window.openDsrModal = openDsrModal;
window.closeDsrModal = closeDsrModal;
window.openEditDsrModal = openEditDsrModal;
window.closeEditDsrModal = closeEditDsrModal;
window.openStatusModal = openStatusModal;
window.closeStatusModal = closeStatusModal;
window.openVerifySubjectModal = openVerifySubjectModal;
window.closeVerifySubjectModal = closeVerifySubjectModal;
window.openAssignRequestModal = openAssignRequestModal;
window.closeAssignRequestModal = closeAssignRequestModal;
window.openReviewPendingModal = openReviewPendingModal;
window.closeReviewPendingModal = closeReviewPendingModal;
window.closeRequestDetailsModal = closeRequestDetailsModal;
window.viewRequestDetails = viewRequestDetails;
window.deleteRequest = deleteRequest;
window.deleteAttachment = deleteAttachment;
window.switchDetailsTab = switchDetailsTab;
window.executeSearch = executeSearch;
window.resetFilters = resetFilters;
window.sortTable = sortTable;
window.changePage = changePage;
window.triggerExport = triggerExport;
window.triggerVerifyFromReview = triggerVerifyFromReview;
window.triggerAssignFromReview = triggerAssignFromReview;
window.triggerStatusFromReview = triggerStatusFromReview;

// Bind DOM event listeners on load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRequests();
    loadAssignees();

    document.getElementById('searchForm').addEventListener('submit', (e) => {
        e.preventDefault();
        executeSearch();
    });

    // Form Submits
    document.getElementById('addDsrForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('addDsrForm', 'backend/api/dsr/create.php', 'dsrModal');
    });

    document.getElementById('editDsrForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('editDsrForm', 'backend/api/dsr/update.php', 'editDsrModal');
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

    document.getElementById('addNoteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('addNoteForm', 'backend/api/dsr/notes.php', null);
    });

    document.getElementById('uploadAttachmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('uploadAttachmentForm', 'backend/api/dsr/attachments.php', null);
    });

    // Quick Action button listeners
    document.getElementById('btn-log-request-qa').addEventListener('click', openDsrModal);
    document.getElementById('btn-verify-subject-qa').addEventListener('click', () => openVerifySubjectModal());
    document.getElementById('btn-assign-request-qa').addEventListener('click', () => openAssignRequestModal());
    document.getElementById('btn-review-requests-qa').addEventListener('click', openReviewPendingModal);

    // Modal closes
    document.getElementById('closeVerifySubjectModal').addEventListener('click', closeVerifySubjectModal);
    document.getElementById('btnCancelVerifySubject').addEventListener('click', closeVerifySubjectModal);

    document.getElementById('closeAssignRequestModal').addEventListener('click', closeAssignRequestModal);
    document.getElementById('btnCancelAssignRequest').addEventListener('click', closeAssignRequestModal);

    document.getElementById('closeReviewPendingModal').addEventListener('click', closeReviewPendingModal);
    document.getElementById('btnCloseReviewPendingModal').addEventListener('click', closeReviewPendingModal);
});
