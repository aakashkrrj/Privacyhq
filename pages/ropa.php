<?php
// governance/pages/ropa.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROPA Management - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 pb-20">

<div class="max-w-7xl mx-auto p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                Article 30: ROPA
            </h1>
            <p class="text-sm text-gray-500 mt-1">Maintain up-to-date documentation of personal data processing operations.</p>
        </div>
        <button onclick="openRopaModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
            + Add New Activity
        </button>
    </div>

    <!-- KPI Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-blue-50">
            <p class="text-sm text-gray-500">Total Processing Activities</p>
            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="kpi-total">...</h2>
            <p class="text-xs text-gray-500 mt-1">Registered activities</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-green-50">
            <p class="text-sm text-gray-500">Active Activities</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2" id="kpi-active">...</h2>
            <p class="text-xs text-gray-500 mt-1">Currently monitored</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-orange-50">
            <p class="text-sm text-gray-500">Inactive Activities</p>
            <h2 class="text-3xl font-bold text-orange-600 mt-2" id="kpi-inactive">...</h2>
            <p class="text-xs text-gray-500 mt-1">Archived/Stopped</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-gray-50">
            <p class="text-sm text-gray-500">New This Month</p>
            <h2 class="text-3xl font-bold text-gray-700 mt-2" id="kpi-new-month">...</h2>
            <p class="text-xs text-gray-500 mt-1">Added recently</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Search & Filter Activities</h2>
        <form id="searchForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" id="filter-search" placeholder="Search Activity or Purpose..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-6 py-2 text-sm font-medium transition">
                Search Records
            </button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Records of Processing Activities</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">Activity Name</th>
                        <th class="p-4">Data Controller</th>
                        <th class="p-4">Department</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Retention</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="ropaTableBody">
                    <tr><td colspan="6" class="text-center py-8 text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="flex justify-between items-center p-4 border-t hidden">
            <span class="text-sm text-gray-600" id="pageInfo"></span>
            <div class="flex gap-2">
                <button id="btnPrev" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Previous</button>
                <button id="btnNext" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit ROPA -->
<div id="ropaModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-2xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeRopaModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4" id="modalTitle">Add New Activity</h3>
        
        <form id="ropaForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="ropa_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activity Name</label>
                    <input type="text" name="activity_name" id="ropa_activity_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <input type="text" name="department" id="ropa_department" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose of Processing</label>
                <textarea name="purpose" id="ropa_purpose" rows="2" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Controller</label>
                    <input type="text" name="data_controller" id="ropa_data_controller" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Categories</label>
                    <input type="text" name="data_categories" id="ropa_data_categories" placeholder="e.g. Names, Emails, Financial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Subjects</label>
                    <input type="text" name="data_subjects" id="ropa_data_subjects" placeholder="e.g. Customers, Employees" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipients</label>
                    <input type="text" name="recipients" id="ropa_recipients" placeholder="e.g. Third-party vendors" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Retention Period</label>
                    <input type="text" name="retention_period" id="ropa_retention_period" placeholder="e.g. 7 years" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                
                <div id="statusGroup" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="ropa_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="pt-4 flex justify-end gap-3 border-t mt-4">
                <button type="button" onclick="closeRopaModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;
