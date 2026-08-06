<?php
// governance/pages/review-assessment.php
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

$assessmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$assessmentId) {
    echo '<div class="p-6 bg-red-50 text-red-800 rounded-xl border border-red-200">Invalid Assessment ID</div>';
    exit;
}

/** @var PDO $pdo */

// Fetch details via backend models & services structure to reuse codebase
require_once __DIR__ . '/../backend/models/PrivacyAssessment.php';
require_once __DIR__ . '/../backend/services/AssessmentService.php';

$model = new \Backend\Models\PrivacyAssessment($pdo);
$service = new \Backend\Services\AssessmentService($model, $pdo);

try {
    $data = $service->getAssessmentDetail($assessmentId, $userId, $userRole);
    $assessment = $data['assessment'];
    $questions = $data['questions'];
    $responses = $data['responses'];
    $notes = $data['notes'];
    $documents = $data['documents'];
    $findings = $data['findings'];
} catch (Exception $e) {
    echo '<div class="p-6 bg-red-50 text-red-800 rounded-xl border border-red-200">' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="space-y-lg max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="index.php?page=assessments" class="text-body-md text-primary hover:underline flex items-center gap-xs mb-base">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to Assessments
            </a>
            <h1 class="text-display font-display text-primary leading-tight">Review Assessment: <?= htmlspecialchars($assessment['title']) ?></h1>
            <p class="text-body-md text-on-surface-variant">Designated DPO review panel. View findings, evidence, and sign off.</p>
        </div>
        <div>
            <span class="inline-flex px-3 py-1 rounded-full text-caption font-semibold border bg-blue-50 text-blue-700 border-blue-200 font-mono">
                <?= htmlspecialchars($assessment['status']) ?>
            </span>
        </div>
    </div>

    <!-- Details grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md bg-surface rounded-xl border border-outline-variant p-md shadow-sm text-body-md text-on-surface">
        <div>
            <span class="text-caption text-on-surface-variant uppercase font-semibold">Assessor</span>
            <p class="font-semibold mt-xs"><?= htmlspecialchars($assessment['assessor_first'] ? $assessment['assessor_first'] . ' ' . $assessment['assessor_last'] : $assessment['assessor_email']) ?></p>
        </div>
        <div>
            <span class="text-caption text-on-surface-variant uppercase font-semibold">Reviewer</span>
            <p class="font-semibold mt-xs"><?= htmlspecialchars($assessment['reviewer_first'] ? $assessment['reviewer_first'] . ' ' . $assessment['reviewer_last'] : $assessment['reviewer_email']) ?></p>
        </div>
        <div>
            <span class="text-caption text-on-surface-variant uppercase font-semibold">Due Date</span>
            <p class="font-mono mt-xs"><?= htmlspecialchars($assessment['due_date'] ?? 'N/A') ?></p>
        </div>
    </div>

    <!-- Review Responses Card -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low">
            <h2 class="font-semibold text-on-surface text-title-md">Questionnaire Submission</h2>
        </div>
        <div class="p-md space-y-md">
            <?php foreach ($questions as $q): ?>
                <div class="pb-sm border-b border-outline-variant/60 last:border-b-0">
                    <div class="font-semibold text-on-surface"><?= htmlspecialchars($q['question_text']) ?></div>
                    <div class="mt-base p-2 bg-surface-container-low rounded-lg font-mono text-body-md text-on-surface-variant">
                        <?= htmlspecialchars($responses[$q['question_id']] ?? '[No answer provided]') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Evidence & Findings Summary grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <!-- Findings & System Recommendations -->
        <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
            <h3 class="font-display text-title-md text-primary">Generated Risk Findings</h3>
            <div class="space-y-sm">
                <?php if (empty($findings)): ?>
                    <div class="p-sm bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-body-md flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        No security risks or compliance findings detected.
                    </div>
                <?php else: ?>
                    <?php foreach ($findings as $f): ?>
                        <div class="p-sm bg-red-50 text-red-700 border border-red-200 rounded-lg space-y-xs">
                            <div class="font-semibold text-body-md uppercase flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[18px]">warning</span>
                                <?= htmlspecialchars($f['category_name']) ?>
                            </div>
                            <p class="text-caption text-red-600"><?= htmlspecialchars($f['description']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evidence files -->
        <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
            <h3 class="font-display text-title-md text-primary font-semibold">Evidence Documents</h3>
            <div class="space-y-xs">
                <?php if (empty($documents)): ?>
                    <p class="text-caption text-on-surface-variant">No evidence documents uploaded.</p>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="flex justify-between items-center bg-surface-container-low p-2 rounded-lg border border-outline-variant">
                            <span class="text-body-md font-mono text-on-surface truncate max-w-xs"><?= basename($doc['file_path']) ?></span>
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="text-primary text-caption font-semibold hover:underline">Download</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviewer Feedback & Actions -->
    <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
        <h3 class="font-display text-title-md text-primary">Reviewer Sign-off</h3>
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1" for="review_notes">Reviewer Comments / Feedback</label>
            <textarea id="review_notes" rows="3" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Provide notes regarding approval or rejection reasons..."></textarea>
        </div>
        <div class="flex justify-end gap-sm pt-base">
            <button onclick="rejectDpia(<?= $assessmentId ?>)" class="px-md py-2.5 bg-red-600 text-white text-body-md rounded-xl font-semibold hover:opacity-90 transition-all">Reject / Request Changes</button>
            <button onclick="approveDpia(<?= $assessmentId ?>)" class="px-md py-2.5 bg-emerald-600 text-white text-body-md rounded-xl font-semibold hover:opacity-90 transition-all">Approve DPIA</button>
        </div>
    </div>
</div>

<script src="assets/js/review-assessment.js"></script>
