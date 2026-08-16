// governance/assets/js/risk-register.js
// Risk Register Frontend Controller

let currentPage = 1;
let currentEndpoint = 'create.php';

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 1. Dashboard Live Metrics Telemetry
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/risk-register/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;
            const kpiTotal = document.getElementById('kpi-total');
            const kpiHigh = document.getElementById('kpi-high');
            const kpiMitigated = document.getElementById('kpi-mitigated');
            const kpiNeedsAction = document.getElementById('kpi-needs-action');
            const kpiAvgScore = document.getElementById('kpi-avg-score');

            if (kpiTotal) kpiTotal.innerText = d.total_risks || 0;
            if (kpiHigh) kpiHigh.innerText = (d.critical_risks + d.high_risks) || 0;
            if (kpiMitigated) kpiMitigated.innerText = d.mitigated_risks || 0;
            if (kpiNeedsAction) kpiNeedsAction.innerText = d.needs_action || 0;
            if (kpiAvgScore) kpiAvgScore.innerText = (d.avg_risk_score || 0) + ' / 25';
        }
    } catch (e) {
        console.error('Failed to load risk dashboard metrics', e);
    }
}

// 2. Interactive 5x5 Risk Matrix
async function loadRiskMatrix(type = 'residual') {
    const gridContainer = document.getElementById('riskMatrixGrid');
    if (!gridContainer) return;

    try {
        const res = await fetch(`backend/api/risk-register/matrix.php?type=${type}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const cells = data.data;
            let html = '';
            
            // 5x5 Matrix Layout (Likelihood 5 to 1, Impact 1 to 5)
            cells.forEach(c => {
                let cellBg = 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200';
                if (c.level === 'Critical') cellBg = 'bg-red-100 hover:bg-red-200 text-red-900 border-red-300 font-bold';
                else if (c.level === 'High') cellBg = 'bg-orange-50 hover:bg-orange-100 text-orange-800 border-orange-200';
                else if (c.level === 'Medium') cellBg = 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200';

                html += `
                    <div onclick="filterMatrixCell(${c.likelihood}, ${c.impact})" class="p-3 border rounded-xl flex flex-col justify-between items-center cursor-pointer transition shadow-xs ${cellBg}" title="Likelihood: ${c.likelihood}, Impact: ${c.impact} (${c.level} Risk Zone - Score ${c.score})">
                        <div class="text-[10px] font-mono uppercase opacity-75">L:${c.likelihood} &times; I:${c.impact}</div>
                        <div class="text-xl font-bold my-1">${c.count}</div>
                        <div class="text-[10px] font-semibold">${c.level} (${c.score})</div>
                    </div>
                `;
            });
            gridContainer.innerHTML = html;
        }
    } catch (e) {
        console.error('Failed to load risk matrix', e);
    }
}

function filterMatrixCell(l, i) {
    alert(`Filtering inventory for Risks with Likelihood ${l} & Impact ${i}`);
    currentPage = 1;
    loadRisks();
}

// 3. Paginated Risk Inventory List
async function loadRisks() {
    const search = document.getElementById('filter-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const riskLevel = document.getElementById('filter-risk-level')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const treatment = document.getElementById('filter-treatment')?.value || '';

    const url = `backend/api/risk-register/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(riskLevel)}&status=${encodeURIComponent(status)}&treatment_strategy=${encodeURIComponent(treatment)}`;
    
    const tbody = document.getElementById('riskTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading risk register inventory...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500">No matching risk register records found.</td></tr>';
            } else {
                items.forEach(r => {
                    let inhBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (r.inherent_level === 'Critical') inhBadge = 'bg-red-100 text-red-800 border-red-300 font-bold';
                    else if (r.inherent_level === 'High') inhBadge = 'bg-red-50 text-red-700 border-red-200';
                    else if (r.inherent_level === 'Medium') inhBadge = 'bg-amber-50 text-amber-700 border-amber-200';

                    let resBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (r.residual_level === 'Critical') resBadge = 'bg-red-100 text-red-800 border-red-300 font-bold';
                    else if (r.residual_level === 'High') resBadge = 'bg-red-50 text-red-700 border-red-200';
                    else if (r.residual_level === 'Medium') resBadge = 'bg-amber-50 text-amber-700 border-amber-200';

                    let statusBadge = 'bg-red-50 text-red-700 border-red-200';
                    if (r.status === 'Mitigated') statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    else if (r.status === 'In Review') statusBadge = 'bg-indigo-50 text-indigo-700 border-indigo-200';

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-primary font-bold">${escapeHtml(r.risk_code)}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(r.title)}</div>
                                <span class="text-caption text-on-surface-variant font-mono">${escapeHtml(r.affected_asset || 'Core Asset')} &bull; ${escapeHtml(r.department || 'Governance')}</span>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant">${escapeHtml(r.category)}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${inhBadge}">
                                    ${escapeHtml(r.inherent_level)} (${r.inherent_score})
                                </span>
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${resBadge}">
                                    ${escapeHtml(r.residual_level)} (${r.residual_score})
                                </span>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant">${escapeHtml(r.owner)}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${statusBadge}">
                                    ${escapeHtml(r.status)}
                                </span>
                            </td>
                            <td class="px-lg py-md text-caption font-mono text-on-surface-variant">${escapeHtml(r.target_date || 'N/A')}</td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-1">
                                <button onclick="openRiskDetailsModal(${r.id})" class="text-primary hover:underline font-semibold text-xs px-1">Details</button>
                                <button onclick="openMitigationModal(${r.id})" class="text-emerald-600 hover:underline font-semibold text-xs px-1">Mitigate</button>
                                <button onclick="openRiskHistoryModal(${r.id})" class="text-purple-600 hover:underline font-semibold text-xs px-1">History</button>
                                <button onclick="editRisk(${r.id})" class="text-indigo-600 hover:underline font-semibold text-xs px-1">Edit</button>
                                <button onclick="deleteRisk(${r.id})" class="text-red-600 hover:underline font-semibold text-xs px-1">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10) || 1;
            const paginationDiv = document.getElementById('riskPagination');
            if (paginationDiv) {
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${(currentPage - 1) * 10 + 1}–${Math.min(currentPage * 10, total)}</strong> of <strong>${total}</strong> Risks
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load risk inventory', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-red-600">Failed to load risk register data.</td></tr>';
        }
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadRisks();
}

// 4. Live Score Calculation Preview on Form
function updateFormScorePreview() {
    const il = parseInt(document.getElementById('inherent_likelihood')?.value || 3);
    const ii = parseInt(document.getElementById('inherent_impact')?.value || 3);
    const rl = parseInt(document.getElementById('residual_likelihood')?.value || 2);
    const ri = parseInt(document.getElementById('residual_impact')?.value || 2);

    const inhScore = il * ii;
    const resScore = rl * ri;

    let inhLevel = 'Low';
    if (inhScore >= 17) inhLevel = 'Critical';
    else if (inhScore >= 10) inhLevel = 'High';
    else if (inhScore >= 5) inhLevel = 'Medium';

    let resLevel = 'Low';
    if (resScore >= 17) resLevel = 'Critical';
    else if (resScore >= 10) resLevel = 'High';
    else if (resScore >= 5) resLevel = 'Medium';

    const inhEl = document.getElementById('inh_score_preview');
    const resEl = document.getElementById('res_score_preview');

    if (inhEl) inhEl.innerText = `Inherent: ${inhScore} (${inhLevel})`;
    if (resEl) resEl.innerText = `Residual: ${resScore} (${resLevel})`;
}

// 5. Risk Details Modal
async function openRiskDetailsModal(id) {
    if (!id) return;
    const modal = document.getElementById('riskDetailsModal');
    const content = document.getElementById('riskDetailsContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading risk details...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/risk-register/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            content.innerHTML = `
                <div class="space-y-4 text-sm">
                    <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant flex justify-between items-center">
                        <div>
                            <span class="text-caption text-primary font-mono font-bold">${escapeHtml(r.risk_code)}</span>
                            <h4 class="font-bold text-on-surface text-title-md">${escapeHtml(r.title)}</h4>
                        </div>
                        <span class="px-3 py-1 text-caption font-semibold rounded-full border bg-indigo-50 text-indigo-700 border-indigo-200">
                            ${escapeHtml(r.status_db || 'open')}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Category</span>
                            <strong class="text-on-surface">${escapeHtml(r.category)}</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Inherent Risk</span>
                            <strong class="text-red-600">${escapeHtml(r.inherent_level)} (${r.inherent_score})</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Residual Risk</span>
                            <strong class="text-emerald-600">${escapeHtml(r.residual_level)} (${r.residual_score})</strong>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border">
                            <span class="text-caption text-on-surface-variant block">Owner</span>
                            <strong class="text-on-surface">${escapeHtml(r.owner)}</strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 bg-surface-container-low rounded-lg border space-y-1">
                            <div><span class="text-on-surface-variant">Affected Asset:</span> <strong>${escapeHtml(r.affected_asset || 'Core System')}</strong></div>
                            <div><span class="text-on-surface-variant">Risk Source:</span> <strong>${escapeHtml(r.risk_source || 'Internal Audit')}</strong></div>
                            <div><span class="text-on-surface-variant">Department:</span> <strong>${escapeHtml(r.department || 'Privacy Governance')}</strong></div>
                            <div><span class="text-on-surface-variant">Treatment Strategy:</span> <strong>${escapeHtml(r.treatment_strategy || 'Mitigate / Reduce')}</strong></div>
                        </div>
                        <div class="p-3 bg-surface-container-low rounded-lg border space-y-1">
                            <div><span class="text-on-surface-variant">Target Date:</span> <strong class="font-mono">${escapeHtml(r.target_date || 'N/A')}</strong></div>
                            <div><span class="text-on-surface-variant">Created At:</span> <strong class="font-mono">${escapeHtml(r.created_at)}</strong></div>
                            <div><span class="text-on-surface-variant">Mitigation Progress:</span> <strong>${r.mitigation_progress || 0}%</strong></div>
                            <div><span class="text-on-surface-variant">Mitigation Status:</span> <strong>${escapeHtml(r.mitigation_status || 'Planned')}</strong></div>
                        </div>
                    </div>

                    ${r.mitigation ? `
                        <div class="p-3 bg-emerald-50/50 border border-emerald-200 rounded-lg space-y-1">
                            <h5 class="font-semibold text-emerald-900 text-xs uppercase">Mitigation & Remediation Strategy</h5>
                            <p class="text-gray-700 text-xs leading-relaxed">${escapeHtml(r.mitigation)}</p>
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load risk details.</div>';
        }
    } catch (e) {
        console.error('Failed to load risk details', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading risk details.</div>';
    }
}

function closeRiskDetailsModal() {
    const modal = document.getElementById('riskDetailsModal');
    if (modal) modal.classList.add('hidden');
}

// 6. Mitigation Plan Modal
async function openMitigationModal(riskId) {
    if (!riskId) return;
    const modal = document.getElementById('mitigationModal');
    if (!modal) return;

    try {
        const res = await fetch(`backend/api/risk-register/get.php?id=${riskId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            document.getElementById('mitigation_risk_id').value = r.id;
            document.getElementById('mitigation_risk_code_display').innerText = r.risk_code;
            document.getElementById('mitigation_title').value = r.title + ' Mitigation';
            document.getElementById('mitigation_owner').value = r.mitigation_owner || r.owner;
            document.getElementById('mitigation_target_date').value = r.target_date || '';
            document.getElementById('mitigation_progress').value = r.mitigation_progress || 0;
            document.getElementById('mitigation_progress_val').innerText = (r.mitigation_progress || 0) + '%';
            document.getElementById('mitigation_status').value = r.mitigation_status || 'In Progress';
            document.getElementById('mitigation_details').value = r.mitigation || '';
            document.getElementById('control_details').value = r.control_details || '';

            modal.classList.remove('hidden');
        } else {
            alert('Failed to load risk mitigation details.');
        }
    } catch (e) {
        console.error('Failed to load mitigation modal', e);
        alert('Network error loading mitigation details.');
    }
}

function closeMitigationModal() {
    const modal = document.getElementById('mitigationModal');
    if (modal) modal.classList.add('hidden');
}

// 7. Audit History Log Modal
async function openRiskHistoryModal(riskId) {
    if (!riskId) return;
    const modal = document.getElementById('riskHistoryModal');
    const content = document.getElementById('riskHistoryContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading audit history logs...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/risk-register/history.php?id=${riskId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const items = data.data || [];
            if (items.length === 0) {
                content.innerHTML = '<div class="text-center py-6 text-gray-500">No historical risk changes logged yet.</div>';
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
                            ${h.old_score !== null ? `<div class="text-on-surface-variant">Score: <b>${h.old_score} &rarr; ${h.new_score}</b> (${escapeHtml(h.old_level || 'N/A')} &rarr; ${escapeHtml(h.new_level || 'N/A')})</div>` : ''}
                            ${h.details ? `<div class="mt-1 text-gray-600 italic bg-white p-2 rounded border border-gray-100">"${escapeHtml(h.details)}"</div>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            }
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load risk history logs.</div>';
        }
    } catch (e) {
        console.error('Failed to load risk history', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading risk history logs.</div>';
    }
}

function closeRiskHistoryModal() {
    const modal = document.getElementById('riskHistoryModal');
    if (modal) modal.classList.add('hidden');
}

// 8. Edit / Delete
async function editRisk(id) {
    try {
        const res = await fetch(`backend/api/risk-register/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const r = data.data;
            document.getElementById('risk_id').value = r.id;
            document.getElementById('risk_title').value = r.title;
            document.getElementById('risk_category').value = r.category || 'Data Privacy';
            document.getElementById('risk_source').value = r.risk_source || 'Internal Audit';
            document.getElementById('affected_asset').value = r.affected_asset || 'Core System';
            document.getElementById('risk_owner').value = r.owner || 'Compliance Team';
            document.getElementById('department').value = r.department || 'Privacy Governance';

            document.getElementById('inherent_likelihood').value = r.inherent_likelihood || 3;
            document.getElementById('inherent_impact').value = r.inherent_impact || 3;
            document.getElementById('residual_likelihood').value = r.residual_likelihood || 2;
            document.getElementById('residual_impact').value = r.residual_impact || 2;

            document.getElementById('treatment_strategy').value = r.treatment_strategy || 'Mitigate / Reduce';
            document.getElementById('target_date').value = r.target_date || '';
            document.getElementById('risk_status').value = r.status_db || 'open';
            document.getElementById('mitigation_plan').value = r.mitigation || '';

            updateFormScorePreview();
            document.getElementById('riskModalTitle').innerText = 'Edit Risk Item';
            currentEndpoint = 'update.php';

            document.getElementById('riskModal').classList.remove('hidden');
        } else {
            alert('Failed to load risk details for editing.');
        }
    } catch (e) {
        alert('Failed to load risk details.');
    }
}

async function deleteRisk(id) {
    if (confirm("Are you sure you want to soft-delete this risk item from the register?")) {
        const formData = new FormData();
        formData.append('csrf_token', G_CSRF_TOKEN);
        formData.append('id', id);

        try {
            const res = await fetch('backend/api/risk-register/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadRisks();
                loadDashboard();
                loadRiskMatrix();
                alert('Risk item deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete risk item.');
            }
        } catch (e) {
            alert('Request failed. Connection error.');
        }
    }
}

// 9. Export
function exportRiskRegister(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const riskLevel = document.getElementById('filter-risk-level')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const treatment = document.getElementById('filter-treatment')?.value || '';

    const url = `backend/api/risk-register/export.php?format=${format}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(riskLevel)}&status=${encodeURIComponent(status)}&treatment_strategy=${encodeURIComponent(treatment)}`;
    window.open(url, '_blank');
}

function clearRiskFilters() {
    const s = document.getElementById('filter-search');
    const c = document.getElementById('filter-category');
    const r = document.getElementById('filter-risk-level');
    const st = document.getElementById('filter-status');
    const tr = document.getElementById('filter-treatment');

    if (s) s.value = '';
    if (c) c.value = '';
    if (r) r.value = '';
    if (st) st.value = '';
    if (tr) tr.value = '';

    currentPage = 1;
    loadRisks();
}

function openRiskModal() {
    const form = document.getElementById('riskForm');
    if (form) form.reset();
    document.getElementById('risk_id').value = '';
    document.getElementById('riskModalTitle').innerText = 'Add New Risk Item';
    currentEndpoint = 'create.php';
    updateFormScorePreview();
    document.getElementById('riskModal').classList.remove('hidden');
}

function closeRiskModal() {
    document.getElementById('riskModal').classList.add('hidden');
}

// Global Scope Exports
window.loadDashboard = loadDashboard;
window.loadRisks = loadRisks;
window.loadRiskMatrix = loadRiskMatrix;
window.openRiskModal = openRiskModal;
window.closeRiskModal = closeRiskModal;
window.openRiskDetailsModal = openRiskDetailsModal;
window.closeRiskDetailsModal = closeRiskDetailsModal;
window.openMitigationModal = openMitigationModal;
window.closeMitigationModal = closeMitigationModal;
window.openRiskHistoryModal = openRiskHistoryModal;
window.closeRiskHistoryModal = closeRiskHistoryModal;
window.editRisk = editRisk;
window.deleteRisk = deleteRisk;
window.exportRiskRegister = exportRiskRegister;
window.clearRiskFilters = clearRiskFilters;
window.filterMatrixCell = filterMatrixCell;
window.changePage = changePage;

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRiskMatrix('residual');
    loadRisks();

    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadRisks();
        });
    }

    ['inherent_likelihood', 'inherent_impact', 'residual_likelihood', 'residual_impact'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateFormScorePreview);
            el.addEventListener('change', updateFormScorePreview);
        }
    });

    const progInput = document.getElementById('mitigation_progress');
    if (progInput) {
        progInput.addEventListener('input', function() {
            document.getElementById('mitigation_progress_val').innerText = this.value + '%';
        });
    }

    // Submit Risk Form
    const riskForm = document.getElementById('riskForm');
    if (riskForm) {
        riskForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch(`backend/api/risk-register/${currentEndpoint}`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeRiskModal();
                    loadRisks();
                    loadDashboard();
                    loadRiskMatrix();
                    alert(data.message || 'Risk saved successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                alert('Network error saving risk record.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // Submit Mitigation Form
    const mitigationForm = document.getElementById('mitigationForm');
    if (mitigationForm) {
        mitigationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(this);
            if (!formData.has('csrf_token')) {
                formData.append('csrf_token', G_CSRF_TOKEN);
            }

            try {
                const res = await fetch('backend/api/risk-register/mitigation.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeMitigationModal();
                    loadRisks();
                    loadDashboard();
                    loadRiskMatrix();
                    alert('Mitigation plan saved successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                alert('Network error saving mitigation plan.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
