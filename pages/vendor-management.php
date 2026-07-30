<?php
// pages/vendor-management.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold text-on-surface">Vendor Risk Management</h2>
            <p class="text-on-surface-variant mt-1">Manage and assess third-party vendor risks</p>
        </div>
    </div>

    <!-- Add Vendor Form -->
    <div class="bg-surface-container-lowest rounded-xl border border-[#EDEBE9] p-6 shadow-sm mb-6">
        <h5 class="fw-bold mb-3">+ Add New Vendor</h5>
        <form id="addVendorForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Vendor Name</label>
                    <input type="text" name="vendor_name" class="form-control" placeholder="e.g., AWS, Salesforce..." required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Service Category</label>
                    <select name="category" class="form-select" required>
                        <option value="Cloud Storage">Cloud Storage</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Analytics">Analytics</option>
                        <option value="HR / Payroll">HR / Payroll</option>
                        <option value="Software">Software</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">DPA Status</label>
                    <select name="dpa_status" class="form-select">
                        <option value="Pending">Pending Signature</option>
                        <option value="Signed">Signed / Executed</option>
                        <option value="Not Required">Not Required</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Inherent Risk Level</label>
                    <select name="risk_level" class="form-select">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Data Shared / Processed</label>
                    <textarea name="data_shared" class="form-control" rows="2" placeholder="e.g., Customer email, payment tokens..."></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary fw-semibold">Save Vendor</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Vendor Table -->
    <div class="card p-4 border-0 shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-surface-container-lowest rounded-xl border p-5">
                <h4 class="text-sm text-on-surface-variant">Total Vendors</h4>
                <p class="text-3xl font-bold" id="kpi-total">...</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border p-5">
                <h4 class="text-sm text-on-surface-variant">High Risk</h4>
                <p class="text-3xl font-bold text-red-600" id="kpi-high">...</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border p-5">
                <h4 class="text-sm text-on-surface-variant">Pending DPA</h4>
                <p class="text-3xl font-bold text-yellow-600" id="kpi-pending">...</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border p-5">
                <h4 class="text-sm text-on-surface-variant">Critical</h4>
                <p class="text-3xl font-bold text-red-700" id="kpi-critical">...</p>
            </div>
        </div>

        <h5 class="text-2xl font-bold text-on-surface mb-4">Vendor Inventory</h5>
        
        <!-- Search and Filters -->
        <form id="searchForm">
            <div class="flex gap-4 mb-5 flex-wrap">
                <input type="text" id="filter-search" class="border rounded-xl px-4 py-3 flex-1" placeholder="Search Vendor by name, type, or status...">
                <select id="filter-category" class="border rounded-xl px-4 py-3">
                    <option value="">All Categories</option>
                    <option value="Cloud Storage">Cloud Storage</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Analytics">Analytics</option>
                    <option value="HR / Payroll">HR / Payroll</option>
                    <option value="Software">Software</option>
                    <option value="Other">Other</option>
                </select>
                <select id="filter-risk" class="border rounded-xl px-4 py-3">
                    <option value="">All Risks</option>
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
                <button type="submit" class="btn btn-secondary px-4">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="w-full border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th>Vendor Name</th>
                        <th>Category</th>
                        <th>DPA Status</th>
                        <th>Risk Level</th>
                        <th>Data Shared</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="vendorTableBody">
                    <tr><td colspan="6" class="text-center py-8 text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="flex justify-between items-center mt-4 border-t pt-4 hidden">
            <span class="text-sm text-gray-600" id="pageInfo"></span>
            <div class="flex gap-2">
                <button id="btnPrev" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Previous</button>
                <button id="btnNext" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Vendor Modal -->
