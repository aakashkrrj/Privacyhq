// assets/js/consent-management.js

let currentPage = 1;

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function showAlert(message, type = 'success') {
    const alertBox = document.getElementById('jsAlertBox');
    if (!alertBox) return;
    alertBox.textContent = message;
    alertBox.className = 'p-4 mb-4 text-sm rounded-lg border block';
    if (type === 'success') {
        alertBox.classList.add('text-green-800', 'bg-green-50', 'border-green-200');
    } else {
        alertBox.classList.add('text-red-800', 'bg-red-50', 'border-red-200');
    }
    // Auto-scroll to top to see alert
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => {
        alertBox.classList.add('hidden');
        alertBox.classList.remove('block');
    }, 5000);
}

// Phase 1: Dynamic Dashboard & Record Consent
async function loadDashboard() {
    try {
        const res = await fetch('backend/api/consent/dashboard.php');
        const data = await res.json();
        if (data.success && data.data) {
            const metrics = data.data;
            document.getElementById('kpi-total').innerText = metrics.total || 0;
            document.getElementById('kpi-granted').innerText = metrics.active_consents || 0;
            document.getElementById('kpi-revoked').innerText = metrics.revoked_consents || 0;
            document.getElementById('kpi-pending').innerText = metrics.opt_outs || 0;

            // Health KPIs
            document.getElementById('health-compliance').innerText = metrics.opt_in_rate || '0%';
            
            // Distribution percentages
            const total = parseInt(metrics.total) || 1;
            const grantedPct = Math.round((parseInt(metrics.active_consents) || 0) / total * 100);
            const revokedPct = Math.round((parseInt(metrics.revoked_consents) || 0) / total * 100);
            const pendingPct = Math.round((parseInt(metrics.opt_outs) || 0) / total * 100);

            document.getElementById('dist-granted-pct').innerText = grantedPct + '%';
            document.getElementById('dist-granted-bar').style.width = grantedPct + '%';

            document.getElementById('dist-revoked-pct').innerText = revokedPct + '%';
            document.getElementById('dist-revoked-bar').style.width = revokedPct + '%';

            document.getElementById('dist-pending-pct').innerText = pendingPct + '%';
            document.getElementById('dist-pending-bar').style.width = pendingPct + '%';
        }
    } catch (e) {
        console.error('Failed to load dashboard metrics', e);
    }
}

