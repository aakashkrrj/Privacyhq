<?php
// governance/pages/reports.php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header('Location: login.php');
    exit;
}

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="space-y-lg max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-md">
        <div>
            <div class="flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[32px]">analytics</span>
                <h1 class="text-display font-display text-primary leading-tight">Reports &amp; Automated Scheduling Engine</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Generate custom compliance reports, analyze visual telemetry, export PDF/Excel audits, and automate scheduled report digests.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openGenerateModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm cursor-pointer">
                + Generate Report
            </button>
            <button onclick="openScheduleModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">schedule</span> Schedule Report
            </button>
            <button onclick="runDueSchedulesNow()" title="Trigger cron runner for due scheduled reports" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">play_circle</span> Run Schedules
            </button>
            <button onclick="exportReportsInventory('csv')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export Inventory
            </button>
        </div>
    </div>

    <!-- Executive Dashboard KPI Cards (Row 116) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Reports</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Generated Reports</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-generated">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-blue-600">Current Period (30d)</span>
            <div class="mt-base text-display font-bold text-blue-600" id="kpi-period">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-indigo-600">Active Schedules</span>
            <div class="mt-base text-display font-bold text-indigo-600" id="kpi-scheduled">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Pending / Queued</span>
            <div class="mt-base text-display font-bold text-amber-600" id="kpi-pending">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Failed Runs</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-failed">...</div>
        </div>
    </div>

    <!-- Charts & Visual Telemetry Section (Row 121) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <!-- Chart 1: Category Distribution -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">pie_chart</span>
                Reports by Module Category
            </h3>
            <div id="dist-category" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading module category distribution...</div>
            </div>
        </div>

        <!-- Chart 2: Monthly Generation Trend -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">trending_up</span>
                Monthly Generation Velocity
            </h3>
            <div id="dist-trend" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading monthly trend graph...</div>
            </div>
        </div>

        <!-- Chart 3: Execution Type Distribution -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">donut_small</span>
                Execution Type (Manual vs Scheduled)
            </h3>
            <div id="dist-execution-type" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading execution type breakdown...</div>
            </div>
        </div>
    </div>

    <!-- Main Reports Inventory Table Card (Row 117 & Row 120) -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">Generated Reports Inventory</h2>
            
            <!-- Filters (Row 120) -->
            <form id="reportSearchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search code, title, module...">
                <select id="filter-type" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Module Reports</option>
                    <option value="ROPA Inventory">ROPA Inventory</option>
                    <option value="Risk Register">Risk Register</option>
                    <option value="DSR Performance">DSR Performance</option>
                    <option value="Policies Report">Policies Report</option>
                    <option value="Vendor Risk">Vendor Risk</option>
                    <option value="Incident Summary">Incident Summary</option>
                </select>
                <select id="filter-execution-type" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Execution Types</option>
                    <option value="manual">Manual</option>
                    <option value="scheduled">Scheduled</option>
                </select>
                <select id="filter-status" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">Search</button>
                <button type="button" onclick="clearReportFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition cursor-pointer">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">Code</th>
                        <th class="px-lg py-md">Report Title & Module</th>
                        <th class="px-lg py-md">Execution Type</th>
                        <th class="px-lg py-md">Format & Size</th>
                        <th class="px-lg py-md">Date Generated</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="7" class="px-lg py-md text-center text-on-surface-variant">Loading report executions...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="reportPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="text-caption text-on-surface-variant">Showing report records</div>
        </div>
    </div>

    <!-- Scheduled Reports Register Card (Row 122) -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex justify-between items-center">
            <div>
                <h3 class="font-bold text-on-surface text-title-md flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-emerald-600 text-[22px]">update</span>
                    Active & Scheduled Report Digest Rules
                </h3>
                <p class="text-caption text-on-surface-variant mt-0.5">Recurring daily, weekly, and monthly automated governance report schedules.</p>
            </div>
            <button onclick="openScheduleModal()" class="px-3.5 py-1.5 bg-emerald-600 text-white font-semibold text-xs rounded-lg hover:bg-emerald-700 transition cursor-pointer">
                + New Schedule
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">Code</th>
                        <th class="px-lg py-md">Schedule Title & Module</th>
                        <th class="px-lg py-md">Frequency</th>
                        <th class="px-lg py-md">Format & Recipients</th>
                        <th class="px-lg py-md">Next Run Target</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="schedulesTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="7" class="px-lg py-md text-center text-on-surface-variant">Loading report schedules...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Generate Custom Report (Row 117) -->
<div id="generateReportModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Generate Live Compliance Report</h3>
            <button onclick="closeGenerateModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="generateReportForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Select Report Category / Module *</label>
                <select name="report_type" id="gen_report_type" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="ROPA Inventory">ROPA Inventory Report (Article 30)</option>
                    <option value="Risk Register">Risk Register & Mitigation Report</option>
                    <option value="DSR Performance">Data Subject Rights (DSR) Performance</option>
                    <option value="Policies Report">Policy Governance & Expiry Report</option>
                    <option value="Vendor Risk">Vendor Risk & DPA Compliance Summary</option>
                    <option value="Incident Summary">Security Incident Management Report</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Custom Report Title *</label>
                <input type="text" name="title" id="gen_title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Quarterly GDPR Compliance Executive Audit">
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Export Format *</label>
                    <select name="file_format" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="pdf">PDF Document (.pdf)</option>
                        <option value="excel">Excel / CSV Spreadsheet (.csv)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Department Filter</label>
                    <select name="department" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="">All Departments</option>
                        <option value="Legal & Governance">Legal & Governance</option>
                        <option value="Engineering & IT">Engineering & IT</option>
                        <option value="Human Resources">Human Resources</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?= date('Y-m-d') ?>" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeGenerateModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Schedule Report Workflow (Row 122) -->
<div id="scheduleReportModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md" id="scheduleModalTitle">Configure Automated Report Schedule</h3>
            <button onclick="closeScheduleModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="scheduleReportForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="sched_id" value="">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Select Report Module *</label>
                <select name="report_type" id="sched_report_type" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="DSR Performance">Data Subject Rights (DSR) Performance</option>
                    <option value="ROPA Inventory">ROPA Article 30 Inventory</option>
                    <option value="Risk Register">Risk Register & Mitigations</option>
                    <option value="Policies Report">Policy Governance & Review Target</option>
                    <option value="Vendor Risk">Vendor Risk & DPA Summary</option>
                    <option value="Incident Summary">Security Incident Management Summary</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Schedule Title *</label>
                <input type="text" name="title" id="sched_title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Weekly DSR Executive Summary Digest">
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Frequency *</label>
                    <select name="frequency" id="sched_frequency" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="daily">Daily Digest</option>
                        <option value="weekly" selected>Weekly Digest</option>
                        <option value="monthly">Monthly Audit Digest</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Export Format *</label>
                    <select name="export_format" id="sched_export_format" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="pdf">PDF Document (.pdf)</option>
                        <option value="excel">Excel / CSV Spreadsheet (.csv)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Recipient Email Addresses (Comma Separated) *</label>
                <input type="text" name="recipients" id="sched_recipients" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" value="dpo@privacyhq.com, compliance@privacyhq.com">
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeScheduleModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 font-semibold cursor-pointer">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Report Execution Details Profile -->
<div id="reportDetailsModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-2xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Report Execution Audit Profile</h3>
            <button onclick="closeReportDetailsModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md max-h-[75vh] overflow-y-auto" id="reportDetailsContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading report execution details...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeReportDetailsModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/reports.js?v=<?= time() ?>"></script>