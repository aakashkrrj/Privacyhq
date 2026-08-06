<?php
// pages/incident-management.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="space-y-6 max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">warning</span>
                Incident Management
            </h1>
            <p class="text-sm text-gray-500 mt-1">Track, investigate, and resolve security and privacy incidents.</p>
        </div>
        <button onclick="openIncidentModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            + Log New Incident
        </button>
    </div>
    
    <!-- Quick Actions Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <h3 class="text-md font-semibold text-gray-700 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <button id="btn-log-incident-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                + Log New Incident
            </button>
            <button id="btn-remediate-incident-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                Containment & Remediation
            </button>
            <button id="btn-escalate-incident-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                Escalation & Notification
            </button>
            <button id="btn-export-incidents-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition">
                Export Incident Register
            </button>
            <button id="btn-generate-report-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition col-span-1 sm:col-span-2">
                Generate Incident Compliance Report
            </button>
            <button id="btn-review-active-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition col-span-1 sm:col-span-2">
                Review Active Incidents
            </button>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Active Incidents</p>
            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="kpi-active">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">High Severity</p>
            <h2 class="text-3xl font-bold text-red-600 mt-2" id="kpi-high-severity">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Resolved</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2" id="kpi-resolved">...</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm text-gray-500">Resolution Rate</p>
            <h2 class="text-3xl font-bold text-emerald-500 mt-2" id="kpi-resolution-rate">...</h2>
        </div>
    </div>

    <!-- Analytics & Search -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <!-- Distribution -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-md font-semibold text-gray-700 mb-5">Incident Distribution</h2>
            <div class="space-y-4" id="distributionBars">
                <!-- Injected via JS -->
                <div class="text-gray-500 text-sm">Loading...</div>
            </div>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-md font-semibold text-gray-700 mb-5">Search & Filter</h2>
            <form id="searchForm" class="space-y-4">
                <input type="text" id="filter-search" placeholder="Search Summary or Description..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <div class="grid grid-cols-2 gap-4">
                    <select id="filter-severity" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="">All Severities</option>
                        <option value="Critical">Critical</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                    <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="Investigating">Investigating</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition">
                    Search Incidents
                </button>
            </form>
        </div>
    </div>

    <!-- INCIDENTS TABLE -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Incident Ledger</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">Summary</th>
                        <th class="p-4">Severity</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Impacted Records</th>
                        <th class="p-4">Created At</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="incidentTableBody">
                    <tr><td colspan="6" class="text-center py-8 text-gray-500">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="flex justify-between items-center p-4 border-t hidden">
            <span class="text-sm text-gray-600" id="pageInfo"></span>
            <div class="flex gap-2">
                <button id="btnPrev" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Previous</button>
                <button id="btnNext" class="px-3 py-1 border rounded bg-white text-gray-700 hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Incident -->
<div id="incidentModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative">
        <button onclick="closeIncidentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4" id="modalTitle">Log New Incident</h3>
        
        <form id="incidentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="incident_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
                <input type="text" name="summary" id="incident_summary" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea name="description" id="incident_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                    <select name="severity" id="incident_severity" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Impacted Records</label>
                    <input type="number" name="impacted_records" id="incident_impacted_records" value="0" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>
            
            <div id="statusGroup" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="incident_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="Open">Open</option>
                    <option value="Investigating">Investigating</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
            
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeIncidentModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Incident</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Containment & Remediation -->
<div id="remediateIncidentModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <button id="closeRemediateIncidentModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Containment & Remediation</h3>
        <form id="remediateIncidentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Incident</label>
                <select name="id" id="remediate_incident_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">Choose an incident...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Containment Actions Taken</label>
                <textarea name="containment_actions" id="remediate_containment" rows="3" required placeholder="Describe immediate steps taken to isolate/contain the incident..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Long-term Remediation Notes</label>
                <textarea name="remediation_notes" id="remediate_notes" rows="3" placeholder="Describe long-term structural remediation plans..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" id="btnCancelRemediate" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Remediation</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Escalation & DPO Notification -->
<div id="escalateIncidentModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl relative">
        <button id="closeEscalateIncidentModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Incident Escalation & DPO Notification</h3>
        
        <div id="escalate_severity_warning" class="hidden p-3 mb-4 bg-yellow-50 text-yellow-800 text-xs rounded-lg border border-yellow-200">
            <strong>Warning:</strong> Escalation is only allowed for High and Critical incidents. Lower severity incidents cannot be escalated.
        </div>

        <form id="escalateIncidentForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Incident</label>
                <select name="id" id="escalate_incident_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">Choose an incident...</option>
                </select>
            </div>
            <div class="flex items-center gap-3 py-2">
                <input type="checkbox" name="is_escalated" id="escalate_is_escalated" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                <label for="escalate_is_escalated" class="text-sm font-medium text-gray-700">Escalate to Management</label>
            </div>
            <div class="flex items-center gap-3 py-2">
                <input type="checkbox" name="dpo_notified" id="escalate_dpo_notified" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                <label for="escalate_dpo_notified" class="text-sm font-medium text-gray-700">Notify DPO (Data Protection Officer)</label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Regulatory Notification Status</label>
                <select name="regulatory_status" id="escalate_regulatory_status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="Not Required">Not Required</option>
                    <option value="Required - Under Review">Required - Under Review</option>
                    <option value="Reported to Authority">Reported to Authority</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" id="btnCancelEscalate" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Incident Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Review Active Incidents -->
<div id="reviewActiveModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-4xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button id="closeReviewActiveModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Review Active Incidents</h3>
        <p class="text-sm text-gray-500 mb-6">List of active (Open or Investigating) incidents, as well as High, Critical, or Escalated incidents needing attention.</p>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-3">Summary</th>
                        <th class="p-3">Severity</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Review Reason</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="activeIncidentsTableBody">
                    <tr><td colspan="5" class="text-center py-6 text-gray-500">Loading active incidents...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pt-4 flex justify-end border-t mt-6">
            <button type="button" id="btnCloseReviewActiveModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/incident-management.js"></script>
</body>
</html>