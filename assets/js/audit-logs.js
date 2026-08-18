// governance/assets/js/audit-logs.js
// Audit Logs Module JS Controller

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function toggleExportDropdown() {
    const menu = document.getElementById('exportDropdownMenu');
    if (menu) menu.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('exportDropdownMenu');
    const btn = e.target.closest('button');
    if (menu && !menu.classList.contains('hidden') && (!btn || !btn.getAttribute('onclick')?.includes('toggleExportDropdown'))) {
        menu.classList.add('hidden');
    }
});

// 1. Dashboard Telemetry & Visual Analytics (Row 124)
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/audit-logs/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;

            // KPI Cards
            const kpiTotal = document.getElementById('kpi-total');
            const kpiToday = document.getElementById('kpi-today');
            const kpiPeriod = document.getElementById('kpi-period');
            const kpiLogin = document.getElementById('kpi-login');
            const kpiMutation = document.getElementById('kpi-mutation');
            const kpiSecurity = document.getElementById('kpi-security');

            if (kpiTotal) kpiTotal.innerText = d.total_events || 0;
            if (kpiToday) kpiToday.innerText = d.events_today || 0;
            if (kpiPeriod) kpiPeriod.innerText = d.period_events || 0;
            if (kpiLogin) kpiLogin.innerText = d.login_events || 0;
            if (kpiMutation) kpiMutation.innerText = d.mutation_events || 0;
            if (kpiSecurity) kpiSecurity.innerText = d.security_events || 0;

            // Chart 1: Module Distribution
            const distModule = document.getElementById('dist-module');
            if (distModule) {
                const mods = d.module_distribution || {};
                const keys = Object.keys(mods);
                const total = d.total_events || 1;

                if (keys.length === 0) {
                    distModule.innerHTML = '<div class="text-caption text-gray-500">No module audit events recorded.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = mods[k] || 0;
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
                    distModule.innerHTML = html;
                }
            }

            // Chart 2: 14-Day Velocity Trend
            const distTrend = document.getElementById('dist-trend');
            if (distTrend) {
                const trend = d.daily_trend || {};
                const keys = Object.keys(trend);
                const maxVal = Math.max(...Object.values(trend), 1);

                if (keys.length === 0) {
                    distTrend.innerHTML = '<div class="text-caption text-gray-500">No 14-day velocity data available.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = trend[k] || 0;
                        const pct = Math.round((count / maxVal) * 100);
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span>${escapeHtml(k)}</span>
                                    <span class="font-bold text-on-surface">${count} Events</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distTrend.innerHTML = html;
                }
            }

            // Chart 3: Top Active Users
            const distUsers = document.getElementById('dist-users');
            if (distUsers) {
                const users = d.top_active_users || [];
                const total = d.total_events || 1;

                if (users.length === 0) {
                    distUsers.innerHTML = '<div class="text-caption text-gray-500">No user telemetry recorded.</div>';
                } else {
                    let html = '';
                    users.forEach(u => {
                        const count = u.count || 0;
                        const pct = Math.round((count / total) * 100);
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span class="truncate max-w-[180px]">${escapeHtml(u.user_identifier)}</span>
                                    <span class="font-bold text-on-surface">${count} (${pct}%)</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distUsers.innerHTML = html;
                }
            }
        }
    } catch (e) {
        console.error('Failed to load Audit Logs dashboard metrics', e);
    }
}

