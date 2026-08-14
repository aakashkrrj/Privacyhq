<?php
// pages/dsr-management.php
// Enterprise Data Subject Requests (DSR) Management UI
include_once __DIR__ . '/../includes/bottom-nav.php';

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<div class="space-y-6 max-w-7xl mx-auto p-4 md:p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Data Subject Requests (DSR)</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Enterprise privacy management, identity verification, SLA tracking, and data subject request processing.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <button onclick="openDsrModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                + Log New Request
            </button>
            <div class="relative group">
                <button class="inline-flex items-center justify-center px-3 py-2 text-xs md:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                    Export Register ▾
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

    <!-- Dynamic Metrics Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 md:gap-4">
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total</span>
            <div class="mt-2 text-2xl font-bold text-gray-900" id="kpi-total">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600">Open</span>
            <div class="mt-2 text-2xl font-bold text-indigo-600" id="kpi-open">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600">In Progress</span>
            <div class="mt-2 text-2xl font-bold text-amber-600" id="kpi-pending">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Completed</span>
            <div class="mt-2 text-2xl font-bold text-emerald-600" id="kpi-completed">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-600">Rejected</span>
            <div class="mt-2 text-2xl font-bold text-rose-600" id="kpi-rejected">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-600">Today</span>
            <div class="mt-2 text-2xl font-bold text-purple-600" id="kpi-pending-today">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-600">Avg Resolution</span>
            <div class="mt-2 text-xl font-bold text-blue-600" id="kpi-avg-res">0.0 Days</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-green-600">SLA Compliance</span>
            <div class="mt-2 text-xl font-bold text-green-600" id="kpi-sla">100%</div>
        </div>
    </div>
    
    <!-- Quick Action Toolbars -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-5">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Workflow Quick Actions</h3>
        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar pb-1">
            <button id="btn-log-request-qa" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition whitespace-nowrap">
                + Log Request
            </button>
            <button id="btn-verify-subject-qa" class="px-3 py-2 text-xs font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition whitespace-nowrap">
                Verify Subject Identity
            </button>
            <button id="btn-assign-request-qa" class="px-3 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">
                Assign Officer
            </button>
            <button id="btn-review-requests-qa" class="px-3 py-2 text-xs font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition whitespace-nowrap">
                Review Pending Actions
            </button>
        </div>
    </div>

    <!-- SEARCH & ADVANCED FILTERS TOOLBAR -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Search & Filter Requests</h3>
            <button type="button" onclick="resetFilters()" class="text-xs text-indigo-600 hover:underline font-semibold">Reset Filters</button>
        </div>
        <form id="searchForm" onsubmit="return false;">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <!-- Search Input -->
                <div class="col-span-1 sm:col-span-2">
                    <input type="text" id="filter-search" placeholder="Search Code, Requester Name, Email, Dept, Description..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="verifying">Verifying</option>
                        <option value="processing">Processing</option>
                        <option value="waiting">Waiting</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div>
                    <select id="filter-priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Priorities</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>

                <!-- Request Type Filter -->
                <div>
                    <select id="filter-type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Request Types</option>
                        <option value="access">Access</option>
                        <option value="erasure">Erasure</option>
                        <option value="portability">Portability</option>
                        <option value="rectification">Rectification</option>
                        <option value="objection">Objection</option>
                    </select>
                </div>

                <!-- Assigned User Filter -->
                <div>
                    <select id="filter-assigned" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Assignees</option>
                    </select>
                </div>

                <!-- Date Range Filters -->
                <div>
                    <input type="date" id="filter-from-date" title="From Date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <input type="date" id="filter-to-date" title="To Date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Search Button -->
                <div class="col-span-1 sm:col-span-2 md:col-span-2 flex items-center gap-2">
                    <button type="submit" onclick="executeSearch()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-xs font-semibold transition">
                        Apply Search & Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- DSR DATATABLE -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">DSR Request Register</h2>
            <span id="registerCountInfo" class="text-xs text-gray-500 font-medium">Loading requests...</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs md:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('request_id_code')">Request Code ↕</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('subject_name')">Requester ↕</th>
                        <th class="p-3.5">Department</th>
                        <th class="p-3.5">Type</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('priority')">Priority ↕</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('status')">Status ↕</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('due_date')">Due Date ↕</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="dsrTableBody">
                    <tr><td colspan="8" class="text-center py-10 text-gray-500"><div class="flex flex-col items-center"><span class="material-symbols-outlined animate-spin text-2xl text-indigo-600 mb-2">sync</span>Loading DSR register...</div></td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="flex flex-col sm:flex-row justify-between items-center p-4 border-t border-gray-200 gap-3">
            <span class="text-xs text-gray-600 font-medium" id="pageInfo">Showing 0 of 0</span>
            <div class="flex items-center gap-2">
                <button id="btnPrev" onclick="changePage(-1)" class="px-3 py-1.5 border rounded-lg bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 disabled:opacity-50">Previous</button>
                <button id="btnNext" onclick="changePage(1)" class="px-3 py-1.5 border rounded-lg bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: Log New DSR -->
