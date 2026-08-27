<?php
// pages/audit-logs.php
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
                <span class="material-symbols-outlined text-primary text-[32px]">security</span>
                <h1 class="text-display font-display text-primary leading-tight">Immutable System Audit Logs</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Trace user activities, inspect security events, verify data mutations, export binary audits, and manage retention policies.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openRetentionModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">policy</span> Retention Policy
            </button>
            <button onclick="openPurgeModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-red-700 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">auto_delete</span> Purge Expired
            </button>
            <div class="relative inline-block text-left">
                <button onclick="toggleExportDropdown()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                    <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export Audit Logs
                </button>
                <div id="exportDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-surface rounded-xl border border-outline-variant shadow-lg py-1 z-20">
                    <button onclick="exportAuditLogs('pdf')" class="w-full text-left px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-high font-medium cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600 text-[18px]">picture_as_pdf</span> Genuine PDF (.pdf)
                    </button>
                    <button onclick="exportAuditLogs('excel')" class="w-full text-left px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-high font-medium cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-[18px]">table_view</span> Genuine Excel (.xlsx)
                    </button>
                    <button onclick="exportAuditLogs('csv')" class="w-full text-left px-4 py-2 text-body-md text-on-surface hover:bg-surface-container-high font-medium cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600 text-[18px]">csv</span> CSV Spreadsheet (.csv)
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Executive Dashboard Telemetry Cards (Row 124) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Audit Events</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Events Today</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-today">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-blue-600">Period Events (30d)</span>
            <div class="mt-base text-display font-bold text-blue-600" id="kpi-period">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-indigo-600">Login / Auth Events</span>
            <div class="mt-base text-display font-bold text-indigo-600" id="kpi-login">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-purple-600">Mutation Events</span>
            <div class="mt-base text-display font-bold text-purple-600" id="kpi-mutation">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Security &amp; Denied</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-security">...</div>
        </div>
    </div>

    <!-- Charts & Distribution Section (Row 124) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <!-- Chart 1: Module Event Distribution -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">pie_chart</span>
                Events by Module
            </h3>
            <div id="dist-module" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading module distribution...</div>
            </div>
        </div>

        <!-- Chart 2: 14-Day Velocity Trend -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">trending_up</span>
                14-Day Logging Velocity
            </h3>
            <div id="dist-trend" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading velocity trend...</div>
            </div>
        </div>

        <!-- Chart 3: Top Active Users -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">group</span>
                Top Active Users
            </h3>
            <div id="dist-users" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading active user telemetry...</div>
            </div>
        </div>
    </div>

    <!-- Activity Logs Table Card (Rows 125, 126, 127) -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">System Activity Log Register</h2>
            
            <!-- Filters Panel (Rows 126 & 127) -->
            <form id="auditSearchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search user, action, module, IP...">
                
                <select id="filter-module" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Modules</option>
                    <option value="ROPA">ROPA</option>
                    <option value="Policies">Policies</option>
                    <option value="Reports">Reports</option>
                    <option value="Audit Logs">Audit Logs</option>
                    <option value="Authentication">Authentication</option>
                    <option value="User Management">User Management</option>
                    <option value="Risk Register">Risk Register</option>
                    <option value="Vendor Risk">Vendor Risk</option>
                    <option value="DSR">DSR</option>
                    <option value="Incident">Incident</option>
                </select>

                <input type="date" id="filter-date-from" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" title="Date From">
                <input type="date" id="filter-date-to" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" title="Date To">

                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">Search</button>
                <button type="button" onclick="clearAuditFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition cursor-pointer">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">ID &amp; Timestamp</th>
                        <th class="px-lg py-md">User Identity</th>
                        <th class="px-lg py-md">Module / Resource</th>
                        <th class="px-lg py-md">Action Event</th>
                        <th class="px-lg py-md">IP Address &amp; Device</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="6" class="px-lg py-md text-center text-on-surface-variant">Loading system audit logs...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="auditPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="text-caption text-on-surface-variant">Showing audit log records</div>
        </div>
    </div>
</div>

<!-- Modal 1: Audit Log Details Profile & JSON Metadata Viewer (Row 128) -->
<div id="auditDetailsModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-3xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Audit Event Detailed Profile</h3>
            <button onclick="closeLogDetailsModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md max-h-[75vh] overflow-y-auto" id="logDetailsContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading event details...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeLogDetailsModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Close Profile</button>
        </div>
    </div>
</div>

<!-- Modal 2: Log Retention Policy Settings (Row 130) -->
<div id="retentionModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Configure Audit Log Retention Policy</h3>
            <button onclick="closeRetentionModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="retentionForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Retention Duration (Days) *</label>
                <select name="retention_days" id="ret_days" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="30">30 Days Retention</option>
                    <option value="60">60 Days Retention</option>
                    <option value="90">90 Days Retention (Recommended)</option>
                    <option value="180">180 Days (6 Months)</option>
                    <option value="365">365 Days (1 Year)</option>
                    <option value="3650">3650 Days (10 Years Compliance)</option>
                </select>
                <p class="text-caption text-on-surface-variant mt-1">Audit log records older than the selected threshold will be eligible for automated purging.</p>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="auto_purge_enabled" id="ret_auto_purge" value="1" class="w-4 h-4 text-primary rounded border-outline-variant">
                <label for="ret_auto_purge" class="text-body-md font-semibold text-on-surface cursor-pointer">Enable Automated Background Retention Purging</label>
            </div>

            <div class="p-md bg-surface-container-low border border-outline-variant rounded-lg space-y-1">
                <div class="text-caption text-on-surface-variant uppercase font-semibold">Policy Status Summary</div>
                <div class="text-body-md font-mono text-primary font-bold" id="ret_status_summary">Loading retention configuration...</div>
                <div class="text-caption text-on-surface-variant" id="ret_last_purge">Last Purge Execution: Never</div>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeRetentionModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Save Policy Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Manual Retention Purge Confirmation (Row 130) -->
<div id="purgeModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-red-50">
            <h3 class="font-bold text-red-800 text-title-md flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[22px]">warning</span>
                Confirm Manual Log Retention Purge
            </h3>
            <button onclick="closePurgeModal()" class="text-red-700 hover:text-red-900 text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md space-y-md">
            <p class="text-body-md text-on-surface">Are you sure you want to trigger a manual retention purge? Expired audit log records older than the active retention threshold will be permanently cleaned up.</p>

            <div class="p-md bg-amber-50 border border-amber-200 text-amber-900 rounded-lg text-xs leading-relaxed">
                <strong>Indelible Security Log:</strong> This purge action will be recorded in the active audit trail prior to execution. Active and recent log entries will NOT be deleted.
            </div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end gap-sm bg-surface-container-low">
            <button type="button" onclick="closePurgeModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
            <button type="button" onclick="executePurgeNow()" class="px-4 py-2 text-body-md text-white bg-red-600 rounded-lg hover:bg-red-700 font-semibold cursor-pointer">Execute Retention Purge</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/audit-logs.js?v=<?= time() ?>"></script>