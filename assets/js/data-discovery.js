// assets/js/data-discovery.js
// PrivacyHQ Personal Data Discovery & DSPM Engine Operations

let currentTab = 'dashboard';
let activeScanInterval = null;
let currentScanId = null;
let sortDirections = {};

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
    if (tabId === 'sources') loadSources();
    if (tabId === 'scan') loadScanEngine();
    if (tabId === 'history') loadScanHistory();
    if (tabId === 'findings') loadFindings();
}

// 1. DASHBOARD OVERVIEW (Row 48)
function loadDashboard() {
    fetch('backend/api/data-discovery/dashboard.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                const m = data.data.metrics || {};
                document.getElementById('dash-total-sources').textContent = m.total_sources || 0;
                document.getElementById('dash-pii-records').textContent = formatRecordCount(m.total_pii_records || 0);
                document.getElementById('dash-sensitive-files').textContent = m.total_sensitive_files || 0;
                document.getElementById('dash-compliance-score').textContent = (m.compliance_score || 100) + '%';

                // Classifications
                const c = data.data.classifications || {};
                document.getElementById('class-personal').textContent = formatRecordCount(c.Personal || 0);
                document.getElementById('class-sensitive').textContent = formatRecordCount(c.Sensitive || 0);
                document.getElementById('class-financial').textContent = formatRecordCount(c.Financial || 0);
                document.getElementById('class-health').textContent = formatRecordCount(c.Health || 0);

                // Render Connected Sources Cards Grid
                loadDashboardSourcesGrid();
            }
        })
        .catch(err => console.error('Error loading discovery dashboard:', err));
}

function formatRecordCount(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
    return num;
}

