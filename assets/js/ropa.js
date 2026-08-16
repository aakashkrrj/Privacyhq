// governance/assets/js/ropa.js
// ROPA Article 30 Processing Activities Controller

let currentPage = 1;
let currentEndpoint = 'create.php';

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 1. Live Dashboard Telemetry Metrics & Distribution Analytics (Row 102)
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/ropa/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;

            // KPI Cards
            const kpiTotal = document.getElementById('kpi-total');
            const kpiActive = document.getElementById('kpi-active');
            const kpiUnderReview = document.getElementById('kpi-under-review');
            const kpiApproved = document.getElementById('kpi-approved');
            const kpiOverdue = document.getElementById('kpi-overdue');
            const kpiTransfers = document.getElementById('kpi-transfers');

            if (kpiTotal) kpiTotal.innerText = d.total_activities || 0;
            if (kpiActive) kpiActive.innerText = d.active_activities || 0;
            if (kpiUnderReview) kpiUnderReview.innerText = (d.under_review_activities || 0) + (d.draft_activities || 0);
            if (kpiApproved) kpiApproved.innerText = d.approved_activities || 0;
            if (kpiOverdue) kpiOverdue.innerText = d.overdue_reviews || 0;
            if (kpiTransfers) kpiTransfers.innerText = d.international_transfers || 0;

            // Card 1: Lawful Basis Distribution Breakdown
            const distLawful = document.getElementById('dist-lawful-basis');
            if (distLawful) {
                const lbs = d.lawful_basis_distribution || {};
                const keys = Object.keys(lbs);
                const total = d.total_activities || 1;

                if (keys.length === 0) {
                    distLawful.innerHTML = '<div class="text-caption text-gray-500">No processing records found.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = lbs[k] || 0;
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
                    distLawful.innerHTML = html;
                }
            }

            // Card 2: Department Breakdown
            const distDept = document.getElementById('dist-department');
            if (distDept) {
                const depts = d.department_distribution || {};
                const keys = Object.keys(depts);
                const total = d.total_activities || 1;

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

            // Card 3: Controller / Processor Roles & Risk Profile
            const distRoles = document.getElementById('dist-roles');
            if (distRoles) {
                const roles = d.controller_role_distribution || {};
                const highRisk = d.high_risk_activities || 0;
                
                let html = '<div class="space-y-2">';
                html += `<div class="flex justify-between items-center text-caption p-2 bg-surface-container-low rounded border border-outline-variant">
                    <span>Controller Operations</span>
                    <strong class="text-primary font-bold">${roles['Controller'] || 0}</strong>
                </div>`;
                html += `<div class="flex justify-between items-center text-caption p-2 bg-surface-container-low rounded border border-outline-variant">
                    <span>Processor Operations</span>
                    <strong class="text-indigo-600 font-bold">${roles['Processor'] || 0}</strong>
                </div>`;
                html += `<div class="flex justify-between items-center text-caption p-2 bg-surface-container-low rounded border border-outline-variant">
                    <span>Joint Controller Operations</span>
                    <strong class="text-blue-600 font-bold">${roles['Joint Controller'] || 0}</strong>
                </div>`;
                html += `<div class="flex justify-between items-center text-caption p-2 bg-red-50 text-red-800 rounded border border-red-100 mt-2">
                    <span class="font-semibold">High-Risk Processing Activities</span>
                    <strong class="text-red-700 font-bold">${highRisk}</strong>
                </div>`;
                html += '</div>';

                distRoles.innerHTML = html;
            }
        }
    } catch (e) {
        console.error('Failed to load ROPA dashboard metrics', e);
    }
}

