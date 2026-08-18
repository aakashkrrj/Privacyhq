// governance/assets/js/reports.js
// Reports Engine, Analytics & Automated Scheduling JS Controller

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 KB';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// 1. Live Dashboard Telemetry & Data-Driven Visualizations (Row 116 & Row 121)
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/reports/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;

            // KPI Cards
            const kpiTotal = document.getElementById('kpi-total');
            const kpiGenerated = document.getElementById('kpi-generated');
            const kpiPeriod = document.getElementById('kpi-period');
            const kpiScheduled = document.getElementById('kpi-scheduled');
            const kpiPending = document.getElementById('kpi-pending');
            const kpiFailed = document.getElementById('kpi-failed');

            if (kpiTotal) kpiTotal.innerText = d.total_reports || 0;
            if (kpiGenerated) kpiGenerated.innerText = d.generated_reports || 0;
            if (kpiPeriod) kpiPeriod.innerText = d.period_reports || 0;
            if (kpiScheduled) kpiScheduled.innerText = d.scheduled_reports || 0;
            if (kpiPending) kpiPending.innerText = d.pending_reports || 0;
            if (kpiFailed) kpiFailed.innerText = d.failed_reports || 0;

            // Chart 1: Module Category Distribution
            const distCategory = document.getElementById('dist-category');
            if (distCategory) {
                const cats = d.category_distribution || {};
                const keys = Object.keys(cats);
                const total = d.total_reports || 1;

                if (keys.length === 0) {
                    distCategory.innerHTML = '<div class="text-caption text-gray-500">No report execution categories recorded.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = cats[k] || 0;
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
                    distCategory.innerHTML = html;
                }
            }

            // Chart 2: Monthly Generation Trend
            const distTrend = document.getElementById('dist-trend');
            if (distTrend) {
                const trend = d.monthly_trend || {};
                const keys = Object.keys(trend);
                const maxVal = Math.max(...Object.values(trend), 1);

                if (keys.length === 0) {
                    distTrend.innerHTML = '<div class="text-caption text-gray-500">No monthly generation trend data available.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = trend[k] || 0;
                        const pct = Math.round((count / maxVal) * 100);
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span>${escapeHtml(k)}</span>
                                    <span class="font-bold text-on-surface">${count} Reports</span>
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

            // Chart 3: Execution Type Distribution
            const distExecType = document.getElementById('dist-execution-type');
            if (distExecType) {
                const types = d.execution_type_distribution || {};
                const keys = Object.keys(types);
                const total = d.total_reports || 1;

                if (keys.length === 0) {
                    distExecType.innerHTML = '<div class="text-caption text-gray-500">No execution type distribution records.</div>';
                } else {
                    let html = '';
                    keys.forEach(k => {
                        const count = types[k] || 0;
                        const pct = Math.round((count / total) * 100);
                        const barColor = k === 'manual' ? 'bg-indigo-500' : 'bg-amber-500';
                        html += `
                            <div>
                                <div class="flex justify-between text-caption font-medium mb-1">
                                    <span class="capitalize">${escapeHtml(k)} Executions</span>
                                    <span class="font-bold text-on-surface">${count} (${pct}%)</span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                    <div class="${barColor} h-2 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        `;
                    });
                    distExecType.innerHTML = html;
                }
            }
        }
    } catch (e) {
        console.error('Failed to load Reports dashboard metrics', e);
    }
}