// 2. Paginated Activity Logs Register (Rows 125, 126, 127)
async function loadAuditLogs() {
    const search = document.getElementById('filter-search')?.value || '';
    const module = document.getElementById('filter-module')?.value || '';
    const dateFrom = document.getElementById('filter-date-from')?.value || '';
    const dateTo = document.getElementById('filter-date-to')?.value || '';

    const url = `backend/api/audit-logs/list.php?p=${currentPage}&limit=20&search=${encodeURIComponent(search)}&module=${encodeURIComponent(module)}&date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`;
    
    const tbody = document.getElementById('auditTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading audit log records...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="6" class="px-lg py-md text-center text-gray-500">No matching audit log records found.</td></tr>';
            } else {
                items.forEach(l => {
                    let actionBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                    const act = (l.action || '').toLowerCase();
                    if (act.includes('delete') || act.includes('purge') || act.includes('failed') || act.includes('denied')) {
                        actionBadge = 'bg-red-50 text-red-700 border-red-200';
                    } else if (act.includes('create') || act.includes('generate') || act.includes('add')) {
                        actionBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    } else if (act.includes('update') || act.includes('toggle') || act.includes('save')) {
                        actionBadge = 'bg-purple-50 text-purple-700 border-purple-200';
                    }

                    const userDisp = l.user_email || `User #${l.user_id || 0}`;

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md">
                                <div class="font-mono text-caption text-primary font-bold">#${l.id}</div>
                                <div class="font-mono text-caption text-on-surface-variant">${escapeHtml(l.created_at)}</div>
                            </td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(userDisp)}</div>
                                <div class="text-caption text-on-surface-variant">${escapeHtml(l.user_full_name || '')}</div>
                            </td>
                            <td class="px-lg py-md">
                                <span class="font-semibold text-on-surface">${escapeHtml(l.module || 'System')}</span>
                                ${l.record_id ? `<span class="text-caption font-mono text-on-surface-variant block">Ref #${l.record_id}</span>` : ''}
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${actionBadge}">
                                    ${escapeHtml(l.action)}
                                </span>
                            </td>
                            <td class="px-lg py-md">
                                <div class="font-mono text-caption font-semibold text-on-surface">${escapeHtml(l.ip_address || '127.0.0.1')}</div>
                                <div class="text-caption text-on-surface-variant truncate max-w-[150px]" title="${escapeHtml(l.user_agent)}">${escapeHtml(l.user_agent || 'Internal API')}</div>
                            </td>
                            <td class="px-lg py-md text-right whitespace-nowrap">
                                <button onclick="openLogDetailsModal(${l.id})" class="px-3 py-1.5 bg-primary/10 text-primary hover:bg-primary/20 font-semibold text-xs rounded-lg transition cursor-pointer">
                                    Details
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 20) || 1;
            const paginationDiv = document.getElementById('auditPagination');
            if (paginationDiv) {
                const startIdx = total === 0 ? 0 : (currentPage - 1) * 20 + 1;
                const endIdx = Math.min(currentPage * 20, total);
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${startIdx}–${endIdx}</strong> of <strong>${total}</strong> Audit Log Records
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load audit logs list', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-lg py-md text-center text-red-600">Failed to load audit log records.</td></tr>';
        }
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadAuditLogs();
}

function clearAuditFilters() {
    const s = document.getElementById('filter-search');
    const m = document.getElementById('filter-module');
    const df = document.getElementById('filter-date-from');
    const dt = document.getElementById('filter-date-to');

    if (s) s.value = '';
    if (m) m.value = '';
    if (df) df.value = '';
    if (dt) dt.value = '';

    currentPage = 1;
    loadAuditLogs();
}

// 3. Log Details Audit Profile Modal (Row 128)
async function openLogDetailsModal(id) {
    if (!id) return;
    const modal = document.getElementById('auditDetailsModal');
    const content = document.getElementById('logDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading audit event details...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/audit-logs/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const l = data.data;
            const userDisp = l.user_email || `User #${l.user_id || 0}`;

            content.innerHTML = `
                <div class="space-y-4 text-sm">
                    <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant flex justify-between items-center">
                        <div>
                            <span class="text-caption text-primary font-mono font-bold">LOG ID #${l.id}</span>
                            <h4 class="font-bold text-on-surface text-title-md">${escapeHtml(l.action)}</h4>
                        </div>
                        <span class="px-3 py-1 text-caption font-semibold rounded-full border bg-blue-50 text-blue-700 border-blue-200">
                            ${escapeHtml(l.module || 'System')}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">User Email</span>
                            <strong class="text-primary truncate block">${escapeHtml(userDisp)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Timestamp</span>
                            <strong class="font-mono text-on-surface text-caption block">${escapeHtml(l.created_at)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Target Record ID</span>
                            <strong class="font-mono text-on-surface">${escapeHtml(l.record_id || 'N/A')}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">IP Address</span>
                            <strong class="font-mono text-on-surface">${escapeHtml(l.ip_address || '127.0.0.1')}</strong>
                        </div>
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-lg border">
                        <span class="text-caption text-on-surface-variant block mb-1">User Agent / Client Device</span>
                        <div class="font-mono text-caption text-on-surface bg-surface p-2 rounded border border-outline-variant overflow-x-auto">${escapeHtml(l.user_agent || 'Internal Platform Request')}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <span class="text-caption font-semibold uppercase text-on-surface-variant block mb-1">Original Value (Before Mutation)</span>
                            <pre class="p-3 bg-surface-container-low border border-outline-variant rounded-lg font-mono text-xs text-on-surface overflow-x-auto max-h-48">${escapeHtml(l.old_value_sanitized || 'None / Not Applicable')}</pre>
                        </div>
                        <div>
                            <span class="text-caption font-semibold uppercase text-emerald-700 block mb-1">New Value (After Mutation)</span>
                            <pre class="p-3 bg-surface-container-low border border-outline-variant rounded-lg font-mono text-xs text-on-surface overflow-x-auto max-h-48">${escapeHtml(l.new_value_sanitized || 'None / Not Applicable')}</pre>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load audit event details.</div>';
        }
    } catch (e) {
        console.error('Failed to load Audit Log details', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading audit event profile.</div>';
    }
}

function closeLogDetailsModal() {
    const modal = document.getElementById('auditDetailsModal');
    if (modal) modal.classList.add('hidden');
}

// 4. Export Audit Logs (Row 129)
function exportAuditLogs(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const module = document.getElementById('filter-module')?.value || '';
    const dateFrom = document.getElementById('filter-date-from')?.value || '';
    const dateTo = document.getElementById('filter-date-to')?.value || '';

    const url = `backend/api/audit-logs/export.php?format=${format}&search=${encodeURIComponent(search)}&module=${encodeURIComponent(module)}&date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`;
    window.open(url, '_blank');
}

// 5. Retention Policy Management (Row 130)
async function openRetentionModal() {
    const modal = document.getElementById('retentionModal');
    if (!modal) return;

    modal.classList.remove('hidden');

    try {
        const res = await fetch('backend/api/audit-logs/retention.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            document.getElementById('ret_days').value = r.retention_days || 90;
            document.getElementById('ret_auto_purge').checked = !!r.auto_purge_enabled;

            document.getElementById('ret_status_summary').innerText = `Active Retention: ${r.retention_days} Days | Auto-Purge: ${r.auto_purge_enabled ? 'ENABLED' : 'DISABLED'}`;
            document.getElementById('ret_last_purge').innerText = `Last Purge Execution: ${r.last_purge_at || 'Never'}`;
        }
    } catch (e) {
        console.error('Failed to load retention settings', e);
    }
}

function closeRetentionModal() {
    const modal = document.getElementById('retentionModal');
    if (modal) modal.classList.add('hidden');
}

function openPurgeModal() {
    document.getElementById('purgeModal').classList.remove('hidden');
}

function closePurgeModal() {
    document.getElementById('purgeModal').classList.add('hidden');
}

async function executePurgeNow() {
    closePurgeModal();
    try {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);

        const res = await fetch('backend/api/audit-logs/purge.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadAuditLogs();
            loadDashboard();
            alert(data.message || 'Audit log retention purge executed successfully!');
        } else {
            alert('Error: ' + (data.message || 'Failed to execute retention purge.'));
        }
    } catch (e) {
        alert('Network request failed executing retention purge.');
    }
}

// Global Scope Exports
window.loadDashboard = loadDashboard;
window.loadAuditLogs = loadAuditLogs;
window.changePage = changePage;
window.clearAuditFilters = clearAuditFilters;
window.openLogDetailsModal = openLogDetailsModal;
window.closeLogDetailsModal = closeLogDetailsModal;
window.toggleExportDropdown = toggleExportDropdown;
window.exportAuditLogs = exportAuditLogs;
window.openRetentionModal = openRetentionModal;
window.closeRetentionModal = closeRetentionModal;
window.openPurgeModal = openPurgeModal;
window.closePurgeModal = closePurgeModal;
window.executePurgeNow = executePurgeNow;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadAuditLogs();

    const searchForm = document.getElementById('auditSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadAuditLogs();
        });
    }

    // Submit Retention Form
    const retForm = document.getElementById('retentionForm');
    if (retForm) {
        retForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch('backend/api/audit-logs/retention.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeRetentionModal();
                    loadDashboard();
                    alert(data.message || 'Retention policy settings updated successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to update retention policy.'));
                }
            } catch (err) {
                alert('Network error saving retention settings.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
