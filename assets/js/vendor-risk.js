// governance/assets/js/vendor-risk.js
// Vendor Risk Management Frontend Controller

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 1. Load Live Dashboard Telemetry
async function loadRiskDashboard() {
    try {
        const res = await fetch('backend/api/vendor-risk/dashboard.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;
            const kpiTotal = document.getElementById('kpi-total-vendors');
            const kpiCompliant = document.getElementById('kpi-compliant');
            const kpiHigh = document.getElementById('kpi-high-risk');
            const kpiAvgScore = document.getElementById('kpi-avg-score');
            const kpiAvgBar = document.getElementById('kpi-avg-bar');

            if (kpiTotal) kpiTotal.innerText = d.total_vendors || 0;
            if (kpiCompliant) kpiCompliant.innerText = d.compliant_count || 0;
            if (kpiHigh) kpiHigh.innerText = (d.high_risk + d.critical_risk) || 0;

            const avgScore = d.avg_risk_score || 0;
            if (kpiAvgScore) kpiAvgScore.innerText = avgScore + '%';
            if (kpiAvgBar) kpiAvgBar.style.width = Math.min(100, Math.max(0, avgScore)) + '%';

            // Category breakdown scores
            if (d.categories) {
                const catPrivacy = document.getElementById('cat-privacy-score');
                const catSecurity = document.getElementById('cat-security-score');
                const catOperational = document.getElementById('cat-operational-score');
                const catLegal = document.getElementById('cat-legal-score');

                if (catPrivacy) catPrivacy.innerText = (d.categories.privacy || 0) + '%';
                if (catSecurity) catSecurity.innerText = (d.categories.security || 0) + '%';
                if (catOperational) catOperational.innerText = (d.categories.operational || 0) + '%';
                if (catLegal) catLegal.innerText = (d.categories.legal || 0) + '%';
            }
        }
    } catch (e) {
        console.error('Failed to load vendor risk dashboard telemetry', e);
    }
}