// 2. Paginated Generated Reports Register (Row 117 & Row 120)
async function loadExecutions() {
    const search = document.getElementById('filter-search')?.value || '';
    const reportType = document.getElementById('filter-type')?.value || '';
    const executionType = document.getElementById('filter-execution-type')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';

    const url = `backend/api/reports/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&report_type=${encodeURIComponent(reportType)}&execution_type=${encodeURIComponent(executionType)}&status=${encodeURIComponent(status)}`;
    
    const tbody = document.getElementById('reportTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading report executions...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-gray-500">No matching report execution logs found.</td></tr>';
            } else {
                items.forEach(r => {
                    let statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (r.status === 'pending' || r.status === 'queued') statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    else if (r.status === 'failed') statusBadge = 'bg-red-50 text-red-700 border-red-200';

                    let typeBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                    if (r.execution_type === 'scheduled') typeBadge = 'bg-purple-50 text-purple-700 border-purple-200';

                    const formatLabel = (r.file_format || 'pdf').toUpperCase();

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-primary font-bold">${escapeHtml(r.report_code)}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(r.title)}</div>
                                <div class="text-caption text-on-surface-variant">${escapeHtml(r.report_type)}</div>
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${typeBadge} capitalize">
                                    ${escapeHtml(r.execution_type)}
                                </span>
                            </td>
                            <td class="px-lg py-md">
                                <div class="font-mono text-caption font-bold text-on-surface">${formatLabel}</div>
                                <div class="text-caption text-on-surface-variant">${formatBytes(r.file_size)}</div>
                            </td>
                            <td class="px-lg py-md font-mono text-caption text-on-surface-variant">${escapeHtml(r.created_at)}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${statusBadge} capitalize">
                                    ${escapeHtml(r.status)}
                                </span>
                            </td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-1">
                                <button onclick="openReportDetailsModal(${r.id})" class="text-primary hover:underline font-semibold text-xs px-1 cursor-pointer">Details</button>
                                <button onclick="downloadReport(${r.id}, 'pdf')" class="text-emerald-700 hover:underline font-semibold text-xs px-1 cursor-pointer">PDF</button>
                                <button onclick="downloadReport(${r.id}, 'excel')" class="text-blue-700 hover:underline font-semibold text-xs px-1 cursor-pointer">Excel</button>
                                <button onclick="deleteReportExecution(${r.id})" class="text-red-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10) || 1;
            const paginationDiv = document.getElementById('reportPagination');
            if (paginationDiv) {
                const startIdx = total === 0 ? 0 : (currentPage - 1) * 10 + 1;
                const endIdx = Math.min(currentPage * 10, total);
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${startIdx}–${endIdx}</strong> of <strong>${total}</strong> Report Executions
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold cursor-pointer">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load Report executions list', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-red-600">Failed to load report execution logs.</td></tr>';
        }
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadExecutions();
}

// 3. Active Report Schedules Register (Row 122)
async function loadSchedules() {
    const tbody = document.getElementById('schedulesTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-emerald-600 block mb-1">sync</span>Loading report schedules...</td></tr>';
    }

    try {
        const res = await fetch('backend/api/reports/schedules.php');
        const data = await res.json();
        
        if ((data.status === 'success' || data.success) && data.data) {
            if (!tbody) return;
            tbody.innerHTML = '';
            const items = data.data.items || [];
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-gray-500">No scheduled report rules configured yet.</td></tr>';
            } else {
                items.forEach(s => {
                    let statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (s.status === 'paused') statusBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    else if (s.status === 'disabled') statusBadge = 'bg-gray-100 text-gray-600 border-gray-300';

                    const toggleAction = s.status === 'active' ? 'paused' : 'active';
                    const toggleBtnLabel = s.status === 'active' ? 'Pause' : 'Activate';

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-emerald-700 font-bold">${escapeHtml(s.schedule_code)}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(s.title)}</div>
                                <div class="text-caption text-on-surface-variant">${escapeHtml(s.report_type)}</div>
                            </td>
                            <td class="px-lg py-md font-mono text-caption font-semibold uppercase text-indigo-700">${escapeHtml(s.frequency)}</td>
                            <td class="px-lg py-md">
                                <div class="font-mono text-caption uppercase font-bold text-on-surface">${escapeHtml(s.export_format)}</div>
                                <div class="text-caption text-on-surface-variant truncate max-w-[180px]">${escapeHtml(s.recipients || 'dpo@privacyhq.com')}</div>
                            </td>
                            <td class="px-lg py-md font-mono text-caption text-on-surface-variant">${escapeHtml(s.next_run_at || 'Pending')}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${statusBadge} uppercase">
                                    ${escapeHtml(s.status)}
                                </span>
                            </td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-1">
                                <button onclick="toggleScheduleStatus(${s.id}, '${toggleAction}')" class="text-amber-600 hover:underline font-semibold text-xs px-1 cursor-pointer">${toggleBtnLabel}</button>
                                <button onclick="openScheduleModal(${s.id})" class="text-primary hover:underline font-semibold text-xs px-1 cursor-pointer">Edit</button>
                                <button onclick="deleteSchedule(${s.id})" class="text-red-600 hover:underline font-semibold text-xs px-1 cursor-pointer">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load Report schedules', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-lg py-md text-center text-red-600">Failed to load report schedule configurations.</td></tr>';
        }
    }
}

// 4. Download Report File (Row 118 PDF & Row 119 Excel)
function downloadReport(id, format = 'pdf') {
    const url = `backend/api/reports/export.php?id=${id}&format=${format}`;
    window.open(url, '_blank');
}

function exportReportsInventory(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const reportType = document.getElementById('filter-type')?.value || '';
    const executionType = document.getElementById('filter-execution-type')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';

    const url = `backend/api/reports/export.php?format=${format}&search=${encodeURIComponent(search)}&report_type=${encodeURIComponent(reportType)}&execution_type=${encodeURIComponent(executionType)}&status=${encodeURIComponent(status)}`;
    window.open(url, '_blank');
}

// 5. Report Execution Profile Modal
async function openReportDetailsModal(id) {
    if (!id) return;
    const modal = document.getElementById('reportDetailsModal');
    const content = document.getElementById('reportDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading report execution audit details...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/reports/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            const generator = (r.generator_first ? r.generator_first + ' ' + r.generator_last : r.generator_email) || 'System Automator';
            content.innerHTML = `
                <div class="space-y-4 text-sm">
                    <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant flex justify-between items-center">
                        <div>
                            <span class="text-caption text-primary font-mono font-bold">${escapeHtml(r.report_code)}</span>
                            <h4 class="font-bold text-on-surface text-title-md">${escapeHtml(r.title)}</h4>
                        </div>
                        <span class="px-3 py-1 text-caption font-semibold rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200 uppercase">
                            ${escapeHtml(r.status)}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Module Category</span>
                            <strong class="text-primary">${escapeHtml(r.report_type)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Execution Type</span>
                            <strong class="text-indigo-700 capitalize">${escapeHtml(r.execution_type)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Format</span>
                            <strong class="font-mono text-on-surface uppercase">${escapeHtml(r.file_format)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">File Size</span>
                            <strong class="text-on-surface">${formatBytes(r.file_size)}</strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Date Generated</span>
                            <strong class="font-mono text-on-surface">${escapeHtml(r.created_at)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Generated By</span>
                            <strong class="text-on-surface">${escapeHtml(generator)}</strong>
                        </div>
                    </div>

                    <div class="p-3 bg-surface-container-low rounded-lg border flex justify-between items-center">
                        <div>
                            <span class="text-caption text-on-surface-variant block font-semibold uppercase">Export Document</span>
                            <span class="text-body-md font-mono text-on-surface">${escapeHtml(r.file_name || 'report_document.pdf')}</span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="downloadReport(${r.id}, 'pdf')" class="px-3 py-1.5 bg-emerald-600 text-white font-semibold text-xs rounded-lg hover:bg-emerald-700 cursor-pointer">
                                Download PDF
                            </button>
                            <button onclick="downloadReport(${r.id}, 'excel')" class="px-3 py-1.5 bg-blue-600 text-white font-semibold text-xs rounded-lg hover:bg-blue-700 cursor-pointer">
                                Excel / CSV
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load report execution details.</div>';
        }
    } catch (e) {
        console.error('Failed to load Report execution details', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading report profile.</div>';
    }
}

function closeReportDetailsModal() {
    const modal = document.getElementById('reportDetailsModal');
    if (modal) modal.classList.add('hidden');
}

// 6. Delete Execution Log
async function deleteReportExecution(id) {
    if (confirm("Are you sure you want to soft-delete this report execution log?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('id', id);

        try {
            const res = await fetch('backend/api/reports/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadExecutions();
                loadDashboard();
                alert('Report execution log deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete execution record.');
            }
        } catch (e) {
            alert('Request failed. Connection error.');
        }
    }
}

// 7. Schedule Controls (Row 122)
async function toggleScheduleStatus(id, status) {
    const formData = new FormData();
    formData.append('csrf_token', G_CSRF_TOKEN);
    formData.append('action', 'toggle');
    formData.append('id', id);
    formData.append('status', status);

    try {
        const res = await fetch('backend/api/reports/schedules.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadSchedules();
            loadDashboard();
        } else {
            alert('Failed to update schedule status.');
        }
    } catch (e) {
        alert('Network request error.');
    }
}

async function deleteSchedule(id) {
    if (confirm("Are you sure you want to soft-delete this report schedule configuration?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('action', 'delete');
        formData.append('id', id);

        try {
            const res = await fetch('backend/api/reports/schedules.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadSchedules();
                loadDashboard();
                alert('Report schedule deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete schedule.');
            }
        } catch (e) {
            alert('Request failed. Connection error.');
        }
    }
}

async function runDueSchedulesNow() {
    try {
        const res = await fetch('backend/api/reports/run_schedules.php');
        const data = await res.json();
        if (data.status === 'success' || data.success) {
            loadExecutions();
            loadSchedules();
            loadDashboard();
            alert(`Cron Execution Runner executed successfully! Processed ${data.data.processed} due report schedule(s).`);
        } else {
            alert('Failed executing report schedule runner.');
        }
    } catch (e) {
        alert('Network error triggering schedule execution runner.');
    }
}

// 8. Filters & Modals Helpers
function clearReportFilters() {
    const s = document.getElementById('filter-search');
    const t = document.getElementById('filter-type');
    const e = document.getElementById('filter-execution-type');
    const st = document.getElementById('filter-status');

    if (s) s.value = '';
    if (t) t.value = '';
    if (e) e.value = '';
    if (st) st.value = '';

    currentPage = 1;
    loadExecutions();
}

function openGenerateModal() {
    const form = document.getElementById('generateReportForm');
    if (form) form.reset();
    document.getElementById('generateReportModal').classList.remove('hidden');
}

function closeGenerateModal() {
    document.getElementById('generateReportModal').classList.add('hidden');
}

function openScheduleModal(id = 0) {
    const form = document.getElementById('scheduleReportForm');
    if (form) form.reset();

    const titleEl = document.getElementById('scheduleModalTitle');
    const idEl = document.getElementById('sched_id');

    if (id) {
        if (titleEl) titleEl.innerText = `Edit Report Schedule (SCH-${String(id).padStart(4, '0')})`;
        if (idEl) idEl.value = id;
    } else {
        if (titleEl) titleEl.innerText = 'Configure Automated Report Schedule';
        if (idEl) idEl.value = '';
    }

    document.getElementById('scheduleReportModal').classList.remove('hidden');
}

function closeScheduleModal() {
    document.getElementById('scheduleReportModal').classList.add('hidden');
}

// Global Scope Exports
window.loadDashboard = loadDashboard;
window.loadExecutions = loadExecutions;
window.loadSchedules = loadSchedules;
window.openGenerateModal = openGenerateModal;
window.closeGenerateModal = closeGenerateModal;
window.openScheduleModal = openScheduleModal;
window.closeScheduleModal = closeScheduleModal;
window.openReportDetailsModal = openReportDetailsModal;
window.closeReportDetailsModal = closeReportDetailsModal;
window.downloadReport = downloadReport;
window.exportReportsInventory = exportReportsInventory;
window.clearReportFilters = clearReportFilters;
window.changePage = changePage;
window.deleteReportExecution = deleteReportExecution;
window.toggleScheduleStatus = toggleScheduleStatus;
window.deleteSchedule = deleteSchedule;
window.runDueSchedulesNow = runDueSchedulesNow;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadExecutions();
    loadSchedules();

    const searchForm = document.getElementById('reportSearchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadExecutions();
        });
    }

    // Submit Generate Form
    const genForm = document.getElementById('generateReportForm');
    if (genForm) {
        genForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch('backend/api/reports/generate.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeGenerateModal();
                    loadExecutions();
                    loadDashboard();
                    alert(data.message || 'Report generated successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Report generation failed'));
                }
            } catch (err) {
                alert('Network error generating report.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // Submit Schedule Form
    const schedForm = document.getElementById('scheduleReportForm');
    if (schedForm) {
        schedForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch('backend/api/reports/schedules.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeScheduleModal();
                    loadSchedules();
                    loadDashboard();
                    alert(data.message || 'Report schedule saved successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed saving report schedule'));
                }
            } catch (err) {
                alert('Network error saving schedule.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
