// assets/js/incident-management.js

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

async function loadDashboard() {
    try {
        const res = await fetch('backend/api/incident/dashboard.php');
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            document.getElementById('kpi-active').innerText = data.data.active_incidents;
            document.getElementById('kpi-high-severity').innerText = data.data.high_severity;
            document.getElementById('kpi-resolved').innerText = data.data.resolved;
            document.getElementById('kpi-resolution-rate').innerText = data.data.resolution_rate;
            
            // Build distribution bars
            const dist = data.data.distribution;
            const total = data.data.total;
            let html = '';
            
            if (total === 0) {
                html = '<div class="text-gray-500 text-sm">No data available.</div>';
            } else {
                const levels = [
                    { label: 'Critical', count: dist.critical, color: 'bg-red-600' },
                    { label: 'High', count: dist.high, color: 'bg-orange-500' },
                    { label: 'Medium', count: dist.medium, color: 'bg-yellow-500' },
                    { label: 'Low', count: dist.low, color: 'bg-green-500' }
                ];
                
                levels.forEach(l => {
                    const pct = total > 0 ? round((l.count / total) * 100, 1) : 0;
                    html += `
                        <div>
                            <div class="flex justify-between text-xs font-medium text-gray-500 mb-1">
                                <span>${l.label}</span>
                                <span>${l.count} (${pct}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="${l.color} h-1.5 rounded-full" style="width: ${pct}%"></div>
                            </div>
                        </div>
                    `;
                });
            }
            document.getElementById('distributionBars').innerHTML = html;
        }
    } catch (e) {
        console.error('Failed to load incident dashboard', e);
    }
}

