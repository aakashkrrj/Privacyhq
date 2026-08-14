<?php
// governance/pages/assessments.php
require_once __DIR__ . '/../includes/db.php';

// Check permissions
require_permission('manage_assessments');

/** @var PDO $pdo */

// Fetch all users for selection
$allUsers = $pdo->query("SELECT id, email, first_name, last_name FROM users WHERE deleted_at IS NULL AND status = 'active' ORDER BY email ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch dynamic assessments list (Super Admin and DPO see all)
$assessment_list = [];
$query = "
    SELECT 
        pa.id, 
        pa.title, 
        pa.assigned_to,
        pa.reviewer_id,
        COALESCE(pa.priority, 'Medium') AS priority,
        pa.due_date,
        u_ass.email AS assessor_email,
        u_ass.first_name AS assessor_first,
        u_ass.last_name AS assessor_last,
        u_rev.email AS reviewer_email,
        u_rev.first_name AS reviewer_first,
        u_rev.last_name AS reviewer_last,
        ast.status_name AS status,
        COALESCE(
            (SELECT rm.risk_level_name 
             FROM assessment_risks ar 
             JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id 
             WHERE ar.assessment_id = pa.id 
             ORDER BY rm.risk_score DESC LIMIT 1), 
            'Low'
        ) AS risk_level
    FROM privacy_assessments pa
    LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
    LEFT JOIN users u_ass ON pa.assigned_to = u_ass.id
    LEFT JOIN users u_rev ON pa.reviewer_id = u_rev.id
    WHERE pa.deleted_at IS NULL
    ORDER BY pa.id DESC
";

$stmt = $pdo->query($query);
if ($stmt) {
    $assessment_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Stats metrics
$total_assessments = count($assessment_list);
$under_review_count = 0;
$high_risk_count = 0;
foreach ($assessment_list as $row) {
    $st = $row['status'] ?? '';
    if ($st === 'Under Review' || $st === 'Submitted' || $st === 'Pending Review' || $st === 'In Progress') {
        $under_review_count++;
    }
    if (($row['risk_level'] ?? '') === 'High') {
        $high_risk_count++;
    }
}
?>

<div class="space-y-lg">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-md">
        <div>
            <h1 class="text-display font-display text-primary leading-tight">Privacy Impact Assessments (DPIA)</h1>
            <p class="text-body-md text-on-surface-variant">Perform systematic analysis to identify and mitigate privacy risks in data processing operations.</p>
        </div>
        <button onclick="openAssessmentModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm">
            + Create DPIA
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Assessments</span>
            <div class="mt-base text-display font-bold text-on-surface"><?= $total_assessments ?></div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Pending Review</span>
            <div class="mt-base text-display font-bold text-amber-600"><?= $under_review_count ?></div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">High Risk Thresholds</span>
            <div class="mt-base text-display font-bold text-red-600"><?= $high_risk_count ?></div>
        </div>
    </div>

    <!-- Active Assessments Table -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex justify-between items-center">
            <h2 class="font-semibold text-on-surface text-title-md">All Privacy Assessments</h2>
            <span class="text-caption text-on-surface-variant">Click table headers to sort</span>
        </div>
        <div class="overflow-x-auto">
            <table id="assessmentTable" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(0)">ID ↕</th>
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(1)">Title ↕</th>
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(2)">Assessor ↕</th>
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(3)">Reviewer ↕</th>
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(4)">Due Date ↕</th>
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(5)">Priority ↕</th>
                        <th class="p-md cursor-pointer hover:bg-surface-container-high" onclick="sortAssessmentTable(6)">Status ↕</th>
                        <th class="p-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <?php if (empty($assessment_list)): ?>
                        <tr>
                            <td colspan="8" class="p-lg text-center text-on-surface-variant">No assessment workflows created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assessment_list as $item): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="p-md font-mono text-on-surface-variant">#<?= $item['id'] ?></td>
                                <td class="p-md font-semibold text-on-surface"><?= htmlspecialchars($item['title']) ?></td>
                                <td class="p-md text-on-surface-variant">
                                    <?= htmlspecialchars(($item['assessor_first'] ?? '') ? $item['assessor_first'] . ' ' . $item['assessor_last'] : ($item['assessor_email'] ?? 'Unassigned')) ?>
                                </td>
                                <td class="p-md text-on-surface-variant">
                                    <?= htmlspecialchars(($item['reviewer_first'] ?? '') ? $item['reviewer_first'] . ' ' . $item['reviewer_last'] : ($item['reviewer_email'] ?? 'Unassigned')) ?>
                                </td>
                                <td class="p-md font-mono text-caption text-on-surface-variant"><?= htmlspecialchars($item['due_date'] ?? 'N/A') ?></td>
                                <td class="p-md">
                                    <?php
                                    $prioVal = $item['priority'] ?? 'Medium';
                                    $prioClass = match($prioVal) {
                                        'High' => 'bg-red-50 text-red-700 border-red-200',
                                        'Low' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                    ?>
                                    <span class="px-2 py-0.5 text-caption font-semibold rounded-full border <?= $prioClass ?>">
                                        <?= htmlspecialchars($prioVal) ?>
                                    </span>
                                </td>
                                <td class="p-md">
                                    <?php
                                    $statusVal = $item['status'] ?? 'Draft';
                                    $statusClass = match($statusVal) {
                                        'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Submitted', 'Under Review', 'Pending Review', 'In Progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-surface-container-low text-on-surface-variant border-outline-variant',
                                    };
                                    ?>
                                    <span class="px-2 py-0.5 text-caption font-semibold rounded-full border <?= $statusClass ?>">
                                        <?= htmlspecialchars($statusVal) ?>
                                    </span>
                                </td>
                                <td class="p-md text-right space-x-base whitespace-nowrap">
                                    <?php
                                    $currentUserId = $_SESSION['user_id'] ?? 1;
                                    $currentUserRole = $_SESSION['role_id'] ?? 1;
                                    ?>
                                    <?php if ($statusVal !== 'Approved'): ?>
                                        <a href="index.php?page=perform-assessment&id=<?= $item['id'] ?>" class="text-primary hover:underline font-semibold" title="Fill Answers">Perform</a>
                                    <?php endif; ?>
                                    <?php if (in_array($statusVal, ['Submitted', 'Under Review', 'Pending Review']) && ($currentUserRole == 1 || ($item['reviewer_email'] ?? '') === ($_SESSION['user_email'] ?? ''))): ?>
                                        <span class="text-outline">|</span>
                                        <a href="index.php?page=review-assessment&id=<?= $item['id'] ?>" class="text-amber-600 hover:underline font-semibold" title="Review Submission">Review</a>
                                    <?php endif; ?>
                                    <span class="text-outline">|</span>
                                    <button type="button" onclick="editAssessment(<?= $item['id'] ?>); return false;" class="text-indigo-600 hover:underline font-semibold">Edit</button>
                                    <span class="text-outline">|</span>
                                    <button type="button" onclick="deleteAssessment(<?= $item['id'] ?>); return false;" class="text-red-600 hover:underline font-semibold">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: New Assessment Creation -->
<div id="assessmentModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Create Privacy Assessment</h3>
            <button onclick="closeAssessmentModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="assessmentForm" class="p-md space-y-md">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="title">Assessment Title</label>
                <input type="text" name="title" id="title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. AI Customer Support DPIA">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="assigned_to">Assign Assessor</label>
                <select name="assigned_to" id="assigned_to" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">Select Assessor...</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] ? $u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')' : $u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="reviewer_id">Assign Reviewer</label>
                <select name="reviewer_id" id="reviewer_id" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">Select Reviewer...</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] ? $u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')' : $u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-sm">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="priority">Priority</label>
                    <select name="priority" id="priority" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="due_date">Due Date</label>
                    <input type="date" name="due_date" id="due_date" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                </div>
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeAssessmentModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Create DPIA</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Assessment Details -->
<div id="editAssessmentModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-md overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Edit Assessment Details</h3>
            <button onclick="closeEditAssessmentModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="editAssessmentForm" class="p-md space-y-md">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_title">Assessment Title</label>
                <input type="text" name="title" id="edit_title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_assigned_to">Assign Assessor</label>
                <select name="assigned_to" id="edit_assigned_to" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">Select Assessor...</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] ? $u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')' : $u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_reviewer_id">Assign Reviewer</label>
                <select name="reviewer_id" id="edit_reviewer_id" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">Select Reviewer...</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['first_name'] ? $u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['email'] . ')' : $u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-sm">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_priority">Priority</label>
                    <select name="priority" id="edit_priority" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="edit_due_date">Due Date</label>
                    <input type="date" name="due_date" id="edit_due_date" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                </div>
            </div>
            <div class="flex justify-end gap-sm pt-2">
                <button type="button" onclick="closeEditAssessmentModal()" class="px-md py-2 text-body-md text-on-surface-variant border border-outline-variant rounded-lg hover:bg-surface-container-low">Cancel</button>
                <button type="submit" class="px-md py-2 text-body-md text-white bg-primary rounded-lg hover:opacity-90">Update DPIA</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    window.openAssessmentModal = function() {
        const form = document.getElementById('assessmentForm');
        if (form) form.reset();
        const modal = document.getElementById('assessmentModal');
        if (modal) modal.classList.remove('hidden');
    };

    window.closeAssessmentModal = function() {
        const modal = document.getElementById('assessmentModal');
        if (modal) modal.classList.add('hidden');
    };

    window.closeEditAssessmentModal = function() {
        const modal = document.getElementById('editAssessmentModal');
        if (modal) modal.classList.add('hidden');
    };

    window.populateEditModal = function(item) {
        if (!item) return;
        const editId = document.getElementById('edit_id');
        if (editId) editId.value = item.id || '';

        const editTitle = document.getElementById('edit_title');
        if (editTitle) editTitle.value = item.title || '';

        const assignedSelect = document.getElementById('edit_assigned_to');
        if (assignedSelect) assignedSelect.value = item.assigned_to || '';

        const reviewerSelect = document.getElementById('edit_reviewer_id');
        if (reviewerSelect) reviewerSelect.value = item.reviewer_id || '';

        const editPriority = document.getElementById('edit_priority');
        if (editPriority) editPriority.value = item.priority || 'Medium';

        const editDueDate = document.getElementById('edit_due_date');
        if (editDueDate) editDueDate.value = item.due_date || '';

        const modal = document.getElementById('editAssessmentModal');
        if (modal) modal.classList.remove('hidden');
    };

    window.editAssessment = function(id) {
        if (!id) return;
        fetch('backend/api/assessment/get.php?id=' + encodeURIComponent(id))
            .then(response => {
                if (!response.ok) throw new Error('HTTP error ' + response.status);
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' || data.success) {
                    const item = data.data ? (data.data.assessment || data.data) : data;
                    window.populateEditModal(item);
                } else {
                    alert('Error loading assessment details: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Failed to fetch assessment details:', err);
                alert('Failed to load assessment details.');
            });
    };

    window.openEditAssessmentModal = function(data) {
        if (typeof data === 'number' || (typeof data === 'string' && !data.trim().startsWith('{'))) {
            window.editAssessment(data);
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
        window.populateEditModal(item);
    };

    window.deleteAssessment = function(id) {
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
    };

    let sortDirections = {};
    window.sortAssessmentTable = function(columnIndex) {
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
    };
})();

document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('assessmentForm');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('backend/api/assessment/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    window.closeAssessmentModal();
                    location.reload();
                } else {
                    alert('Error creating assessment: ' + (data.message || 'Action failed'));
                }
            })
            .catch(err => {
                console.error('Create request failed:', err);
                alert('Failed to save assessment. System error.');
            });
        });
    }

    const editForm = document.getElementById('editAssessmentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('backend/api/assessment/update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' || data.success) {
                    window.closeEditAssessmentModal();
                    location.reload();
                } else {
                    alert('Error updating assessment: ' + (data.message || 'Action failed'));
                }
            })
            .catch(err => {
                console.error('Update request failed:', err);
                alert('Failed to update assessment. System error.');
            });
        });
    }
});
</script>
<script src="assets/js/assessments.js?v=<?= time() ?>"></script>