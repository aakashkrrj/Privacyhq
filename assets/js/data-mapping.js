// assets/js/data-mapping.js
// PrivacyHQ Data Mapping & Flow Inventory Operations

let currentTab = 'dashboard';

// Tab Switching Engine
function switchTab(tabId) {
    currentTab = tabId;

    // Update Tab Buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.dataset.tab === tabId) {
            btn.classList.add('border-indigo-600', 'text-indigo-600', 'font-bold');
            btn.classList.remove('border-transparent', 'text-gray-500');
        } else {
            btn.classList.remove('border-indigo-600', 'text-indigo-600', 'font-bold');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });

    // Update Tab Panels
    document.querySelectorAll('.tab-panel').forEach(panel => {
        if (panel.id === `tab-panel-${tabId}`) {
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    });

    // Refresh Data for Active Tab
    if (tabId === 'dashboard') loadDashboard();
    if (tabId === 'activities') loadActivities();
    if (tabId === 'diagram') loadFlowDiagram();
    if (tabId === 'search') loadFlows();
}

// 1. MAPPING DASHBOARD (Row 56)
function loadDashboard() {
    fetch('backend/api/data-mapping/dashboard.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                const m = data.data.metrics || {};
                document.getElementById('dash-total-flows').textContent = m.total_flows || 0;
                document.getElementById('dash-connected-systems').textContent = m.connected_systems || 0;
                document.getElementById('dash-high-risk-flows').textContent = m.high_risk_flows || 0;
                document.getElementById('dash-encrypted-pct').textContent = (m.encrypted_pct || 100) + '%';
                document.getElementById('dash-total-activities').textContent = m.total_activities || 0;

                // Encryption bars
                const encPct = m.encrypted_pct || 0;
                const transPct = m.in_transit_pct || 0;
                const plainPct = m.plaintext_pct || 0;

                document.getElementById('bar-encrypted').style.width = encPct + '%';
                document.getElementById('val-encrypted').textContent = encPct + '%';
                document.getElementById('bar-transit').style.width = transPct + '%';
                document.getElementById('val-transit').textContent = transPct + '%';
                document.getElementById('bar-plain').style.width = plainPct + '%';
                document.getElementById('val-plain').textContent = plainPct + '%';

                // Recent Activity Table
                const recent = data.data.recent_activity || [];
                renderRecentActivityTable(recent);
            }
        })
        .catch(err => console.error('Error loading data mapping dashboard:', err));
}

