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

    <header class="bg-white shadow flex justify-between items-center px-6 py-4">
        <h1 class="text-2xl font-bold text-primary">Data Asset Inventory</h1>
        <div><a href="/governance/index.php" class="text-primary underline">Back to Dashboard</a></div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 md:px-8">
        
        <!-- Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" id="metricsContainer">
            <div class="bg-white p-4 rounded shadow">
                <p class="text-sm text-gray-500 font-bold">TOTAL ASSETS</p>
                <p class="text-2xl font-bold" id="m_total">0</p>
            </div>
            <div class="bg-white p-4 rounded shadow border-l-4 border-red-500">
                <p class="text-sm text-gray-500 font-bold">RESTRICTED</p>
                <p class="text-2xl font-bold text-red-600" id="m_restricted">0</p>
            </div>
            <div class="bg-white p-4 rounded shadow border-l-4 border-yellow-500">
                <p class="text-sm text-gray-500 font-bold">NO OWNER</p>
                <p class="text-2xl font-bold text-yellow-600" id="m_ownerless">0</p>
            </div>
            <div class="bg-white p-4 rounded shadow border-l-4 border-orange-500">
                <p class="text-sm text-gray-500 font-bold">HIGH RISK LINKED</p>
                <p class="text-2xl font-bold text-orange-600" id="m_high_risk">0</p>
            </div>
        </div>

        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold">Asset Inventory</h2>
            <div class="flex gap-2">
                <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded">Import JSON</button>
                <button onclick="document.getElementById('createAssetModal').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded">Add Asset</button>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-4">Asset Name</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Classification</th>
                        <th class="p-4">Owner</th>
                        <th class="p-4">Source ID</th>
                    </tr>
                </thead>
                <tbody id="assetsTableBody">
                    <tr><td colspan="5" class="p-4 text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Create Asset Modal -->
    <div id="createAssetModal" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">New Data Asset</h3>
            <form id="createAssetForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Asset Name</label>
                    <input type="text" name="asset_name" required class="w-full border rounded p-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Asset Type</label>
                    <input type="text" name="asset_type" placeholder="e.g. Relational Database" class="w-full border rounded p-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Classification</label>
                    <select name="classification" class="w-full border rounded p-2">
                        <option value="Public">Public</option>
                        <option value="Internal" selected>Internal</option>
                        <option value="Confidential">Confidential</option>
                        <option value="Restricted">Restricted</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Owner</label>
                    <input type="text" name="owner" class="w-full border rounded p-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Source/Silo ID</label>
                    <input type="number" name="source_id" placeholder="ID from Discovery Sources" class="w-full border rounded p-2">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('createAssetModal').classList.add('hidden')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-gray-900/50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Import Assets (JSON Array)</h3>
            <form id="importForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" value="import">
                <div class="mb-4">
                    <textarea name="assets_json" rows="10" class="w-full border rounded p-2 font-mono text-sm" placeholder='[{"asset_name": "New App DB", "classification": "Confidential"}]'></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded">Import</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function loadAssets() {
            const res = await fetch('/governance/backend/api/dspm/assets.php?action=list');
            const data = await res.json();
            const tbody = document.getElementById('assetsTableBody');
            tbody.innerHTML = '';
            if(data.data && data.data.length > 0) {
                data.data.forEach(a => {
                    let cClass = '';
                    if(a.classification === 'Restricted') cClass = 'text-red-600 font-bold';
                    else if(a.classification === 'Confidential') cClass = 'text-orange-500 font-bold';

                    tbody.innerHTML += `
                        <tr class="border-t">
                            <td class="p-4 font-medium">${a.asset_name}</td>
                            <td class="p-4">${a.asset_type || '-'}</td>
                            <td class="p-4 ${cClass}">${a.classification}</td>
                            <td class="p-4">${a.owner || '-'}</td>
                            <td class="p-4">${a.source_id || '-'}</td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center">No assets found.</td></tr>';
            }
        }

        async function loadMetrics() {
            const res = await fetch('/governance/backend/api/dspm/assets.php?action=metrics');
            const data = await res.json();
            if(data.status === 'success') {
                const m = data.data;
                document.getElementById('m_total').innerText = m.total_assets;
                document.getElementById('m_restricted').innerText = m.restricted_assets;
                document.getElementById('m_ownerless').innerText = m.ownerless_assets;
                document.getElementById('m_high_risk').innerText = m.assets_with_high_risks;
            }
        }

        document.getElementById('createAssetForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'create');
            const res = await fetch('/governance/backend/api/dspm/assets.php', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.status === 'success') {
                document.getElementById('createAssetModal').classList.add('hidden');
                e.target.reset();
                loadAssets();
                loadMetrics();
            } else {
                alert('Error creating asset');
            }
        });

        document.getElementById('importForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const res = await fetch('/governance/backend/api/dspm/assets.php', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.status === 'success') {
                document.getElementById('importModal').classList.add('hidden');
                e.target.reset();
                alert(`Import complete: ${data.data.imported} imported, ${data.data.failed} failed.`);
                loadAssets();
                loadMetrics();
            } else {
                alert('Error importing assets');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadAssets();
            loadMetrics();
        });
    </script>