function loadDashboardSourcesGrid() {
    const grid = document.getElementById('dashSourcesGrid');
    if (!grid) return;

    fetch('backend/api/data-discovery/sources.php?limit=6')
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            if (items.length === 0) {
                grid.innerHTML = `<div class="col-span-3 text-center py-8 text-gray-500 text-xs">No data sources connected yet.</div>`;
                return;
            }

            let html = '';
            items.forEach(src => {
                const riskBadge = src.risk_level === 'high' ? 'bg-rose-100 text-rose-800' : (src.risk_level === 'low' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800');
                const typeBadge = 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                const piiList = src.pii_types_json ? (typeof src.pii_types_json === 'string' ? JSON.parse(src.pii_types_json) : src.pii_types_json) : ['Email'];

                html += `
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded ${typeBadge}">${escapeHtml(src.source_type)}</span>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded ${riskBadge}">${escapeHtml(src.risk_level)} Risk</span>
                            </div>
                            <h4 class="font-bold text-gray-900 text-sm mb-1">${escapeHtml(src.name)}</h4>
                            <p class="text-xs text-gray-500 font-mono mb-3 truncate"><code>${escapeHtml(src.connection_uri)}</code></p>
                            
                            <div class="text-[11px] font-semibold text-gray-700 mb-1">Discovered PII Types:</div>
                            <div class="flex flex-wrap gap-1 mb-3">
                                ${piiList.map(p => `<span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[10px] font-medium">${escapeHtml(p)}</span>`).join('')}
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                            <span class="text-gray-500 font-medium">${formatRecordCount(src.pii_count)} PII Records</span>
                            <button onclick="triggerQuickScan(${src.id})" class="text-indigo-600 font-bold hover:underline">Scan Now</button>
                        </div>
                    </div>
                `;
            });
            grid.innerHTML = html;
        });
}

// 2. SOURCE MANAGEMENT (Row 49)
function loadSources() {
    const tbody = document.getElementById('sourcesTableBody');
    if (!tbody) return;

    const search = document.getElementById('filter-source-search')?.value || '';
    const type = document.getElementById('filter-source-type')?.value || '';
    const risk = document.getElementById('filter-source-risk')?.value || '';
    const status = document.getElementById('filter-source-status')?.value || '';

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-indigo-600 block mb-1">sync</span>Loading data sources...</td></tr>`;

    fetch(`backend/api/data-discovery/sources.php?search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}&risk=${encodeURIComponent(risk)}&status=${encodeURIComponent(status)}`)
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            const total = data.data ? data.data.total : items.length;
            document.getElementById('sourcesCountInfo').textContent = `Showing ${items.length} of ${total} Data Sources`;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs">No matching data sources found.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(src => {
                const riskBadge = src.risk_level === 'high' ? 'bg-rose-100 text-rose-800' : (src.risk_level === 'low' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800');
                const piiList = src.pii_types_json ? (typeof src.pii_types_json === 'string' ? JSON.parse(src.pii_types_json) : src.pii_types_json) : [];

                html += `
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-3.5"><input type="checkbox" class="source-checkbox rounded" value="${src.id}"></td>
                        <td class="p-3.5">
                            <div class="font-bold text-gray-900 text-xs">${escapeHtml(src.name)}</div>
                            <div class="text-[11px] text-gray-400 capitalize">${escapeHtml(src.environment || 'Production')}</div>
                        </td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 font-semibold text-[10px] rounded uppercase">${escapeHtml(src.source_type)}</span>
                        </td>
                        <td class="p-3.5 font-mono text-xs text-gray-600"><code>${escapeHtml(src.connection_uri)}</code></td>
                        <td class="p-3.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${riskBadge}">${escapeHtml(src.risk_level)}</span></td>
                        <td class="p-3.5">
                            <div class="flex flex-wrap gap-1">
                                ${piiList.map(p => `<span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[10px] font-semibold">${escapeHtml(p)}</span>`).join('')}
                            </div>
                        </td>
                        <td class="p-3.5 text-right space-x-2 whitespace-nowrap">
                            <button onclick="triggerQuickScan(${src.id})" class="text-indigo-600 font-bold hover:underline text-xs">Scan</button>
                            <span class="text-gray-300">|</span>
                            <button onclick="openEditSourceModal(${src.id})" class="text-gray-700 font-semibold hover:underline text-xs">Edit</button>
                            <span class="text-gray-300">|</span>
                            <button onclick="confirmDeleteSource(${src.id})" class="text-rose-600 font-semibold hover:underline text-xs">Delete</button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load sources:', err);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-rose-600 text-xs">Failed to load data sources.</td></tr>`;
        });
}

function openAddSourceModal() {
    document.getElementById('addSourceForm').reset();
    document.getElementById('addSourceModal').classList.remove('hidden');
}

function closeAddSourceModal() {
    document.getElementById('addSourceModal').classList.add('hidden');
}

function openEditSourceModal(id) {
    fetch(`backend/api/data-discovery/sources.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                const src = data.data;
                document.getElementById('edit_source_id').value = src.id;
                document.getElementById('edit_source_name').value = src.name;
                document.getElementById('edit_source_type').value = src.source_type;
                document.getElementById('edit_connection_uri').value = src.connection_uri;
                document.getElementById('edit_host_port').value = src.host_port || '';
                document.getElementById('edit_environment').value = src.environment || 'production';
                document.getElementById('edit_risk_level').value = src.risk_level || 'medium';
                document.getElementById('edit_description').value = src.description || '';
                document.getElementById('editSourceModal').classList.remove('hidden');
            } else {
                alert('Error loading source details: ' + (data.message || 'Error'));
            }
        });
}

function closeEditSourceModal() {
    document.getElementById('editSourceModal').classList.add('hidden');
}

function confirmDeleteSource(id) {
    document.getElementById('delete_source_id').value = id;
    document.getElementById('deleteSourceModal').classList.remove('hidden');
}

function closeDeleteSourceModal() {
    document.getElementById('deleteSourceModal').classList.add('hidden');
}

function submitDeleteSource() {
    const id = document.getElementById('delete_source_id').value;
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('backend/api/data-discovery/sources.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            closeDeleteSourceModal();
            loadSources();
            loadDashboard();
        } else {
            alert('Error deleting source: ' + (data.message || 'Error'));
        }
    });
}

// 3. DISCOVERY SCAN ENGINE (Row 50)
function openTriggerScanModal() {
    // Populate Sources Dropdown
    fetch('backend/api/data-discovery/sources.php?limit=100')
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            const select = document.getElementById('scan_target_source_id');
            if (select) {
                select.innerHTML = '<option value="">Select Target Data Source...</option>' + 
                    items.map(s => `<option value="${s.id}">${escapeHtml(s.name)} (${escapeHtml(s.source_type)})</option>`).join('');
            }
            document.getElementById('triggerScanModal').classList.remove('hidden');
        });
}

function closeTriggerScanModal() {
    document.getElementById('triggerScanModal').classList.add('hidden');
}

function triggerQuickScan(sourceId) {
    const formData = new FormData();
    formData.append('source_id', sourceId);
    formData.append('scan_type', 'quick');
    formData.append('action', 'trigger');

    fetch('backend/api/data-discovery/scan.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            currentScanId = data.data ? data.data.scan_id : null;
            switchTab('scan');
        } else {
            alert('Scan error: ' + (data.message || 'Failed to start scan'));
        }
    });
}

function loadScanEngine() {
    fetch('backend/api/data-discovery/history.php?limit=1')
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            if (items.length > 0) {
                const s = items[0];
                currentScanId = s.id;
                document.getElementById('engine-status-badge').textContent = s.status.toUpperCase();
                document.getElementById('engine-target-source').textContent = s.source_name + ' (' + s.source_type + ')';
                document.getElementById('engine-progress-bar').style.width = (s.progress_percentage || 100) + '%';
                document.getElementById('engine-progress-pct').textContent = (s.progress_percentage || 100) + '%';
                document.getElementById('engine-items-scanned').textContent = (s.items_scanned || 0).toLocaleString();
                document.getElementById('engine-pii-found').textContent = (s.pii_records_found || 0).toLocaleString();
                document.getElementById('engine-files-found').textContent = (s.sensitive_files_found || 0).toLocaleString();
                document.getElementById('engine-duration').textContent = (s.duration_seconds || 0) + 's';
            }
        });
}

function controlScan(action) {
    if (!currentScanId) return;
    const formData = new FormData();
    formData.append('scan_id', currentScanId);
    formData.append('action', action);

    fetch('backend/api/data-discovery/scan.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            loadScanEngine();
        } else {
            alert('Scan Action Error: ' + (data.message || 'Failed'));
        }
    });
}

// 4. SCAN HISTORY (Row 51)
function loadScanHistory() {
    const tbody = document.getElementById('historyTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-indigo-600 block mb-1">sync</span>Loading scan logs...</td></tr>`;

    fetch('backend/api/data-discovery/history.php?limit=20')
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs">No scan history recorded.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(s => {
                const statusClass = s.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : (s.status === 'scanning' ? 'bg-indigo-100 text-indigo-800 animate-pulse' : 'bg-gray-100 text-gray-800');
                html += `
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-3.5 font-mono text-xs text-gray-500">#SCN-${s.id}</td>
                        <td class="p-3.5 font-semibold text-gray-900 text-xs">${escapeHtml(s.source_name)}</td>
                        <td class="p-3.5 uppercase text-[10px] font-bold text-gray-600">${escapeHtml(s.scan_type)}</td>
                        <td class="p-3.5 text-xs text-gray-600 font-mono">${escapeHtml(s.started_at || 'Recently')}</td>
                        <td class="p-3.5 text-xs text-gray-600">${s.duration_seconds || 0}s</td>
                        <td class="p-3.5 text-xs font-bold text-indigo-600">${(s.pii_records_found || 0).toLocaleString()}</td>
                        <td class="p-3.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${statusClass}">${escapeHtml(s.status)}</span></td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        });
}

// 5. SENSITIVE DATA DETECTION (Row 52)
function loadFindings() {
    const tbody = document.getElementById('findingsTableBody');
    if (!tbody) return;

    const search = document.getElementById('filter-finding-search')?.value || '';
    const category = document.getElementById('filter-finding-category')?.value || '';
    const severity = document.getElementById('filter-finding-severity')?.value || '';

    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-indigo-600 block mb-1">sync</span>Loading sensitive data findings...</td></tr>`;

    fetch(`backend/api/data-discovery/findings.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&severity=${encodeURIComponent(severity)}`)
        .then(res => res.json())
        .then(data => {
            const items = data.data ? (data.data.items || data.data) : [];
            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-gray-500 text-xs">No sensitive data findings detected.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach(f => {
                const sevClass = f.risk_severity === 'critical' ? 'bg-rose-100 text-rose-800 border-rose-200' : (f.risk_severity === 'high' ? 'bg-orange-100 text-orange-800' : 'bg-amber-100 text-amber-800');
                const catClass = f.classification_category === 'Sensitive' ? 'bg-purple-100 text-purple-800' : (f.classification_category === 'Financial' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800');

                html += `
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                        <td class="p-3.5 font-bold text-gray-900 text-xs">${escapeHtml(f.data_element_name)}</td>
                        <td class="p-3.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded ${catClass}">${escapeHtml(f.classification_category)}</span></td>
                        <td class="p-3.5 font-mono text-xs text-gray-600"><code>${escapeHtml(f.location_path)}</code></td>
                        <td class="p-3.5 font-semibold text-xs text-gray-800">${(f.record_count || 0).toLocaleString()}</td>
                        <td class="p-3.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase ${sevClass}">${escapeHtml(f.risk_severity)}</span></td>
                        <td class="p-3.5 text-xs text-emerald-600 font-bold">${f.confidence_score || 95}%</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        });
}

// 6. EXPORT REPORTS (Rows 53 & 54)
function triggerExport(format = 'csv') {
    const url = `backend/api/data-discovery/export.php?format=${format}&type=summary`;
    window.open(url, '_blank');
}

function submitReportGenerator(e) {
    if (e) e.preventDefault();
    const type = document.getElementById('report_type')?.value || 'summary';
    const format = document.getElementById('report_format')?.value || 'csv';
    const category = document.getElementById('report_category')?.value || '';
    
    const url = `backend/api/data-discovery/export.php?format=${format}&type=${type}&category=${encodeURIComponent(category)}`;
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
window.loadSources = loadSources;
window.openAddSourceModal = openAddSourceModal;
window.closeAddSourceModal = closeAddSourceModal;
window.openEditSourceModal = openEditSourceModal;
window.closeEditSourceModal = closeEditSourceModal;
window.confirmDeleteSource = confirmDeleteSource;
window.closeDeleteSourceModal = closeDeleteSourceModal;
window.submitDeleteSource = submitDeleteSource;
window.openTriggerScanModal = openTriggerScanModal;
window.closeTriggerScanModal = closeTriggerScanModal;
window.triggerQuickScan = triggerQuickScan;
window.loadScanEngine = loadScanEngine;
window.controlScan = controlScan;
window.loadScanHistory = loadScanHistory;
window.loadFindings = loadFindings;
window.triggerExport = triggerExport;
window.submitReportGenerator = submitReportGenerator;

document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    loadDashboard();

    // Form Event Listeners
    document.getElementById('addSourceForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'create');

        fetch('backend/api/data-discovery/sources.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                closeAddSourceModal();
                loadSources();
                loadDashboard();
            } else {
                alert('Error creating source: ' + (data.message || 'Error'));
            }
        });
    });

    document.getElementById('editSourceForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update');

        fetch('backend/api/data-discovery/sources.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                closeEditSourceModal();
                loadSources();
                loadDashboard();
            } else {
                alert('Error updating source: ' + (data.message || 'Error'));
            }
        });
    });

    document.getElementById('triggerScanForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'trigger');

        fetch('backend/api/data-discovery/scan.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' || data.success) {
                closeTriggerScanModal();
                currentScanId = data.data ? data.data.scan_id : null;
                switchTab('scan');
            } else {
                alert('Error triggering scan: ' + (data.message || 'Error'));
            }
        });
    });
});