// 2. Load Paginated Vendor Risk List
async function loadRiskVendors() {
    const search = document.getElementById('filter-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const risk = document.getElementById('filter-risk')?.value || '';
    const compliance = document.getElementById('filter-compliance')?.value || '';

    const url = `backend/api/vendor-risk/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(risk)}&compliance_status=${encodeURIComponent(compliance)}`;
    
    const tbody = document.getElementById('vendorTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading vendor risk inventory...</td></tr>';
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
                tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-gray-500">No vendor risk records found.</td></tr>';
            } else {
                items.forEach(v => {
                    let riskBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (v.risk_level === 'Critical') {
                        riskBadge = 'bg-red-100 text-red-800 border-red-300 font-bold';
                    } else if (v.risk_level === 'High') {
                        riskBadge = 'bg-red-50 text-red-700 border-red-200';
                    } else if (v.risk_level === 'Medium') {
                        riskBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    }

                    let compBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    if (v.compliance_status === 'Non-Compliant' || v.compliance_status === 'Critical Audit') {
                        compBadge = 'bg-red-50 text-red-700 border-red-200';
                    } else if (v.compliance_status === 'Under Review') {
                        compBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                    }

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant text-body-md text-on-surface">
                            <td class="px-lg py-md font-mono text-caption text-on-surface-variant">#${v.vendor_id}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold text-on-surface">${escapeHtml(v.vendor_name)}</div>
                                <span class="text-caption text-on-surface-variant font-mono">${escapeHtml(v.contact_email || 'No email')}</span>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant">${escapeHtml(v.category)}</td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${riskBadge}">
                                    ${escapeHtml(v.risk_level)} (${v.risk_score}%)
                                </span>
                            </td>
                            <td class="px-lg py-md text-caption font-mono">
                                <div class="grid grid-cols-2 gap-x-2 text-[11px]">
                                    <span>Priv: <b>${v.privacy_score}%</b></span>
                                    <span>Sec: <b>${v.security_score}%</b></span>
                                    <span>Ops: <b>${v.operational_score}%</b></span>
                                    <span>Leg: <b>${v.legal_score}%</b></span>
                                </div>
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-2.5 py-0.5 rounded-full text-caption font-semibold border ${compBadge}">
                                    ${escapeHtml(v.compliance_status)}
                                </span>
                            </td>
                            <td class="px-lg py-md text-caption font-mono text-on-surface-variant">${escapeHtml(v.last_assessment_date || 'Not Assessed')}</td>
                            <td class="px-lg py-md text-right whitespace-nowrap space-x-1">
                                <button onclick="openRiskAssessmentModal(${v.vendor_id})" class="text-primary hover:underline font-semibold text-xs px-1.5" title="Audit Risk Factors">
                                    <span class="material-symbols-outlined text-[16px] align-middle">fact_check</span> Assess
                                </button>
                                <span class="text-outline">|</span>
                                <button onclick="openRiskHistoryModal(${v.vendor_id})" class="text-indigo-600 hover:underline font-semibold text-xs px-1.5" title="View Audit History">History</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10) || 1;
            const paginationDiv = document.getElementById('vendorPagination');
            if (paginationDiv) {
                paginationDiv.innerHTML = `
                    <div class="text-caption text-on-surface-variant">
                        Showing <strong>${(currentPage - 1) * 10 + 1}–${Math.min(currentPage * 10, total)}</strong> of <strong>${total}</strong> Vendors
                    </div>
                    <div class="flex gap-sm">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-outline-variant hover:bg-surface-container-high disabled:opacity-50 text-body-md font-semibold">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load vendor risk list', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="px-lg py-md text-center text-red-600">Failed to load vendor risk data.</td></tr>';
        }
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadRiskVendors();
}

// 3. Risk Assessment Audit Modal
async function openRiskAssessmentModal(vendorId) {
    if (!vendorId) return;
    const modal = document.getElementById('riskAssessmentModal');
    if (!modal) return;

    try {
        const res = await fetch(`backend/api/vendor-risk/get.php?vendor_id=${vendorId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const v = data.data;
            document.getElementById('assess_vendor_id').value = v.vendor_id;
            document.getElementById('assess_vendor_name_display').innerText = v.vendor_name;
            document.getElementById('assess_category_display').innerText = v.category;
            
            document.getElementById('privacy_score_input').value = v.privacy_score || 20;
            document.getElementById('privacy_score_val').innerText = (v.privacy_score || 20) + '%';

            document.getElementById('security_score_input').value = v.security_score || 20;
            document.getElementById('security_score_val').innerText = (v.security_score || 20) + '%';

            document.getElementById('operational_score_input').value = v.operational_score || 20;
            document.getElementById('operational_score_val').innerText = (v.operational_score || 20) + '%';

            document.getElementById('legal_score_input').value = v.legal_score || 20;
            document.getElementById('legal_score_val').innerText = (v.legal_score || 20) + '%';

            document.getElementById('assess_compliance_status').value = v.compliance_status || 'Under Review';
            document.getElementById('assess_notes').value = v.assessment_notes || '';

            updateLiveCalculatedScore();
            modal.classList.remove('hidden');
        } else {
            alert('Error loading vendor assessment details: ' + (data.message || 'Error'));
        }
    } catch (e) {
        console.error('Failed to load vendor risk details', e);
        alert('Network error loading vendor risk details.');
    }
}

function closeRiskAssessmentModal() {
    const modal = document.getElementById('riskAssessmentModal');
    if (modal) modal.classList.add('hidden');
}

function updateLiveCalculatedScore() {
    const p = parseInt(document.getElementById('privacy_score_input')?.value || 0);
    const s = parseInt(document.getElementById('security_score_input')?.value || 0);
    const o = parseInt(document.getElementById('operational_score_input')?.value || 0);
    const l = parseInt(document.getElementById('legal_score_input')?.value || 0);

    document.getElementById('privacy_score_val').innerText = p + '%';
    document.getElementById('security_score_val').innerText = s + '%';
    document.getElementById('operational_score_val').innerText = o + '%';
    document.getElementById('legal_score_val').innerText = l + '%';

    const avg = Math.round((p + s + o + l) / 4);
    let level = 'Low';
    let badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (avg >= 80) {
        level = 'Critical';
        badgeClass = 'bg-red-100 text-red-800 border-red-300 font-bold';
    } else if (avg >= 60) {
        level = 'High';
        badgeClass = 'bg-red-50 text-red-700 border-red-200';
    } else if (avg >= 40) {
        level = 'Medium';
        badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
    }

    const preview = document.getElementById('live_score_preview');
    if (preview) {
        preview.className = `px-3 py-1 rounded-full text-caption font-semibold border ${badgeClass}`;
        preview.innerText = `Calculated: ${avg}% (${level} Risk)`;
    }
}

// 4. Risk History Modal
async function openRiskHistoryModal(vendorId) {
    if (!vendorId) return;
    const modal = document.getElementById('riskHistoryModal');
    const content = document.getElementById('riskHistoryContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading risk history logs...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/vendor-risk/history.php?vendor_id=${vendorId}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const items = data.data || [];
            if (items.length === 0) {
                content.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">No historical risk changes recorded yet.</div>';
            } else {
                let html = '<div class="space-y-3">';
                items.forEach(h => {
                    const user = (h.first_name ? h.first_name + ' ' + h.last_name : h.email) || 'System Auditor';
                    html += `
                        <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant text-caption">
                            <div class="flex justify-between font-semibold text-on-surface mb-1">
                                <span>${escapeHtml(user)}</span>
                                <span class="text-on-surface-variant font-mono text-[11px]">${escapeHtml(h.changed_at || '')}</span>
                            </div>
                            <div class="text-on-surface-variant">
                                Risk Score: <span class="font-bold text-primary">${h.previous_risk_score}% &rarr; ${h.new_risk_score}%</span> | Level: <b>${escapeHtml(h.previous_risk_level || 'Low')} &rarr; ${escapeHtml(h.new_risk_level)}</b>
                            </div>
                            <div class="text-on-surface-variant">
                                Compliance: <b>${escapeHtml(h.previous_status || 'Under Review')} &rarr; ${escapeHtml(h.new_status)}</b>
                            </div>
                            ${h.notes ? `<div class="mt-1 text-gray-600 italic">"${escapeHtml(h.notes)}"</div>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            }
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load risk history.</div>';
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

// 5. Export Functionality
function exportRiskReport(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const risk = document.getElementById('filter-risk')?.value || '';
    const compliance = document.getElementById('filter-compliance')?.value || '';

    const url = `backend/api/vendor-risk/export.php?format=${format}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(risk)}&compliance_status=${encodeURIComponent(compliance)}`;
    window.open(url, '_blank');
}

function searchRiskVendors() {
    currentPage = 1;
    loadRiskVendors();
}

function clearRiskFilters() {
    const s = document.getElementById('filter-search');
    const c = document.getElementById('filter-category');
    const r = document.getElementById('filter-risk');
    const comp = document.getElementById('filter-compliance');

    if (s) s.value = '';
    if (c) c.value = '';
    if (r) r.value = '';
    if (comp) comp.value = '';

    currentPage = 1;
    loadRiskVendors();
}

// Global scope exports to window
window.loadRiskDashboard = loadRiskDashboard;
window.loadRiskVendors = loadRiskVendors;
window.openRiskAssessmentModal = openRiskAssessmentModal;
window.closeRiskAssessmentModal = closeRiskAssessmentModal;
window.openRiskHistoryModal = openRiskHistoryModal;
window.closeRiskHistoryModal = closeRiskHistoryModal;
window.exportRiskReport = exportRiskReport;
window.searchRiskVendors = searchRiskVendors;
window.clearRiskFilters = clearRiskFilters;
window.changePage = changePage;
window.updateLiveCalculatedScore = updateLiveCalculatedScore;

document.addEventListener('DOMContentLoaded', () => {
    loadRiskDashboard();
    loadRiskVendors();

    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadRiskVendors();
        });
    }

    ['privacy_score_input', 'security_score_input', 'operational_score_input', 'legal_score_input'].forEach(id => {
        const inp = document.getElementById(id);
        if (inp) {
            inp.addEventListener('input', updateLiveCalculatedScore);
            inp.addEventListener('change', updateLiveCalculatedScore);
        }
    });

    const form = document.getElementById('riskAssessmentForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving Assessment...';
            }

            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/vendor-risk/save.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeRiskAssessmentModal();
                    loadRiskVendors();
                    loadRiskDashboard();
                    alert('Vendor risk assessment saved and score recalculated successfully!');
                } else {
                    alert('Error saving assessment: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                console.error(err);
                alert('Failed to save assessment. Connection error.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save & Recalculate';
                }
            }
        });
    }
});