async function loadIncidents() {
    const search = document.getElementById('filter-search').value;
    const severity = document.getElementById('filter-severity').value;
    const status = document.getElementById('filter-status').value;

    const url = `backend/api/incident/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&severity=${encodeURIComponent(severity)}&status=${encodeURIComponent(status)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('incidentTableBody');
        
        if (data.status === 'success') {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No incidents registered.</td></tr>';
            } else {
                items.forEach(i => {
                    let statusClass = 'bg-gray-100 text-gray-800';
                    if (i.status === 'Resolved') statusClass = 'bg-green-100 text-green-800';
                    if (i.status === 'Investigating') statusClass = 'bg-yellow-100 text-yellow-800';
                    if (i.status === 'Open') statusClass = 'bg-red-100 text-red-800';

                    let severityClass = 'text-gray-600';
                    if (i.severity === 'Critical') severityClass = 'text-red-600 font-semibold';
                    if (i.severity === 'High') severityClass = 'text-orange-500 font-semibold';

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-4">
                                <div class="font-medium text-gray-900">${escapeHtml(i.summary)}</div>
                            </td>
                            <td class="p-4 ${severityClass}">${escapeHtml(i.severity)}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                                    ${escapeHtml(i.status)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600">${escapeHtml(i.impacted_records)}</td>
                            <td class="p-4 text-gray-600 text-sm">${escapeHtml(i.created_at)}</td>
                            <td class="p-4 text-right">
                                <button onclick="editIncident(${i.id})" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm mx-1">Edit</button>
                                <button onclick="deleteIncident(${i.id})" class="text-red-600 hover:text-red-900 font-medium text-sm mx-1">Delete</button>
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
        console.error('Failed to load incidents', e);
    }
}

async function submitApi(formId, endpoint, modalIdToClose) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
            loadIncidents();
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

async function editIncident(id) {
    try {
        const res = await fetch(`backend/api/incident/details.php?id=${id}`);
        const data = await res.json();
        if (data.status === 'success') {
            const i = data.data;
            document.getElementById('incident_id').value = i.id;
            document.getElementById('incident_summary').value = i.summary;
            document.getElementById('incident_description').value = i.description || '';
            document.getElementById('incident_severity').value = i.severity;
            document.getElementById('incident_impacted_records').value = i.impacted_records;
            document.getElementById('incident_status').value = i.status;
            
            document.getElementById('statusGroup').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Edit Incident';
            currentEndpoint = 'update.php';
            
            document.getElementById('incidentModal').classList.remove('hidden');
        }
    } catch (e) {
        alert('Failed to load incident details');
    }
}

async function deleteIncident(id) {
    if (confirm("Are you sure you want to delete this incident?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('id', id);
        
        try {
            const res = await fetch('backend/api/incident/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                loadIncidents();
                loadDashboard();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

// Select Dropdowns Populator
async function loadIncidentSelectOptions(selectElementId, preselectedId = '') {
    try {
        const res = await fetch('backend/api/incident/list.php?p=1&limit=1000');
        const data = await res.json();
        const select = document.getElementById(selectElementId);
        if (data.status === 'success' && data.data && data.data.items) {
            select.innerHTML = '<option value="">Choose an incident...</option>';
            data.data.items.forEach(i => {
                // Populate severity map
                incidentSeverityMap[i.id] = i.severity;
                const isSel = String(i.id) === String(preselectedId) ? 'selected' : '';
                select.innerHTML += `<option value="${i.id}" ${isSel}>${escapeHtml(i.summary)} (${escapeHtml(i.severity)})</option>`;
            });
        }
    } catch (e) {
        console.error('Failed to load select options', e);
    }
}

// Review active incidents loader
async function loadActiveIncidents() {
    try {
        const res = await fetch('backend/api/incident/list.php?p=1&limit=1000');
        const data = await res.json();
        const tbody = document.getElementById('activeIncidentsTableBody');
        
        if (data.status === 'success' && data.data && data.data.items) {
            tbody.innerHTML = '';
            // We filter for active review requests
            const items = data.data.items.filter(i => {
                return i.status === 'Open' || i.status === 'Investigating' || ['High', 'Critical'].includes(i.severity);
            });

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-500">No active or high-severity incidents require attention!</td></tr>';
            } else {
                items.forEach(i => {
                    let reasons = [];
                    if (i.status === 'Open') reasons.push('Open Status');
                    if (i.status === 'Investigating') reasons.push('Under Investigation');
                    if (['High', 'Critical'].includes(i.severity)) reasons.push('High Severity Alert');

                    const actionButtons = `
                        <button onclick="triggerRemediateFromReview(${i.id})" class="text-green-600 hover:text-green-900 font-semibold text-xs mx-1">Remediate</button>
                        <button onclick="triggerEscalateFromReview(${i.id})" class="text-blue-600 hover:text-blue-900 font-semibold text-xs mx-1">Escalate</button>
                        <button onclick="triggerEditFromReview(${i.id})" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs mx-1">Edit</button>
                    `;

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-3 font-medium text-gray-900">${escapeHtml(i.summary)}</td>
                            <td class="p-3 text-xs uppercase font-bold text-gray-700">${escapeHtml(i.severity)}</td>
                            <td class="p-3">${escapeHtml(i.status)}</td>
                            <td class="p-3 text-red-600 text-xs font-semibold">${escapeHtml(reasons.join(' & '))}</td>
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

// Modal open/close actions
function openIncidentModal() {
    document.getElementById('incidentForm').reset();
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
        // Fetch details to pre-populate existing containment/remediation
        fetch(`backend/api/incident/details.php?id=${preselectedId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    document.getElementById('remediate_containment').value = res.data.containment_actions || '';
                    document.getElementById('remediate_notes').value = res.data.remediation_notes || '';
                }
            });
    } else {
        document.getElementById('remediate_containment').value = '';
        document.getElementById('remediate_notes').value = '';
    }
    document.getElementById('remediateIncidentModal').classList.remove('hidden');
}

function closeRemediateIncidentModal() {
    document.getElementById('remediateIncidentModal').classList.add('hidden');
}

