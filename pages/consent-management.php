<?php
// pages/consent-management.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="space-y-6 max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                Consent Management
            </h1>
            <p class="text-sm text-gray-500 mt-1">Capture, audit, and revoke user consent preferences across digital properties.</p>
        </div>
        <button onclick="openConsentModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            + Log New Consent
        </button>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Total Consents</p>
            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="kpi-total">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Active / Granted</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2" id="kpi-active">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Revoked</p>
            <h2 class="text-3xl font-bold text-red-600 mt-2" id="kpi-revoked">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Opt-In Rate</p>
            <h2 class="text-3xl font-bold text-emerald-500 mt-2" id="kpi-optin">...</h2>
        </div>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Search & Filter Consents</h2>
        <form id="searchForm">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="text" id="filter-search" placeholder="Search Identifier / Email..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <select id="filter-category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">All Categories</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Analytics">Analytics</option>
                    <option value="Essential">Essential</option>
                    <option value="Third-Party Sharing">Third-Party Sharing</option>
                </select>
                <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">All Statuses</option>
                    <option value="opt_in">Granted</option>
                    <option value="opt_out">Pending</option>
                    <option value="withdrawn">Revoked</option>
                </select>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition col-span-1 md:col-span-2">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- CONSENTS TABLE -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Consent Ledger</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">User Identifier</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Source</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Captured At</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="consentTableBody">
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

<!-- Modal: Log New Consent -->
<div id="consentModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <button onclick="closeConsentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Log Manual Consent</h3>
        
        <form id="addConsentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User Identifier (Email / ID)</label>
                <input type="text" name="user_identifier" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Consent Category</label>
                <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="Marketing">Marketing</option>
                    <option value="Analytics">Analytics</option>
                    <option value="Essential">Essential</option>
                    <option value="Third-Party Sharing">Third-Party Sharing</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="Granted">Granted</option>
                    <option value="Pending">Pending / Opt-Out</option>
                    <option value="Revoked">Revoked</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeConsentModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Consent</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Revoke Consent -->
<div id="revokeModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button onclick="closeRevokeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Revoke Consent</h3>
        <p class="text-sm text-gray-500 mb-4">Are you sure you want to withdraw this consent? This action is logged.</p>
        
        <form id="revokeConsentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="revoke_id" id="revoke_consent_id">
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeRevokeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Revoke Consent</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function loadDashboard() {
    try {
        const res = await fetch('backend/api/consent/dashboard.php');
        const data = await res.json();
        if (data.status === 'success' && data.data) {
            document.getElementById('kpi-total').innerText = data.data.total;
            document.getElementById('kpi-active').innerText = data.data.active_consents;
            document.getElementById('kpi-revoked').innerText = data.data.revoked_consents;
            document.getElementById('kpi-optin').innerText = data.data.opt_in_rate;
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics', e);
    }
}

async function loadConsents() {
    const search = document.getElementById('filter-search').value;
    const category = document.getElementById('filter-category').value;
    const status = document.getElementById('filter-status').value;

    const url = `backend/api/consent/list.php?p=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('consentTableBody');
        
        if (data.status === 'success') {
            tbody.innerHTML = '';
            const items = data.data.items;
            const total = data.data.total;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No consents found.</td></tr>';
            } else {
                items.forEach(c => {
                    let statusLabel = 'Granted';
                    let statusClass = 'bg-green-100 text-green-800';
                    if (c.status === 'withdrawn') {
                        statusLabel = 'Revoked';
                        statusClass = 'bg-red-100 text-red-800';
                    } else if (c.status === 'opt_out') {
                        statusLabel = 'Pending';
                        statusClass = 'bg-yellow-100 text-yellow-800';
                    }

                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-4 font-medium text-gray-900">${escapeHtml(c.subject_email)}</td>
                            <td class="p-4 text-gray-600">${escapeHtml(c.category)}</td>
                            <td class="p-4 text-gray-500 text-sm">${escapeHtml(c.source)}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                                    ${escapeHtml(statusLabel)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-500 text-sm">${escapeHtml(c.created_at)}</td>
                            <td class="p-4 text-right">
                                ${c.status !== 'withdrawn' ? `<button onclick="openRevokeModal(${c.id})" class="text-red-600 hover:text-red-900 font-medium text-sm">Revoke</button>` : `<span class="text-gray-400 text-sm">Revoked</span>`}
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
        console.error('Failed to load consents', e);
    }
}

document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    currentPage = 1;
    loadConsents();
});

document.getElementById('btnPrev').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        loadConsents();
    }
});

document.getElementById('btnNext').addEventListener('click', () => {
    currentPage++;
    loadConsents();
});

async function submitApi(formId, endpoint, modalCallback) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    try {
        const res = await fetch(endpoint, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
            loadConsents();
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

document.getElementById('addConsentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitApi('addConsentForm', 'backend/api/consent/create.php', closeConsentModal);
});

document.getElementById('revokeConsentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitApi('revokeConsentForm', 'backend/api/consent/revoke.php', closeRevokeModal);
});

function openConsentModal() {
    document.getElementById('consentModal').classList.remove('hidden');
}

function closeConsentModal() {
    document.getElementById('consentModal').classList.add('hidden');
}

function openRevokeModal(id) {
    document.getElementById('revoke_consent_id').value = id;
    document.getElementById('revokeModal').classList.remove('hidden');
}

function closeRevokeModal() {
    document.getElementById('revokeModal').classList.add('hidden');
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadConsents();
});
</script>