function renderRecentActivityTable(items) {
    const tbody = document.getElementById('recentActivityTableBody');
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-6 text-gray-500 text-xs">No recent mapping activity recorded.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach(item => {
        const riskClass = item.risk_level === 'High' || item.risk_level === 'Critical' ? 'bg-rose-100 text-rose-800' : (item.risk_level === 'Medium' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800');
        const dt = item.created_at ? item.created_at.substring(0, 10) : 'Recently';

        html += `
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="p-3 font-mono text-xs text-gray-500">${escapeHtml(dt)}</td>
                <td class="p-3 font-semibold text-gray-900 text-xs">${escapeHtml(item.source_system)}</td>
                <td class="p-3 font-semibold text-gray-900 text-xs">${escapeHtml(item.target_system)}</td>
                <td class="p-3 text-xs text-gray-600">${escapeHtml(item.flow_name || item.data_type || 'Data Flow Mapping')}</td>
                <td class="p-3"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${riskClass}">${escapeHtml(item.risk_level)}</span></td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// 2. PROCESSING ACTIVITIES (Row 57)
function loadActivities() {
    const tbody = document.getElementById('activitiesTableBody');
    if (!tbody) return;

    const search = document.getElementById('filter-act-search')?.value || '';
    const department = document.getElementById('filter-act-dept')?.value || '';
    const risk = document.getElementById('filter-act-risk')?.value || '';

    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-indigo-600 block mb-1">sync</span>Loading processing activities...</td></tr>`;

    fetch(`backend/api/data-mapping/activities.php?search=${encodeURIComponent(search)}&department=${encodeURIComponent(department)}&risk=${encodeURIComponent(risk)}`)
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            const total = data.data ? data.data.total : items.length;
            document.getElementById('activitiesCountInfo').textContent = `Showing ${items.length} of ${total} Activities`;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-500 text-xs">No processing activities registered.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(act => {
                const riskBadge = act.risk_level === 'High' || act.risk_level === 'Critical' ? 'bg-rose-100 text-rose-800' : (act.risk_level === 'Medium' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800');

                html += `
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-3.5">
                            <div class="font-bold text-gray-900 text-xs">${escapeHtml(act.activity_name)}</div>
                            <div class="text-[11px] text-gray-400 truncate max-w-xs">${escapeHtml(act.purpose || '')}</div>
                        </td>
                        <td class="p-3.5 text-xs text-gray-700 font-medium">${escapeHtml(act.department || 'Engineering')}</td>
                        <td class="p-3.5 text-xs text-gray-600">
                            <div><strong>Ctrl:</strong> ${escapeHtml(act.data_controller || 'PrivacyHQ')}</div>
                            <div class="text-[11px] text-gray-400"><strong>Proc:</strong> ${escapeHtml(act.processor || 'Internal')}</div>
                        </td>
                        <td class="p-3.5 text-xs text-gray-700 truncate max-w-xs">${escapeHtml(act.data_categories || 'Identity')}</td>
                        <td class="p-3.5 text-xs text-gray-600">${escapeHtml(act.legal_basis || 'Legitimate Interest')}</td>
                        <td class="p-3.5 text-xs text-gray-600">${escapeHtml(act.retention_period || '3 Years')}</td>
                        <td class="p-3.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${riskBadge}">${escapeHtml(act.risk_level)}</span></td>
                        <td class="p-3.5 text-right space-x-2 whitespace-nowrap">
                            <button onclick="openEditActivityModal(${act.id})" class="text-indigo-600 font-bold hover:underline text-xs">Edit</button>
                            <span class="text-gray-300">|</span>
                            <button onclick="confirmDeleteActivity(${act.id})" class="text-rose-600 font-semibold hover:underline text-xs">Delete</button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load activities:', err);
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-600 text-xs">Failed to load processing activities.</td></tr>`;
        });
}

function openAddActivityModal() {
    document.getElementById('addActivityForm').reset();
    document.getElementById('addActivityModal').classList.remove('hidden');
}

function closeAddActivityModal() {
    document.getElementById('addActivityModal').classList.add('hidden');
}

