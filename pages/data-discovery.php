<?php
// governance/pages/data-discovery.php
// Enterprise Personal Data Discovery & DSPM Engine
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
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Personal Data Discovery & DSPM</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Identify, classify, and protect PII/SPII data assets across cloud, database, SaaS, and on-premise infrastructure.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <button onclick="openAddSourceModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                + Add Data Source
            </button>
            <button onclick="openTriggerScanModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                <span class="material-symbols-outlined text-sm mr-1 text-indigo-600">travel_explore</span> Run Scan
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
                <span class="material-symbols-outlined text-base">dashboard</span> Dashboard Overview
            </button>
            <button onclick="switchTab('sources')" data-tab="sources" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">dataset</span> Source Management
            </button>
            <button onclick="switchTab('scan')" data-tab="scan" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">radar</span> Discovery Scan Engine
            </button>
            <button onclick="switchTab('history')" data-tab="history" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">history</span> Scan History
            </button>
            <button onclick="switchTab('findings')" data-tab="findings" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">saved_search</span> Sensitive Data Detection
            </button>
            <button onclick="switchTab('reports')" data-tab="reports" class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 border-transparent font-medium text-xs md:text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">download</span> Reports & Export
            </button>
        </nav>
    </div>

    <!-- TAB 1: DASHBOARD OVERVIEW (Row 48) -->
    <div id="tab-panel-dashboard" class="tab-panel space-y-6">
        <!-- 4 KPI CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Connected Sources</span>
                <div class="mt-2 text-2xl font-bold text-gray-900" id="dash-total-sources">0</div>
            </div>
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600">PII Records Found</span>
                <div class="mt-2 text-2xl font-bold text-indigo-600" id="dash-pii-records">0M</div>
            </div>
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-600">Sensitive Files</span>
                <div class="mt-2 text-2xl font-bold text-rose-600" id="dash-sensitive-files">0</div>
            </div>
            <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Compliance Score</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600" id="dash-compliance-score">100%</div>
            </div>
        </div>

        <!-- DATA CLASSIFICATION OVERVIEW -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-600">pie_chart</span> Data Classification Overview
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="border border-gray-200 rounded-xl p-4 text-center bg-gray-50">
                    <span class="material-symbols-outlined text-indigo-600 text-3xl mb-1">person</span>
                    <div class="font-bold text-lg text-gray-900" id="class-personal">0</div>
                    <div class="text-xs text-gray-500 font-medium">Personal Records</div>
                </div>
                <div class="border border-gray-200 rounded-xl p-4 text-center bg-gray-50">
                    <span class="material-symbols-outlined text-rose-600 text-3xl mb-1">lock</span>
                    <div class="font-bold text-lg text-gray-900" id="class-sensitive">0</div>
                    <div class="text-xs text-gray-500 font-medium">Sensitive Personal Data</div>
                </div>
                <div class="border border-gray-200 rounded-xl p-4 text-center bg-gray-50">
                    <span class="material-symbols-outlined text-emerald-600 text-3xl mb-1">account_balance</span>
                    <div class="font-bold text-lg text-gray-900" id="class-financial">0</div>
                    <div class="text-xs text-gray-500 font-medium">Financial Records</div>
                </div>
                <div class="border border-gray-200 rounded-xl p-4 text-center bg-gray-50">
                    <span class="material-symbols-outlined text-amber-600 text-3xl mb-1">medical_services</span>
                    <div class="font-bold text-lg text-gray-900" id="class-health">0</div>
                    <div class="text-xs text-gray-500 font-medium">Health Records</div>
                </div>
            </div>
        </div>

        <!-- CONNECTED SOURCES GRID -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-sm">Active Connected Data Sources</h3>
                <button onclick="switchTab('sources')" class="text-xs text-indigo-600 font-bold hover:underline">Manage All Sources &rarr;</button>
            </div>
            <div id="dashSourcesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="col-span-3 text-center py-8 text-gray-500 text-xs">Loading data sources...</div>
            </div>
        </div>
    </div>

    <!-- TAB 2: SOURCE MANAGEMENT (Row 49) -->
    <div id="tab-panel-sources" class="tab-panel hidden space-y-6">
        <!-- SEARCH & FILTER TOOLBAR -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <input type="text" id="filter-source-search" onkeyup="loadSources()" placeholder="Search Source Name, Host, URI..." class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                <select id="filter-source-type" onchange="loadSources()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Source Types</option>
                    <option value="database">Database</option>
                    <option value="cloud_storage">Cloud Storage</option>
                    <option value="nosql">NoSQL</option>
                    <option value="saas">SaaS</option>
                    <option value="crm">CRM</option>
                </select>
                <select id="filter-source-risk" onchange="loadSources()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Risk Levels</option>
                    <option value="high">High Risk</option>
                    <option value="medium">Medium Risk</option>
                    <option value="low">Low Risk</option>
                </select>
                <select id="filter-source-status" onchange="loadSources()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- DATATABLE -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Data Sources Ledger</h3>
                <span id="sourcesCountInfo" class="text-xs text-gray-500 font-medium">Loading sources...</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="p-3.5 w-10"><input type="checkbox" id="selectAllSources" onclick="document.querySelectorAll('.source-checkbox').forEach(c => c.checked = this.checked)" class="rounded"></th>
                            <th class="p-3.5">Source Name</th>
                            <th class="p-3.5">Type</th>
                            <th class="p-3.5">Connection URI</th>
                            <th class="p-3.5">Risk Exposure</th>
                            <th class="p-3.5">Discovered PII</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sourcesTableBody">
                        <tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs">Loading data sources...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: DISCOVERY SCAN ENGINE (Row 50) -->
    <div id="tab-panel-scan" class="tab-panel hidden space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">radar</span> Active Scan Engine Telemetry
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Live data scanner scanning databases, storage buckets, and document repositories.</p>
                </div>
                <span id="engine-status-badge" class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 uppercase">IDLE</span>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between text-xs font-semibold">
                    <span class="text-gray-600">Current Target: <strong id="engine-target-source" class="text-gray-900">PostgreSQL Production</strong></span>
                    <span id="engine-progress-pct" class="text-indigo-600 font-bold">100%</span>
                </div>
                <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden">
                    <div id="engine-progress-bar" class="bg-indigo-600 h-full rounded-full transition-all duration-300" style="width: 100%"></div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200 text-center">
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold">Items Scanned</div>
                        <div class="font-bold text-lg text-gray-900" id="engine-items-scanned">48,200</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold">PII Found</div>
                        <div class="font-bold text-lg text-indigo-600" id="engine-pii-found">1,250,000</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold">Sensitive Files</div>
                        <div class="font-bold text-lg text-rose-600" id="engine-files-found">142</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase font-bold">Duration</div>
                        <div class="font-bold text-lg text-gray-900" id="engine-duration">345s</div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button onclick="openTriggerScanModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg transition">+ New Scan</button>
                <button onclick="controlScan('pause')" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-lg transition">Pause</button>
                <button onclick="controlScan('resume')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition">Resume</button>
                <button onclick="controlScan('cancel')" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-lg transition">Cancel</button>
            </div>
        </div>
    </div>

    <!-- TAB 4: SCAN HISTORY (Row 51) -->
    <div id="tab-panel-history" class="tab-panel hidden space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Historical Scan Logs</h3>
                <button onclick="loadScanHistory()" class="text-xs text-indigo-600 font-semibold hover:underline">Refresh Logs</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="p-3.5">Scan ID</th>
                            <th class="p-3.5">Target Source</th>
                            <th class="p-3.5">Type</th>
                            <th class="p-3.5">Started At</th>
                            <th class="p-3.5">Duration</th>
                            <th class="p-3.5">PII Discovered</th>
                            <th class="p-3.5">Status</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <tr><td colspan="7" class="text-center py-8 text-gray-500 text-xs">Loading scan history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 5: SENSITIVE DATA DETECTION (Row 52) -->
    <div id="tab-panel-findings" class="tab-panel hidden space-y-6">
        <!-- TOOLBAR -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" id="filter-finding-search" onkeyup="loadFindings()" placeholder="Search Data Element Name, Path..." class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                <select id="filter-finding-category" onchange="loadFindings()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Categories</option>
                    <option value="Personal">Personal</option>
                    <option value="Sensitive">Sensitive</option>
                    <option value="Financial">Financial</option>
                    <option value="Health">Health</option>
                </select>
                <select id="filter-finding-severity" onchange="loadFindings()" class="border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
        </div>

        <!-- DATATABLE -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Discovered PII/SPII Findings Ledger</h3>
                <button onclick="loadFindings()" class="text-xs text-indigo-600 font-semibold hover:underline">Refresh Findings</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                            <th class="p-3.5">Element Name</th>
                            <th class="p-3.5">Category</th>
                            <th class="p-3.5">Location Path</th>
                            <th class="p-3.5">Record Count</th>
                            <th class="p-3.5">Risk Severity</th>
                            <th class="p-3.5">Confidence</th>
                        </tr>
                    </thead>
                    <tbody id="findingsTableBody">
                        <tr><td colspan="6" class="text-center py-8 text-gray-500 text-xs">Loading sensitive findings...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 6: REPORTS & EXPORT CENTER (Rows 53 & 54) -->
    <div id="tab-panel-reports" class="tab-panel hidden space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl mx-auto space-y-5">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <span class="material-symbols-outlined text-indigo-600">summarize</span> Generate Data Discovery Report
                </h3>
                <p class="text-xs text-gray-500 mt-1">Export complete inventory and sensitive data detection findings for compliance audit.</p>
            </div>

            <form onsubmit="submitReportGenerator(event)" class="space-y-4 text-xs md:text-sm">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Report Type</label>
                    <select id="report_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="summary">Data Discovery Summary Inventory</option>
                        <option value="sensitive_findings">Detailed Sensitive PII/SPII Findings</option>
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
                    <label class="block font-semibold text-gray-700 mb-1">Category Filter</label>
                    <select id="report_category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">All Categories</option>
                        <option value="Personal">Personal Data</option>
                        <option value="Sensitive">Sensitive Data</option>
                        <option value="Financial">Financial Data</option>
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

