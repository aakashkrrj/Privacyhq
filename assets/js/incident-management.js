// governance/assets/js/incident-management.js
// Incident Management Frontend Controller

let currentPage = 1;
let currentEndpoint = 'create.php';
let incidentSeverityMap = {};

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function round(value, precision) {
    const multiplier = Math.pow(10, precision || 0);
    return Math.round(value * multiplier) / multiplier;
}

// 1. Dashboard Metrics Telemetry
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/incident/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;
            const kpiActive = document.getElementById('kpi-active');
            const kpiHigh = document.getElementById('kpi-high-severity');
            const kpiResolved = document.getElementById('kpi-resolved');
            const kpiRate = document.getElementById('kpi-resolution-rate');

            if (kpiActive) kpiActive.innerText = d.active_incidents || 0;
            if (kpiHigh) kpiHigh.innerText = d.high_severity || 0;
            if (kpiResolved) kpiResolved.innerText = d.resolved || 0;
            if (kpiRate) kpiRate.innerText = d.resolution_rate || '0%';
            
            // Build distribution bars
            const dist = d.distribution || {};
            const total = d.total || 0;
            const distContainer = document.getElementById('distributionBars');
            
            if (distContainer) {
                if (total === 0) {
                    distContainer.innerHTML = '<div class="text-gray-500 text-sm">No incidents registered.</div>';
                } else {
                    const levels = [
                        { label: 'Critical', count: dist.critical || 0, color: 'bg-red-600' },
                        { label: 'High', count: dist.high || 0, color: 'bg-orange-500' },
                        { label: 'Medium', count: dist.medium || 0, color: 'bg-yellow-500' },
                        { label: 'Low', count: dist.low || 0, color: 'bg-green-500' }
                    ];
                    
                    let html = '';
                    levels.forEach(l => {
                        const pct = total > 0 ? round((l.count / total) * 100, 1) : 0;
                        html += `
                            <div>
                                <div class="flex justify-between text-xs font-medium text-gray-500 mb-1">
                                    <span>${l.label}</span>
                                    <span>${l.count} (${pct}%)</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="${l.color} h-1.5 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distContainer.innerHTML = html;
                }
            }
        }
    } catch (e) {
        console.error('Failed to load incident dashboard telemetry', e);
    }
}

// 2. Paginated Incidents List
async function loadIncidents() {
    const search = document.getElementById('filter-search')?.value || '';
    const severity = document.getElementById('filter-severity')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const type = document.getElementById('filter-type')?.value || '';

    const url = `backend/api/incident/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&severity=${encodeURIComponent(severity)}&status=${encodeURIComponent(status)}&incident_type=${encodeURIComponent(type)}`;
    
    const tbody = document.getElementById('incidentTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading incident register...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500">No matching incidents registered.</td></tr>';
            } else {
                items.forEach(i => {
                    incidentSeverityMap[i.id] = i.severity;

                    let statusClass = 'bg-gray-100 text-gray-800 border-gray-200';
                    if (i.status === 'Resolved' || i.status === 'Closed') statusClass = 'bg-green-100 text-green-800 border-green-200';
                    if (i.status === 'Investigating') statusClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                    if (i.status === 'Contained') statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                    if (i.status === 'Open') statusClass = 'bg-red-100 text-red-800 border-red-200';

                    let severityClass = 'text-gray-600 font-semibold';
                    if (i.severity === 'Critical') severityClass = 'text-red-600 font-bold';
                    if (i.severity === 'High') severityClass = 'text-orange-600 font-semibold';

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100 text-sm">
                            <td class="p-4 font-mono text-xs text-gray-500">#${i.id}</td>
                            <td class="p-4">
                                <div class="font-medium text-gray-900">${escapeHtml(i.summary)}</div>
                                <span class="text-xs text-gray-400 font-mono">${escapeHtml(i.incident_type || 'Data Privacy')} &bull; ${escapeHtml(i.affected_system || 'Core System')}</span>
                            </td>
                            <td class="p-4 ${severityClass}">${escapeHtml(i.severity)}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full border ${statusClass}">
                                    ${escapeHtml(i.status)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600 font-mono">${i.impacted_records}</td>
                            <td class="p-4 text-gray-500 text-xs font-mono">${escapeHtml(i.created_at || '')}</td>
                            <td class="p-4 text-right whitespace-nowrap space-x-1">
                                <button onclick="openIncidentDetailsModal(${i.id})" class="text-primary hover:underline font-semibold text-xs px-1">View</button>
                                <button onclick="openAssignIncidentModal(${i.id})" class="text-blue-600 hover:underline font-semibold text-xs px-1">Assign</button>
                                <button onclick="openIncidentTimelineModal(${i.id})" class="text-purple-600 hover:underline font-semibold text-xs px-1">Timeline</button>
                                <button onclick="editIncident(${i.id})" class="text-indigo-600 hover:underline font-semibold text-xs px-1">Edit</button>
                                <button onclick="deleteIncident(${i.id})" class="text-red-600 hover:underline font-semibold text-xs px-1">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10) || 1;
            const controls = document.getElementById('paginationControls');
            if (controls) {
                if (totalPages > 1) {
                    controls.classList.remove('hidden');
                    document.getElementById('pageInfo').innerText = `Showing page ${currentPage} of ${totalPages} (${total} Total Incidents)`;
                    document.getElementById('btnPrev').disabled = currentPage === 1;
                    document.getElementById('btnNext').disabled = currentPage >= totalPages;
                } else {
                    controls.classList.add('hidden');
                }
            }
        }
    } catch (e) {
        console.error('Failed to load incident register', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-600">Failed to load incident register data.</td></tr>';
        }
    }
}

async function submitApi(formId, endpoint, modalIdToClose) {
    const form = document.getElementById(formId);
    if (!form) return;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
    }

    const formData = new FormData(form);
    if (!formData.has('csrf_token')) {
        formData.append('csrf_token', G_CSRF_TOKEN);
    }

    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadIncidents();
            loadDashboard();
            form.reset();
            if (modalIdToClose) {
                const modal = document.getElementById(modalIdToClose);
                if (modal) modal.classList.add('hidden');
            }
            alert(data.message || 'Operation executed successfully!');
        } else {
            alert('Error: ' + (data.message || 'Error occurred during request processing.'));
        }
    } catch (e) {
        console.error(e);
        alert('Network request failed. Please verify network connection.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }
}

// 3. Incident Details Modal
async function openIncidentDetailsModal(id) {
    if (!id) return;
    const modal = document.getElementById('incidentDetailsModal');
    const content = document.getElementById('incidentDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-8 text-gray-500"><span class="material-symbols-outlined animate-spin text-2xl text-primary block mb-1">sync</span>Loading incident details...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/incident/details.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const i = data.data;
            const assignee = (i.assignee_first ? i.assignee_first + ' ' + i.assignee_last : i.assignee_email) || 'Unassigned';
            const reporter = (i.reporter_first ? i.reporter_first + ' ' + i.reporter_last : i.reporter_email) || 'System/Admin';

            content.innerHTML = `
                <div class="space-y-4 text-sm">
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-500 font-mono uppercase">Incident ID</span>
                            <h4 class="font-bold text-gray-900 text-lg">#${i.id} - ${escapeHtml(i.summary)}</h4>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200">
                            ${escapeHtml(i.status)}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-gray-50 rounded-lg border">
                            <span class="text-xs text-gray-500 block">Incident Type</span>
                            <strong class="text-gray-800">${escapeHtml(i.incident_type || 'Data Privacy')}</strong>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border">
                            <span class="text-xs text-gray-500 block">Severity</span>
                            <strong class="text-red-600">${escapeHtml(i.severity)}</strong>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border">
                            <span class="text-xs text-gray-500 block">Priority</span>
                            <strong class="text-gray-800">${escapeHtml(i.priority || 'Medium')}</strong>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border">
                            <span class="text-xs text-gray-500 block">Impacted Records</span>
                            <strong class="text-gray-800 font-mono">${i.impacted_records}</strong>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50 rounded-lg border">
                        <span class="text-xs text-gray-500 block font-semibold mb-1">Description</span>
                        <p class="text-gray-700 leading-relaxed">${escapeHtml(i.description || 'No detailed description logged.')}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 bg-gray-50 rounded-lg border space-y-1">
                            <div><span class="text-gray-500">Affected System:</span> <strong>${escapeHtml(i.affected_system || 'Core System')}</strong></div>
                            <div><span class="text-gray-500">Assigned Team:</span> <strong>${escapeHtml(i.assigned_team || 'Response Team')}</strong></div>
                            <div><span class="text-gray-500">Assignee:</span> <strong>${escapeHtml(assignee)}</strong></div>
                            <div><span class="text-gray-500">Reporter:</span> <strong>${escapeHtml(reporter)}</strong></div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border space-y-1">
                            <div><span class="text-gray-500">Due Date:</span> <strong class="font-mono">${escapeHtml(i.due_date || 'N/A')}</strong></div>
                            <div><span class="text-gray-500">Created At:</span> <strong class="font-mono">${escapeHtml(i.created_at)}</strong></div>
                            <div><span class="text-gray-500">Resolved At:</span> <strong class="font-mono">${escapeHtml(i.resolved_at || 'Not Resolved')}</strong></div>
                            <div><span class="text-gray-500">Escalated:</span> <strong>${i.is_escalated ? 'Yes' : 'No'} (DPO Notified: ${i.dpo_notified ? 'Yes' : 'No'})</strong></div>
                        </div>
                    </div>

                    ${i.containment_actions || i.remediation_notes ? `
                        <div class="p-3 bg-blue-50/50 border border-blue-200 rounded-lg space-y-2">
                            <h5 class="font-semibold text-blue-900 text-xs uppercase">Containment & Remediation Log</h5>
                            ${i.containment_actions ? `<div><strong class="text-xs text-blue-800">Containment Actions:</strong> <p class="text-gray-700 text-xs">${escapeHtml(i.containment_actions)}</p></div>` : ''}
                            ${i.remediation_notes ? `<div><strong class="text-xs text-blue-800">Remediation Notes:</strong> <p class="text-gray-700 text-xs">${escapeHtml(i.remediation_notes)}</p></div>` : ''}
                            ${i.root_cause ? `<div><strong class="text-xs text-blue-800">Root Cause:</strong> <p class="text-gray-700 text-xs">${escapeHtml(i.root_cause)}</p></div>` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-8 text-red-600">Failed to load incident details.</div>';
        }
    } catch (e) {
        console.error('Failed to load incident details', e);
        content.innerHTML = '<div class="text-center py-8 text-red-600">Error loading incident details.</div>';
    }
}

function closeIncidentDetailsModal() {
    const modal = document.getElementById('incidentDetailsModal');
    if (modal) modal.classList.add('hidden');
}

// 4. Assign Incident Modal
function openAssignIncidentModal(id) {
    const modal = document.getElementById('assignIncidentModal');
    if (!modal) return;
    document.getElementById('assign_incident_id').value = id;
    loadIncidentSelectOptions('assign_incident_select', id);
    modal.classList.remove('hidden');
}

function closeAssignIncidentModal() {
    const modal = document.getElementById('assignIncidentModal');
    if (modal) modal.classList.add('hidden');
}

// 5. Timeline Modal
async function openIncidentTimelineModal(id) {
    if (!id) return;
    const modal = document.getElementById('incidentTimelineModal');
    const content = document.getElementById('incidentTimelineContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-8 text-gray-500"><span class="material-symbols-outlined animate-spin text-2xl text-primary block mb-1">sync</span>Loading event timeline logs...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/incident/timeline.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const items = data.data || [];
            if (items.length === 0) {
                content.innerHTML = '<div class="text-center py-8 text-gray-500">No historical timeline logs recorded yet.</div>';
            } else {
                let html = '<div class="space-y-3">';
                items.forEach(t => {
                    const user = (t.first_name ? t.first_name + ' ' + t.last_name : t.email) || 'System Auditor';
                    html += `
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs">
                            <div class="flex justify-between font-semibold text-gray-900 mb-1">
                                <span class="text-primary font-bold">${escapeHtml(t.action)}</span>
                                <span class="text-gray-400 font-mono">${escapeHtml(t.created_at || '')}</span>
                            </div>
                            <div class="text-gray-600 mb-1">
                                Performed By: <strong>${escapeHtml(user)}</strong>
                            </div>
                            ${t.details ? `<div class="text-gray-700 bg-white p-2 rounded border border-gray-100">${escapeHtml(t.details)}</div>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            }
        } else {
            content.innerHTML = '<div class="text-center py-8 text-red-600">Failed to load timeline logs.</div>';
        }
    } catch (e) {
        console.error('Failed to load incident timeline', e);
        content.innerHTML = '<div class="text-center py-8 text-red-600">Error loading incident timeline.</div>';
    }
}

function closeIncidentTimelineModal() {
    const modal = document.getElementById('incidentTimelineModal');
    if (modal) modal.classList.add('hidden');
}

// 6. Edit / Delete
async function editIncident(id) {
    try {
        const res = await fetch(`backend/api/incident/details.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const i = data.data;
            document.getElementById('incident_id').value = i.id;
            document.getElementById('incident_summary').value = i.summary;
            document.getElementById('incident_description').value = i.description || '';
            document.getElementById('incident_type').value = i.incident_type || 'Data Privacy';
            document.getElementById('incident_severity').value = i.severity || 'Medium';
            document.getElementById('incident_priority').value = i.priority || 'Medium';
            document.getElementById('incident_impacted_records').value = i.impacted_records || 0;
            document.getElementById('incident_affected_system').value = i.affected_system || 'Core System';
            document.getElementById('incident_due_date').value = i.due_date || '';
            document.getElementById('incident_status').value = i.status || 'Open';
            
            document.getElementById('statusGroup').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Edit Incident';
            currentEndpoint = 'update.php';
            
            document.getElementById('incidentModal').classList.remove('hidden');
        } else {
            alert('Failed to load incident details for editing.');
        }
    } catch (e) {
        alert('Failed to load incident details for editing.');
    }
}

async function deleteIncident(id) {
    if (confirm("Are you sure you want to soft-delete this incident record?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('id', id);
        
        try {
            const res = await fetch('backend/api/incident/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadIncidents();
                loadDashboard();
                alert('Incident soft-deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete incident.');
            }
        } catch (e) {
            alert('Request failed. Connection error.');
        }
    }
}

// 7. Select Dropdown Populator
async function loadIncidentSelectOptions(selectElementId, preselectedId = '') {
    try {
        const res = await fetch('backend/api/incident/list.php?p=1&limit=1000');
        const data = await res.json();
        const select = document.getElementById(selectElementId);
        if (select && (data.status === 'success' || data.success) && data.data && data.data.items) {
            select.innerHTML = '<option value="">Choose an incident...</option>';
            data.data.items.forEach(i => {
                incidentSeverityMap[i.id] = i.severity;
                const isSel = String(i.id) === String(preselectedId) ? 'selected' : '';
                select.innerHTML += `<option value="${i.id}" ${isSel}>#${i.id} - ${escapeHtml(i.summary)} (${escapeHtml(i.severity)})</option>`;
            });
        }
    } catch (e) {
        console.error('Failed to load select options', e);
    }
}

// 8. Review Active Incidents
async function loadActiveIncidents() {
    try {
        const res = await fetch('backend/api/incident/list.php?p=1&limit=1000');
        const data = await res.json();
        const tbody = document.getElementById('activeIncidentsTableBody');
        
        if ((data.status === 'success' || data.success) && data.data && data.data.items) {
            if (!tbody) return;
            tbody.innerHTML = '';
            const items = data.data.items.filter(i => {
                return ['Open', 'Investigating', 'Contained'].includes(i.status) || ['High', 'Critical'].includes(i.severity);
            });

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-500">No active or high-severity incidents require immediate attention!</td></tr>';
            } else {
                items.forEach(i => {
                    let reasons = [];
                    if (i.status === 'Open') reasons.push('Open Status');
                    if (i.status === 'Investigating') reasons.push('Under Investigation');
                    if (i.status === 'Contained') reasons.push('Contained');
                    if (['High', 'Critical'].includes(i.severity)) reasons.push('High Severity Alert');

                    const actionButtons = `
                        <button onclick="triggerRemediateFromReview(${i.id})" class="text-green-600 hover:text-green-900 font-semibold text-xs mx-1">Remediate</button>
                        <button onclick="triggerEscalateFromReview(${i.id})" class="text-blue-600 hover:text-blue-900 font-semibold text-xs mx-1">Escalate</button>
                        <button onclick="triggerEditFromReview(${i.id})" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs mx-1">Edit</button>
                    `;

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100 text-xs">
                            <td class="p-3 font-medium text-gray-900">#${i.id} - ${escapeHtml(i.summary)}</td>
                            <td class="p-3 text-xs uppercase font-bold text-red-600">${escapeHtml(i.severity)}</td>
                            <td class="p-3">${escapeHtml(i.status)}</td>
                            <td class="p-3 text-red-600 font-semibold">${escapeHtml(reasons.join(' & '))}</td>
                            <td class="p-3 text-right">${actionButtons}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load active incidents', e);
    }
}

// Modals Setup
function openIncidentModal() {
    const form = document.getElementById('incidentForm');
    if (form) form.reset();
    document.getElementById('incident_id').value = '';
    document.getElementById('statusGroup').classList.add('hidden');
    document.getElementById('modalTitle').innerText = 'Log New Incident';
    currentEndpoint = 'create.php';
    document.getElementById('incidentModal').classList.remove('hidden');
}

function closeIncidentModal() {
    document.getElementById('incidentModal').classList.add('hidden');
}

function openRemediateIncidentModal(preselectedId = '') {
    loadIncidentSelectOptions('remediate_incident_select', preselectedId);
    if (preselectedId) {
        fetch(`backend/api/incident/details.php?id=${preselectedId}`)
            .then(r => r.json())
            .then(res => {
                if ((res.status === 'success' || res.success) && res.data) {
                    document.getElementById('remediate_containment').value = res.data.containment_actions || '';
                    document.getElementById('remediate_notes').value = res.data.remediation_notes || '';
                    document.getElementById('remediate_root_cause').value = res.data.root_cause || '';
                    document.getElementById('remediate_preventive_actions').value = res.data.preventive_actions || '';
                }
            });
    } else {
        document.getElementById('remediate_containment').value = '';
        document.getElementById('remediate_notes').value = '';
        document.getElementById('remediate_root_cause').value = '';
        document.getElementById('remediate_preventive_actions').value = '';
    }
    document.getElementById('remediateIncidentModal').classList.remove('hidden');
}

function closeRemediateIncidentModal() {
    document.getElementById('remediateIncidentModal').classList.add('hidden');
}

function openEscalateIncidentModal(preselectedId = '') {
    loadIncidentSelectOptions('escalate_incident_select', preselectedId);
    const checkbox = document.getElementById('escalate_is_escalated');
    const warning = document.getElementById('escalate_severity_warning');
    if (checkbox) checkbox.disabled = false;
    if (warning) warning.classList.add('hidden');

    if (preselectedId) {
        fetch(`backend/api/incident/details.php?id=${preselectedId}`)
            .then(r => r.json())
            .then(res => {
                if ((res.status === 'success' || res.success) && res.data) {
                    const data = res.data;
                    if (checkbox) checkbox.checked = String(data.is_escalated) === '1';
                    document.getElementById('escalate_dpo_notified').checked = String(data.dpo_notified) === '1';
                    document.getElementById('escalate_regulatory_status').value = data.regulatory_status || 'Not Required';
                    
                    if (!['High', 'Critical'].includes(data.severity)) {
                        if (checkbox) {
                            checkbox.checked = false;
                            checkbox.disabled = true;
                        }
                        if (warning) warning.classList.remove('hidden');
                    }
                }
            });
    } else {
        if (checkbox) checkbox.checked = false;
        document.getElementById('escalate_dpo_notified').checked = false;
        document.getElementById('escalate_regulatory_status').value = 'Not Required';
    }
    document.getElementById('escalateIncidentModal').classList.remove('hidden');
}

function closeEscalateIncidentModal() {
    document.getElementById('escalateIncidentModal').classList.add('hidden');
}

function openReviewActiveModal() {
    loadActiveIncidents();
    document.getElementById('reviewActiveModal').classList.remove('hidden');
}

function closeReviewActiveModal() {
    document.getElementById('reviewActiveModal').classList.add('hidden');
}

function triggerRemediateFromReview(id) {
    closeReviewActiveModal();
    openRemediateIncidentModal(id);
}

function triggerEscalateFromReview(id) {
    closeReviewActiveModal();
    openEscalateIncidentModal(id);
}

function triggerEditFromReview(id) {
    closeReviewActiveModal();
    editIncident(id);
}

function exportIncidents(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const severity = document.getElementById('filter-severity')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    window.open(`backend/api/incident/export.php?format=${format}&search=${encodeURIComponent(search)}&severity=${encodeURIComponent(severity)}&status=${encodeURIComponent(status)}`, '_blank');
}

function clearIncidentFilters() {
    const s = document.getElementById('filter-search');
    const sev = document.getElementById('filter-severity');
    const st = document.getElementById('filter-status');
    const t = document.getElementById('filter-type');

    if (s) s.value = '';
    if (sev) sev.value = '';
    if (st) st.value = '';
    if (t) t.value = '';

    currentPage = 1;
    loadIncidents();
}

// Global Exports
window.loadDashboard = loadDashboard;
window.loadIncidents = loadIncidents;
window.editIncident = editIncident;
window.deleteIncident = deleteIncident;
window.openIncidentModal = openIncidentModal;
window.closeIncidentModal = closeIncidentModal;
window.openIncidentDetailsModal = openIncidentDetailsModal;
window.closeIncidentDetailsModal = closeIncidentDetailsModal;
window.openAssignIncidentModal = openAssignIncidentModal;
window.closeAssignIncidentModal = closeAssignIncidentModal;
window.openIncidentTimelineModal = openIncidentTimelineModal;
window.closeIncidentTimelineModal = closeIncidentTimelineModal;
window.openRemediateIncidentModal = openRemediateIncidentModal;
window.closeRemediateIncidentModal = closeRemediateIncidentModal;
window.openEscalateIncidentModal = openEscalateIncidentModal;
window.closeEscalateIncidentModal = closeEscalateIncidentModal;
window.openReviewActiveModal = openReviewActiveModal;
window.closeReviewActiveModal = closeReviewActiveModal;
window.triggerRemediateFromReview = triggerRemediateFromReview;
window.triggerEscalateFromReview = triggerEscalateFromReview;
window.triggerEditFromReview = triggerEditFromReview;
window.exportIncidents = exportIncidents;
window.clearIncidentFilters = clearIncidentFilters;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadIncidents();

    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadIncidents();
        });
    }

    const btnPrev = document.getElementById('btnPrev');
    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadIncidents();
            }
        });
    }

    const btnNext = document.getElementById('btnNext');
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            currentPage++;
            loadIncidents();
        });
    }

    // Form handlers
    const incidentForm = document.getElementById('incidentForm');
    if (incidentForm) {
        incidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitApi('incidentForm', `backend/api/incident/${currentEndpoint}`, 'incidentModal');
        });
    }

    const assignIncidentForm = document.getElementById('assignIncidentForm');
    if (assignIncidentForm) {
        assignIncidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitApi('assignIncidentForm', 'backend/api/incident/assign.php', 'assignIncidentModal');
        });
    }

    const remediateIncidentForm = document.getElementById('remediateIncidentForm');
    if (remediateIncidentForm) {
        remediateIncidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitApi('remediateIncidentForm', 'backend/api/incident/remediate.php', 'remediateIncidentModal');
        });
    }

    const escalateIncidentForm = document.getElementById('escalateIncidentForm');
    if (escalateIncidentForm) {
        escalateIncidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitApi('escalateIncidentForm', 'backend/api/incident/escalate.php', 'escalateIncidentModal');
        });
    }

    // Severity check logic for escalation
    const escalateSelect = document.getElementById('escalate_incident_select');
    if (escalateSelect) {
        escalateSelect.addEventListener('change', function() {
            const id = this.value;
            const severity = incidentSeverityMap[id] || '';
            const checkbox = document.getElementById('escalate_is_escalated');
            const warning = document.getElementById('escalate_severity_warning');
            
            if (id && !['High', 'Critical'].includes(severity)) {
                if (checkbox) {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                }
                if (warning) warning.classList.remove('hidden');
            } else {
                if (checkbox) checkbox.disabled = false;
                if (warning) warning.classList.add('hidden');
            }
        });
    }

    // Quick Actions
    document.getElementById('btn-log-incident-qa')?.addEventListener('click', openIncidentModal);
    document.getElementById('btn-remediate-incident-qa')?.addEventListener('click', () => openRemediateIncidentModal());
    document.getElementById('btn-escalate-incident-qa')?.addEventListener('click', () => openEscalateIncidentModal());
    document.getElementById('btn-review-active-qa')?.addEventListener('click', openReviewActiveModal);

    document.getElementById('btn-export-incidents-qa')?.addEventListener('click', () => {
        exportIncidents('csv');
    });

    document.getElementById('btn-generate-report-qa')?.addEventListener('click', () => {
        exportIncidents('pdf');
    });

    // Close buttons
    document.getElementById('closeRemediateIncidentModal')?.addEventListener('click', closeRemediateIncidentModal);
    document.getElementById('btnCancelRemediate')?.addEventListener('click', closeRemediateIncidentModal);

    document.getElementById('closeEscalateIncidentModal')?.addEventListener('click', closeEscalateIncidentModal);
    document.getElementById('btnCancelEscalate')?.addEventListener('click', closeEscalateIncidentModal);

    document.getElementById('closeReviewActiveModal')?.addEventListener('click', closeReviewActiveModal);
    document.getElementById('btnCloseReviewActiveModal')?.addEventListener('click', closeReviewActiveModal);
});
