<?php
// pages/incident-management.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="space-y-6 max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">warning</span>
                Incident Management
            </h1>
            <p class="text-sm text-gray-500 mt-1">Track, investigate, and resolve security and privacy incidents.</p>
        </div>
        <button onclick="openIncidentModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            + Log New Incident
        </button>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Active Incidents</p>
            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="kpi-active">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">High Severity</p>
            <h2 class="text-3xl font-bold text-red-600 mt-2" id="kpi-high-severity">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Resolved</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2" id="kpi-resolved">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Resolution Rate</p>
            <h2 class="text-3xl font-bold text-emerald-500 mt-2" id="kpi-resolution-rate">...</h2>
        </div>
    </div>

    <!-- Analytics & Search -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <!-- Distribution -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-md font-semibold text-gray-700 mb-5">Incident Distribution</h2>
            <div class="space-y-4" id="distributionBars">
                <!-- Injected via JS -->
                <div class="text-gray-500 text-sm">Loading...</div>
            </div>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-md font-semibold text-gray-700 mb-5">Search & Filter</h2>
            <form id="searchForm" class="space-y-4">
                <input type="text" id="filter-search" placeholder="Search Summary or Description..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <div class="grid grid-cols-2 gap-4">
                    <select id="filter-severity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="">All Severities</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                    <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="Investigating">Investigating</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition">
                    Search Incidents
                </button>
            </form>
        </div>
    </div>

    <!-- INCIDENTS TABLE -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Incident Ledger</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">Summary</th>
                        <th class="p-4">Severity</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Impacted Records</th>
                        <th class="p-4">Created At</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="incidentTableBody">
                    <tr><td colspan="6" class="text-center py-8 text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="flex justify-between items-center p-4 border-t hidden">
            <span class="text-sm text-gray-600" id="pageInfo"></span>
            <div class="flex gap-2">
                <button id="btnPrev" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Previous</button>
                <button id="btnNext" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Incident -->
<div id="incidentModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative">
        <button onclick="closeIncidentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4" id="modalTitle">Log New Incident</h3>
        
        <form id="incidentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="incident_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
                <input type="text" name="summary" id="incident_summary" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea name="description" id="incident_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                    <select name="severity" id="incident_severity" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Impacted Records</label>
                    <input type="number" name="impacted_records" id="incident_impacted_records" value="0" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>
            
            <div id="statusGroup" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="incident_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="Open">Open</option>
                    <option value="Investigating">Investigating</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeIncidentModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Incident</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let currentEndpoint = 'create.php';

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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
                    { label: 'Medium', count: dist.medium, color: 'bg-blue-500' },
                    { label: 'Low', count: dist.low, color: 'bg-gray-400' }
                ];
                
                levels.forEach(lvl => {
                    const pct = Math.round((lvl.count / total) * 100);
                    html += `
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>${lvl.label}</span>
                                <span>${pct}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full rounded-full ${lvl.color}" style="width: ${pct}%"></div>
                            </div>
                        </div>
                    `;
                });
            }
            document.getElementById('distributionBars').innerHTML = html;
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics', e);
    }
}

async function loadIncidents() {
    const search = document.getElementById('filter-search').value;
    const severity = document.getElementById('filter-severity').value;
    const status = document.getElementById('filter-status').value;

    const url = `backend/api/incident/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&severity=${encodeURIComponent(severity)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('incidentTableBody');
        
        if (data.status === 'success') {
            tbody.innerHTML = '';
            const items = data.data.items;
            const total = data.data.total;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No incidents found.</td></tr>';
            } else {
                items.forEach(i => {
                    let statusClass = 'bg-gray-100 text-gray-800';
                    if (i.status === 'Resolved') statusClass = 'bg-green-100 text-green-800';
                    else if (i.status === 'Investigating') statusClass = 'bg-yellow-100 text-yellow-800';
                    else if (i.status === 'Open') statusClass = 'bg-red-100 text-red-800';

                    let severityClass = 'text-gray-600';
                    if (i.severity === 'Critical') severityClass = 'text-red-700 font-bold';
                    else if (i.severity === 'High') severityClass = 'text-orange-600 font-bold';
                    else if (i.severity === 'Medium') severityClass = 'text-blue-600';
                    
                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-4 font-medium text-gray-900">${escapeHtml(i.summary)}</td>
                            <td class="p-4 ${severityClass}">${escapeHtml(i.severity)}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                                    ${escapeHtml(i.status)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600">${Number(i.impacted_records).toLocaleString()}</td>
                            <td class="p-4 text-gray-500 text-sm">${escapeHtml(i.created_at)}</td>
                            <td class="p-4 text-right">
                                <button onclick="editIncident(${i.id})" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm mr-3">Edit</button>
                                <button onclick="deleteIncident(${i.id})" class="text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
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

async function submitApi(formId, endpoint, modalCallback) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
            loadIncidents();
            loadDashboard();
            form.reset();
            modalCallback();
        } else {
            alert(data.message || 'Error occurred');
        }
    } catch (e) {
        alert('Request failed');
    }
}

document.getElementById('incidentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitApi('incidentForm', `backend/api/incident/${currentEndpoint}`, closeIncidentModal);
});

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
        formData.append('csrf_token', '<?= $csrfToken ?>');
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

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadIncidents();
});
</script>