<?php
// pages/consent-management.php

// 1. Database Connection check (MySQLi Compatible)
if (!isset($conn) && !isset($pdo)) {
    if (file_exists(__DIR__ . '/../includes/db.php')) {
        require_once __DIR__ . '/../includes/db.php';
    }
}

// Ensure $conn is the active MySQLi connection variable
if (!isset($conn) && isset($pdo) && $pdo instanceof mysqli) {
    $conn = $pdo;
}

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');

$message = '';
$error = '';

// 2. Handle Form Submission (Save Consent) - Fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_consent') {
    $user_identifier = trim($_POST['user_identifier'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = trim($_POST['status'] ?? 'Granted');

    if (!empty($user_identifier) && !empty($category)) {
        if (isset($conn) && $conn) {
            $db_status = 'opt_in'; // default
            if ($status === 'Revoked') $db_status = 'withdrawn';
            if ($status === 'Pending') $db_status = 'opt_out';

            // 1. Get or create data_subject
            $subject_id = null;
            $stmt_ds = $conn->prepare("SELECT id FROM data_subjects WHERE identifier_hash = ?");
            $stmt_ds->bind_param("s", $user_identifier);
            $stmt_ds->execute();
            $res_ds = $stmt_ds->get_result();
            if ($row = $res_ds->fetch_assoc()) {
                $subject_id = $row['id'];
            } else {
                $stmt_insert_ds = $conn->prepare("INSERT INTO data_subjects (identifier_hash, type) VALUES (?, 'customer')");
                $stmt_insert_ds->bind_param("s", $user_identifier);
                $stmt_insert_ds->execute();
                $subject_id = $conn->insert_id;
                $stmt_insert_ds->close();
            }
            $stmt_ds->close();

            // 2. Get or create consent_purpose
            $purpose_id = null;
            $stmt_cp = $conn->prepare("SELECT id FROM consent_purposes WHERE purpose_name = ?");
            $stmt_cp->bind_param("s", $category);
            $stmt_cp->execute();
            $res_cp = $stmt_cp->get_result();
            if ($row = $res_cp->fetch_assoc()) {
                $purpose_id = $row['id'];
            } else {
                $stmt_insert_cp = $conn->prepare("INSERT INTO consent_purposes (purpose_name) VALUES (?)");
                $stmt_insert_cp->bind_param("s", $category);
                $stmt_insert_cp->execute();
                $purpose_id = $conn->insert_id;
                $stmt_insert_cp->close();
            }
            $stmt_cp->close();

            // 3. Insert consent
            $stmt = $conn->prepare("INSERT INTO consents (data_subject_id, consent_purpose_id, policy_id, status, source) VALUES (?, ?, 1, ?, 'Manual')");
            if ($stmt) {
                $stmt->bind_param("iis", $subject_id, $purpose_id, $db_status);
                if ($stmt->execute()) {
                    $message = "Consent logged successfully!";
                    
                    if (function_exists('log_audit_event')) {
                        log_audit_event($conn, 'CONSENT_LOGGED', "Consent recorded for: $user_identifier ($category)");
                    }
                } else {
                    $error = "Execution Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database Query Error: " . $conn->error;
            }
        } else {
            $error = "Database connection not available.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// 3. Handle Revoke Action - Fallback
if (isset($_GET['revoke_id'])) {
    $revoke_id = intval($_GET['revoke_id']);
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("UPDATE consents SET status = 'withdrawn' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $revoke_id);
            if ($stmt->execute()) {
                $message = "Consent revoked successfully!";
            } else {
                $error = "Error revoking consent: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// 4. Fetch Existing Consents (MySQLi Compatible) - Fallback/Initial Load
$consents = [];
if (isset($conn) && $conn) {
    $query = "SELECT c.id, ds.identifier_hash AS user_identifier, p.purpose_name AS category, c.status AS db_status, c.created_at AS captured_at 
              FROM consents c 
              JOIN data_subjects ds ON c.data_subject_id = ds.id 
              JOIN consent_purposes p ON c.consent_purpose_id = p.id 
              ORDER BY c.created_at DESC";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = 'Granted';
            if ($row['db_status'] === 'withdrawn') $status = 'Revoked';
            if ($row['db_status'] === 'opt_out') $status = 'Pending';
            if ($row['db_status'] === 'expired') $status = 'Expired';
            
            $row['status'] = $status;
            $consents[] = $row;
        }
    }
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-green-600">check_circle</span>
            Consent Management
        </h1>
        <p class="text-sm text-gray-500">Capture, audit, and revoke user consent preferences across digital properties.</p>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div id="jsAlertBox" class="hidden p-4 mb-4 text-sm rounded-lg border"></div>

    <!-- ================= KPI CARDS ================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Total Consents</p>
            <h2 id="kpi-total" class="text-3xl font-bold text-blue-600 mt-2">1,248</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Granted</p>
            <h2 id="kpi-granted" class="text-3xl font-bold text-green-600 mt-2">926</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Revoked</p>
            <h2 id="kpi-revoked" class="text-3xl font-bold text-red-600 mt-2">214</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Pending</p>
            <h2 id="kpi-pending" class="text-3xl font-bold text-amber-500 mt-2">108</h2>
        </div>
    </div>

    <!-- ================= SEARCH & FILTER ================= -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Search & Filter Consents</h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input
                type="text"
                id="filter-search"
                placeholder="Search User..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">

            <select id="filter-category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Categories</option>
                <option value="Marketing Emails">Marketing Emails</option>
                <option value="Analytics Cookies">Analytics Cookies</option>
                <option value="Third-party Sharing">Third-party Sharing</option>
                <option value="Terms of Service">Terms of Service</option>
            </select>

            <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="opt_in">Granted</option>
                <option value="opt_out">Pending</option>
                <option value="withdrawn">Revoked</option>
            </select>

            <input
                type="date"
                id="filter-date"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm">

            <button
                id="btn-search"
                class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition">
                Search
            </button>
        </div>
    </div>

    <!-- ================= CONSENT ANALYTICS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-700 mb-5">Consent Distribution</h2>
            <div class="space-y-5">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Granted</span>
                        <span id="dist-granted-pct">74%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div id="dist-granted-bar" class="h-2 rounded-full bg-green-500 w-3/4"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Revoked</span>
                        <span id="dist-revoked-pct">18%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div id="dist-revoked-bar" class="h-2 rounded-full bg-red-500" style="width:18%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Pending</span>
                        <span id="dist-pending-pct">8%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div id="dist-pending-bar" class="h-2 rounded-full bg-yellow-400" style="width:8%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-700 mb-5">Consent Health</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Compliance Rate</p>
                    <h3 id="health-compliance" class="text-2xl font-bold text-green-600 mt-2">97%</h3>
                </div>

                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Categories</p>
                    <h3 id="health-categories" class="text-2xl font-bold text-blue-600 mt-2">4</h3>
                </div>

                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Awaiting Review</p>
                    <h3 id="health-pending" class="text-2xl font-bold text-yellow-600 mt-2">16</h3>
                </div>

                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Revoked Today</p>
                    <h3 id="health-revoked" class="text-2xl font-bold text-red-600 mt-2">9</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Consent Activity Log Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-md font-semibold text-gray-700">Consent Activity Log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3">User Identifier</th>
                        <th class="px-6 py-3">Category</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Captured At</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="consentTableBody">
                    <?php if (empty($consents)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">No consent records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consents as $c): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($c['user_identifier']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($c['category']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full font-medium <?php 
                                        echo $c['status'] === 'Granted' ? 'bg-green-100 text-green-700' : 
                                            ($c['status'] === 'Revoked' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); 
                                    ?>">
                                        <?php echo htmlspecialchars($c['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500"><?php echo htmlspecialchars($c['captured_at']); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($c['status'] !== 'Revoked'): ?>
                                        <a href="index.php?page=consent&revoke_id=<?php echo $c['id']; ?>" class="text-xs text-red-600 hover:underline">Revoke</a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="consentPagination" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <!-- Dynamic pagination will load here -->
        </div>
    </div>

    <!-- ================= CONSENT CATEGORIES ================= -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Consent Categories Overview</h2>
        <div class="space-y-5">
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Marketing Emails</span>
                    <span id="cat-marketing-pct">42%</span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div id="cat-marketing-bar" class="h-2 bg-blue-500 rounded-full" style="width:42%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Analytics Cookies</span>
                    <span id="cat-analytics-pct">31%</span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div id="cat-analytics-bar" class="h-2 bg-green-500 rounded-full" style="width:31%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Third-party Sharing</span>
                    <span id="cat-sharing-pct">17%</span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div id="cat-sharing-bar" class="h-2 bg-yellow-500 rounded-full" style="width:17%"></div>
                </div>
            </div>

            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span>Terms of Service</span>
                    <span id="cat-tos-pct">10%</span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full">
                    <div id="cat-tos-bar" class="h-2 bg-purple-500 rounded-full" style="width:10%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= RECENT CONSENT EVENTS ================= -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Recent Consent Events</h2>
        <div id="recentEventsList" class="space-y-4">
            <!-- Dynamic recent events will load here -->
            <div class="flex justify-between items-center border-b pb-3">
                <div>
                    <p class="font-medium text-gray-700">John Miller granted Marketing Emails</p>
                    <p class="text-xs text-gray-500">Today • 09:15 AM</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs bg-green-100 text-green-700">Granted</span>
            </div>

            <div class="flex justify-between items-center border-b pb-3">
                <div>
                    <p class="font-medium text-gray-700">Sarah revoked Analytics Cookies</p>
                    <p class="text-xs text-gray-500">Today • 08:42 AM</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs bg-red-100 text-red-700">Revoked</span>
            </div>
        </div>
    </div>

    <!-- ================= QUICK ACTIONS ================= -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button id="btn-record-consent" class="bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-lg font-medium transition">
                + Record Consent
            </button>

            <button id="btn-export-log" class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition">
                Export Log
            </button>

            <button id="btn-generate-report" class="bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-medium transition">
                Generate Report
            </button>

            <button id="btn-import-csv" class="bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-lg font-medium transition">
                Import CSV
            </button>
        </div>
    </div>
</div>

<!-- Modal: Record Consent -->
<div id="recordConsentModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative">
        <button id="closeConsentModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        <h2 class="text-lg font-bold text-gray-800 mb-4">+ Record User Consent</h2>
        
        <form id="recordConsentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">User Identifier (Email / ID)</label>
                <input type="text" name="user_identifier" required placeholder="user@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Consent Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Marketing Emails">Marketing Emails</option>
                    <option value="Analytics Cookies">Analytics Cookies</option>
                    <option value="Third-party Sharing">Third-party Sharing</option>
                    <option value="Terms of Service">Terms of Service</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Initial Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Granted">Granted</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Collection Method</label>
                <select name="collection_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="web_portal">Web Portal</option>
                    <option value="mobile_app">Mobile App</option>
                    <option value="api">API Integration</option>
                    <option value="crm">CRM Platform</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Expiration Date (Optional)</label>
                <input type="date" name="expires_at" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" id="cancelConsentModalBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Save Consent</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Import CSV -->
<div id="importCsvModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative">
        <button id="closeImportModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        <h2 class="text-lg font-bold text-gray-800 mb-4">Import Consent CSV</h2>
        
        <form id="importCsvForm" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-400 mt-2">
                    Expected columns: <code>user_identifier</code> (or <code>email</code>), <code>category</code> (or <code>consent_category</code>), <code>status</code>.
                </p>
            </div>

            <div id="importResults" class="hidden max-h-40 overflow-y-auto p-3 text-xs bg-gray-50 border rounded-lg space-y-1"></div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" id="cancelImportModalBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-orange-500 hover:bg-orange-600">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Consent History Timeline -->
<div id="consentHistoryModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[85vh] flex flex-col">
        <button id="closeHistoryModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">history</span>
            Consent Audit History
        </h2>
        <p id="historyModalSubtitle" class="text-xs text-gray-500 mb-4">Audit log timeline of status changes.</p>
        
        <div id="historyTimelineContainer" class="overflow-y-auto flex-1 pr-2 space-y-4">
            <!-- Dynamic timeline will load here -->
        </div>

        <div class="flex justify-end pt-4 border-t mt-4">
            <button type="button" id="cancelHistoryModalBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Modify Preference -->
<div id="modifyPreferenceModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative">
        <button id="closeModifyModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-600">edit_note</span>
            Modify Consent Preference
        </h2>
        <p id="modifyModalSubtitle" class="text-xs text-gray-500 mb-4">Update consent status with mandatory audit reason.</p>

        <form id="modifyPreferenceForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="consent_id" id="modify_consent_id">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">New Consent Status</label>
                <select name="status" id="modify_status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <option value="Granted">Granted (Opt-In)</option>
                    <option value="Pending">Pending (Opt-Out)</option>
                    <option value="Revoked">Revoked (Withdrawn)</option>
                    <option value="Expired">Expired</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Reason for Modification <span class="text-red-500">*</span></label>
                <textarea name="reason" id="modify_reason" required rows="3" placeholder="Specify why the consent preference is being updated..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" id="cancelModifyModalBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">Update Preference</button>
            </div>
        </form>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/consent-management.js"></script>