<?php
// governance/pages/data-mapping.php
// Enterprise Data Mapping & Flow Inventory Engine
include_once __DIR__ . '/../includes/bottom-nav.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = htmlspecialchars($_SESSION['csrf_token']);
?>

<div class="space-y-6 max-w-7xl mx-auto p-4 md:p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Data Mapping & Flow Inventory</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Track how personal data travels across internal services, databases, cloud repositories, and third-party API destinations.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <button onclick="openAddFlowModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                + Map Data Flow
            </button>
            <button onclick="openAddActivityModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                + Add Activity
            </button>
            <div class="relative group">
                <button class="inline-flex items-center justify-center px-3 py-2 text-xs md:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                    Export Report ▾
                </button>
                <div class="hidden group-hover:block absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-30 overflow-hidden py-1">
                    <button onclick="triggerExport('csv')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">csv</span> Export CSV
                    </button>
                    <button onclick="triggerExport('excel')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">table_view</span> Export Excel
                    </button>
                    <button onclick="triggerExport('pdf')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span> Print / PDF Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Bar -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-4 md:space-x-8 overflow-x-auto" aria-label="Tabs">
            <button onclick="switchTab('dashboard')" data-tab="dashboard" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-indigo-600 font-bold text-xs md:text-sm text-indigo-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">dashboard</span> Mapping Dashboard
            </button>
            <button onclick="switchTab('activities')" data-tab="activities" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">fact_check</span> Processing Activities
            </button>
            <button onclick="switchTab('diagram')" data-tab="diagram" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">account_tree</span> Flow Diagram
            </button>
            <button onclick="switchTab('search')" data-tab="search" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">search</span> Search Mapping
            </button>
            <button onclick="switchTab('reports')" data-tab="reports" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">download</span> Reports & Export
            </button>
        </nav>
    </div>

    <!-- TAB 1: MAPPING DASHBOARD (Row 56) -->
    <div id="tab-panel-dashboard" class="tab-panel space-y-6">
        <!-- 4 KPI CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Data Flows</span>
                <div class="mt-2 text-2xl font-bold text-indigo-600" id="dash-total-flows">0</div>
            </div>
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Connected Systems</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600" id="dash-connected-systems">0</div>
            </div>
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-600">High Risk Flows</span>
                <div class="mt-2 text-2xl font-bold text-rose-600" id="dash-high-risk-flows">0</div>
            </div>
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-600">Encrypted Flows</span>
                <div class="mt-2 text-2xl font-bold text-purple-600" id="dash-encrypted-pct">100%</div>
            </div>
        </div>

        <!-- MAPPING STATISTICS & ENCRYPTION COVERAGE -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Encryption Coverage -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">lock</span> Security & Encryption Coverage
                </h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <div class="flex justify-between font-semibold mb-1">
                            <span class="text-gray-700">Encrypted in Transit & Rest</span>
                            <span id="val-encrypted" class="text-emerald-600 font-bold">71%</span>
                        </div>
                        <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div id="bar-encrypted" class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: 71%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-semibold mb-1">
                            <span class="text-gray-700">In Transit Only</span>
                            <span id="val-transit" class="text-amber-600 font-bold">29%</span>
                        </div>
                        <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div id="bar-transit" class="bg-amber-500 h-full rounded-full transition-all duration-300" style="width: 29%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between font-semibold mb-1">
                            <span class="text-gray-700">Plaintext / Unencrypted</span>
                            <span id="val-plain" class="text-rose-600 font-bold">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div id="bar-plain" class="bg-rose-500 h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flow Distribution -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">pie_chart</span> Flow Topology Distribution
                </h3>
                <table class="w-full text-left text-xs border-collapse">
                    <tbody class="divide-y divide-gray-100">
                        <tr class="py-2">
                            <td class="py-2.5 text-gray-600">Internal Core Databases</td>
                            <td class="py-2.5 text-right font-bold text-gray-900">64%</td>
                        </tr>
                        <tr class="py-2">
                            <td class="py-2.5 text-gray-600">Cloud Storage (S3 / Blob)</td>
                            <td class="py-2.5 text-right font-bold text-gray-900">22%</td>
                        </tr>
                        <tr class="py-2">
                            <td class="py-2.5 text-gray-600">Third-Party SaaS APIs</td>
                            <td class="py-2.5 text-right font-bold text-gray-900">14%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RECENT MAPPING ACTIVITY -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Recent Data Mapping Activity</h3>
                <button onclick="switchTab('search')" class="text-xs text-indigo-600 font-bold hover:underline">View All Mapped Flows &rarr;</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="p-3">Date</th>
                            <th class="p-3">Source System</th>
                            <th class="p-3">Target System</th>
                            <th class="p-3">Pipeline / Payload</th>
                            <th class="p-3">Risk Rating</th>
                        </tr>
                    </thead>
                    <tbody id="recentActivityTableBody">
                        <tr><td colspan="5" class="text-center py-6 text-gray-500 text-xs">Loading activity log...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: PROCESSING ACTIVITIES (Row 57) -->
    <div id="tab-panel-activities" class="tab-panel hidden space-y-6">
        <!-- SEARCH & FILTER TOOLBAR -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" id="filter-act-search" onkeyup="loadActivities()" placeholder="Search Activity Name, Categories, Controller..." class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                <select id="filter-act-dept" onchange="loadActivities()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Departments</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Finance">Finance</option>
                    <option value="Human Resources">Human Resources</option>
                    <option value="Customer Success">Customer Success</option>
                    <option value="Marketing">Marketing</option>
                </select>
                <select id="filter-act-risk" onchange="loadActivities()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Risk Levels</option>
                    <option value="High">High Risk</option>
                    <option value="Medium">Medium Risk</option>
                    <option value="Low">Low Risk</option>
                </select>
            </div>
        </div>

        <!-- DATATABLE -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Processing Activities Registry (ROPA Integration)</h3>
                <span id="activitiesCountInfo" class="text-xs text-gray-500 font-medium">Loading activities...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="p-3.5">Activity Name</th>
                            <th class="p-3.5">Department</th>
                            <th class="p-3.5">Controller / Processor</th>
                            <th class="p-3.5">Data Categories</th>
                            <th class="p-3.5">Legal Basis</th>
                            <th class="p-3.5">Retention</th>
                            <th class="p-3.5">Risk Level</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activitiesTableBody">
                        <tr><td colspan="8" class="text-center py-8 text-gray-500 text-xs">Loading processing activities...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: FLOW DIAGRAM (Row 58) -->
    <div id="tab-panel-diagram" class="tab-panel hidden space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">account_tree</span> Interactive Data Flow Topology
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Visualization of data lineage: Origin → Pipeline Payload → Target Storage / Recipient.</p>
                </div>
                <button onclick="loadFlowDiagram()" class="text-xs text-indigo-600 font-semibold hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">refresh</span> Refresh Topology
                </button>
            </div>

            <div id="diagramCanvas" class="min-h-[300px]">
                <div class="text-center py-12 text-gray-500 text-xs">Loading flow diagram...</div>
            </div>
        </div>
    </div>

    <!-- TAB 4: SEARCH MAPPING (Row 59) -->
    <div id="tab-panel-search" class="tab-panel hidden space-y-6">
        <!-- TOOLBAR -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" id="filter-flow-search" onkeyup="loadFlows()" placeholder="Search Source or Target System, Payload..." class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                <select id="filter-flow-risk" onchange="loadFlows()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Risk Levels</option>
                    <option value="High">High Risk</option>
                    <option value="Medium">Medium Risk</option>
                    <option value="Low">Low Risk</option>
                </select>
                <select id="filter-flow-encryption" onchange="loadFlows()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Encryption Statuses</option>
                    <option value="Encrypted">Encrypted in Transit & Rest</option>
                    <option value="Transit">In Transit Only</option>
                    <option value="None">None / Plaintext</option>
                </select>
            </div>
        </div>

        <!-- DATATABLE -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Data Pipeline Mapping Registry</h3>
                <span id="flowsCountInfo" class="text-xs text-gray-500 font-medium">Loading flows...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="p-3.5">Source System</th>
                            <th class="p-3.5">Target System</th>
                            <th class="p-3.5">Payload Data Types</th>
                            <th class="p-3.5">Transfer Method</th>
                            <th class="p-3.5">Encryption Security</th>
                            <th class="p-3.5">Risk Rating</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="flowsTableBody">
                        <tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs">Loading data flows...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 5: REPORTS & EXPORT CENTER (Row 60) -->
    <div id="tab-panel-reports" class="tab-panel hidden space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl mx-auto space-y-5">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">summarize</span> Generate Data Mapping Report
                </h3>
                <p class="text-xs text-gray-500 mt-1">Export complete data flow inventory and processing activity registry for compliance audit.</p>
            </div>

            <form onsubmit="submitReportGenerator(event)" class="space-y-4 text-xs md:text-sm">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Report Type</label>
                    <select id="report_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="flows">Data Pipelines & Flow Inventory</option>
                        <option value="activities">Processing Activities Registry (ROPA)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Export Format</label>
                    <select id="report_format" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="csv">CSV Spreadsheet (.csv)</option>
                        <option value="excel">Excel Workbook (.excel)</option>
                        <option value="pdf">PDF / Printable Report (.pdf)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Risk Rating Filter</label>
                    <select id="report_risk" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">All Risk Ratings</option>
                        <option value="High">High Risk Only</option>
                        <option value="Medium">Medium Risk Only</option>
                        <option value="Low">Low Risk Only</option>
                    </select>
                </div>

                <div class="pt-3 border-t">
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                        Generate & Download Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 1: Add Processing Activity -->
<div id="addActivityModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeAddActivityModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Register Processing Activity</h3>
        <p class="text-xs text-gray-500 mb-4">Add a new processing activity record for ROPA and Data Mapping.</p>

        <form id="addActivityForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Activity Name</label>
                <input type="text" name="activity_name" required placeholder="User Authentication & Profile Management" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Department</label>
                    <select name="department" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Engineering">Engineering</option>
                        <option value="Finance">Finance</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Customer Success">Customer Success</option>
                        <option value="Marketing">Marketing</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Risk Level</label>
                    <select name="risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="High">High Risk</option>
                        <option value="Medium" selected>Medium Risk</option>
                        <option value="Low">Low Risk</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Business Purpose</label>
                <textarea name="purpose" rows="2" placeholder="Describe the business purpose..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Data Controller</label>
                    <input type="text" name="data_controller" value="PrivacyHQ Inc" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Processor</label>
                    <input type="text" name="processor" placeholder="AWS / Stripe" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Data Categories</label>
                <input type="text" name="data_categories" placeholder="Identity, Email, Billing, IP Address" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Legal Basis</label>
                    <select name="legal_basis" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Contractual Necessity">Contractual Necessity</option>
                        <option value="Legitimate Interest">Legitimate Interest</option>
                        <option value="Legal Obligation">Legal Obligation</option>
                        <option value="Consent">Consent</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Retention Period</label>
                    <input type="text" name="retention_period" value="3 Years" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeAddActivityModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Edit Processing Activity -->
