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
    if (($row['status'] ?? '') === 'Under Review' || ($row['status'] ?? '') === 'Submitted') {
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
        <div class="p-md border-b border-outline-variant bg-surface-container-low">
            <h2 class="font-semibold text-on-surface text-title-md">All Privacy Assessments</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                        <th class="p-md">ID</th>
                        <th class="p-md">Title</th>
                        <th class="p-md">Assessor</th>
                        <th class="p-md">Reviewer</th>
                        <th class="p-md">Due Date</th>
                        <th class="p-md">Priority</th>
                        <th class="p-md">Status</th>
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
                                        'Submitted', 'Under Review' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-surface-container-low text-on-surface-variant border-outline-variant',
                                    };
                                    ?>
                                    <span class="px-2 py-0.5 text-caption font-semibold rounded-full border <?= $statusClass ?>">
                                        <?= htmlspecialchars($statusVal) ?>
                                    </span>
                                </td>
                                <td class="p-md text-right space-x-base">
                                    <?php
                                    $currentUserId = $_SESSION['user_id'] ?? 1;
                                    $currentUserRole = $_SESSION['role_id'] ?? 1;
                                    ?>
                                    <?php if ($statusVal !== 'Approved'): ?>
                                        <a href="index.php?page=perform-assessment&id=<?= $item['id'] ?>" class="text-primary hover:underline font-semibold" title="Fill Answers">Perform</a>
                                    <?php endif; ?>
                                    <?php if (in_array($statusVal, ['Submitted', 'Under Review']) && ($currentUserRole == 1 || ($item['reviewer_email'] ?? '') === ($_SESSION['user_email'] ?? ''))): ?>
                                        <span class="text-outline">|</span>
                                        <a href="index.php?page=review-assessment&id=<?= $item['id'] ?>" class="text-amber-600 hover:underline font-semibold" title="Review Submission">Review</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Assessment Creation -->
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

<script src="assets/js/assessments.js"></script>