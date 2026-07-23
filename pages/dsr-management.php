<?php
// Ensure DB connection is loaded
require_once __DIR__ . '/../includes/db.php';

// Fetch dynamic DSR records
$dsr_list = [];
$total_requests = 0;
$pending_requests = 0;
$completed_requests = 0;

if ($conn && !$conn->connect_error) {
    $result = $conn->query("SELECT * FROM dsr_requests ORDER BY id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dsr_list[] = $row;
            $total_requests++;
            if ($row['status'] === 'Pending' || $row['status'] === 'In Progress') {
                $pending_requests++;
            } elseif ($row['status'] === 'Completed') {
                $completed_requests++;
            }
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Subject Requests (DSR)</h1>
            <p class="text-sm text-gray-500">Track and manage user data access, erasure, and portability requests.</p>
        </div>
        <button onclick="openDsrModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            + Log New Request
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Requests</span>
            <div class="mt-2 text-3xl font-bold text-gray-900"><?= $total_requests ?></div>
        </div>
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600">Pending / Active</span>
            <div class="mt-2 text-3xl font-bold text-amber-600"><?= $pending_requests ?></div>
        </div>
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Completed</span>
            <div class="mt-2 text-3xl font-bold text-emerald-600"><?= $completed_requests ?></div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Recent Requests</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">ID</th>
                        <th class="p-4">Subject Email</th>
                        <th class="p-4">Request Type</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Due Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php if (empty($dsr_list)): ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">No data subject requests logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dsr_list as $request): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-mono text-gray-500">#<?= htmlspecialchars($request['id']) ?></td>
                                <td class="p-4 font-medium text-gray-900"><?= htmlspecialchars($request['subject_email']) ?></td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-50 text-blue-700">
                                        <?= htmlspecialchars($request['request_type']) ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php
                                    $statusClass = match($request['status']) {
                                        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'In Progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Rejected' => 'bg-red-50 text-red-700 border-red-200',
                                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full border <?= $statusClass ?>">
                                        <?= htmlspecialchars($request['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 font-mono text-xs"><?= htmlspecialchars($request['due_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Log New Request -->
<div id="dsrModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">Log Data Subject Request</h3>
            <button onclick="closeDsrModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>
        <form id="dsrForm" class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Subject Email</label>
                <input type="email" name="subject_email" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="user@example.com">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase mb-1">Request Type</label>
                <select name="request_type" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="Access">Access (Right to Know)</option>
                    <option value="Erasure">Erasure (Right to be Forgotten)</option>
                    <option value="Export">Export (Data Portability)</option>
                    <option value="Rectification">Rectification (Data Correction)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeDsrModal()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDsrModal() {
    document.getElementById('dsrModal').classList.remove('hidden');
}

function closeDsrModal() {
    document.getElementById('dsrModal').classList.add('hidden');
    document.getElementById('dsrForm').reset();
}

document.getElementById('dsrForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/save-dsr.php', {
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
            closeDsrModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Failed to save request: ' + error);
    });
});
</script>