let currentEndpoint = 'create.php';

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadDashboard() {
    try {
        const res = await fetch('backend/api/ropa/dashboard.php');
        const data = await res.json();
        if (data.success && data.data) {
            document.getElementById('kpi-total').innerText = data.data.total_activities;
            document.getElementById('kpi-active').innerText = data.data.active_activities;
            document.getElementById('kpi-inactive').innerText = data.data.inactive_activities;
            document.getElementById('kpi-new-month').innerText = data.data.new_this_month;
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics', e);
    }
}

async function loadRecords() {
    const search = document.getElementById('filter-search').value;
    const status = document.getElementById('filter-status').value;

    const url = `backend/api/ropa/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('ropaTableBody');
        
        if (data.success) {
            tbody.innerHTML = '';
            const items = data.data.items;
            const total = data.data.total;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No records found.</td></tr>';
            } else {
                items.forEach(i => {
                    let statusClass = 'bg-gray-100 text-gray-800';
                    if (i.status === 'active') statusClass = 'bg-green-100 text-green-800';
                    else if (i.status === 'inactive') statusClass = 'bg-orange-100 text-orange-800';

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-4 font-medium text-gray-900">
                                ${escapeHtml(i.activity_name)}
                                <div class="text-xs text-gray-500 truncate max-w-xs" title="${escapeHtml(i.purpose)}">${escapeHtml(i.purpose)}</div>
                            </td>
                            <td class="p-4 text-gray-600">${escapeHtml(i.data_controller)}</td>
                            <td class="p-4 text-gray-600">${escapeHtml(i.department)}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                                    ${escapeHtml(i.status)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600 text-sm">${escapeHtml(i.retention_period)}</td>
                            <td class="p-4 text-right">
                                <button onclick="editRopa(${i.id})" class="text-blue-600 hover:text-blue-900 font-medium text-sm mr-3">Edit</button>
                                <button onclick="deleteRopa(${i.id})" class="text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
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
        console.error('Failed to load records', e);
    }
}

document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    currentPage = 1;
    loadRecords();
});

document.getElementById('btnPrev').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        loadRecords();
    }
});

document.getElementById('btnNext').addEventListener('click', () => {
    currentPage++;
    loadRecords();
});

async function submitApi(formId, endpoint, modalCallback) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            loadRecords();
            loadDashboard();
            form.reset();
            modalCallback();
        } else {
            alert(data.message || 'Error occurred');
        }
    } catch (e) {
        alert('Request failed');
    }
}

document.getElementById('ropaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitApi('ropaForm', `backend/api/ropa/${currentEndpoint}`, closeRopaModal);
});

async function editRopa(id) {
    try {
        const res = await fetch(`backend/api/ropa/details.php?id=${id}`);
        const data = await res.json();
        if (data.success) {
            const i = data.data;
            document.getElementById('ropa_id').value = i.id;
            document.getElementById('ropa_activity_name').value = i.activity_name;
            document.getElementById('ropa_purpose').value = i.purpose;
            document.getElementById('ropa_department').value = i.department || '';
            document.getElementById('ropa_data_controller').value = i.data_controller || '';
            document.getElementById('ropa_data_categories').value = i.data_categories || '';
            document.getElementById('ropa_data_subjects').value = i.data_subjects || '';
            document.getElementById('ropa_recipients').value = i.recipients || '';
            document.getElementById('ropa_retention_period').value = i.retention_period || '';
            document.getElementById('ropa_status').value = i.status || 'active';
            
            document.getElementById('statusGroup').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Edit Activity';
            currentEndpoint = 'update.php';
            
            document.getElementById('ropaModal').classList.remove('hidden');
        }
    } catch (e) {
        alert('Failed to load record details');
    }
}

async function deleteRopa(id) {
    if (confirm("Are you sure you want to delete this ROPA record?")) {
        const formData = new FormData();
        formData.append('csrf_token', '<?= $csrfToken ?>');
        formData.append('id', id);
        
        try {
            const res = await fetch('backend/api/ropa/delete.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                loadRecords();
                loadDashboard();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert('Request failed');
        }
    }
}

function openRopaModal() {
    document.getElementById('ropaForm').reset();
    document.getElementById('ropa_id').value = '';
    document.getElementById('statusGroup').classList.add('hidden');
    document.getElementById('modalTitle').innerText = 'Add New Activity';
    currentEndpoint = 'create.php';
    document.getElementById('ropaModal').classList.remove('hidden');
}

function closeRopaModal() {
    document.getElementById('ropaModal').classList.add('hidden');
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRecords();
});
</script>
</body>
</html>