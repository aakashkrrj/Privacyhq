// assets/js/ropa.js

let currentPage = 1;
let currentEndpoint = 'create.php';

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
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
            const items = data.data.items || [];
            const total = data.data.total || 0;
            
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
                            <td class="p-4 text-gray-600">${escapeHtml(i.data_controller || 'N/A')}</td>
                            <td class="p-4 text-gray-600">${escapeHtml(i.department || 'N/A')}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusClass}">
                                    ${escapeHtml(i.status)}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600 text-sm">${escapeHtml(i.retention_period || 'N/A')}</td>
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
        formData.append('csrf_token', G_CSRF_TOKEN);
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

// Review Activities logic
async function loadIncompleteActivities() {
    try {
        const res = await fetch('backend/api/ropa/review.php');
        const data = await res.json();
        const tbody = document.getElementById('incompleteTableBody');
        
        if (data.success && data.data) {
            tbody.innerHTML = '';
            const items = data.data;
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-gray-500">All processing activities are fully documented!</td></tr>';
            } else {
                items.forEach(i => {
                    let missing = [];
                    if (!i.data_controller || !i.data_controller.trim()) missing.push("Controller");
                    if (!i.data_categories || !i.data_categories.trim()) missing.push("Categories");
                    if (!i.data_subjects || !i.data_subjects.trim()) missing.push("Subjects");
                    if (!i.recipients || !i.recipients.trim()) missing.push("Recipients");
                    if (!i.retention_period || !i.retention_period.trim()) missing.push("Retention");
                    
                    const row = `
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="p-3 font-medium text-gray-900">${escapeHtml(i.activity_name)}</td>
                            <td class="p-3 text-red-600 text-xs font-semibold">${escapeHtml(missing.join(', '))}</td>
                            <td class="p-3 text-gray-600">${escapeHtml(i.department || 'N/A')}</td>
                            <td class="p-3 text-right">
                                <button onclick="triggerEditFromReview(${i.id})" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Edit</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    } catch (e) {
        console.error('Failed to load incomplete activities', e);
    }
}

function triggerEditFromReview(id) {
    closeReviewActivitiesModal();
    editRopa(id);
}

function openReviewActivitiesModal() {
    loadIncompleteActivities();
    document.getElementById('reviewActivitiesModal').classList.remove('hidden');
}

function closeReviewActivitiesModal() {
    document.getElementById('reviewActivitiesModal').classList.add('hidden');
}

// Bind events globally so inline HTML onclicks work
window.editRopa = editRopa;
window.deleteRopa = deleteRopa;
window.openRopaModal = openRopaModal;
window.closeRopaModal = closeRopaModal;
window.triggerEditFromReview = triggerEditFromReview;

// Bind DOM event listeners on load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadRecords();

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

    document.getElementById('ropaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitApi('ropaForm', `backend/api/ropa/${currentEndpoint}`, closeRopaModal);
    });

    // Quick Actions
    document.getElementById('btn-add-activity-qa').addEventListener('click', openRopaModal);
    
    document.getElementById('btn-export-records-qa').addEventListener('click', () => {
        const search = document.getElementById('filter-search').value;
        const status = document.getElementById('filter-status').value;
        window.open(`backend/api/ropa/export.php?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`, '_blank');
    });

    document.getElementById('btn-generate-report-qa').addEventListener('click', () => {
        window.open('backend/api/reports/ropa.php', '_blank');
    });

    document.getElementById('btn-review-activities-qa').addEventListener('click', openReviewActivitiesModal);
    document.getElementById('closeReviewActivitiesModal').addEventListener('click', closeReviewActivitiesModal);
    document.getElementById('btnCloseReviewActivitiesModal').addEventListener('click', closeReviewActivitiesModal);
});
