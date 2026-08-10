// assets/js/my-tasks.js

let currentWorkspaceTab = 'Tasks';
let currentFilter = 'Pending';
let tasksList = [];
let assessmentsList = [];
let dsrList = [];

document.addEventListener('DOMContentLoaded', function() {
    loadWorkspaceData();
});

function switchWorkspaceTab(tab) {
    currentWorkspaceTab = tab;
    
    // Toggle active state classes on tab buttons
    const tabs = ['Tasks', 'Assessments', 'DSR'];
    tabs.forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        if (t === tab) {
            btn.classList.add('text-primary', 'border-b-2', 'border-primary');
            btn.classList.remove('text-on-surface-variant');
        } else {
            btn.classList.remove('text-primary', 'border-b-2', 'border-primary');
            btn.classList.add('text-on-surface-variant');
        }
    });

    loadWorkspaceData();
}

function loadWorkspaceData() {
    if (currentWorkspaceTab === 'Tasks') {
        loadTasks();
    } else if (currentWorkspaceTab === 'Assessments') {
        loadAssessments();
    } else if (currentWorkspaceTab === 'DSR') {
        loadDSRs();
    }
}

function loadTasks() {
    fetch(`backend/api/tasks/list.php?status=${currentFilter}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                tasksList = data.data;
                renderWorkspaceData();
                updateCounts();
            } else {
                console.error(data.message);
            }
        })
        .catch(err => console.error('Error loading tasks:', err));
}

function renderTasks() {
    const tbody = document.getElementById('tasksTableBody');
    const searchVal = document.getElementById('taskSearch').value.toLowerCase();
    
    // Filter by search query
    const filtered = tasksList.filter(t => 
        t.title.toLowerCase().includes(searchVal) || 
        t.description.toLowerCase().includes(searchVal) ||
        t.module.toLowerCase().includes(searchVal)
    );

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="p-md text-center text-on-surface-variant">No tasks found in this section.</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(t => {
        const priorityColors = {
            'Low': 'bg-gray-100 text-gray-700 border-gray-200',
            'Medium': 'bg-blue-50 text-blue-700 border-blue-200',
            'High': 'bg-orange-50 text-orange-700 border-orange-200',
            'Critical': 'bg-red-50 text-red-700 border-red-200'
        };
        const pColor = priorityColors[t.priority] || 'bg-gray-50 text-gray-700 border-gray-200';

        // Action links based on module and type
        let openLink = 'index.php?page=dashboard';
        if (t.module === 'Assessment') {
            if (t.task_type.includes('Review')) {
                openLink = `index.php?page=review-assessment&id=${t.record_id}`;
            } else {
                openLink = `index.php?page=perform-assessment&id=${t.record_id}`;
            }
        } else if (t.module === 'DSR') {
            openLink = 'index.php?page=dsr-management';
        } else if (t.module === 'Incident') {
            openLink = 'index.php?page=incident-management';
        } else if (t.module === 'Policy') {
            openLink = 'index.php?page=policies';
        } else if (t.module === 'Vendor') {
            openLink = 'index.php?page=vendor-management';
        } else if (t.module === 'ROPA') {
            openLink = 'index.php?page=ropa';
        } else if (t.module === 'Risk') {
            openLink = 'index.php?page=risk-register';
        }

        const isCompleted = t.status === 'Completed';

        return `
            <tr class="hover:bg-surface-container-lowest transition-colors">
                <td class="p-md">
                    <div class="font-semibold text-on-surface">${escapeHtml(t.title)}</div>
                    <div class="text-caption text-on-surface-variant mt-0.5">${escapeHtml(t.description)}</div>
                    <div class="text-[11px] text-primary/70 mt-1 font-semibold">Assigned By: ${escapeHtml(t.assigned_by_email)}</div>
                </td>
                <td class="p-md font-semibold text-on-surface-variant">${escapeHtml(t.module)}</td>
                <td class="p-md">
                    <span class="inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border ${pColor}">
                        ${escapeHtml(t.priority)}
                    </span>
                </td>
                <td class="p-md text-on-surface-variant font-medium">${t.due_date ? t.due_date : 'N/A'}</td>
                <td class="p-md">
                    <span class="status-badge inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border ${isCompleted ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'}">
                        ${escapeHtml(t.status)}
                    </span>
                </td>
                <td class="p-md text-right space-x-base">
                    <a href="${openLink}" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-primary transition-all" title="Open Associated Record">
                        <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                    </a>
                    ${!isCompleted ? `
                        <button onclick="completeTask(${t.id})" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-emerald-600 transition-all" title="Mark Task Completed">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');
}

function updateCounts() {
    // Dynamic counts
    let pendingCount = 0;
    let todayCount = 0;
    let overdueCount = 0;
    let completedCount = 0;

    const todayStr = new Date().toISOString().split('T')[0];

    // Request counts for all categories from current loaded payload
    // To be perfectly accurate, we count based on details
    tasksList.forEach(t => {
        if (t.status === 'Completed') {
            completedCount++;
        } else {
            pendingCount++;
            if (t.due_date === todayStr) {
                todayCount++;
            }
            if (t.due_date && t.due_date < todayStr) {
                overdueCount++;
            }
        }
    });

    // Fallback/direct API count loader can be used, but client-side counting of all tasks is instant
    // To make sure all tab indicators are set:
    document.getElementById('count-Pending').innerText = pendingCount;
    document.getElementById('count-DueToday').innerText = todayCount;
    document.getElementById('count-Overdue').innerText = overdueCount;
    document.getElementById('count-Completed').innerText = completedCount;
}

function switchTaskFilter(filter) {
    currentFilter = filter;
    
    // Update active state on cards
    const filters = ['Pending', 'DueToday', 'Overdue', 'Completed'];
    filters.forEach(f => {
        const card = document.getElementById(`card-${f}`);
        if (f === filter) {
            card.classList.add('border-primary');
            card.classList.remove('border-outline-variant');
        } else {
            card.classList.remove('border-primary');
            card.classList.add('border-outline-variant');
        }
    });

    const titles = {
        'Pending': 'Pending Assigned Tasks',
        'DueToday': 'Tasks Due Today',
        'Overdue': 'Overdue Action Items',
        'Completed': 'Completed Task History'
    };
    document.getElementById('table-title').innerText = titles[filter];

    loadTasks();
}

function completeTask(taskId) {
    if (!confirm('Are you sure you want to mark this task as completed?')) {
        return;
    }
    const formData = new FormData();
    formData.append('task_id', taskId);

    fetch('backend/api/tasks/complete.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccessToast('Task marked completed successfully.');
            loadTasks();
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

function showSuccessToast(message) {
    const toast = document.createElement('div');
    toast.className = "fixed bottom-5 right-5 z-50 p-4 bg-primary text-white text-body-md rounded-xl shadow-lg border border-primary/20 flex items-center gap-xs animate-fade-in";
    toast.innerHTML = `
        <span class="material-symbols-outlined">check_circle</span>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function loadAssessments() {
    fetch('backend/api/assessment/list.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                assessmentsList = data.data;
                renderWorkspaceData();
            }
        })
        .catch(err => console.error(err));
}

function loadDSRs() {
    fetch('backend/api/dsr/list.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                dsrList = data.data;
                renderWorkspaceData();
            }
        })
        .catch(err => console.error(err));
}

