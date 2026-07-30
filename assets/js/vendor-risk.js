// assets/js/vendor-risk.js

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// 1. Load Metrics & KPIs
async function loadKpis() {
    try {
        const res = await fetch('backend/api/vendors/kpis.php');
        const data = await res.json();
        if (data.success && data.data) {
            const kpis = data.data;
            document.getElementById('kpi-total-vendors').innerText = kpis.total || 0;
            document.getElementById('kpi-compliant').innerText = (parseInt(kpis.total) - parseInt(kpis.pending_dpa) - parseInt(kpis.high_risk) - parseInt(kpis.critical_risk)) || 0;
            document.getElementById('kpi-high-risk').innerText = (parseInt(kpis.high_risk) + parseInt(kpis.critical_risk)) || 0;
            
            // Calculate Average Score
            const total = parseInt(kpis.total) || 1;
            const criticalCount = parseInt(kpis.critical_risk) || 0;
            const highCount = parseInt(kpis.high_risk) || 0;
            const pendingCount = parseInt(kpis.pending_dpa) || 0;
            
            // Basic score formula: start at 100%, deduct risk classifications
            let avgScore = 100 - Math.round(((criticalCount * 30) + (highCount * 15) + (pendingCount * 5)) / total);
            if (avgScore < 0) avgScore = 0;
            if (avgScore > 100) avgScore = 100;

            document.getElementById('kpi-avg-score').innerText = avgScore + '%';
            document.getElementById('kpi-avg-bar').style.width = avgScore + '%';
        }
    } catch (e) {
        console.error('Failed to load vendor KPIs', e);
    }
}

// 2. Load Paginated Vendors List
async function loadVendors() {
    const search = document.getElementById('filter-search').value;
    const risk = document.getElementById('filter-risk').value;
    const status = document.getElementById('filter-status').value;

    const url = `backend/api/vendors/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&risk_level=${encodeURIComponent(risk)}&status=${encodeURIComponent(status)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('vendorTableBody');
        
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-lg py-md text-center text-gray-500">No vendors found.</td></tr>';
            } else {
                items.forEach(v => {
                    let riskClass = 'bg-green-100 text-green-700';
                    if (v.risk_level === 'Critical' || v.risk_level === 'High') {
                        riskClass = 'bg-red-100 text-red-700';
                    } else if (v.risk_level === 'Medium') {
                        riskClass = 'bg-yellow-100 text-yellow-700';
                    }

                    let dpaClass = 'bg-green-100 text-green-700';
                    if (v.dpa_status !== 'Compliant') {
                        dpaClass = 'bg-yellow-100 text-yellow-700';
                    }

                    const row = `
                        <tr class="hover:bg-surface-container-low border-b border-outline-variant">
                            <td class="px-lg py-md">
                                <div class="font-semibold">${escapeHtml(v.vendor_name)}</div>
                            </td>
                            <td class="px-lg py-md">${escapeHtml(v.category)}</td>
                            <td class="px-lg py-md">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${riskClass}">
                                    ${escapeHtml(v.risk_level)}
                                </span>
                            </td>
                            <td class="px-lg py-md">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${dpaClass}">
                                    ${escapeHtml(v.dpa_status)}
                                </span>
                            </td>
                            <td class="px-lg py-md text-center">
                                <button onclick="triggerDpiaForVendor(${v.id}, '${escapeHtml(v.vendor_name)}')" class="text-primary hover:underline">
                                    <span class="material-symbols-outlined align-middle">fact_check</span> Assess
                                </button>
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
                    <div class="text-sm text-outline">
                        Showing <strong>${(currentPage - 1) * 10 + 1}–${Math.min(currentPage * 10, total)}</strong> of <strong>${total}</strong> Vendors
                    </div>
                    <div class="flex gap-2">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-4 py-2 rounded-lg border border-outline-variant hover:bg-gray-50 disabled:opacity-50">Previous</button>
                        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-4 py-2 rounded-lg border border-outline-variant hover:bg-gray-50 disabled:opacity-50">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load vendors', e);
    }
}

function changePage(page) {
    currentPage = page;
    loadVendors();
}

// 3. Start Assessment for selected Vendor
function triggerDpiaForVendor(vendorId, vendorName) {
    const select = document.getElementById('assessment_vendor_select');
    select.innerHTML = `<option value="${vendorId}" selected>${vendorName}</option>`;
    document.getElementById('assessment_title').value = `${vendorName} Security Audit`;
    document.getElementById('startAssessmentModal').classList.remove('hidden');
    document.getElementById('startAssessmentModal').classList.add('flex');
}

// Helper to load all vendors into Select dropdown
async function loadVendorSelectDropdown() {
    try {
        const res = await fetch('backend/api/vendors/list.php?p=1&limit=100');
        const data = await res.json();
        if (data.success && data.data) {
            const items = data.data.items || [];
            const select = document.getElementById('assessment_vendor_select');
            select.innerHTML = '<option value="">-- Choose a Vendor --</option>';
            items.forEach(v => {
                select.innerHTML += `<option value="${v.id}">${escapeHtml(v.vendor_name)}</option>`;
            });
        }
    } catch (e) {
        console.error('Failed to load vendors dropdown', e);
    }
}