<div id="editActivityModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeEditActivityModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit Processing Activity</h3>
        <p class="text-xs text-gray-500 mb-4">Update legal basis, data categories, or risk levels.</p>

        <form id="editActivityForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="edit_act_id">

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Activity Name</label>
                <input type="text" name="activity_name" id="edit_activity_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Department</label>
                    <select name="department" id="edit_department" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Engineering">Engineering</option>
                        <option value="Finance">Finance</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Customer Success">Customer Success</option>
                        <option value="Marketing">Marketing</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Risk Level</label>
                    <select name="risk_level" id="edit_risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="High">High Risk</option>
                        <option value="Medium">Medium Risk</option>
                        <option value="Low">Low Risk</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Business Purpose</label>
                <textarea name="purpose" id="edit_purpose" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Data Controller</label>
                    <input type="text" name="data_controller" id="edit_data_controller" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Processor</label>
                    <input type="text" name="processor" id="edit_processor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Data Categories</label>
                <input type="text" name="data_categories" id="edit_data_categories" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Legal Basis</label>
                    <select name="legal_basis" id="edit_legal_basis" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Contractual Necessity">Contractual Necessity</option>
                        <option value="Legitimate Interest">Legitimate Interest</option>
                        <option value="Legal Obligation">Legal Obligation</option>
                        <option value="Consent">Consent</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Retention Period</label>
                    <input type="text" name="retention_period" id="edit_retention_period" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeEditActivityModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Update Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: Delete Activity Confirmation -->
