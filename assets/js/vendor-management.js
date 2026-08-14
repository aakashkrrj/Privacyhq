// governance/assets/js/vendor-management.js
// Vendor Management JS Controller

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadKpis() {
    try {
        const res = await fetch('backend/api/vendors/kpis.php');
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const d = data.data;
            const kpiTotal = document.getElementById('kpi-total');
            const kpiActive = document.getElementById('kpi-active');
            const kpiHigh = document.getElementById('kpi-high');
            const kpiPending = document.getElementById('kpi-pending');
            const kpiCritical = document.getElementById('kpi-critical');

            if (kpiTotal) kpiTotal.innerText = d.total || 0;
            if (kpiActive) kpiActive.innerText = d.active || 0;
            if (kpiHigh) kpiHigh.innerText = d.high_risk || 0;
            if (kpiPending) kpiPending.innerText = d.pending_dpa || 0;
            if (kpiCritical) kpiCritical.innerText = d.critical_risk || 0;
        }
    } catch (e) {
        console.error('Failed to load KPIs', e);
    }
}

async function loadVendors() {
    const search = document.getElementById('filter-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const risk = document.getElementById('filter-risk')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';

    const url = `backend/api/vendors/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(risk)}&status=${encodeURIComponent(status)}`;
    
    const tbody = document.getElementById('vendorTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-500"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading vendor inventory...</td></tr>';
    }

    try {
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.status === 'success' || data.success) {
            if (!tbody) return;
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-500">No matching vendor records found.</td></tr>';
            } else {
                items.forEach(v => {
                    const dpaClass = v.dpa_status === 'Signed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (v.dpa_status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-surface-container-low text-on-surface-variant border-outline-variant');
                    
                    const riskClass = v.risk_level === 'Critical' ? 'bg-red-100 text-red-800 border-red-300 font-bold' : (v.risk_level === 'High' ? 'bg-red-50 text-red-700 border-red-200' : (v.risk_level === 'Medium' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'));
                    
                    const statusClass = v.status === 'Active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (v.status === 'Inactive' ? 'bg-surface-container-low text-on-surface-variant' : 'bg-blue-50 text-blue-700 border-blue-200');

                    const contactStr = (v.contact_name || v.contact_email) ? `${escapeHtml(v.contact_name)} ${v.contact_email ? `<span class="text-xs text-gray-500 block">&lt;${escapeHtml(v.contact_email)}&gt;</span>` : ''}` : '<span class="text-gray-400">Unassigned</span>';

                    const row = `
                        <tr class="border-b border-outline-variant hover:bg-surface-container-lowest transition">
                            <td class="py-4 font-mono text-caption text-on-surface-variant">#${v.id}</td>
                            <td><strong class="text-on-surface">${escapeHtml(v.vendor_name)}</strong></td>
                            <td class="text-on-surface-variant">${escapeHtml(v.category)}</td>
                            <td>${contactStr}</td>
                            <td><span class="inline-flex px-2.5 py-0.5 rounded-full text-caption font-semibold border ${dpaClass}">${escapeHtml(v.dpa_status)}</span></td>
                            <td><span class="inline-flex px-2.5 py-0.5 rounded-full text-caption font-semibold border ${riskClass}">${escapeHtml(v.risk_level)}</span></td>
                            <td><span class="inline-flex px-2.5 py-0.5 rounded-full text-caption font-semibold border ${statusClass}">${escapeHtml(v.status)}</span></td>
                            <td class="text-right whitespace-nowrap space-x-1">
                                <button type="button" onclick="viewVendor(${v.id})" class="text-primary hover:underline font-semibold text-xs px-2">View</button>
                                <span class="text-outline">|</span>
                                <button type="button" onclick="editVendor(${v.id})" class="text-indigo-600 hover:underline font-semibold text-xs px-2">Edit</button>
                                <span class="text-outline">|</span>
                                <button type="button" onclick="deleteVendor(${v.id})" class="text-red-600 hover:underline font-semibold text-xs px-2">Delete</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            // Pagination Controls
            const totalPages = Math.ceil(total / 10);
            const controls = document.getElementById('paginationControls');
            if (controls) {
                if (totalPages > 1) {
                    controls.classList.remove('hidden');
                    const pageInfo = document.getElementById('pageInfo');
                    if (pageInfo) pageInfo.innerText = `Showing page ${currentPage} of ${totalPages} (${total} total vendors)`;
                    const btnPrev = document.getElementById('btnPrev');
                    const btnNext = document.getElementById('btnNext');
                    if (btnPrev) btnPrev.style.display = currentPage > 1 ? 'inline-block' : 'none';
                    if (btnNext) btnNext.style.display = currentPage < totalPages ? 'inline-block' : 'none';
                } else {
                    controls.classList.add('hidden');
                }
            }
        }
    } catch (e) {
        console.error('Failed to load vendors', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-600">Failed to load vendor records. Connection error.</td></tr>';
        }
    }
}

function openVendorModal() {
    const form = document.getElementById('addVendorForm');
    if (form) form.reset();
    const modal = document.getElementById('addVendorModal');
    if (modal) modal.classList.remove('hidden');
}

function closeVendorModal() {
    const modal = document.getElementById('addVendorModal');
    if (modal) modal.classList.add('hidden');
}

function openEditVendorModal(data) {
    if (!data) return;
    document.getElementById('edit_vendor_id').value = data.id || '';
    document.getElementById('edit_vendor_name').value = data.vendor_name || data.name || '';
    document.getElementById('edit_category').value = data.category || data.service_type || 'Software';
    document.getElementById('edit_contact_name').value = data.contact_name || '';
    document.getElementById('edit_contact_email').value = data.contact_email || '';
    document.getElementById('edit_dpa_status').value = data.dpa_status || 'Pending';
    document.getElementById('edit_risk_level').value = data.risk_level || 'Low';
    document.getElementById('edit_status').value = data.status || 'Active';
    document.getElementById('edit_data_shared').value = data.data_shared || '';
    document.getElementById('edit_next_assessment_date').value = data.next_assessment_date || '';
    document.getElementById('edit_contract_expiry').value = data.contract_expiry || '';
    document.getElementById('edit_notes').value = data.notes || '';

    const modal = document.getElementById('editVendorModal');
    if (modal) modal.classList.remove('hidden');
}

function closeEditVendorModal() {
    const modal = document.getElementById('editVendorModal');
    if (modal) modal.classList.add('hidden');
}

async function editVendor(id) {
    if (!id) return;
    try {
        const res = await fetch(`backend/api/vendors/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            openEditVendorModal(data.data);
        } else {
            alert('Failed to load vendor details: ' + (data.message || 'Unknown error'));
        }
    } catch (e) {
        console.error('Failed to fetch vendor details', e);
        alert('Network error loading vendor details.');
    }
}

async function viewVendor(id) {
    if (!id) return;
    const modal = document.getElementById('viewVendorModal');
    const content = document.getElementById('viewVendorContent');
    if (!modal || !content) return;

    content.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading vendor information...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/vendors/get.php?id=${id}`);
        const data = await res.json();
        if ((data.status === 'success' || data.success) && data.data) {
            const v = data.data;
            content.innerHTML = `
                <div class="space-y-4 text-body-md text-on-surface">
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <h4 class="text-title-lg font-bold text-primary">${escapeHtml(v.vendor_name)}</h4>
                            <span class="text-caption text-on-surface-variant uppercase font-semibold">${escapeHtml(v.category)}</span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-caption font-semibold border ${v.risk_level === 'Critical' ? 'bg-red-100 text-red-800' : (v.risk_level === 'High' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700')}">
                            ${escapeHtml(v.risk_level)} Risk
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-caption">
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-on-surface-variant font-semibold block">Contact Person</span>
                            <span class="font-bold text-on-surface">${escapeHtml(v.contact_name || 'N/A')}</span>
                        </div>
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-on-surface-variant font-semibold block">Contact Email</span>
                            <span class="font-bold text-on-surface font-mono">${escapeHtml(v.contact_email || 'N/A')}</span>
                        </div>
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-on-surface-variant font-semibold block">DPA Agreement</span>
                            <span class="font-bold text-on-surface">${escapeHtml(v.dpa_status)}</span>
                        </div>
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-on-surface-variant font-semibold block">Vendor Status</span>
                            <span class="font-bold text-on-surface">${escapeHtml(v.status)}</span>
                        </div>
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-on-surface-variant font-semibold block">Next Review Date</span>
                            <span class="font-mono text-on-surface">${escapeHtml(v.next_assessment_date || 'N/A')}</span>
                        </div>
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-on-surface-variant font-semibold block">Contract Expiry</span>
                            <span class="font-mono text-on-surface">${escapeHtml(v.contract_expiry || 'N/A')}</span>
                        </div>
                    </div>

                    ${v.data_shared ? `
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-caption text-on-surface-variant font-semibold block mb-1">Personal Data Shared / Processed</span>
                            <p class="text-caption text-on-surface">${escapeHtml(v.data_shared)}</p>
                        </div>
                    ` : ''}

                    ${v.notes ? `
                        <div class="bg-surface-container-low p-3 rounded-lg">
                            <span class="text-caption text-on-surface-variant font-semibold block mb-1">Vendor Audit Notes</span>
                            <p class="text-caption text-on-surface italic">${escapeHtml(v.notes)}</p>
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<div class="text-center py-6 text-red-600">Failed to load vendor details.</div>';
        }
    } catch (e) {
        console.error('Failed to load vendor details', e);
        content.innerHTML = '<div class="text-center py-6 text-red-600">Error loading vendor details.</div>';
    }
}

function closeViewVendorModal() {
    const modal = document.getElementById('viewVendorModal');
    if (modal) modal.classList.add('hidden');
}

async function deleteVendor(id) {
    if (!id) return;
    if (confirm('Are you sure you want to delete this third-party vendor record?')) {
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        const fd = new FormData();
        fd.append('vendor_id', id);
        fd.append('csrf_token', csrfToken);
        
        try {
            const res = await fetch('backend/api/vendors/delete.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success' || data.success) {
                loadVendors();
                loadKpis();
            } else {
                alert(data.message || 'Error occurred while deleting vendor.');
            }
        } catch (e) {
            console.error('Delete error', e);
            alert('Request failed. Connection error.');
        }
    }
}

function exportVendors(format = 'csv') {
    const search = document.getElementById('filter-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const risk = document.getElementById('filter-risk')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';

    const url = `backend/api/vendors/export.php?format=${format}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(risk)}&status=${encodeURIComponent(status)}`;
    window.open(url, '_blank');
}

function searchVendors() {
    currentPage = 1;
    loadVendors();
}

function filterVendors() {
    currentPage = 1;
    loadVendors();
}

function clearFilters() {
    const s = document.getElementById('filter-search');
    const c = document.getElementById('filter-category');
    const r = document.getElementById('filter-risk');
    const st = document.getElementById('filter-status');

    if (s) s.value = '';
    if (c) c.value = '';
    if (r) r.value = '';
    if (st) st.value = '';

    currentPage = 1;
    loadVendors();
}

// Global scope exports to window
window.openVendorModal = openVendorModal;
window.closeVendorModal = closeVendorModal;
window.openEditVendorModal = openEditVendorModal;
window.closeEditVendorModal = closeEditVendorModal;
window.editVendor = editVendor;
window.viewVendor = viewVendor;
window.closeViewVendorModal = closeViewVendorModal;
window.deleteVendor = deleteVendor;
window.exportVendors = exportVendors;
window.searchVendors = searchVendors;
window.filterVendors = filterVendors;
window.clearFilters = clearFilters;

document.addEventListener('DOMContentLoaded', () => {
    loadKpis();
    loadVendors();

    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadVendors();
        });
    }

    const filterCategory = document.getElementById('filter-category');
    if (filterCategory) filterCategory.addEventListener('change', () => { currentPage = 1; loadVendors(); });

    const filterRisk = document.getElementById('filter-risk');
    if (filterRisk) filterRisk.addEventListener('change', () => { currentPage = 1; loadVendors(); });

    const filterStatus = document.getElementById('filter-status');
    if (filterStatus) filterStatus.addEventListener('change', () => { currentPage = 1; loadVendors(); });

    const btnPrev = document.getElementById('btnPrev');
    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadVendors();
            }
        });
    }

    const btnNext = document.getElementById('btnNext');
    if (btnNext) {
        btnNext.addEventListener('click', () => {
            currentPage++;
            loadVendors();
        });
    }

    const addForm = document.getElementById('addVendorForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }

            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/vendors/create.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeVendorModal();
                    loadVendors();
                    loadKpis();
                    this.reset();
                } else {
                    alert('Error creating vendor: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                console.error(err);
                alert('Failed to save vendor. Connection error.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save Vendor';
                }
            }
        });
    }

    const editForm = document.getElementById('editVendorForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';
            }

            const formData = new FormData(this);
            try {
                const res = await fetch('backend/api/vendors/update.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    closeEditVendorModal();
                    loadVendors();
                    loadKpis();
                } else {
                    alert('Error updating vendor: ' + (data.message || 'Action failed'));
                }
            } catch (err) {
                console.error(err);
                alert('Failed to update vendor. Connection error.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Update Vendor';
                }
            }
        });
    }
});