<!-- MODAL 1: Add Data Source -->
<div id="addSourceModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeAddSourceModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Connect Data Source</h3>
        <p class="text-xs text-gray-500 mb-4">Register a database, cloud storage, SaaS, or CRM endpoint for automated DSPM scanning.</p>

        <form id="addSourceForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Data Source Name</label>
                <input type="text" name="name" required placeholder="PostgreSQL Customer DB" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Source Type</label>
                    <select name="source_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="database">Database (SQL)</option>
                        <option value="cloud_storage">Cloud Storage (S3/Blob)</option>
                        <option value="nosql">NoSQL Document Store</option>
                        <option value="saas">SaaS Workspace</option>
                        <option value="crm">CRM System</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Environment</label>
                    <select name="environment" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="production">Production</option>
                        <option value="staging">Staging</option>
                        <option value="development">Development</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                    <label class="block font-semibold text-gray-700 mb-1">Connection URI / Host</label>
                    <input type="text" name="connection_uri" required placeholder="db.prod.internal" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Port</label>
                    <input type="text" name="host_port" placeholder="5432" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Risk Exposure Level</label>
                <select name="risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="high">High Risk Exposure</option>
                    <option value="medium" selected>Medium Risk Exposure</option>
                    <option value="low">Low Risk Exposure</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Primary customer transactional database..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeAddSourceModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Data Source</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Edit Data Source -->