async function loadRecords() {
    const search = document.getElementById('filter-search').value;
    const status = document.getElementById('filter-status').value;
    const category = document.getElementById('filter-category').value;
    const date = document.getElementById('filter-date').value;

    const url = `backend/api/consent/list.php?p=${currentPage}&limit=${pageSize}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}&date=${encodeURIComponent(date)}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        const tbody = document.getElementById('consentTableBody');
        
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No consent records found.</td></tr>';
            } else {
                // Update recent events list dynamically too
                const recentList = document.getElementById('recentEventsList');
                if (recentList) recentList.innerHTML = '';

                    window.loadedConsentsMap = {};
                    items.forEach((c, idx) => {
                        window.loadedConsentsMap[c.id] = c;
                        let statusLabel = 'Granted';
                        let statusClass = 'bg-green-100 text-green-700';
                        if (c.status === 'withdrawn') {
                            statusLabel = 'Revoked';
                            statusClass = 'bg-red-100 text-red-700';
                        } else if (c.status === 'opt_out') {
                            statusLabel = 'Pending';
                            statusClass = 'bg-yellow-100 text-yellow-700';
                        } else if (c.status === 'expired') {
                            statusLabel = 'Expired';
                            statusClass = 'bg-gray-100 text-gray-700';
                        }

                        const row = `
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-6 py-4 font-medium text-gray-900">${escapeHtml(c.subject_email)}</td>
                                <td class="px-6 py-4">${escapeHtml(c.category)}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full font-medium ${statusClass}">
                                        ${statusLabel}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">${escapeHtml(c.created_at)}</td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="viewConsentHistory(${c.id})" class="text-xs text-blue-600 hover:underline mr-2">History</button>
                                    <button onclick="openModifyPreferenceModal(${c.id})" class="text-xs text-purple-600 hover:underline mr-2">Modify</button>
                                    ${c.status !== 'withdrawn' ? 
                                        `<button onclick="revokeConsent(${c.id})" class="text-xs text-red-600 hover:underline">Revoke</button>` : 
                                        `<span class="text-xs text-gray-400">Revoked</span>`}
                                </td>
                            </tr>
                        `;
                    tbody.innerHTML += row;

                    // Populate recent events with first 4 items
                    if (recentList && idx < 4) {
                        let actionText = `${escapeHtml(c.subject_email)} granted ${escapeHtml(c.category)}`;
                        if (c.status === 'withdrawn') {
                            actionText = `${escapeHtml(c.subject_email)} revoked ${escapeHtml(c.category)}`;
                        } else if (c.status === 'opt_out') {
                            actionText = `${escapeHtml(c.subject_email)} pending ${escapeHtml(c.category)}`;
                        } else if (c.status === 'expired') {
                            actionText = `${escapeHtml(c.subject_email)} expired ${escapeHtml(c.category)}`;
                        }

                        const date = new Date(c.created_at);
                        const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                        recentList.innerHTML += `
                            <div class="flex justify-between items-center border-b pb-3">
                                <div>
                                    <p class="font-medium text-gray-700">${actionText}</p>
                                    <p class="text-xs text-gray-500">${escapeHtml(c.created_at)}</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs ${statusClass}">${statusLabel}</span>
                            </div>
                        `;
                    }
                });
            }

            // Pagination
            const totalPages = Math.ceil(total / pageSize) || 1;
            const startItem = total > 0 ? (currentPage - 1) * pageSize + 1 : 0;
            const endItem = Math.min(currentPage * pageSize, total);
            const paginationDiv = document.getElementById('consentPagination');

            if (paginationDiv) {
                let pageButtons = '';
                const maxButtons = 5;
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + maxButtons - 1);
                if (endPage - startPage < maxButtons - 1) {
                    startPage = Math.max(1, endPage - maxButtons + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === currentPage ? 'bg-blue-600 text-white font-bold' : 'bg-white text-gray-700 hover:bg-gray-50';
                    pageButtons += `<button onclick="changePage(${i})" class="px-2.5 py-1 text-xs border rounded ${activeClass}">${i}</button>`;
                }

                paginationDiv.innerHTML = `
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>Showing <b>${startItem}</b> - <b>${endItem}</b> of <b>${total}</b> records</span>
                        <div class="flex items-center gap-1">
                            <span>Per page:</span>
                            <select onchange="changePageSize(this.value)" class="border rounded px-1.5 py-0.5 text-xs bg-white focus:outline-none">
                                <option value="10" ${pageSize === 10 ? 'selected' : ''}>10</option>
                                <option value="25" ${pageSize === 25 ? 'selected' : ''}>25</option>
                                <option value="50" ${pageSize === 50 ? 'selected' : ''}>50</option>
                                <option value="100" ${pageSize === 100 ? 'selected' : ''}>100</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''} class="px-2.5 py-1 text-xs border rounded bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50">Prev</button>
                        ${pageButtons}
                        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''} class="px-2.5 py-1 text-xs border rounded bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50">Next</button>
                    </div>
                `;
            }
        }
    } catch (e) {
        console.error('Failed to load records', e);
    }
}

let pageSize = 10;

function changePageSize(newSize) {
    pageSize = parseInt(newSize) || 10;
    currentPage = 1;
    loadRecords();
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadRecords();
}

async function revokeConsent(id) {
    if (!confirm("Are you sure you want to revoke this consent record?")) return;
    const fd = new FormData();
    fd.append('revoke_id', id);
    fd.append('csrf_token', G_CSRF_TOKEN);
    try {
        const res = await fetch('backend/api/consent/revoke.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAlert(data.message || 'Consent revoked successfully.');
            loadRecords();
            loadDashboard();
        } else {
            showAlert(data.message || 'Failed to revoke consent.', 'error');
        }
    } catch (e) {
        showAlert("Network error revoking consent.", 'error');
    }
}

// Modal Toggle Helpers
function openRecordConsentModal() {
    document.getElementById('recordConsentModal').classList.remove('hidden');
}

function closeRecordConsentModal() {
    document.getElementById('recordConsentModal').classList.add('hidden');
    document.getElementById('recordConsentForm').reset();
}

// Event Listeners Setup
document.addEventListener('DOMContentLoaded', () => {
    // Load initial data
    loadDashboard();
    loadRecords();

    // Search action
    document.getElementById('btn-search').addEventListener('click', () => {
        currentPage = 1;
        loadRecords();
    });

    // Quick Action button triggers
    document.getElementById('btn-record-consent').addEventListener('click', openRecordConsentModal);
    document.getElementById('closeConsentModalBtn').addEventListener('click', closeRecordConsentModal);
    document.getElementById('cancelConsentModalBtn').addEventListener('click', closeRecordConsentModal);

    // Save Consent form submit
    document.getElementById('recordConsentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        try {
            const res = await fetch('backend/api/consent/create.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showAlert(data.message || 'Consent recorded successfully.');
                closeRecordConsentModal();
                loadRecords();
                loadDashboard();
            } else {
                alert(data.message || 'Failed to record consent.');
            }
        } catch (e) {
            alert('Network error recording consent.');
        }
    });

    // Phase 2: Export Log (CSV & Excel)
    document.getElementById('btn-export-log').addEventListener('click', () => {
        const search = document.getElementById('filter-search').value;
        const status = document.getElementById('filter-status').value;
        const category = document.getElementById('filter-category').value;
        const date = document.getElementById('filter-date').value;
        const url = `backend/api/consent/export.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}&date=${encodeURIComponent(date)}`;
        window.location.href = url;
    });

    const btnExcel = document.getElementById('btn-export-excel');
    if (btnExcel) {
        btnExcel.addEventListener('click', () => {
            const search = document.getElementById('filter-search').value;
            const status = document.getElementById('filter-status').value;
            const category = document.getElementById('filter-category').value;
            const date = document.getElementById('filter-date').value;
            const url = `backend/api/consent/export-excel.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&category=${encodeURIComponent(category)}&date=${encodeURIComponent(date)}`;
            window.location.href = url;
        });
    }

    // Phase 3: Generate Report
    document.getElementById('btn-generate-report').addEventListener('click', () => {
        window.open('backend/api/consent/report.php', '_blank');
    });

    // Phase 4: Import CSV
    document.getElementById('btn-import-csv').addEventListener('click', () => {
        document.getElementById('importCsvModal').classList.remove('hidden');
    });
    document.getElementById('closeImportModalBtn').addEventListener('click', () => {
        document.getElementById('importCsvModal').classList.add('hidden');
        document.getElementById('importCsvForm').reset();
        document.getElementById('importResults').classList.add('hidden');
    });
    document.getElementById('cancelImportModalBtn').addEventListener('click', () => {
        document.getElementById('importCsvModal').classList.add('hidden');
        document.getElementById('importCsvForm').reset();
        document.getElementById('importResults').classList.add('hidden');
    });

    document.getElementById('importCsvForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const resultsDiv = document.getElementById('importResults');
        resultsDiv.innerHTML = 'Uploading and processing file...';
        resultsDiv.className = 'max-h-40 overflow-y-auto p-3 text-xs bg-gray-50 border rounded-lg space-y-1 block';

        try {
            const res = await fetch('backend/api/consent/import.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                let reportHtml = `<p class="font-bold text-green-700">Success: ${data.data.success_count} imported successfully.</p>`;
                if (data.data.errors && data.data.errors.length > 0) {
                    reportHtml += `<p class="font-bold text-amber-700 mt-1">Warnings / Skip Reasons (${data.data.errors.length}):</p>`;
                    data.data.errors.forEach(err => {
                        reportHtml += `<p class="text-red-600">${escapeHtml(err)}</p>`;
                    });
                }
                resultsDiv.innerHTML = reportHtml;
                loadRecords();
                loadDashboard();
            } else {
                resultsDiv.innerHTML = `<p class="font-bold text-red-700">Error: ${escapeHtml(data.message)}</p>`;
            }
        } catch (e) {
            resultsDiv.innerHTML = '<p class="font-bold text-red-700">Network error processing import.</p>';
        }
    });

    // History Modal Event Listeners
    document.getElementById('closeHistoryModalBtn').addEventListener('click', () => {
        document.getElementById('consentHistoryModal').classList.add('hidden');
    });
    document.getElementById('cancelHistoryModalBtn').addEventListener('click', () => {
        document.getElementById('consentHistoryModal').classList.add('hidden');
    });

    // Modify Preference Modal Event Listeners
    document.getElementById('closeModifyModalBtn').addEventListener('click', closeModifyPreferenceModal);
    document.getElementById('cancelModifyModalBtn').addEventListener('click', closeModifyPreferenceModal);

    document.getElementById('modifyPreferenceForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const consentId = document.getElementById('modify_consent_id').value;
        const reason = document.getElementById('modify_reason').value.trim();

        if (!reason) {
            alert('Please provide a reason for modifying this consent preference.');
            return;
        }

        try {
            const res = await fetch('backend/api/consent/update.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                closeModifyPreferenceModal();
                showAlert(data.message || 'Consent preference updated successfully.');
                
                try {
                    await loadRecords();
                } catch (err) {
                    console.error("Error refreshing records:", err);
                }

                try {
                    await loadDashboard();
                } catch (err) {
                    console.error("Error refreshing dashboard:", err);
                }

                try {
                    const historyModal = document.getElementById('consentHistoryModal');
                    if (historyModal && !historyModal.classList.contains('hidden')) {
                        viewConsentHistory(consentId);
                    }
                } catch (err) {
                    console.error("Error refreshing history timeline:", err);
                }
            } else {
                alert(data.message || 'Failed to update consent preference.');
            }
        } catch (e) {
            console.error('Network error updating consent preference:', e);
            alert('Network error updating consent preference.');
        }
    });
});

function openModifyPreferenceModal(consentId) {
    const record = window.loadedConsentsMap[consentId] || {};
    document.getElementById('modify_consent_id').value = consentId;
    document.getElementById('modifyModalSubtitle').innerText = `Updating consent for ${record.subject_email || 'User'} (${record.category || 'Consent'})`;
    
    let currStatus = 'Granted';
    if (record.status === 'withdrawn') currStatus = 'Revoked';
    if (record.status === 'opt_out') currStatus = 'Pending';
    if (record.status === 'expired') currStatus = 'Expired';
    document.getElementById('modify_status').value = currStatus;
    document.getElementById('modify_reason').value = '';

    document.getElementById('modifyPreferenceModal').classList.remove('hidden');
}

function closeModifyPreferenceModal() {
    document.getElementById('modifyPreferenceModal').classList.add('hidden');
    document.getElementById('modifyPreferenceForm').reset();
}

window.loadedConsentsMap = window.loadedConsentsMap || {};

async function viewConsentHistory(consentId) {
    const modal = document.getElementById('consentHistoryModal');
    const subtitle = document.getElementById('historyModalSubtitle');
    const container = document.getElementById('historyTimelineContainer');
    
    const record = window.loadedConsentsMap[consentId] || {};
    const email = record.subject_email || 'User';
    const category = record.category || 'Consent';

    subtitle.innerText = `Audit history for ${email} (${category})`;
    container.innerHTML = '<p class="text-xs text-gray-400 p-4 text-center">Loading audit history...</p>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`backend/api/consent/history.php?id=${consentId}`);
        const data = await res.json();
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = '';
            data.data.forEach((item, index) => {
                let prevStatus = item.previous_status || 'Initial';
                let newStatus = item.new_status || 'Granted';
                let reason = item.reason || 'No details provided';
                let changedBy = item.user_full_name || item.changed_by || 'System / Self';
                let dateStr = item.created_at || item.changed_at || item.timestamp || 'N/A';

                const entry = `
                    <div class="relative pl-6 pb-4 border-l-2 border-blue-200 last:border-l-0 last:pb-0">
                        <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-blue-600 border-2 border-white"></div>
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-xs font-semibold text-gray-800">
                                    Status: <span class="text-gray-500">${escapeHtml(prevStatus)}</span> &rarr; <span class="text-blue-700 font-bold">${escapeHtml(newStatus)}</span>
                                </span>
                                <span class="text-[10px] text-gray-400">${escapeHtml(dateStr)}</span>
                            </div>
                            <p class="text-xs text-gray-600 mb-1"><strong>Reason:</strong> ${escapeHtml(reason)}</p>
                            <p class="text-[11px] text-gray-400"><strong>Changed By:</strong> ${escapeHtml(changedBy)}</p>
                        </div>
                    </div>
                `;
                container.innerHTML += entry;
            });
        } else {
            if (!data.success && data.message) {
                console.error("History API returned error:", data.message);
            }
            container.innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">No consent history available.</p>';
        }
    } catch (e) {
        console.error("Error fetching consent history:", e);
        container.innerHTML = '<p class="text-sm text-red-500 py-4 text-center">Error loading consent history.</p>';
    }
}
