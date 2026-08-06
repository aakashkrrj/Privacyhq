<?php
// pages/dsr-management.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<div class="space-y-6 max-w-7xl mx-auto p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Data Subject Requests (DSR)</h1>
            <p class="text-sm text-gray-500 mt-1">Track and manage user data access, erasure, and portability requests.</p>
        </div>
        <button onclick="openDsrModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            + Log New Request
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Requests</span>
            <div class="mt-2 text-3xl font-bold text-gray-900" id="kpi-total">...</div>
        </div>
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-600">Pending / Active</span>
            <div class="mt-2 text-3xl font-bold text-amber-600" id="kpi-pending">...</div>
        </div>
        <div class="p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Completed</span>
            <div class="mt-2 text-3xl font-bold text-emerald-600" id="kpi-completed">...</div>
        </div>
    </div>
    
    <!-- Quick Actions Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <h3 class="text-md font-semibold text-gray-700 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <button id="btn-log-request-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                + Log New Request
            </button>
            <button id="btn-verify-subject-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                Verify Subject Identity
            </button>
            <button id="btn-assign-request-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                Assign Request
            </button>
            <button id="btn-export-dsr-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition">
                Export DSR Register
            </button>
            <button id="btn-generate-report-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition col-span-1 sm:col-span-2">
                Generate SLA Compliance Report
            </button>
            <button id="btn-review-requests-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition col-span-1 sm:col-span-2">
                Review Pending Actions
            </button>
        </div>
    </div>
    
    <!-- ENHANCED KPI DASHBOARD -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">SLA Compliance</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2" id="kpi-sla">...</h2>
            <p class="text-xs text-gray-400 mt-1">Requests completed within SLA</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Average Resolution</p>
            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="kpi-avg-res">...</h2>
            <p class="text-xs text-gray-400 mt-1">Average completion time</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Open High Priority</p>
            <h2 class="text-3xl font-bold text-red-600 mt-2" id="kpi-high-priority">...</h2>
            <p class="text-xs text-gray-400 mt-1">Require immediate attention</p>
        </div>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-gray-700 mb-5">Search & Filter Requests</h2>
        <form id="searchForm">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="text" id="filter-search" placeholder="Search Subject Email or Request ID..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                <select id="filter-type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Request Types</option>
                    <option value="access">Access</option>
                    <option value="erasure">Erasure</option>
                    <option value="portability">Portability</option>
                    <option value="rectification">Rectification</option>
                    <option value="objection">Objection</option>
                </select>
                <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="verifying">Verifying</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition col-span-1 md:col-span-2">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- DSR ANALYTICS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-5">Request Distribution</h2>
            <div class="space-y-5">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Access Requests</span>
                        <span id="dist-access">0%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="bg-indigo-600 h-2 rounded-full" id="bar-access" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Erasure Requests</span>
                        <span id="dist-erasure">0%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="bg-red-500 h-2 rounded-full" id="bar-erasure" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Portability Requests</span>
                        <span id="dist-portability">0%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="bg-green-500 h-2 rounded-full" id="bar-portability" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>Rectification</span>
                        <span id="dist-rectification">0%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full">
                        <div class="bg-yellow-400 h-2 rounded-full" id="bar-rectification" style="width:0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="font-semibold text-gray-700 mb-5">Processing Performance</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-indigo-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Verified</p>
                    <h3 class="text-2xl font-bold text-indigo-600 mt-2" id="perf-verified">0%</h3>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Completed</p>
                    <h3 class="text-2xl font-bold text-green-600 mt-2" id="perf-completed">0%</h3>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Pending</p>
                    <h3 class="text-2xl font-bold text-yellow-600 mt-2" id="perf-pending">0%</h3>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500">Escalated</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-2" id="perf-escalated">0%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
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
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="dsrTableBody">
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

<!-- Modal: Log New Request -->
<div id="dsrModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeDsrModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Log New DSR</h3>
        
        <form id="addDsrForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject Email</label>
                <input type="email" name="subject_email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject Type</label>
                <select name="subject_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="customer">Customer</option>
                    <option value="employee">Employee</option>
                    <option value="vendor_contact">Vendor Contact</option>
                    <option value="citizen">Citizen</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                <select name="request_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="access">Access Request</option>
                    <option value="erasure">Erasure / Deletion</option>
                    <option value="portability">Data Portability</option>
                    <option value="rectification">Rectification</option>
                    <option value="objection">Objection to Processing</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeDsrModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Change Status -->
<div id="statusModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button onclick="closeStatusModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Change Status</h3>
        
        <form id="changeStatusForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="request_id" id="status_request_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                <select name="status" id="status_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="open">Open</option>
                    <option value="verifying">Verifying</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comments (Optional)</label>
                <textarea name="comments" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Verify Subject Identity -->
<div id="verifySubjectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button id="closeVerifySubjectModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Verify Subject Identity</h3>
        <form id="verifySubjectForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select DSR Request</label>
                <select name="request_id" id="verify_request_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">Choose a request...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Status</label>
                <select name="verification_status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="verified">Verified (Identity Confirmed)</option>
                    <option value="failed">Failed (Verification Failed)</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" id="btnCancelVerifySubject" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Verification</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Request -->
<div id="assignRequestModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button id="closeAssignRequestModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4">Assign DSR Request</h3>
        <form id="assignRequestForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select DSR Request</label>
                <select name="request_id" id="assign_request_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">Choose a request...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Assignee (Active Officers)</label>
                <select name="assigned_to" id="assignee_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">Loading users...</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" id="btnCancelAssignRequest" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Assign Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Review Pending Actions -->
<div id="reviewPendingModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-4xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button id="closeReviewPendingModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Review Pending Actions</h3>
        <p class="text-sm text-gray-500 mb-6">List of requests requiring verification, assignment, or processing updates.</p>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-3">Request ID</th>
                        <th class="p-3">Subject Email</th>
                        <th class="p-3">Reason for Review</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <tr><td colspan="4" class="text-center py-6 text-gray-500">Loading pending requests...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pt-4 flex justify-end border-t mt-6">
            <button type="button" id="btnCloseReviewPendingModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/dsr-management.js"></script>
</body>
</html>