<div id="dsrModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeDsrModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Log New DSR Request</h3>
        <p class="text-xs text-gray-500 mb-4">Submit a new data subject request into the governance ledger.</p>
        
        <form id="addDsrForm" enctype="multipart/form-data" class="space-y-4 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Requester Full Name</label>
                    <input type="text" name="subject_name" required placeholder="e.g., Jane Smith" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Subject Email</label>
                    <input type="email" name="subject_email" required placeholder="jane@example.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="subject_phone" placeholder="+1-555-0199" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Department</label>
                    <input type="text" name="subject_dept" placeholder="Engineering / Marketing" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Subject Category</label>
                    <select name="subject_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="customer">Customer</option>
                        <option value="employee">Employee</option>
                        <option value="vendor_contact">Vendor Contact</option>
                        <option value="citizen">Citizen</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Request Type</label>
                    <select name="request_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="access">Access Request</option>
                        <option value="erasure">Erasure / Deletion</option>
                        <option value="portability">Data Portability</option>
                        <option value="rectification">Rectification</option>
                        <option value="objection">Objection to Processing</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Description / Details</label>
                <textarea name="description" rows="3" placeholder="Provide request context or identity notes..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Supporting Document / Proof (Optional)</label>
                <input type="file" name="attachment" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs outline-none bg-gray-50">
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeDsrModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Request</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Edit DSR -->
<div id="editDsrModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeEditDsrModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit DSR Request</h3>
        <p class="text-xs text-gray-500 mb-4">Modify details, due dates, priority, or assigned officer.</p>
        
        <form id="editDsrForm" class="space-y-4 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="request_id" id="edit_request_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Requester Full Name</label>
                    <input type="text" name="subject_name" id="edit_subject_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Subject Email</label>
                    <input type="email" name="subject_email" id="edit_subject_email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="subject_phone" id="edit_subject_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Department</label>
                    <input type="text" name="subject_dept" id="edit_subject_dept" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Subject Category</label>
                    <select name="subject_type" id="edit_subject_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="customer">Customer</option>
                        <option value="employee">Employee</option>
                        <option value="vendor_contact">Vendor Contact</option>
                        <option value="citizen">Citizen</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Priority</label>
                    <select name="priority" id="edit_priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="open">Open</option>
                        <option value="assigned">Assigned</option>
                        <option value="verifying">Verifying</option>
                        <option value="processing">Processing</option>
                        <option value="waiting">Waiting</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" id="edit_due_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Description / Details</label>
                <textarea name="description" id="edit_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeEditDsrModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Update Request</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: Change Status -->
<div id="statusModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button onclick="closeStatusModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Update Status</h3>
        <p class="text-xs text-gray-500 mb-4">Transition request state across workflow.</p>
        
        <form id="changeStatusForm" class="space-y-4 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="request_id" id="status_request_id">
            <div>
                <label class="block font-semibold text-gray-700 mb-1">New Workflow Status</label>
                <select name="status" id="status_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="open">Open</option>
                    <option value="assigned">Assigned</option>
                    <option value="verifying">Verifying</option>
                    <option value="processing">Processing</option>
                    <option value="waiting">Waiting</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Audit Comments (Optional)</label>
                <textarea name="comments" rows="2" placeholder="Reason for status change..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Status</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 4: Verify Subject Identity -->
<div id="verifySubjectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button id="closeVerifySubjectModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Verify Subject Identity</h3>
        <p class="text-xs text-gray-500 mb-4">Validate subject identity documentation.</p>

        <form id="verifySubjectForm" class="space-y-4 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Select DSR Request</label>
                <select name="request_id" id="verify_request_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">Choose a request...</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Verification Decision</label>
                <select name="verification_status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="verified">Verified (Identity Confirmed)</option>
                    <option value="failed">Failed (Verification Failed)</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t">
                <button type="button" id="btnCancelVerifySubject" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Verification</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 5: Assign Request -->
<div id="assignRequestModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button id="closeAssignRequestModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Assign DSR Officer</h3>
        <p class="text-xs text-gray-500 mb-4">Delegate request handling to a Privacy Officer.</p>

        <form id="assignRequestForm" class="space-y-4 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Select DSR Request</label>
                <select name="request_id" id="assign_request_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">Choose a request...</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Assigned Officer</label>
                <select name="assigned_to" id="assignee_select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="">Loading officers...</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t">
                <button type="button" id="btnCancelAssignRequest" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Assign Officer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 6: Review Pending Actions -->
<div id="reviewPendingModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-4xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button id="closeReviewPendingModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Review Pending Actions</h3>
        <p class="text-xs text-gray-500 mb-4">Requests requiring identity verification, officer assignment, or processing updates.</p>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs md:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-3">Request ID</th>
                        <th class="p-3">Requester</th>
                        <th class="p-3">Pending Reason</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <tr><td colspan="4" class="text-center py-6 text-gray-500">Loading pending requests...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pt-4 flex justify-end border-t mt-4">
            <button type="button" id="btnCloseReviewPendingModal" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<!-- MODAL 7: COMPREHENSIVE REQUEST DETAILS VIEW DRAWER -->