<div id="deleteActivityModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Delete Processing Activity</h3>
        <p class="text-xs text-gray-500 mb-4">Are you sure you want to remove this processing activity record?</p>
        <input type="hidden" id="delete_act_id">
        <div class="flex justify-end gap-3 border-t pt-3">
            <button onclick="closeDeleteActivityModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg">Cancel</button>
            <button onclick="submitDeleteActivity()" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700">Confirm Delete</button>
        </div>
    </div>
</div>

<!-- MODAL 4: Map Data Flow -->
<div id="addFlowModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeAddFlowModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Map Data Flow Pipeline</h3>
        <p class="text-xs text-gray-500 mb-4">Register a data transfer pipeline between origin and target systems.</p>

        <form id="addFlowForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Associated Processing Activity (Optional)</label>
                <select name="processing_activity_id" id="flow_processing_activity_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">Select Processing Activity...</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Source System (Origin)</label>
                    <input type="text" name="source_system" required placeholder="Web Portal Frontend" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Target System (Destination)</label>
                    <input type="text" name="target_system" required placeholder="PostgreSQL Production" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Data Types / Payload</label>
                <input type="text" name="data_type" placeholder="User Email, PII, Billing Data" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Transfer Protocol</label>
                    <input type="text" name="transfer_method" value="REST API (HTTPS)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Encryption Security</label>
                    <select name="encryption_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Encrypted in Transit & Rest">Encrypted in Transit & Rest</option>
                        <option value="In Transit Only">In Transit Only</option>
                        <option value="None / Plaintext">None / Plaintext</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Risk Rating</label>
                <select name="risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="Low" selected>Low Risk</option>
                    <option value="Medium">Medium Risk</option>
                    <option value="High">High Risk</option>
                </select>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeAddFlowModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Map Pipeline</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/data-mapping.js?v=<?= time() ?>"></script>