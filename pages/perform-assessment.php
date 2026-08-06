<?php
// governance/pages/perform-assessment.php
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
} catch (Exception $e) {
    echo '<div class="p-6 bg-red-50 text-red-800 rounded-xl border border-red-200">' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="space-y-lg max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="index.php?page=my-assessments" class="text-body-md text-primary hover:underline flex items-center gap-xs mb-base">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back to My Assessments
            </a>
            <h1 class="text-display font-display text-primary leading-tight"><?= htmlspecialchars($assessment['title']) ?></h1>
            <p class="text-body-md text-on-surface-variant">Assigned DPIA questionnaire. Progress is saved automatically.</p>
        </div>
        <div class="text-right">
            <span class="inline-flex px-3 py-1 rounded-full text-caption font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                <?= htmlspecialchars($assessment['status']) ?>
            </span>
        </div>
    </div>

    <!-- Instructions banner -->
    <div class="p-md bg-surface-container-low rounded-xl border border-outline-variant flex items-center gap-sm">
        <span class="material-symbols-outlined text-primary">assignment</span>
        <div class="text-body-md text-on-surface-variant">
            Please answer all questions below. You may save a draft and return later. Upload supporting documents if necessary.
        </div>
    </div>

    <!-- Questionnaire Form -->
    <form id="assessmentForm" class="space-y-lg bg-surface rounded-xl border border-outline-variant p-md shadow-sm">
        <input type="hidden" name="assessment_id" value="<?= $assessmentId ?>">

        <div class="space-y-md">
            <?php
            $currentSection = null;
            foreach ($questions as $q):
                if ($currentSection !== $q['section_name']):
                    $currentSection = $q['section_name'];
            ?>
                <div class="pt-sm">
                    <h3 class="font-display text-title-md text-primary border-b border-outline-variant pb-base mb-base"><?= htmlspecialchars($currentSection) ?></h3>
                    <?php if (!empty($q['section_description'])): ?>
                        <p class="text-caption text-on-surface-variant mb-md"><?= htmlspecialchars($q['section_description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="space-y-xs pb-sm" data-question-id="<?= $q['question_id'] ?>">
                <label class="block text-body-md font-semibold text-on-surface">
                    <?= htmlspecialchars($q['question_text']) ?>
                    <?php if ($q['is_required']): ?><span class="text-red-500">*</span><?php endif; ?>
                </label>
                <?php if (!empty($q['help_text'])): ?>
                    <p class="text-caption text-on-surface-variant"><?= htmlspecialchars($q['help_text']) ?></p>
                <?php endif; ?>

                <?php 
                $ansVal = $responses[$q['question_id']] ?? ''; 
                $qname = "answers[" . $q['question_id'] . "]";
                ?>

                <!-- Field Inputs according to type -->
                <?php if ($q['question_type'] === 'textarea'): ?>
                    <textarea name="<?= $qname ?>" rows="3" placeholder="<?= htmlspecialchars($q['placeholder'] ?? 'Provide details...') ?>" class="autosave-input w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none"><?= htmlspecialchars($ansVal) ?></textarea>

                <?php elseif ($q['question_type'] === 'yes_no'): ?>
                    <div class="flex gap-md pt-base">
                        <label class="flex items-center gap-xs font-body-md text-on-surface cursor-pointer">
                            <input type="radio" name="<?= $qname ?>" value="Yes" class="autosave-input w-4 h-4 text-primary focus:ring-primary" <?= ($ansVal === 'Yes') ? 'checked' : '' ?>> Yes
                        </label>
                        <label class="flex items-center gap-xs font-body-md text-on-surface cursor-pointer">
                            <input type="radio" name="<?= $qname ?>" value="No" class="autosave-input w-4 h-4 text-primary focus:ring-primary" <?= ($ansVal === 'No') ? 'checked' : '' ?>> No
                        </label>
                    </div>

                <?php elseif ($q['question_type'] === 'radio'): ?>
                    <div class="flex flex-col gap-base pt-base">
                        <?php 
                        $opts = json_decode($q['options_json'], true) ?: ['Local', 'Cloud'];
                        foreach ($opts as $opt):
                        ?>
                            <label class="flex items-center gap-xs font-body-md text-on-surface cursor-pointer">
                                <input type="radio" name="<?= $qname ?>" value="<?= htmlspecialchars($opt) ?>" class="autosave-input w-4 h-4 text-primary focus:ring-primary" <?= ($ansVal === $opt) ? 'checked' : '' ?>> <?= htmlspecialchars($opt) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($q['question_type'] === 'dropdown'): ?>
                    <select name="<?= $qname ?>" class="autosave-input border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface max-w-xs">
                        <option value="">Select option...</option>
                        <?php 
                        $opts = json_decode($q['options_json'], true) ?: [];
                        foreach ($opts as $opt):
                        ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= ($ansVal === $opt) ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php else: ?>
                    <input type="text" name="<?= $qname ?>" value="<?= htmlspecialchars($ansVal) ?>" placeholder="<?= htmlspecialchars($q['placeholder'] ?? '') ?>" class="autosave-input w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Form Actions -->
        <div class="border-t border-outline-variant pt-md flex justify-between items-center gap-sm">
            <span class="text-caption text-on-surface-variant flex items-center gap-xs">
                <span class="material-symbols-outlined text-[16px] text-emerald-600 animate-pulse">sync</span>
                <span id="saveStatus">All changes saved locally</span>
            </span>
            <div class="flex gap-sm">
                <button type="button" onclick="saveDraft()" class="px-md py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-low transition-all font-semibold">Save Draft</button>
                <button type="button" onclick="submitForReview()" class="px-md py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 transition-all font-semibold">Submit for Review</button>
            </div>
        </div>
    </form>

    <!-- Upload Evidence & Notes -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <!-- Evidence Uploads Card -->
        <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
            <h3 class="font-display text-title-md text-primary">Supporting Evidence</h3>
            <form id="evidenceForm" enctype="multipart/form-data" class="flex gap-sm items-center">
                <input type="hidden" name="assessment_id" value="<?= $assessmentId ?>">
                <input type="file" name="evidence_file" id="evidenceFile" required class="text-body-md text-on-surface-variant border border-outline-variant rounded-lg p-base bg-surface-container-low max-w-xs focus:outline-none">
                <button type="submit" class="px-md py-2 bg-primary text-white text-body-md rounded-lg font-semibold hover:opacity-90">Upload</button>
            </form>
            <div class="space-y-base pt-sm">
                <h4 class="text-caption font-semibold uppercase text-on-surface-variant tracking-wider">Uploaded Documents</h4>
                <div id="documentsList" class="space-y-xs">
                    <?php if (empty($documents)): ?>
                        <p class="text-caption text-on-surface-variant">No evidence files uploaded yet.</p>
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

        <!-- Notes / Comments Card -->
        <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
            <h3 class="font-display text-title-md text-primary">Assessment Notes</h3>
            <div class="space-y-sm max-h-48 overflow-y-auto">
                <div id="notesList" class="space-y-xs">
                    <?php if (empty($notes)): ?>
                        <p class="text-caption text-on-surface-variant">No notes entered yet.</p>
                    <?php else: ?>
                        <?php foreach ($notes as $n): ?>
                            <div class="bg-surface-container-low p-2 rounded-lg border border-outline-variant text-caption text-on-surface">
                                <div class="font-semibold"><?= htmlspecialchars($n['first_name'] ? $n['first_name'] . ' ' . $n['last_name'] : $n['email']) ?></div>
                                <div class="mt-base text-on-surface-variant"><?= nl2br(htmlspecialchars($n['note_text'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/perform-assessment.js"></script>