function openEditActivityModal(id) {
    fetch(`backend/api/data-mapping/activities.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                const act = data.data;
                document.getElementById('edit_act_id').value = act.id;
                document.getElementById('edit_activity_name').value = act.activity_name;
                document.getElementById('edit_purpose').value = act.purpose || '';
                document.getElementById('edit_department').value = act.department || 'Engineering';
                document.getElementById('edit_data_controller').value = act.data_controller || '';
                document.getElementById('edit_processor').value = act.processor || '';
                document.getElementById('edit_data_categories').value = act.data_categories || '';
                document.getElementById('edit_data_subjects').value = act.data_subjects || '';
                document.getElementById('edit_legal_basis').value = act.legal_basis || 'Legitimate Interest';
                document.getElementById('edit_retention_period').value = act.retention_period || '3 Years';
                document.getElementById('edit_risk_level').value = act.risk_level || 'Medium';
                document.getElementById('editActivityModal').classList.remove('hidden');
            } else {
                alert('Error loading activity details: ' + (data.message || 'Error'));
            }
        });
}

function closeEditActivityModal() {
    document.getElementById('editActivityModal').classList.add('hidden');
}

function confirmDeleteActivity(id) {
    document.getElementById('delete_act_id').value = id;
    document.getElementById('deleteActivityModal').classList.remove('hidden');
}

function closeDeleteActivityModal() {
    document.getElementById('deleteActivityModal').classList.add('hidden');
}

function submitDeleteActivity() {
    const id = document.getElementById('delete_act_id').value;
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('backend/api/data-mapping/activities.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            closeDeleteActivityModal();
            loadActivities();
            loadDashboard();
        } else {
            alert('Error deleting activity: ' + (data.message || 'Error'));
        }
    });
}

// 3. FLOW DIAGRAM (Row 58)
function loadFlowDiagram() {
    const container = document.getElementById('diagramCanvas');
    if (!container) return;

    container.innerHTML = `<div class="text-center py-12 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-2xl text-indigo-600 block mb-2">sync</span>Rendering Data Flow Topology Diagram...</div>`;

    fetch('backend/api/data-mapping/diagram.php')
        .then(res => res.json())
        .then(data => {
            const flows = data.data || [];
            if (flows.length === 0) {
                container.innerHTML = `<div class="text-center py-12 text-gray-500 text-xs">No data mapping flows registered. Click "+ Register Data Flow" to create a pipeline mapping.</div>`;
                return;
            }

            let html = '<div class="space-y-4 max-w-4xl mx-auto">';
            flows.forEach((f, idx) => {
                const riskBadge = f.risk_level === 'High' || f.risk_level === 'Critical' ? 'bg-rose-100 text-rose-800 border-rose-200' : (f.risk_level === 'Medium' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800');

                html += `
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3 border-b border-gray-200 pb-2">
                            <span class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px]">${idx + 1}</span>
                                ${escapeHtml(f.activity_name || f.flow_name || 'Data Mapping Flow #' + f.id)}
                            </span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${riskBadge}">${escapeHtml(f.risk_level)} Risk</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-center items-center">
                            <!-- Step 1: Source -->
                            <div class="p-3 bg-white rounded-lg border border-indigo-200 shadow-2xs">
                                <div class="text-[10px] uppercase font-bold text-indigo-600 mb-0.5">Source / Origin</div>
                                <div class="font-bold text-gray-900 text-xs truncate">${escapeHtml(f.source_system)}</div>
                            </div>

                            <!-- Connector 1 -->
                            <div class="hidden sm:flex flex-col items-center">
                                <span class="material-symbols-outlined text-indigo-500 text-lg">arrow_forward</span>
                                <span class="text-[9px] font-mono text-gray-500">${escapeHtml(f.transfer_method || 'HTTPS')}</span>
                            </div>

                            <!-- Step 2: Payload -->
                            <div class="p-3 bg-white rounded-lg border border-emerald-200 shadow-2xs">
                                <div class="text-[10px] uppercase font-bold text-emerald-600 mb-0.5">Payload Data</div>
                                <div class="font-bold text-gray-900 text-xs truncate">${escapeHtml(f.data_type || 'PII Data')}</div>
                            </div>

                            <!-- Connector 2 -->
                            <div class="hidden sm:flex flex-col items-center">
                                <span class="material-symbols-outlined text-indigo-500 text-lg">arrow_forward</span>
                                <span class="text-[9px] font-mono text-gray-500">${escapeHtml(f.encryption_status || 'Encrypted')}</span>
                            </div>

                            <!-- Step 3: Target -->
                            <div class="p-3 bg-white rounded-lg border border-purple-200 shadow-2xs col-span-1 sm:col-span-1 mt-2 sm:mt-0">
                                <div class="text-[10px] uppercase font-bold text-purple-600 mb-0.5">Target Destination</div>
                                <div class="font-bold text-gray-900 text-xs truncate">${escapeHtml(f.target_system)}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load flow diagram:', err);
            container.innerHTML = `<div class="text-center py-12 text-rose-600 text-xs">Failed to render flow diagram.</div>`;
        });
}

