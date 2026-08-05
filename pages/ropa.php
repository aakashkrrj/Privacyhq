<?php
// governance/pages/ropa.php
// Pure Frontend View - NO SQL LOGIC
include_once __DIR__ . '/../includes/bottom-nav.php';

// Session variables for JS
$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROPA Management - PrivacyHQ</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 pb-20">

<div class="max-w-7xl mx-auto p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                Article 30: ROPA
            </h1>
            <p class="text-sm text-gray-500 mt-1">Maintain up-to-date documentation of personal data processing operations.</p>
        </div>
        <button onclick="openRopaModal()" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
            + Add New Activity
        </button>
    </div>

    <!-- KPI Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-blue-50">
            <p class="text-sm text-gray-500">Total Processing Activities</p>
            <h2 class="text-3xl font-bold text-blue-600 mt-2" id="kpi-total">...</h2>
            <p class="text-xs text-gray-500 mt-1">Registered activities</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-green-50">
            <p class="text-sm text-gray-500">Active Activities</p>
            <h2 class="text-3xl font-bold text-green-600 mt-2" id="kpi-active">...</h2>
            <p class="text-xs text-gray-500 mt-1">Currently monitored</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-orange-50">
            <p class="text-sm text-gray-500">Inactive Activities</p>
            <h2 class="text-3xl font-bold text-orange-600 mt-2" id="kpi-inactive">...</h2>
            <p class="text-xs text-gray-500 mt-1">Archived/Stopped</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 bg-gray-50">
            <p class="text-sm text-gray-500">New This Month</p>
            <h2 class="text-3xl font-bold text-gray-700 mt-2" id="kpi-new-month">...</h2>
            <p class="text-xs text-gray-500 mt-1">Added recently</p>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-md font-semibold text-gray-700 mb-5">Search & Filter Activities</h2>
        <form id="searchForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" id="filter-search" placeholder="Search Activity or Purpose..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-6 py-2 text-sm font-medium transition">
                Search Records
            </button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Records of Processing Activities</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-4">Activity Name</th>
                        <th class="p-4">Data Controller</th>
                        <th class="p-4">Department</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Retention</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="ropaTableBody">
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

    <!-- Quick Actions Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <h3 class="text-md font-semibold text-gray-700 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <button id="btn-add-activity-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                + Add Processing Activity
            </button>
            <button id="btn-export-records-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">
                Export Records
            </button>
            <button id="btn-generate-report-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition">
                Generate Report
            </button>
            <button id="btn-review-activities-qa" class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition">
                Review Activities
            </button>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit ROPA -->
<div id="ropaModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-2xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeRopaModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-4" id="modalTitle">Add New Activity</h3>
        
        <form id="ropaForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="ropa_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activity Name</label>
                    <input type="text" name="activity_name" id="ropa_activity_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <input type="text" name="department" id="ropa_department" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purpose of Processing</label>
                <textarea name="purpose" id="ropa_purpose" rows="2" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Controller</label>
                    <input type="text" name="data_controller" id="ropa_data_controller" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Categories</label>
                    <input type="text" name="data_categories" id="ropa_data_categories" placeholder="e.g. Names, Emails, Financial" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data Subjects</label>
                    <input type="text" name="data_subjects" id="ropa_data_subjects" placeholder="e.g. Customers, Employees" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipients</label>
                    <input type="text" name="recipients" id="ropa_recipients" placeholder="e.g. Third-party vendors" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Retention Period</label>
                    <input type="text" name="retention_period" id="ropa_retention_period" placeholder="e.g. 7 years" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                
                <div id="statusGroup" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="ropa_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="pt-4 flex justify-end gap-3 border-t mt-4">
                <button type="button" onclick="closeRopaModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Review Activities -->
<div id="reviewActivitiesModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-4xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button id="closeReviewActivitiesModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">&times;</button>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Review Incomplete Activities</h3>
        <p class="text-sm text-gray-500 mb-6">Listed below are active processing operations requiring further compliance documentation.</p>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-3">Activity Name</th>
                        <th class="p-3">Missing Details</th>
                        <th class="p-3">Department</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="incompleteTableBody">
                    <tr><td colspan="4" class="text-center py-6 text-gray-500">Loading incomplete activities...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="pt-4 flex justify-end border-t mt-6">
            <button type="button" id="btnCloseReviewActivitiesModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/ropa.js"></script>
</body>
</html>