// 4. Modal Triggers
function openVendorModal() {
    document.getElementById('vendorModal').classList.remove('hidden');
    document.getElementById('vendorModal').classList.add('flex');
}

function closeVendorModal() {
    document.getElementById('vendorModal').classList.add('hidden');
    document.getElementById('vendorModal').classList.remove('flex');
    document.getElementById('vendorForm').reset();
}

function openAssessmentModal() {
    loadVendorSelectDropdown();
    document.getElementById('assessment_title').value = '';
    document.getElementById('startAssessmentModal').classList.remove('hidden');
    document.getElementById('startAssessmentModal').classList.add('flex');
}

function closeAssessmentModal() {
    document.getElementById('startAssessmentModal').classList.add('hidden');
    document.getElementById('startAssessmentModal').classList.remove('flex');
    document.getElementById('assessmentForm').reset();
}

// 5. Review Flags (High/Critical or non-compliant status)
async function openFlagsModal() {
    const tbody = document.getElementById('flaggedVendorsTableBody');
    tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-gray-500">Checking flags...</td></tr>';
    document.getElementById('reviewFlagsModal').classList.remove('hidden');
    document.getElementById('reviewFlagsModal').classList.add('flex');

    try {
        // Fetch all vendors
        const res = await fetch('backend/api/vendors/list.php?p=1&limit=100');
        const data = await res.json();
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const flagged = items.filter(v => 
                v.risk_level === 'Critical' || 
                v.risk_level === 'High' || 
                v.dpa_status !== 'Compliant'
            );

            if (flagged.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-green-600 font-semibold">No critical flags found! All systems green.</td></tr>';
            } else {
                flagged.forEach(v => {
                    let riskClass = 'text-green-600';
                    if (v.risk_level === 'Critical' || v.risk_level === 'High') {
                        riskClass = 'text-red-600 font-bold';
                    }

                    let dpaClass = 'text-green-600';
                    if (v.dpa_status !== 'Compliant') {
                        dpaClass = 'text-yellow-600 font-bold';
                    }

                    tbody.innerHTML += `
                        <tr class="border-b">
                            <td class="p-4 font-semibold">${escapeHtml(v.vendor_name)}</td>
                            <td class="p-4 ${riskClass}">${escapeHtml(v.risk_level)}</td>
                            <td class="p-4 ${dpaClass}">${escapeHtml(v.dpa_status)}</td>
                        </tr>
                    `;
                });
            }
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-red-600">Error loading flags.</td></tr>';
    }
}

function closeFlagsModal() {
    document.getElementById('reviewFlagsModal').classList.add('hidden');
    document.getElementById('reviewFlagsModal').classList.remove('flex');
}

// 6. Setup Listeners
document.addEventListener('DOMContentLoaded', () => {
    loadKpis();
    loadVendors();

    // Filters Search Button
    document.getElementById('btn-search').addEventListener('click', () => {
        currentPage = 1;
        loadVendors();
    });

    // Onboard Header Button & Modal Toggles
    document.getElementById('btn-onboard-header')?.addEventListener('click', openVendorModal);
    document.getElementById('closeVendorModal')?.addEventListener('click', closeVendorModal);
    document.getElementById('cancelVendorModal')?.addEventListener('click', closeVendorModal);

    // Save Vendor AJAX Submit
    document.getElementById('vendorForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('csrf_token', G_CSRF_TOKEN); // ensure token is added
        
        try {
            const res = await fetch('backend/api/vendors/create.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Vendor onboarded successfully!');
                closeVendorModal();
                loadVendors();
                loadKpis();
            } else {
                alert(data.message || 'Failed to onboard vendor.');
            }
        } catch (e) {
            alert('Network error onboarding vendor.');
        }
    });

    // Start Assessment Trigger
    document.getElementById('btn-start-assessment').addEventListener('click', openAssessmentModal);
    document.getElementById('closeAssessmentModal').addEventListener('click', closeAssessmentModal);
    document.getElementById('cancelAssessmentModal').addEventListener('click', closeAssessmentModal);

    // Save Assessment AJAX Submit
    document.getElementById('assessmentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        
        try {
            const res = await fetch('api/save-assessment.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                alert(data.message || 'DPIA Assessment started successfully!');
                closeAssessmentModal();
                loadVendors();
                loadKpis();
            } else {
                alert(data.message || 'Failed to start assessment.');
            }
        } catch (e) {
            alert('Network error starting assessment.');
        }
    });

    // Download Report
    document.getElementById('btn-download-report').addEventListener('click', () => {
        window.open('backend/api/reports/vendor-risk.php', '_blank');
    });
    document.getElementById('btn-export-report-header')?.addEventListener('click', () => {
        window.open('backend/api/reports/vendor-risk.php', '_blank');
    });

    // Review Flags
    document.getElementById('btn-review-flags').addEventListener('click', openFlagsModal);
    document.getElementById('closeFlagsModal').addEventListener('click', closeFlagsModal);
    document.getElementById('closeFlagsModalBtn').addEventListener('click', closeFlagsModal);
});