<div id="editVendorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h5 class="fw-bold mb-3">Edit Vendor</h5>
        <form id="editVendorForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="vendor_id" id="edit_vendor_id">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Vendor Name</label>
                    <input type="text" name="vendor_name" id="edit_vendor_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Service Category</label>
                    <select name="category" id="edit_category" class="form-select" required>
                        <option value="Cloud Storage">Cloud Storage</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Analytics">Analytics</option>
                        <option value="HR / Payroll">HR / Payroll</option>
                        <option value="Software">Software</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">DPA Status</label>
                    <select name="dpa_status" id="edit_dpa_status" class="form-select">
                        <option value="Pending">Pending Signature</option>
                        <option value="Signed">Signed / Executed</option>
                        <option value="Not Required">Not Required</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Inherent Risk Level</label>
                    <select name="risk_level" id="edit_risk_level" class="form-select">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div class="col-12 flex gap-3 justify-end mt-4">
                    <button type="button" onclick="closeEditModal()" class="btn btn-light fw-semibold border">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Update Vendor</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
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
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadKpis() {
    try {
        const res = await fetch('backend/api/vendors/kpis.php');
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            document.getElementById('kpi-total').innerText = data.data.total || 0;
            document.getElementById('kpi-high').innerText = data.data.high_risk || 0;
            document.getElementById('kpi-pending').innerText = data.data.pending_dpa || 0;
            document.getElementById('kpi-critical').innerText = data.data.critical_risk || 0;
        }
    } catch (e) {
        console.error('Failed to load KPIs', e);
    }
}

async function loadVendors() {
    const search = document.getElementById('filter-search').value;
    const category = document.getElementById('filter-category').value;
    const risk = document.getElementById('filter-risk').value;

    const url = `backend/api/vendors/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&risk_level=${encodeURIComponent(risk)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('vendorTableBody');
        
        if (data.status === 'success') {
            tbody.innerHTML = '';
            const items = data.data.items;
            const total = data.data.total;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No vendors found.</td></tr>';
            } else {
                items.forEach(v => {
                    const dpaClass = v.dpa_status === 'Signed' ? 'bg-green-100 text-green-700' : (v.dpa_status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700');
                    const riskClass = v.risk_level === 'Critical' ? 'bg-red-100 text-red-700' : (v.risk_level === 'High' ? 'bg-red-100 text-red-700' : (v.risk_level === 'Medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'));
                    
                    const row = `
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="py-4"><strong>${escapeHtml(v.vendor_name)}</strong></td>
                            <td>${escapeHtml(v.category)}</td>
                            <td><span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold ${dpaClass}">${escapeHtml(v.dpa_status)}</span></td>
                            <td><span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold ${riskClass}">${escapeHtml(v.risk_level)}</span></td>
                            <td class="text-gray-600">${escapeHtml(v.data_shared)}</td>
                            <td class="text-right">
                                <button type="button" onclick="editVendor(${v.id}, '${escapeHtml(v.vendor_name)}', '${escapeHtml(v.category)}', '${escapeHtml(v.dpa_status)}', '${escapeHtml(v.risk_level)}')" class="text-blue-600 hover:text-blue-800 font-semibold text-sm px-2">Edit</button>
                                <button type="button" onclick="deleteVendor(${v.id})" class="text-red-600 hover:text-red-800 font-semibold text-sm px-2">Delete</button>
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
        console.error('Failed to load vendors', e);
    }
}

document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    currentPage = 1;
    loadVendors();
});

document.getElementById('filter-category').addEventListener('change', () => { currentPage = 1; loadVendors(); });
document.getElementById('filter-risk').addEventListener('change', () => { currentPage = 1; loadVendors(); });

document.getElementById('btnPrev').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        loadVendors();
    }
});

document.getElementById('btnNext').addEventListener('click', () => {
    currentPage++;
    loadVendors();
});

async function submitApi(formId, endpoint) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
            loadVendors();
            loadKpis();
            if (formId === 'addVendorForm') form.reset();
            if (formId === 'editVendorForm') closeEditModal();
        } else {
            alert(data.message || 'Error occurred');
        }
    } catch (e) {
        alert('Request failed');
    }
}

document.getElementById('addVendorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitApi('addVendorForm', 'backend/api/vendors/create.php');
});

document.getElementById('editVendorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitApi('editVendorForm', 'backend/api/vendors/update.php');
});

function editVendor(id, name, category, dpa, risk) {
    document.getElementById('edit_vendor_id').value = id;
    document.getElementById('edit_vendor_name').value = name;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_dpa_status').value = (dpa === 'Signed') ? 'Signed' : 'Pending';
    document.getElementById('edit_risk_level').value = risk;
    document.getElementById('editVendorModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editVendorModal').classList.add('hidden');
}

async function deleteVendor(id) {
    if (confirm('Are you sure you want to delete this vendor?')) {
        const fd = new FormData();
        fd.append('vendor_id', id);
        fd.append('csrf_token', '<?= $csrfToken ?>');
        
        try {
            const res = await fetch('backend/api/vendors/delete.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                loadVendors();
                loadKpis();
            } else {
                alert(data.message || 'Error occurred');
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadKpis();
    loadVendors();
});
</script>