// 4. SEARCH MAPPING (Row 59)
function loadFlows() {
    const tbody = document.getElementById('flowsTableBody');
    if (!tbody) return;

    const search = document.getElementById('filter-flow-search')?.value || '';
    const risk = document.getElementById('filter-flow-risk')?.value || '';
    const encryption = document.getElementById('filter-flow-encryption')?.value || '';

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-indigo-600 block mb-1">sync</span>Searching data flows...</td></tr>`;

    fetch(`backend/api/data-mapping/flows.php?search=${encodeURIComponent(search)}&risk=${encodeURIComponent(risk)}&encryption=${encodeURIComponent(encryption)}`)
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            const total = data.data ? data.data.total : items.length;
            document.getElementById('flowsCountInfo').textContent = `Showing ${items.length} of ${total} Data Pipelines`;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs">No matching data flows found.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(flow => {
                const riskBadge = flow.risk_level === 'High' || flow.risk_level === 'Critical' ? 'bg-rose-100 text-rose-800' : (flow.risk_level === 'Medium' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800');

                html += `
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-3.5 font-bold text-gray-900 text-xs">${escapeHtml(flow.source_system)}</td>
                        <td class="p-3.5 font-bold text-gray-900 text-xs">${escapeHtml(flow.target_system)}</td>
                        <td class="p-3.5 text-xs text-gray-700">${escapeHtml(flow.data_type || 'PII Data')}</td>
                        <td class="p-3.5 font-mono text-xs text-gray-600">${escapeHtml(flow.transfer_method || 'HTTPS')}</td>
                        <td class="p-3.5 text-xs text-emerald-600 font-semibold">${escapeHtml(flow.encryption_status || 'Encrypted')}</td>
                        <td class="p-3.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${riskBadge}">${escapeHtml(flow.risk_level)}</span></td>
                        <td class="p-3.5 text-right whitespace-nowrap">
                            <button onclick="confirmDeleteFlow(${flow.id})" class="text-rose-600 font-semibold hover:underline text-xs">Delete</button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        });
}

function openAddFlowModal() {
    // Populate Processing Activities dropdown in modal
    fetch('backend/api/data-mapping/activities.php?limit=100')
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            const select = document.getElementById('flow_processing_activity_id');
            if (select) {
                select.innerHTML = '<option value="">Select Processing Activity (Optional)...</option>' + 
                    items.map(a => `<option value="${a.id}">${escapeHtml(a.activity_name)} (${escapeHtml(a.department)})</option>`).join('');
            }
            document.getElementById('addFlowForm').reset();
            document.getElementById('addFlowModal').classList.remove('hidden');
        });
}

function closeAddFlowModal() {
    document.getElementById('addFlowModal').classList.add('hidden');
}

function confirmDeleteFlow(id) {
    if (confirm('Are you sure you want to remove this mapped data flow?')) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'delete');

        fetch('backend/api/data-mapping/flows.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                loadFlows();
                loadDashboard();
            } else {
                alert('Error deleting flow: ' + (data.message || 'Error'));
            }
        });
    }
}

// 5. EXPORT MAPPING (Row 60)
function triggerExport(format = 'csv') {
    const url = `backend/api/data-mapping/export.php?format=${format}&type=flows`;
    window.open(url, '_blank');
}

function submitReportGenerator(e) {
    if (e) e.preventDefault();
    const type = document.getElementById('report_type')?.value || 'flows';
    const format = document.getElementById('report_format')?.value || 'csv';
    const risk = document.getElementById('report_risk')?.value || '';
    
    const url = `backend/api/data-mapping/export.php?format=${format}&type=${type}&risk=${encodeURIComponent(risk)}`;
    window.open(url, '_blank');
}

// Helper Utilities
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Attach to Global Window Scope
window.switchTab = switchTab;
window.loadDashboard = loadDashboard;
window.loadActivities = loadActivities;
window.openAddActivityModal = openAddActivityModal;
window.closeAddActivityModal = closeAddActivityModal;
window.openEditActivityModal = openEditActivityModal;
window.closeEditActivityModal = closeEditActivityModal;
window.confirmDeleteActivity = confirmDeleteActivity;
window.closeDeleteActivityModal = closeDeleteActivityModal;
window.submitDeleteActivity = submitDeleteActivity;
window.loadFlowDiagram = loadFlowDiagram;
window.loadFlows = loadFlows;
window.openAddFlowModal = openAddFlowModal;
window.closeAddFlowModal = closeAddFlowModal;
window.confirmDeleteFlow = confirmDeleteFlow;
window.triggerExport = triggerExport;
window.submitReportGenerator = submitReportGenerator;

document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadDashboard();

    // Form Event Listeners
    document.getElementById('addActivityForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create');

        fetch('backend/api/data-mapping/activities.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                closeAddActivityModal();
                loadActivities();
                loadDashboard();
            } else {
                alert('Error creating activity: ' + (data.message || 'Error'));
            }
        });
    });

    document.getElementById('editActivityForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update');

        fetch('backend/api/data-mapping/activities.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                closeEditActivityModal();
                loadActivities();
                loadDashboard();
            } else {
                alert('Error updating activity: ' + (data.message || 'Error'));
            }
        });
    });

    document.getElementById('addFlowForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create');

        fetch('backend/api/data-mapping/flows.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                closeAddFlowModal();
                loadFlows();
                loadDashboard();
            } else {
                alert('Error mapping data flow: ' + (data.message || 'Error'));
            }
        });
    });
});
