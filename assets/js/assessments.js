// assets/js/assessments.js
// Governance Privacy Impact Assessments (DPIA) Operations

function openAssessmentModal() {
    const form = document.getElementById('assessmentForm');
    if (form) form.reset();
    const modal = document.getElementById('assessmentModal');
    if (modal) modal.classList.remove('hidden');
}

function closeAssessmentModal() {
    const modal = document.getElementById('assessmentModal');
    if (modal) modal.classList.add('hidden');
}

function closeEditAssessmentModal() {
    const modal = document.getElementById('editAssessmentModal');
    if (modal) modal.classList.add('hidden');
}

function openHistoryModal(id) {
    const modal = document.getElementById('historyModal');
    const content = document.getElementById('historyModalContent');
    if (!modal || !content) return;

    content.innerHTML = `<div class="text-center py-6 text-gray-500 text-xs"><span class="material-symbols-outlined animate-spin text-xl text-primary block mb-1">sync</span>Loading assessment history...</div>`;
    modal.classList.remove('hidden');

    fetch(`backend/api/assessment/history.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            const items = data.data || [];
            if (items.length === 0) {
                content.innerHTML = `<div class="text-center py-6 text-gray-500 text-xs">No historical state transitions logged yet.</div>`;
                return;
            }

            let html = '<div class="space-y-3">';
            items.forEach(h => {
                const user = (h.first_name ? h.first_name + ' ' + h.last_name : h.email) || 'System User';
                html += `
                    <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant text-xs">
                        <div class="flex justify-between font-semibold text-on-surface mb-1">
                            <span>${escapeHtml(user)}</span>
                            <span class="text-on-surface-variant font-mono text-[11px]">${escapeHtml(h.changed_at || '')}</span>
                        </div>
                        <div class="text-on-surface-variant">
                            Status changed: <span class="font-bold text-primary">${escapeHtml(h.old_status || 'Start')} &rarr; ${escapeHtml(h.new_status || 'Current')}</span>
                        </div>
                        ${h.reason ? `<div class="mt-1 text-gray-600 italic">"${escapeHtml(h.reason)}"</div>` : ''}
                    </div>
                `;
            });
            html += '</div>';
            content.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load history:', err);
            content.innerHTML = `<div class="text-center py-6 text-red-600 text-xs">Failed to load history logs.</div>`;
        });
}

function closeHistoryModal() {
    const modal = document.getElementById('historyModal');
    if (modal) modal.classList.add('hidden');
}

function exportAssessment(id, format = 'csv') {
    const url = `backend/api/assessment/export.php?id=${id}&format=${format}`;
    window.open(url, '_blank');
}

function populateEditModal(item) {
    if (!item) return;
    const editId = document.getElementById('edit_id');
    if (editId) editId.value = item.id || '';

    const editTitle = document.getElementById('edit_title');
    if (editTitle) editTitle.value = item.title || '';

    const assignedSelect = document.getElementById('edit_assigned_to');
    if (assignedSelect) {
        assignedSelect.value = item.assigned_to || '';
    }

    const reviewerSelect = document.getElementById('edit_reviewer_id');
    if (reviewerSelect) {
        reviewerSelect.value = item.reviewer_id || '';
    }

    const editPriority = document.getElementById('edit_priority');
    if (editPriority) editPriority.value = item.priority || 'Medium';

    const editDueDate = document.getElementById('edit_due_date');
    if (editDueDate) editDueDate.value = item.due_date || '';

    const modal = document.getElementById('editAssessmentModal');
    if (modal) modal.classList.remove('hidden');
}

function editAssessment(id) {
    if (!id) return;
    fetch(`backend/api/assessment/get.php?id=${encodeURIComponent(id)}`)
        .then(response => {
            if (!response.ok) throw new Error('HTTP error ' + response.status);
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' || data.success) {
                const item = data.data ? (data.data.assessment || data.data) : data;
                populateEditModal(item);
            } else {
                alert('Error loading assessment details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Failed to fetch assessment details:', err);
            alert('Failed to load assessment details.');
        });
}

function openEditAssessmentModal(data) {
    if (typeof data === 'number' || (typeof data === 'string' && !data.trim().startsWith('{'))) {
        editAssessment(data);
        return;
    }

    let item = data;
    if (typeof data === 'string') {
        try {
            item = JSON.parse(data);
        } catch (e) {
            console.error('Invalid JSON passed to openEditAssessmentModal:', e);
            return;
        }
    }
    populateEditModal(item);
}

function deleteAssessment(id) {
    if (!id) return;
    if (!confirm('Are you sure you want to delete this DPIA assessment?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);

    fetch('backend/api/assessment/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('HTTP error ' + response.status);
        return response.json();
    })
    .then(data => {
        if (data.status === 'success' || data.success) {
            location.reload();
        } else {
            alert('Error deleting assessment: ' + (data.message || 'Action failed'));
        }
    })
    .catch(err => {
        console.error('Delete request failed:', err);
        alert('Failed to delete assessment. System error.');
    });
}

// Table Sorting Engine
let sortDirections = {};
function sortAssessmentTable(columnIndex) {
    const table = document.getElementById('assessmentTable');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    if (rows.length === 1 && rows[0].cells.length <= 1) return;

    const dir = sortDirections[columnIndex] === 'asc' ? 'desc' : 'asc';
    sortDirections[columnIndex] = dir;

    rows.sort((a, b) => {
        const cellA = a.cells[columnIndex]?.textContent.trim().toLowerCase() || '';
        const cellB = b.cells[columnIndex]?.textContent.trim().toLowerCase() || '';

        if (!isNaN(cellA) && !isNaN(cellB) && cellA !== '' && cellB !== '') {
            return dir === 'asc' ? Number(cellA) - Number(cellB) : Number(cellB) - Number(cellA);
        }
        return dir === 'asc' ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
    });

    rows.forEach(row => tbody.appendChild(row));
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Global Export to Window Object to prevent ReferenceError
window.openAssessmentModal = openAssessmentModal;
window.closeAssessmentModal = closeAssessmentModal;
window.openEditAssessmentModal = openEditAssessmentModal;
window.closeEditAssessmentModal = closeEditAssessmentModal;
window.openHistoryModal = openHistoryModal;
window.closeHistoryModal = closeHistoryModal;
window.exportAssessment = exportAssessment;
window.editAssessment = editAssessment;
window.deleteAssessment = deleteAssessment;
window.sortAssessmentTable = sortAssessmentTable;

document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('assessmentForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }

            const formData = new FormData(this);

            fetch('backend/api/assessment/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP error ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' || data.success) {
                    closeAssessmentModal();
                    location.reload();
                } else {
                    alert('Error creating assessment: ' + (data.message || 'Action failed'));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Save & Assign';
                    }
                }
            })
            .catch(err => {
                console.error('Create request failed:', err);
                alert('Failed to save assessment. System error.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save & Assign';
                }
            });
        });
    }

    const editForm = document.getElementById('editAssessmentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';
            }

            const formData = new FormData(this);

            fetch('backend/api/assessment/update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP error ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' || data.success) {
                    closeEditAssessmentModal();
                    location.reload();
                } else {
                    alert('Error updating assessment: ' + (data.message || 'Action failed'));
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Update Assessment';
                    }
                }
            })
            .catch(err => {
                console.error('Update request failed:', err);
                alert('Failed to update assessment. System error.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Update Assessment';
                }
            });
        });
    }
});
