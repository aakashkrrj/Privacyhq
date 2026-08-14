<?php
// pages/audit-logs.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$roleId = $_SESSION['role_id'] ?? 0;
if ($roleId != 1 && $roleId != 2) {
    echo '<div class="p-6 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl">Access Denied: You do not have permission to view audit logs.</div>';
    exit;
}
?>

<div class="space-y-lg">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-display font-display text-primary leading-none">System Audit Log</h1>
            <p class="text-body-md text-on-surface-variant">Trace, filter, and export immutable logs of user and system security activities.</p>
        </div>
        <button onclick="exportAuditLogs()" class="inline-flex items-center gap-xs px-4 py-2 bg-emerald-600 text-white font-semibold rounded-lg shadow hover:bg-emerald-700 transition-all text-body-sm">
            <span class="material-symbols-outlined text-[18px]">download</span>
            <span>Export CSV</span>
        </button>
    </div>

    <!-- Filters Panel -->
    <div class="p-md bg-surface border border-outline-variant rounded-xl flex flex-wrap items-center gap-md shadow-sm">
        <div class="flex flex-col gap-xs">
            <label class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Date</label>
            <input type="date" id="filter-date" onchange="loadAuditLogs()" class="border border-outline-variant rounded-lg px-3 py-1 text-body-sm bg-surface">
        </div>
        <div class="flex flex-col gap-xs">
            <label class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Module</label>
            <select id="filter-module" onchange="loadAuditLogs()" class="border border-outline-variant rounded-lg px-3 py-1 text-body-sm bg-surface">
                <option value="">All Modules</option>
                <option value="User Management">User Management</option>
                <option value="Assessment">Assessment</option>
                <option value="DSR">DSR</option>
                <option value="Incident">Incident</option>
                <option value="Risk">Risk</option>
                <option value="Policy">Policy</option>
                <option value="Vendor">Vendor</option>
            </select>
        </div>
        <div class="flex flex-col gap-xs">
            <label class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Search Input</label>
            <input type="text" id="filter-search" oninput="loadAuditLogs()" placeholder="Search user or action..." class="border border-outline-variant rounded-lg px-3 py-1 text-body-sm bg-surface">
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                        <th class="p-md">Timestamp</th>
                        <th class="p-md">User</th>
                        <th class="p-md">Module</th>
                        <th class="p-md">Action</th>
                        <th class="p-md">IP Address</th>
                        <th class="p-md">Browser</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr>
                        <td colspan="6" class="p-md text-center text-on-surface-variant">Loading logs...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-md bg-surface-container-low border-t border-outline-variant flex justify-between items-center">
            <button id="prevBtn" onclick="changePage(-1)" class="px-3 py-1.5 border border-outline-variant rounded-lg text-body-sm hover:bg-surface-container-high disabled:opacity-50" disabled>Previous</button>
            <span id="pageIndicator" class="text-body-sm text-on-surface-variant">Page 1 of 1</span>
            <button id="nextBtn" onclick="changePage(1)" class="px-3 py-1.5 border border-outline-variant rounded-lg text-body-sm hover:bg-surface-container-high disabled:opacity-50" disabled>Next</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadAuditLogs();
});

function loadAuditLogs() {
    const date = document.getElementById('filter-date').value;
    const module = document.getElementById('filter-module').value;
    const search = document.getElementById('filter-search').value;

    let url = `backend/api/audit-logs/list.php?p=${currentPage}&date=${encodeURIComponent(date)}&module=${encodeURIComponent(module)}&search=${encodeURIComponent(search)}`;

    fetch(url)
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById('auditTableBody');
            if (res.success) {
                const logs = res.data.items;
                const total = res.data.total;
                const totalPages = Math.ceil(total / 20) || 1;

                document.getElementById('pageIndicator').innerText = `Page ${currentPage} of ${totalPages}`;
                document.getElementById('prevBtn').disabled = (currentPage === 1);
                document.getElementById('nextBtn').disabled = (currentPage === totalPages);

                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="p-md text-center text-on-surface-variant">No audit logs found.</td></tr>';
                    return;
                }

                tbody.innerHTML = logs.map(l => `
                    <tr class="hover:bg-surface-container-lowest transition-colors">
                        <td class="p-md text-on-surface-variant font-medium">${l.created_at}</td>
                        <td class="p-md font-semibold">${escapeHtml(l.user_email)}</td>
                        <td class="p-md">${escapeHtml(l.module)}</td>
                        <td class="p-md">
                            <span class="inline-flex px-2.5 py-0.5 text-caption font-semibold rounded-full border bg-blue-50 text-blue-700 border-blue-100">
                                ${escapeHtml(l.action)}
                            </span>
                        </td>
                        <td class="p-md font-mono text-caption text-on-surface-variant">${escapeHtml(l.ip_address || 'N/A')}</td>
                        <td class="p-md text-caption truncate max-w-[150px]" title="${escapeHtml(l.user_agent)}">${escapeHtml(l.user_agent || 'N/A')}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="p-md text-center text-red-600">${escapeHtml(res.message)}</td></tr>`;
            }
        })
        .catch(err => console.error(err));
}

function changePage(dir) {
    currentPage += dir;
    loadAuditLogs();
}

function exportAuditLogs() {
    const date = document.getElementById('filter-date').value;
    const module = document.getElementById('filter-module').value;
    const search = document.getElementById('filter-search').value;

    window.location.href = `backend/api/reports/export.php?module=Incident&format=csv`; // Or make audit-logs export API
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>