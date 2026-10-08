<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>PrivacyHQ - Compliance Frameworks</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #faf9f8; color: #1a1c1c; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-background min-h-screen pb-24">
    <header class="bg-white shadow flex justify-between items-center px-6 py-4">
        <h1 class="text-2xl font-bold text-primary">Compliance Frameworks</h1>
        <div><a href="/governance/index.php" class="text-primary underline">Back to Dashboard</a></div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 md:px-8">
        <div class="mb-6 flex justify-between">
            <h2 class="text-xl font-semibold">Framework Catalogue</h2>
            <button onclick="document.getElementById('createFwModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded">Add Framework</button>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4">Name</th>
                        <th class="p-4">Version</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Score</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="fwTableBody">
                    <tr><td colspan="5" class="p-4 text-center">Loading frameworks...</td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Create Framework Modal -->
    <div id="createFwModal" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">New Framework</h3>
            <form id="createFwForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Name</label>
                    <input type="text" name="name" required class="w-full border rounded p-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Version</label>
                    <input type="text" name="version" class="w-full border rounded p-2">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('createFwModal').classList.add('hidden')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requirements/Assess Modal -->
    <div id="assessModal" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-lg">
            <h3 class="text-lg font-bold mb-4">Assess Requirement</h3>
            <form id="assessForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="assess_requirement">
                <input type="hidden" name="requirement_id" id="assess_req_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Assessment Status</label>
                    <select name="assessment_status" class="w-full border rounded p-2">
                        <option value="Not Assessed">Not Assessed</option>
                        <option value="Compliant">Compliant</option>
                        <option value="Partially Compliant">Partially Compliant</option>
                        <option value="Non-Compliant">Non-Compliant</option>
                        <option value="Not Applicable">Not Applicable</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Notes / Evidence Ref</label>
                    <textarea name="notes" class="w-full border rounded p-2"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('assessModal').classList.add('hidden')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Save Assessment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';

        async function loadFrameworks() {
            const res = await fetch('/governance/backend/api/grc/frameworks.php?action=list');
            const data = await res.json();
            const tbody = document.getElementById('fwTableBody');
            tbody.innerHTML = '';
            if(data.data && data.data.length > 0) {
                data.data.forEach(fw => {
                    tbody.innerHTML += `
                        <tr class="border-t">
                            <td class="p-4">${fw.name}</td>
                            <td class="p-4">${fw.version}</td>
                            <td class="p-4">${fw.status}</td>
                            <td class="p-4 font-bold ${fw.score >= 80 ? 'text-green-600' : 'text-amber-600'}">${fw.score}%</td>
                            <td class="p-4">
                                <button onclick="assessMock(${fw.id})" class="text-primary underline text-sm">Assess Reqs</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center">No frameworks found.</td></tr>';
            }
        }

        document.getElementById('createFwForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'create');
            const res = await fetch('/governance/backend/api/grc/frameworks.php', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.status === 'success') {
                document.getElementById('createFwModal').classList.add('hidden');
                e.target.reset();
                loadFrameworks();
            } else {
                alert('Error creating framework');
            }
        });

        document.getElementById('assessForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const res = await fetch('/governance/backend/api/grc/frameworks.php', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.status === 'success') {
                document.getElementById('assessModal').classList.add('hidden');
                e.target.reset();
                loadFrameworks();
            } else {
                alert('Error updating assessment');
            }
        });

        // Mock function to open assess modal for a req
        function assessMock(fwId) {
            // For now, hardcode reqId = 1
            document.getElementById('assess_req_id').value = 1; 
            document.getElementById('assessModal').classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', loadFrameworks);
    </script>
</body>
</html>
