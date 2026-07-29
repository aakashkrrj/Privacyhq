<?php
// Ensure DB connection is loaded
require_once __DIR__ . '/../includes/db.php';

// Fetch dynamic Assessment records
$assessment_list = [];
$total_assessments = 0;
$high_risk_count = 0;
$under_review_count = 0;

if ($conn && !$conn->connect_error) {
    $query = "SELECT pa.id, pa.title, u.email AS assessor, rm.risk_level_name AS risk_level, ast.status_name AS status
              FROM privacy_assessments pa
              LEFT JOIN assessment_statuses ast ON pa.status_id = ast.id
              LEFT JOIN users u ON pa.assigned_to = u.id
              LEFT JOIN assessment_risks ar ON ar.assessment_id = pa.id
              LEFT JOIN risk_matrix rm ON ar.inherent_risk_matrix_id = rm.id
              ORDER BY pa.id DESC";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $assessment_list[] = $row;
            $total_assessments++;
            if ($row['risk_level'] === 'High') {
                $high_risk_count++;
            }
            if ($row['status'] === 'Under Review') {
                $under_review_count++;
            }
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Privacy Impact Assessments (DPIA)</h1>
            <p class="text-sm text-gray-500">Evaluate and track risk levels for high-risk data processing activities.</p>
        </div>
        <button onclick="openAssessmentModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            + New Assessment
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Assessments</span>
            <div class="mt-2 text-3xl font-bold text-gray-900"><?= $total_assessments ?></div>
        </div>
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600">Under Review</span>
            <div class="mt-2 text-3xl font-bold text-amber-600"><?= $under_review_count ?></div>
        </div>
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-red-600">High Risk Items</span>
            <div class="mt-2 text-3xl font-bold text-red-600"><?= $high_risk_count ?></div>
        </div>
    </div>

    <!-- Assessments Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Active DPIAs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">ID</th>
                        <th class="p-4">Title</th>
                        <th class="p-4">Assessor</th>
                        <th class="p-4">Risk Level</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php if (empty($assessment_list)): ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">No assessments created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assessment_list as $item): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-mono text-gray-500">#<?= htmlspecialchars($item['id']) ?></td>
                                <td class="p-4 font-medium text-gray-900"><?= htmlspecialchars($item['title']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($item['assessor']) ?></td>
                                <td class="p-4">
                                    <?php
                                    $riskClass = match($item['risk_level']) {
                                        'High' => 'bg-red-50 text-red-700 border-red-200',
                                        'Medium' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full border <?= $riskClass ?>">
                                        <?= htmlspecialchars($item['risk_level']) ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php
                                    $statusClass = match($item['status']) {
                                        'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Under Review' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full border <?= $statusClass ?>">
                                        <?= htmlspecialchars($item['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Assessment -->
<div id="assessmentModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">Create Privacy Assessment</h3>
            <button onclick="closeAssessmentModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        <form id="assessmentForm" class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Assessment Title</label>
                <input type="text" name="title" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="e.g. AI Customer Service Integration">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Assessor Email / Name</label>
                <input type="text" name="assessor" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="jane@company.com">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Risk Level</label>
                    <select name="risk_level" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="Draft">Draft</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Approved">Approved</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAssessmentModal()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Assessment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssessmentModal() {
    document.getElementById('assessmentModal').classList.remove('hidden');
}

function closeAssessmentModal() {
    document.getElementById('assessmentModal').classList.add('hidden');
    document.getElementById('assessmentForm').reset();
}

document.getElementById('assessmentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/save-assessment.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const isJson = response.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await response.json() : null;

        if (!response.ok) {
            const error = (data && data.message) || response.statusText;
            return Promise.reject(error);
        }
        return data;
    })
    .then(data => {
        if (data.status === 'success') {
            closeAssessmentModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Failed to save assessment: ' + error);
    });
});
</script>