<div id="requestDetailsModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-4xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeRequestDetailsModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        
        <!-- Details Header -->
        <div id="detailsHeaderContainer" class="border-b border-gray-200 pb-4 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-xl font-bold text-gray-900" id="detailsRequestCode">DSR-XXXXXX</h3>
                    <p class="text-xs text-gray-500 mt-0.5" id="detailsMetaInfo">Logged on --</p>
                </div>
                <div class="flex items-center gap-2" id="detailsBadges">
                    <!-- Badges injected dynamically -->
                </div>
            </div>
        </div>

        <!-- Detail Tabs -->
        <div class="flex items-center gap-2 border-b border-gray-200 mb-4 overflow-x-auto">
            <button onclick="switchDetailsTab('overview')" id="tabBtnOverview" class="px-4 py-2 text-xs font-bold border-b-2 border-indigo-600 text-indigo-600 whitespace-nowrap">Overview & Details</button>
            <button onclick="switchDetailsTab('notes')" id="tabBtnNotes" class="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 whitespace-nowrap">Notes (<span id="notesCount">0</span>)</button>
            <button onclick="switchDetailsTab('attachments')" id="tabBtnAttachments" class="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 whitespace-nowrap">Attachments (<span id="attachmentsCount">0</span>)</button>
            <button onclick="switchDetailsTab('history')" id="tabBtnHistory" class="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 whitespace-nowrap">Status History</button>
            <button onclick="switchDetailsTab('audit')" id="tabBtnAudit" class="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-gray-800 whitespace-nowrap">Audit Log</button>
        </div>

        <!-- TAB: OVERVIEW -->
        <div id="detailsTabOverview" class="space-y-4 text-xs md:text-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider mb-3">Requester Profile</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between"><span class="text-gray-500">Name:</span> <span class="font-semibold text-gray-900" id="detName">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Email:</span> <span class="font-semibold text-gray-900" id="detEmail">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Phone:</span> <span class="font-semibold text-gray-900" id="detPhone">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Department:</span> <span class="font-semibold text-gray-900" id="detDept">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Category:</span> <span class="font-semibold text-gray-900 capitalize" id="detType">--</span></div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider mb-3">Request Metadata</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between"><span class="text-gray-500">Request Type:</span> <span class="font-semibold text-indigo-600 uppercase" id="detReqType">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Assigned Officer:</span> <span class="font-semibold text-gray-900" id="detAssignee">Unassigned</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Due Date:</span> <span class="font-semibold text-gray-900" id="detDueDate">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">SLA Days Remaining:</span> <span class="font-semibold text-emerald-600" id="detSlaTimer">--</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Verification Status:</span> <span class="font-semibold text-gray-900 capitalize" id="detVerification">--</span></div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider mb-2">Request Description</h4>
                <p class="text-gray-700 text-xs leading-relaxed" id="detDescription">No description provided.</p>
            </div>
        </div>

        <!-- TAB: NOTES -->
        <div id="detailsTabNotes" class="hidden space-y-4">
            <form id="addNoteForm" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="request_id" id="note_request_id">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Add Internal / Public Note</label>
                    <textarea name="note_text" rows="2" required placeholder="Type notes or processing updates here..." class="w-full border border-gray-300 rounded-lg p-2 text-xs outline-none"></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-600">
                        <input type="checkbox" name="is_public" value="1" class="rounded text-indigo-600">
                        <span>Public Note (Visible to requester)</span>
                    </label>
                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg">Add Note</button>
                </div>
            </form>

            <div id="notesContainer" class="space-y-3 max-h-60 overflow-y-auto pr-1">
                <!-- Notes injected dynamically -->
            </div>
        </div>

        <!-- TAB: ATTACHMENTS -->
        <div id="detailsTabAttachments" class="hidden space-y-4">
            <form id="uploadAttachmentForm" enctype="multipart/form-data" class="bg-gray-50 p-4 rounded-xl border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="request_id" id="attachment_request_id">
                <input type="file" name="attachment" required class="w-full text-xs text-gray-500">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg whitespace-nowrap">Upload File</button>
            </form>

            <div id="attachmentsContainer" class="divide-y divide-gray-200 border rounded-xl overflow-hidden bg-white">
                <!-- Attachments injected dynamically -->
            </div>
        </div>

        <!-- TAB: HISTORY -->
        <div id="detailsTabHistory" class="hidden space-y-3">
            <div id="historyContainer" class="divide-y divide-gray-200 border rounded-xl overflow-hidden bg-white">
                <!-- History injected dynamically -->
            </div>
        </div>

        <!-- TAB: AUDIT LOG -->
        <div id="detailsTabAudit" class="hidden space-y-3">
            <div id="auditContainer" class="divide-y divide-gray-200 border rounded-xl overflow-hidden bg-white">
                <!-- Audit logs injected dynamically -->
            </div>
        </div>

        <div class="pt-4 flex justify-end border-t mt-4 gap-2">
            <button type="button" onclick="closeRequestDetailsModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/dsr-management.js"></script>