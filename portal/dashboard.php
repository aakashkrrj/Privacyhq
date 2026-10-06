<?php
require_once __DIR__ . '/../backend/api/portal/PortalBootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>PrivacyHQ - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>";
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">PrivacyHQ Portal</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"><?= htmlspecialchars($_SESSION['portal_email'] ?? '') ?></span>
                    <button id="logoutBtn" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 p-8">
                <h2 class="text-2xl font-semibold mb-4">Welcome to your Privacy Portal</h2>
                <p class="text-gray-600 mb-8">This is a secure area authenticated via magic link.</p>
                
                <!-- DSR Requests Section -->
                <div class="mt-8 bg-white shadow sm:rounded-lg mb-6 max-w-4xl p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Your Data Requests</h3>
                    <div class="mt-2 text-sm text-gray-500 mb-6">
                        <p>Submit and track requests regarding your personal data.</p>
                    </div>

                    <!-- Create Request Form -->
                    <div class="bg-gray-50 p-4 rounded-md mb-6 border">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Submit a New Request</h4>
                        <div id="dsrMsg" class="hidden mb-3 p-2 text-sm rounded"></div>
                        <form id="createDsrForm" class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Request Type</label>
                                <select id="requestType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" required>
                                    <option value="" disabled selected>Select a request type</option>
                                    <option value="access">Right of Access (Download my data)</option>
                                    <option value="erasure">Right to Erasure (Delete my data)</option>
                                    <option value="rectification">Right to Rectification (Correct my data)</option>
                                    <option value="portability">Right to Data Portability</option>
                                    <option value="objection">Right to Object</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Details / Context</label>
                                <textarea id="requestDetails" rows="2" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md p-2" placeholder="Provide any specific details..."></textarea>
                            </div>
                            <button type="submit" id="submitDsrBtn" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                                Submit Request
                            </button>
                        </form>
                    </div>

                    <!-- Requests List -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Request History</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody id="dsrListBody" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">Loading your requests...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            let fd = new FormData();
            fd.append('csrf_token', csrfToken);
            await fetch('../backend/api/portal/auth/logout.php', {
                method: 'POST',
                body: fd
            });
            window.location.href = 'index.php';
        });

        // Load DSRs
        async function loadDsrs() {
            try {
                const res = await fetch('../backend/api/portal/requests/list.php');
                const data = await res.json();
                const tbody = document.getElementById('dsrListBody');
                if (data.status === 'success') {
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">You have no past requests.</td></tr>';
                    } else {
                        tbody.innerHTML = data.data.map(r => `
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">${r.request_id_code}</td>
                                <td class="px-4 py-2 text-sm text-gray-900 capitalize">${r.request_type}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 uppercase">
                                        ${r.status}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">${r.created_at.split(' ')[0]}</td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (err) {
                console.error("Error loading requests", err);
            }
        }

        // Submit DSR
        document.getElementById('createDsrForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitDsrBtn');
            const msgEl = document.getElementById('dsrMsg');
            const type = document.getElementById('requestType').value;
            const desc = document.getElementById('requestDetails').value;
            
            btn.disabled = true;
            btn.innerText = 'Submitting...';
            
            try {
                let fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('request_type', type);
                fd.append('description', desc);
                
                const res = await fetch('../backend/api/portal/requests/create.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                
                msgEl.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
                if (data.status === 'success') {
                    msgEl.classList.add('bg-green-100', 'text-green-700');
                    msgEl.innerText = 'Request submitted successfully.';
                    document.getElementById('createDsrForm').reset();
                    loadDsrs(); // reload list
                } else {
                    msgEl.classList.add('bg-red-100', 'text-red-700');
                    msgEl.innerText = data.message || 'Failed to submit request.';
                }
            } catch (err) {
                msgEl.classList.remove('hidden');
                msgEl.classList.add('bg-red-100', 'text-red-700');
                msgEl.innerText = 'An error occurred.';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Submit Request';
            }
        });

        // Init
        loadDsrs();
    </script>
</body>
</html>
