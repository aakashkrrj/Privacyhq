<?php
// governance/pages/my-assessments.php
require_once __DIR__ . '/../includes/db.php';

// Authenticated check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role_id'] ?? 0;

if (!$userId) {
    header('Location: login.php');
    exit;
}

/** @var PDO $pdo */

// Fetch assessments assigned only to the logged-in user (unless Super Admin/DPO who can view all assigned to them)
$query = "
    SELECT 
        pa.id, 
        pa.title, 
        COALESCE(pa.priority, 'Medium') AS priority,
        pa.due_date,
        ast.status_name AS status,
        u_rev.email AS reviewer_email,
        u_rev.first_name AS reviewer_first,
        u_rev.last_name AS reviewer_last
    FROM privacy_assessments pa
    LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
    LEFT JOIN users u_rev ON pa.reviewer_id = u_rev.id
    WHERE pa.assigned_to = ? AND pa.deleted_at IS NULL
    ORDER BY pa.id DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$my_assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$total = count($my_assessments);
$in_progress = 0;
$completed = 0;
foreach ($my_assessments as $row) {
    if (($row['status'] ?? '') === 'In Progress' || ($row['status'] ?? '') === 'Assigned') {
        $in_progress++;
    } elseif (($row['status'] ?? '') === 'Approved') {
        $completed++;
    }
}
?>

<div class="space-y-lg">
    <!-- Header -->
    <div>
        <h1 class="text-display font-display text-primary leading-tight">My Assigned Assessments</h1>
        <p class="text-body-md text-on-surface-variant">Manage and perform Privacy Impact Assessments (DPIA) assigned to you.</p>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Assigned to Me</span>
            <div class="mt-base text-display font-bold text-on-surface"><?= $total ?></div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">In Progress / Action Needed</span>
            <div class="mt-base text-display font-bold text-amber-600"><?= $in_progress ?></div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Completed & Approved</span>
            <div class="mt-base text-display font-bold text-emerald-600"><?= $completed ?></div>
        </div>
    </div>

    <!-- Active Assessments Table -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low">
            <h2 class="font-semibold text-on-surface text-title-md">My Active Tasks</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant">
                        <th class="p-md">ID</th>
                        <th class="p-md">Assessment Title</th>
                        <th class="p-md">Reviewer</th>
                        <th class="p-md">Due Date</th>
                        <th class="p-md">Priority</th>
                        <th class="p-md">Status</th>
                        <th class="p-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <?php if (empty($my_assessments)): ?>
                        <tr>
                            <td colspan="7" class="p-lg text-center text-on-surface-variant">You have no active assessment tasks assigned.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($my_assessments as $item): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="p-md font-mono text-on-surface-variant">#<?= $item['id'] ?></td>
                                <td class="p-md font-semibold text-on-surface"><?= htmlspecialchars($item['title']) ?></td>
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
                                    <a href="index.php?page=perform-assessment&id=<?= $item['id'] ?>" class="text-primary hover:underline font-semibold">Perform Task</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