<div id="editSourceModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeEditSourceModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit Data Source</h3>
        <p class="text-xs text-gray-500 mb-4">Modify connection parameters or exposure settings.</p>

        <form id="editSourceForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="edit_source_id">

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Data Source Name</label>
                <input type="text" name="name" id="edit_source_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Source Type</label>
                    <select name="source_type" id="edit_source_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="database">Database (SQL)</option>
                        <option value="cloud_storage">Cloud Storage (S3/Blob)</option>
                        <option value="nosql">NoSQL Document Store</option>
                        <option value="saas">SaaS Workspace</option>
                        <option value="crm">CRM System</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Environment</label>
                    <select name="environment" id="edit_environment" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="production">Production</option>
                        <option value="staging">Staging</option>
                        <option value="development">Development</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                    <label class="block font-semibold text-gray-700 mb-1">Connection URI / Host</label>
                    <input type="text" name="connection_uri" id="edit_connection_uri" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Port</label>
                    <input type="text" name="host_port" id="edit_host_port" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Risk Exposure Level</label>
                <select name="risk_level" id="edit_risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="high">High Risk Exposure</option>
                    <option value="medium">Medium Risk Exposure</option>
                    <option value="low">Low Risk Exposure</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Description</label>
                <textarea name="description" id="edit_description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeEditSourceModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Update Data Source</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: Delete Source Confirmation -->
<div id="deleteSourceModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Delete Data Source</h3>
        <p class="text-xs text-gray-500 mb-4">Are you sure you want to remove this data source? Historical scan data will be archived.</p>
        <input type="hidden" id="delete_source_id">
        <div class="flex justify-end gap-3 border-t pt-3">
            <button onclick="closeDeleteSourceModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg">Cancel</button>
            <button onclick="submitDeleteSource()" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700">Confirm Delete</button>
        </div>
    </div>
</div>

<!-- MODAL 4: Trigger Discovery Scan -->
<div id="triggerScanModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <button onclick="closeTriggerScanModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Configure & Run Discovery Scan</h3>
        <p class="text-xs text-gray-500 mb-4">Select target environment and scan depth options.</p>

        <form id="triggerScanForm" class="space-y-4 text-xs md:text-sm">
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Target Data Source</label>
                <select name="source_id" id="scan_target_source_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">Select Data Source...</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Scan Type / Depth</label>
                <select name="scan_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="full">Full Deep Discovery (All Tables & Files)</option>
                    <option value="quick">Quick Delta Scan (Recent Changes Only)</option>
                    <option value="deep">Deep Sampling Inspection</option>
                </select>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeTriggerScanModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Start Scan Engine</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/data-discovery.js?v=<?= time() ?>"></script>