// 2. Paginated ROPA Processing Inventory (Row 103)
async function loadRecords() {
    const search = document.getElementById('filter-search')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const department = document.getElementById('filter-department')?.value || '';
    const lawfulBasis = document.getElementById('filter-legal-basis')?.value || '';
    const controllerRole = document.getElementById('filter-role')?.value || '';
    const overdue = document.getElementById('filter-overdue')?.checked ? '1' : '';

    const url = `backend/api/ropa/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&department=${encodeURIComponent(department)}&legal_basis=${encodeURIComponent(lawfulBasis)}&controller_role=${encodeURIComponent(controllerRole)}&overdue=${overdue}`;
    
    const tbody = document.getElementById('ropaTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading Article 30 records of processing activities...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500">No matching processing activity records found.</td></tr>';
            } else {
                items.forEach(i => {
                    let statusBadge = 'bg-gray-100 text-gray-800 border-gray-200';
                    if (i.status === 'active') statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    else if (i.status === 'under_review') statusBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                    else if (i.status === 'approved') statusBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                    else if (i.status === 'draft') statusBadge = 'bg-gray-50 text-gray-700 border-gray-300';
                    else if (i.status === 'inactive') statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';

                    const isOverdue = i.is_overdue || (i.review_date && new Date(i.review_date) < new Date() && i.status !== 'inactive');

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-primary font-bold">${escapeHtml(i.ropa_code)}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(i.activity_name)}</div>
                                <div class="text-caption text-on-surface-variant truncate max-w-xs" title="${escapeHtml(i.purpose)}">${escapeHtml(i.purpose)}</div>
                            </td>
                            <td class="px-lg py-md">
                                <div class="font-medium text-on-surface">${escapeHtml(i.data_controller || 'PrivacyHQ Inc')}</div>
                                <span class="text-caption text-on-surface-variant">${escapeHtml(i.controller_role || 'Controller')}</span>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant">${escapeHtml(i.department)}</td>
                            <td class="px-lg py-md text-on-surface-variant">${escapeHtml(i.legal_basis || 'Legitimate Interest')}</td>
                            <td class="px-lg py-md text-caption text-on-surface-variant truncate max-w-xs" title="${escapeHtml(i.data_categories)}">${escapeHtml(i.data_categories || 'Personal Identifiers')}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${statusBadge}">
                                    ${escapeHtml(i.status)}
                                </span>
                            </td>
                            <td class="px-lg py-md font-mono text-caption">
                                ${escapeHtml(i.review_date || 'N/A')}
                                ${isOverdue ? '<span class="ml-1 text-[10px] font-bold text-red-600 bg-red-100 px-1.5 py-0.5 rounded">OVERDUE</span>' : ''}
                            </td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-1">
                                <button onclick="openRopaDetailsModal(${i.id})" class="text-primary hover:underline font-semibold text-xs px-1 cursor-pointer">Details</button>
                                <button onclick="openRopaHistoryModal(${i.id})" class="text-purple-600 hover:underline font-semibold text-xs px-1 cursor-pointer">History</button>
                                <button onclick="editRopa(${i.id})" class="text-indigo-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Edit</button>
                                <button onclick="deleteRopa(${i.id})" class="text-red-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10) || 1;
            const paginationDiv = document.getElementById('ropaPagination');
            if (paginationDiv) {
                const startIdx = total === 0 ? 0 : (currentPage - 1) * 10 + 1;
                const endIdx = Math.min(currentPage * 10, total);
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${startIdx}–${endIdx}</strong> of <strong>${total}</strong> Activities
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load ROPA inventory', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-red-600">Failed to load processing activities inventory.</td></tr>';
        }
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadRecords();
}

// 3. ROPA Details Profile Modal
async function openRopaDetailsModal(id) {
    if (!id) return;
    const modal = document.getElementById('ropaDetailsModal');
    const content = document.getElementById('ropaDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading processing activity profile...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/ropa/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            content.innerHTML = `
                <div class="space-y-4 text-sm">
                    <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant flex justify-between items-center">
                        <div>
                            <span class="text-caption text-primary font-mono font-bold">${escapeHtml(r.ropa_code)}</span>
                            <h4 class="font-bold text-on-surface text-title-md">${escapeHtml(r.activity_name)}</h4>
                        </div>
                        <span class="px-3 py-1 text-caption font-semibold rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">
                            ${escapeHtml(r.status)}
                        </span>
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-lg border">
                        <span class="text-caption text-on-surface-variant block font-semibold uppercase">Purpose of Processing</span>
                        <p class="text-on-surface mt-1">${escapeHtml(r.purpose)}</p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Department</span>
                            <strong class="text-on-surface">${escapeHtml(r.department)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Lawful Basis</span>
                            <strong class="text-primary">${escapeHtml(r.legal_basis || 'Legitimate Interest')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Data Controller</span>
                            <strong class="text-on-surface">${escapeHtml(r.data_controller)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Controller Role</span>
                            <strong class="text-on-surface">${escapeHtml(r.controller_role || 'Controller')}</strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 bg-surface-container-low rounded-lg border space-y-1">
                            <h5 class="font-semibold text-xs uppercase text-primary mb-1">Data Scope & Operations</h5>
                            <div><span class="text-on-surface-variant">Categories:</span> <strong>${escapeHtml(r.data_categories || 'N/A')}</strong></div>
                            <div><span class="text-on-surface-variant">Data Subjects:</span> <strong>${escapeHtml(r.data_subjects || 'N/A')}</strong></div>
                            <div><span class="text-on-surface-variant">Operations:</span> <strong>${escapeHtml(r.processing_operations || 'Collection, Storage')}</strong></div>
                            <div><span class="text-on-surface-variant">Data Source:</span> <strong>${escapeHtml(r.data_source || 'Direct User Input')}</strong></div>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border space-y-1">
                            <h5 class="font-semibold text-xs uppercase text-primary mb-1">Data Transfer & Retention</h5>
                            <div><span class="text-on-surface-variant">Recipients:</span> <strong>${escapeHtml(r.recipients || 'Internal Only')}</strong></div>
                            <div><span class="text-on-surface-variant">International Transfer:</span> <strong>${escapeHtml(r.international_transfers || 'No')}</strong> (${escapeHtml(r.transfer_safeguards || 'N/A')})</div>
                            <div><span class="text-on-surface-variant">Retention Period:</span> <strong>${escapeHtml(r.retention_period || 'N/A')}</strong> (${escapeHtml(r.retention_basis || 'Legal Requirement')})</div>
                            <div><span class="text-on-surface-variant">Disposal Mechanism:</span> <strong>${escapeHtml(r.disposal_mechanism || 'Secure Erasure')}</strong></div>
                        </div>
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-lg border space-y-1">
                        <h5 class="font-semibold text-xs uppercase text-primary mb-1">Security & Safeguards</h5>
                        <div><span class="text-on-surface-variant">Storage Location:</span> <strong>${escapeHtml(r.storage_location || 'Cloud Server')}</strong></div>
                        <div><span class="text-on-surface-variant">Technical Controls:</span> <strong>${escapeHtml(r.technical_measures || r.safeguards || 'TLS 1.3, Encryption')}</strong></div>
                        <div><span class="text-on-surface-variant">Organizational Measures:</span> <strong>${escapeHtml(r.organizational_measures || 'RBAC Access Controls')}</strong></div>
                        <div><span class="text-on-surface-variant">Review Target Date:</span> <strong class="font-mono text-indigo-700">${escapeHtml(r.review_date || 'N/A')}</strong></div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load processing activity profile.</div>';
        }
    } catch (e) {
        console.error('Failed to load ROPA details', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading processing activity details.</div>';
    }
}

function closeRopaDetailsModal() {
    const modal = document.getElementById('ropaDetailsModal');
    if (modal) modal.classList.add('hidden');
}

// 4. Audit History Log Modal (Section 11)
async function openRopaHistoryModal(ropaId) {
    if (!ropaId) return;
    const modal = document.getElementById('ropaHistoryModal');
    const content = document.getElementById('ropaHistoryContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading audit history logs...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/ropa/history.php?id=${ropaId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const items = data.data || [];
            if (items.length === 0) {
                content.innerHTML = '<div class="text-center py-6 text-gray-500">No historical activity changes logged yet.</div>';
            } else {
                let html = '<div class="space-y-3">';
                items.forEach(h => {
                    const user = (h.first_name ? h.first_name + ' ' + h.last_name : h.email) || 'System Auditor';
                    html += `
                        <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant text-caption">
                            <div class="flex justify-between font-semibold text-on-surface mb-1">
                                <span class="text-primary font-bold">${escapeHtml(h.action)}</span>
                                <span class="text-on-surface-variant font-mono text-[11px]">${escapeHtml(h.created_at || '')}</span>
                            </div>
                            <div class="text-on-surface-variant">
                                Actor: <strong>${escapeHtml(user)}</strong>
                            </div>
                            ${h.old_status ? `<div class="text-on-surface-variant">Status Transition: <b>${escapeHtml(h.old_status)} &rarr; ${escapeHtml(h.new_status)}</b></div>` : ''}
                            ${h.details ? `<div class="mt-1 text-gray-600 italic bg-white p-2 rounded border border-gray-100">"${escapeHtml(h.details)}"</div>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            }
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load ROPA history logs.</div>';
        }
    } catch (e) {
        console.error('Failed to load ROPA history', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading ROPA history logs.</div>';
    }
}

function closeRopaHistoryModal() {
    const modal = document.getElementById('ropaHistoryModal');
    if (modal) modal.classList.add('hidden');
}

// 5. Edit / Delete ROPA Workflows (Rows 105 & 106)
async function editRopa(id) {
    try {
        const res = await fetch(`backend/api/ropa/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            document.getElementById('ropa_id').value = r.id;
            document.getElementById('ropa_activity_name').value = r.activity_name;
            document.getElementById('ropa_purpose').value = r.purpose;
            document.getElementById('ropa_department').value = r.department || 'Finance & Billing';
            document.getElementById('ropa_data_controller').value = r.data_controller || 'PrivacyHQ Inc';
            document.getElementById('ropa_business_owner').value = r.business_owner || 'Data Owner';
            document.getElementById('ropa_controller_role').value = r.controller_role || 'Controller';
            document.getElementById('ropa_legal_basis').value = r.legal_basis || 'Legitimate Interest';

            document.getElementById('ropa_data_categories').value = r.data_categories || '';
            document.getElementById('ropa_data_subjects').value = r.data_subjects || '';
            document.getElementById('ropa_processing_operations').value = r.processing_operations || '';
            document.getElementById('ropa_data_source').value = r.data_source || '';

            document.getElementById('ropa_recipients').value = r.recipients || '';
            document.getElementById('ropa_third_parties').value = r.third_parties || '';
            document.getElementById('ropa_international_transfers').value = r.international_transfers || 'No';
            document.getElementById('ropa_transfer_safeguards').value = r.transfer_safeguards || '';

            document.getElementById('ropa_retention_period').value = r.retention_period || '';
            document.getElementById('ropa_retention_basis').value = r.retention_basis || 'Legal Obligation';
            document.getElementById('ropa_disposal_mechanism').value = r.disposal_mechanism || 'Secure Erasure';

            document.getElementById('ropa_storage_location').value = r.storage_location || 'AWS Cloud Server';
            document.getElementById('ropa_technical_measures').value = r.technical_measures || r.safeguards || 'TLS 1.3, AES-256 Encryption';
            document.getElementById('ropa_organizational_measures').value = r.organizational_measures || 'RBAC Access Control Policies';
            document.getElementById('ropa_risk_level').value = r.risk_level || 'Medium';
            document.getElementById('ropa_status').value = r.status || 'active';
            document.getElementById('ropa_review_date').value = r.review_date || '';

            document.getElementById('ropaModalTitle').innerText = 'Edit Processing Activity (ROPA)';
            currentEndpoint = 'update.php';

            document.getElementById('ropaModal').classList.remove('hidden');
        } else {
            alert('Failed to load ROPA record for editing.');
        }
    } catch (e) {
        alert('Failed to load ROPA details for edit.');
    }
}

async function deleteRopa(id) {
    if (confirm("Are you sure you want to soft-delete this Article 30 processing activity from the ROPA register?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('id', id);

        try {
            const res = await fetch('backend/api/ropa/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadRecords();
                loadDashboard();
                alert('Processing activity deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete processing activity.');
            }
        } catch (e) {
            alert('Request failed. Connection error.');
        }
    }
}

// 6. Export Records (Row 107)
function exportRopa(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const department = document.getElementById('filter-department')?.value || '';
    const lawfulBasis = document.getElementById('filter-legal-basis')?.value || '';
    const controllerRole = document.getElementById('filter-role')?.value || '';
    const overdue = document.getElementById('filter-overdue')?.checked ? '1' : '';

    const url = `backend/api/ropa/export.php?format=${format}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&department=${encodeURIComponent(department)}&legal_basis=${encodeURIComponent(lawfulBasis)}&controller_role=${encodeURIComponent(controllerRole)}&overdue=${overdue}`;
    window.open(url, '_blank');
}

function clearRopaFilters() {
    const s = document.getElementById('filter-search');
    const st = document.getElementById('filter-status');
    const d = document.getElementById('filter-department');
    const l = document.getElementById('filter-legal-basis');
    const r = document.getElementById('filter-role');
    const o = document.getElementById('filter-overdue');

    if (s) s.value = '';
    if (st) st.value = '';
    if (d) d.value = '';
    if (l) l.value = '';
    if (r) r.value = '';
    if (o) o.checked = false;

    currentPage = 1;
    loadRecords();
}

function openRopaModal() {
    const form = document.getElementById('ropaForm');
    if (form) form.reset();
    document.getElementById('ropa_id').value = '';
    
    // Set default review date to 1 year from today
    const nextYear = new Date();
    nextYear.setFullYear(nextYear.getFullYear() + 1);
    const dateStr = nextYear.toISOString().split('T')[0];
    document.getElementById('ropa_review_date').value = dateStr;

    document.getElementById('ropaModalTitle').innerText = 'Add New Processing Activity (ROPA)';
    currentEndpoint = 'create.php';
    document.getElementById('ropaModal').classList.remove('hidden');
}

function closeRopaModal() {
    document.getElementById('ropaModal').classList.add('hidden');
}

// Global Scope Exports
window.loadDashboard = loadDashboard;
window.loadRecords = loadRecords;
window.openRopaModal = openRopaModal;
window.closeRopaModal = closeRopaModal;
window.openRopaDetailsModal = openRopaDetailsModal;
window.closeRopaDetailsModal = closeRopaDetailsModal;
window.openRopaHistoryModal = openRopaHistoryModal;
window.closeRopaHistoryModal = closeRopaHistoryModal;
window.editRopa = editRopa;
window.deleteRopa = deleteRopa;
window.exportRopa = exportRopa;
window.clearRopaFilters = clearRopaFilters;
window.changePage = changePage;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRecords();

    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadRecords();
        });
    }

    // Submit ROPA Form
    const ropaForm = document.getElementById('ropaForm');
    if (ropaForm) {
        ropaForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch(`backend/api/ropa/${currentEndpoint}`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeRopaModal();
                    loadRecords();
                    loadDashboard();
                    alert(data.message || 'Processing activity saved successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                alert('Network error saving processing activity record.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