function openEscalateIncidentModal(preselectedId = '') {
    loadIncidentSelectOptions('escalate_incident_select', preselectedId);
    
    // Clear warnings & enable state initially
    const checkbox = document.getElementById('escalate_is_escalated');
    const warning = document.getElementById('escalate_severity_warning');
    checkbox.disabled = false;
    warning.classList.add('hidden');

    if (preselectedId) {
        // Fetch details to pre-populate settings
        fetch(`backend/api/incident/details.php?id=${preselectedId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    const data = res.data;
                    checkbox.checked = String(data.is_escalated) === '1';
                    document.getElementById('escalate_dpo_notified').checked = String(data.dpo_notified) === '1';
                    document.getElementById('escalate_regulatory_status').value = data.regulatory_status || 'Not Required';
                    
                    // Apply severity restriction
                    const severity = data.severity;
                    if (!['High', 'Critical'].includes(severity)) {
                        checkbox.checked = false;
                        checkbox.disabled = true;
                        warning.classList.remove('hidden');
                    }
                }
            });
    } else {
        document.getElementById('escalate_is_escalated').checked = false;
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

// Triggers from review list
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

// Bind to window for HTML triggers
window.editIncident = editIncident;
window.deleteIncident = deleteIncident;
window.openIncidentModal = openIncidentModal;
window.closeIncidentModal = closeIncidentModal;
window.triggerRemediateFromReview = triggerRemediateFromReview;
window.triggerEscalateFromReview = triggerEscalateFromReview;
window.triggerEditFromReview = triggerEditFromReview;

// Bind event listeners
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadIncidents();

    document.getElementById('searchForm').addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        loadIncidents();
    });

    document.getElementById('btnPrev').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadIncidents();
        }
    });

    document.getElementById('btnNext').addEventListener('click', () => {
        currentPage++;
        loadIncidents();
    });

    // Form handlers
    document.getElementById('incidentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('incidentForm', `backend/api/incident/${currentEndpoint}`, 'incidentModal');
    });

    document.getElementById('remediateIncidentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('remediateIncidentForm', 'backend/api/incident/remediate.php', 'remediateIncidentModal');
    });

    document.getElementById('escalateIncidentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('escalateIncidentForm', 'backend/api/incident/escalate.php', 'escalateIncidentModal');
    });

    // Severity check logic for escalation
    document.getElementById('escalate_incident_select').addEventListener('change', function() {
        const id = this.value;
        const severity = incidentSeverityMap[id] || '';
        const checkbox = document.getElementById('escalate_is_escalated');
        const warning = document.getElementById('escalate_severity_warning');
        
        if (id && !['High', 'Critical'].includes(severity)) {
            checkbox.checked = false;
            checkbox.disabled = true;
            warning.classList.remove('hidden');
        } else {
            checkbox.disabled = false;
            warning.classList.add('hidden');
        }
    });

    // Quick Actions
    document.getElementById('btn-log-incident-qa').addEventListener('click', openIncidentModal);
    document.getElementById('btn-remediate-incident-qa').addEventListener('click', () => openRemediateIncidentModal());
    document.getElementById('btn-escalate-incident-qa').addEventListener('click', () => openEscalateIncidentModal());
    document.getElementById('btn-review-active-qa').addEventListener('click', openReviewActiveModal);

    document.getElementById('btn-export-incidents-qa').addEventListener('click', () => {
        const search = document.getElementById('filter-search').value;
        const severity = document.getElementById('filter-severity').value;
        const status = document.getElementById('filter-status').value;
        window.open(`backend/api/incident/export.php?search=${encodeURIComponent(search)}&severity=${encodeURIComponent(severity)}&status=${encodeURIComponent(status)}`, '_blank');
    });

    document.getElementById('btn-generate-report-qa').addEventListener('click', () => {
        window.open('backend/api/reports/incident.php', '_blank');
    });

    // Close buttons
    document.getElementById('closeRemediateIncidentModal').addEventListener('click', closeRemediateIncidentModal);
    document.getElementById('btnCancelRemediate').addEventListener('click', closeRemediateIncidentModal);

    document.getElementById('closeEscalateIncidentModal').addEventListener('click', closeEscalateIncidentModal);
    document.getElementById('btnCancelEscalate').addEventListener('click', closeEscalateIncidentModal);

    document.getElementById('closeReviewActiveModal').addEventListener('click', closeReviewActiveModal);
    document.getElementById('btnCloseReviewActiveModal').addEventListener('click', closeReviewActiveModal);
});