function renderWorkspaceData() {
    if (currentWorkspaceTab === 'Tasks') {
        renderTasks();
    } else if (currentWorkspaceTab === 'Assessments') {
        renderAssessments();
    } else if (currentWorkspaceTab === 'DSR') {
        renderDSRs();
    }
}

function renderAssessments() {
    const tbody = document.getElementById('tasksTableBody');
    const searchVal = document.getElementById('taskSearch').value.toLowerCase();

    const filtered = assessmentsList.filter(a => 
        a.title.toLowerCase().includes(searchVal)
    );

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="p-md text-center text-on-surface-variant">No assessments found.</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(a => `
        <tr class="hover:bg-surface-container-lowest transition-colors">
            <td class="p-md">
                <div class="font-semibold text-on-surface">${escapeHtml(a.title)}</div>
                <div class="text-[11px] text-primary/70 mt-1 font-semibold">Assessor: ${escapeHtml(a.assessor_email || 'N/A')}</div>
            </td>
            <td class="p-md font-semibold text-on-surface-variant">Assessment</td>
            <td class="p-md">
                <span class="inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border bg-blue-50 text-blue-700 border-blue-200">
                    ${escapeHtml(a.priority || 'Medium')}
                </span>
            </td>
            <td class="p-md text-on-surface-variant font-medium">${a.due_date || 'N/A'}</td>
            <td class="p-md">
                <span class="inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border bg-amber-50 text-amber-700 border-amber-200">
                    ${escapeHtml(a.status_name || 'Under Review')}
                </span>
            </td>
            <td class="p-md text-right">
                <a href="index.php?page=perform-assessment&id=${a.id}" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-primary transition-all">
                    <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                </a>
            </td>
        </tr>
    `).join('');
}

function renderDSRs() {
    const tbody = document.getElementById('tasksTableBody');
    const searchVal = document.getElementById('taskSearch').value.toLowerCase();

    const filtered = dsrList.filter(d => 
        d.request_id_code.toLowerCase().includes(searchVal) || 
        d.request_type.toLowerCase().includes(searchVal)
    );

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="p-md text-center text-on-surface-variant">No DSRs found.</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(d => `
        <tr class="hover:bg-surface-container-lowest transition-colors">
            <td class="p-md">
                <div class="font-semibold text-on-surface">${escapeHtml(d.request_id_code)}</div>
                <div class="text-[11px] text-primary/70 mt-1 font-semibold">Subject Email: ${escapeHtml(d.subject_email || 'N/A')}</div>
            </td>
            <td class="p-md font-semibold text-on-surface-variant">DSR</td>
            <td class="p-md">
                <span class="inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border bg-purple-50 text-purple-700 border-purple-200">
                    ${escapeHtml(d.priority || 'Medium')}
                </span>
            </td>
            <td class="p-md text-on-surface-variant font-medium">${d.due_date || 'N/A'}</td>
            <td class="p-md">
                <span class="inline-flex px-2.5 py-1 text-caption font-semibold rounded-full border bg-amber-50 text-amber-700 border-amber-200">
                    ${escapeHtml(d.status || 'open')}
                </span>
            </td>
            <td class="p-md text-right">
                <a href="index.php?page=dsr-management" class="inline-flex items-center justify-center p-2 rounded-lg border border-outline-variant hover:bg-surface-container-low text-primary transition-all">
                    <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                </a>
            </td>
        </tr>
    `).join('');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
