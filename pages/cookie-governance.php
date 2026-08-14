<?php
// pages/cookie-governance.php
// Enterprise Cookie Governance, Scanner, Categories & Consent Center UI
include_once __DIR__ . '/../includes/bottom-nav.php';

$csrfToken = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>
<div class="space-y-6 max-w-7xl mx-auto p-4 md:p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Cookie Governance & Scan Center</h1>
            <p class="text-xs md:text-sm text-gray-500 mt-1">Discover web trackers, classify cookie categories, configure consent banners, and enforce global privacy compliance.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <button onclick="openAddCookieModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                + Add Cookie
            </button>
            <button onclick="openCategoriesModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                Categories
            </button>
            <button onclick="openBannerModal()" class="inline-flex items-center justify-center px-4 py-2 text-xs md:text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
                Consent Banner
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

    <!-- 1. LIVE DASHBOARD METRICS CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Cookies</span>
            <div class="mt-2 text-2xl font-bold text-gray-900" id="metric-total">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600">First Party</span>
            <div class="mt-2 text-2xl font-bold text-indigo-600" id="metric-first-party">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-600">Third Party</span>
            <div class="mt-2 text-2xl font-bold text-purple-600" id="metric-third-party">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600">Awaiting Review</span>
            <div class="mt-2 text-2xl font-bold text-amber-600" id="metric-awaiting">0</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Compliance %</span>
            <div class="mt-2 text-2xl font-bold text-emerald-600" id="metric-compliance">100%</div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-600">Opt-In Rate</span>
            <div class="mt-2 text-2xl font-bold text-blue-600" id="metric-opt-in">100%</div>
        </div>
    </div>

    <!-- 2. SCANNER & CATEGORY BREAKDOWN -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- SCANNER CONTROL CENTER WIDGET -->
        <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-indigo-600">travel_explore</span> Cookie Scanner Engine
                    </h3>
                    <span id="scan-status" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">IDLE</span>
                </div>
                <p class="text-xs text-gray-500 mb-4">Target Domain: <strong id="scan-domain" class="text-gray-800">privacyhq.com</strong></p>

                <!-- Scanner Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-gray-600">Scan Progress</span>
                        <span id="scan-progress-pct" class="text-indigo-600">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden">
                        <div id="scan-progress-bar" class="bg-indigo-600 h-full rounded-full transition-all duration-300" style="width:0%"></div>
                    </div>
                </div>

                <!-- Scanner Telemetry Grid -->
                <div class="grid grid-cols-3 gap-2 bg-gray-50 p-3 rounded-lg border border-gray-200 text-center mb-4">
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase">Pages</div>
                        <div class="font-bold text-sm text-gray-800" id="scan-pages">0</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase">Detected</div>
                        <div class="font-bold text-sm text-indigo-600" id="scan-cookies-found">0</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500 uppercase">Duration</div>
                        <div class="font-bold text-sm text-gray-800" id="scan-time">0s</div>
                    </div>
                </div>

                <div class="text-xs text-gray-500 mb-4">Last Automated Scan: <span id="scan-last-time" class="font-medium text-gray-700">Never</span></div>
            </div>

            <!-- Scanner Action Buttons -->
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                <button id="btnStartScan" onclick="controlScan('start')" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg transition">Start Scan</button>
                <button id="btnPauseScan" onclick="controlScan('pause')" disabled class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-lg transition disabled:opacity-50">Pause</button>
                <button id="btnResumeScan" onclick="controlScan('resume')" disabled class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition disabled:opacity-50">Resume</button>
                <button id="btnCancelScan" onclick="controlScan('cancel')" disabled class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-lg transition disabled:opacity-50">Cancel</button>
            </div>
        </div>

        <!-- CATEGORIES DISTRIBUTION & RISK SUMMARY -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h3 class="font-bold text-gray-800 text-sm">Cookie Category Distribution</h3>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="text-gray-500">Risk Profile:</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold">Low: <strong id="risk-low">0</strong></span>
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Med: <strong id="risk-medium">0</strong></span>
                        <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-semibold">High: <strong id="risk-high">0</strong></span>
                    </div>
                </div>

                <!-- Category Progress Bars -->
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                            <span>Necessary Cookies</span>
                        </div>
                        <div class="w-full bg-gray-100 h-5 rounded-lg overflow-hidden p-0.5">
                            <div id="cat-necessary" class="bg-emerald-500 h-full rounded-md text-[10px] font-bold text-white flex items-center justify-center transition-all duration-500" style="width:0%">0%</div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                            <span>Analytics Cookies</span>
                        </div>
                        <div class="w-full bg-gray-100 h-5 rounded-lg overflow-hidden p-0.5">
                            <div id="cat-analytics" class="bg-blue-500 h-full rounded-md text-[10px] font-bold text-white flex items-center justify-center transition-all duration-500" style="width:0%">0%</div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                            <span>Marketing & Advertising</span>
                        </div>
                        <div class="w-full bg-gray-100 h-5 rounded-lg overflow-hidden p-0.5">
                            <div id="cat-marketing" class="bg-rose-500 h-full rounded-md text-[10px] font-bold text-white flex items-center justify-center transition-all duration-500" style="width:0%">0%</div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                            <span>Preferences & Functionality</span>
                        </div>
                        <div class="w-full bg-gray-100 h-5 rounded-lg overflow-hidden p-0.5">
                            <div id="cat-preferences" class="bg-amber-500 h-full rounded-md text-[10px] font-bold text-white flex items-center justify-center transition-all duration-500" style="width:0%">0%</div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                            <span>Unclassified Trackers</span>
                        </div>
                        <div class="w-full bg-gray-100 h-5 rounded-lg overflow-hidden p-0.5">
                            <div id="cat-unclassified" class="bg-gray-400 h-full rounded-md text-[10px] font-bold text-white flex items-center justify-center transition-all duration-500" style="width:0%">0%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>Compliance Rule: Unclassified cookies require review within 14 days.</span>
                <button onclick="openPreferenceCenterModal()" class="text-indigo-600 font-semibold hover:underline">Preview Preference Center</button>
            </div>
        </div>
    </div>

    <!-- 3. SEARCH & ADVANCED FILTERS TOOLBAR -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Search & Filter Cookies</h3>
            <div class="flex items-center gap-3">
                <button type="button" onclick="openReassignModal()" class="text-xs text-indigo-600 hover:underline font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">drive_file_move</span> Reassign Selected
                </button>
                <button type="button" onclick="resetFilters()" class="text-xs text-gray-500 hover:underline font-semibold">Reset Filters</button>
            </div>
        </div>
        <form id="searchForm" onsubmit="return false;">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="col-span-1 sm:col-span-2">
                    <input type="text" id="filter-search" placeholder="Search Cookie Name, Domain, Provider, Purpose..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Category Filter -->
                <div>
                    <select id="filter-category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">All Categories</option>
                    </select>
                </div>

                <!-- Party Type Filter -->
                <div>
                    <select id="filter-party" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">All Party Types</option>
                        <option value="first_party">First Party</option>
                        <option value="third_party">Third Party</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filter-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="awaiting_review">Awaiting Review</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <!-- Risk Filter -->
                <div>
                    <select id="filter-risk" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">All Risk Levels</option>
                        <option value="low">Low Risk</option>
                        <option value="medium">Medium Risk</option>
                        <option value="high">High Risk</option>
                    </select>
                </div>

                <!-- Apply Search Button -->
                <div class="col-span-1 sm:col-span-2 md:col-span-4 flex items-center">
                    <button type="submit" onclick="executeSearch()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-xs font-semibold transition">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 4. COOKIE INVENTORY DATATABLE -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">Discovered Cookies & Trackers Inventory</h2>
            <span id="inventoryCountInfo" class="text-xs text-gray-500 font-medium">Loading inventory...</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs md:text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-200">
                        <th class="p-3.5 w-10"><input type="checkbox" id="selectAllCookies" onclick="document.querySelectorAll('.cookie-checkbox').forEach(c => c.checked = this.checked)" class="rounded"></th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('name')">Cookie Name ↕</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('domain')">Domain ↕</th>
                        <th class="p-3.5">Category</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('party_type')">Party Type ↕</th>
                        <th class="p-3.5 cursor-pointer hover:bg-gray-100" onclick="sortTable('risk_level')">Risk Level ↕</th>
                        <th class="p-3.5">Retention</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="cookieTableBody">
                    <tr><td colspan="8" class="text-center py-10 text-gray-500"><span class="material-symbols-outlined animate-spin text-2xl text-indigo-600 block mb-1">sync</span>Loading cookie inventory...</td></tr>
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

<!-- MODAL 1: Add Cookie -->
<div id="addCookieModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeAddCookieModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Add Cookie Record</h3>
        <p class="text-xs text-gray-500 mb-4">Manually register a cookie or tracker into the classification ledger.</p>

        <form id="addCookieForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Cookie Name</label>
                    <input type="text" name="name" required placeholder="_ga" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Domain</label>
                    <input type="text" name="domain" required placeholder="privacyhq.com" value="privacyhq.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="cookie_category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="">Select Category...</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Provider / Vendor</label>
                    <input type="text" name="provider" placeholder="Google Analytics" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Party Type</label>
                    <select name="party_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="first_party">First Party</option>
                        <option value="third_party">Third Party</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Risk Level</label>
                    <select name="risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="low">Low Risk</option>
                        <option value="medium">Medium Risk</option>
                        <option value="high">High Risk</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Retention</label>
                    <input type="text" name="retention" placeholder="2 Years / Session" value="Session" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="active">Active (Approved)</option>
                    <option value="awaiting_review">Awaiting Review</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Purpose / Description</label>
                <textarea name="purpose" rows="2" placeholder="Used to preserve user session state across requests..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeAddCookieModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Cookie</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Edit Cookie -->
<div id="editCookieModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeEditCookieModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Edit Cookie Record</h3>
        <p class="text-xs text-gray-500 mb-4">Modify cookie classification, risk level, or retention parameters.</p>

        <form id="editCookieForm" class="space-y-3 text-xs md:text-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="edit_cookie_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Cookie Name</label>
                    <input type="text" name="name" id="edit_cookie_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Domain</label>
                    <input type="text" name="domain" id="edit_cookie_domain" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="edit_cookie_category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Provider / Vendor</label>
                    <input type="text" name="provider" id="edit_cookie_provider" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Party Type</label>
                    <select name="party_type" id="edit_cookie_party_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="first_party">First Party</option>
                        <option value="third_party">Third Party</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Risk Level</label>
                    <select name="risk_level" id="edit_cookie_risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                        <option value="low">Low Risk</option>
                        <option value="medium">Medium Risk</option>
                        <option value="high">High Risk</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Retention</label>
                    <input type="text" name="retention" id="edit_cookie_retention" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" id="edit_cookie_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    <option value="active">Active (Approved)</option>
                    <option value="awaiting_review">Awaiting Review</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-gray-700 mb-1">Purpose / Description</label>
                <textarea name="purpose" id="edit_cookie_purpose" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
            </div>

            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeEditCookieModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Update Cookie</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: Manage Categories -->
<div id="categoriesModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-2xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeCategoriesModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Manage Cookie Categories</h3>
        <p class="text-xs text-gray-500 mb-4">Define classification categories and rules for consent management.</p>

        <form id="addCategoryForm" class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4 space-y-2 text-xs">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" id="cat_action" value="create">
            <input type="hidden" name="id" id="cat_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-gray-700 mb-0.5">Category Name</label>
                    <input type="text" name="name" id="cat_name" required placeholder="e.g. Analytics" class="w-full border border-gray-300 rounded px-2.5 py-1.5 outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-0.5">Description</label>
                    <input type="text" name="description" id="cat_description" placeholder="Short purpose description..." class="w-full border border-gray-300 rounded px-2.5 py-1.5 outline-none">
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                    <input type="checkbox" name="is_necessary" id="cat_is_necessary" value="1" class="rounded text-indigo-600">
                    <span>Strictly Necessary Category (Mandatory for site operation)</span>
                </label>
                <div class="flex gap-2">
                    <button type="button" onclick="resetCategoryForm()" class="px-2.5 py-1 text-gray-600 border rounded bg-white hover:bg-gray-50">Reset</button>
                    <button type="submit" id="btnSubmitCategory" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded">Save Category</button>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 uppercase border-b border-gray-200">
                        <th class="p-3">Category</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Cookies</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="categoryTableBody">
                    <tr><td colspan="4" class="text-center py-4 text-gray-500">Loading categories...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 4: Reassign Category -->
<div id="reassignModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-xl relative">
        <button onclick="closeReassignModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Reassign Selected Cookies</h3>
        <p class="text-xs text-gray-500 mb-4" id="reassignCountText">Reassigning selected cookies:</p>

        <div class="space-y-4 text-xs md:text-sm">
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Target Category</label>
                <select id="reassign_category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </select>
            </div>
            <div class="pt-3 flex justify-end gap-3 border-t">
                <button type="button" onclick="closeReassignModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="button" onclick="submitReassignCookies()" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Apply Reassignment</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 5: Consent Banner Customizer -->
<div id="bannerModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-3xl shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeBannerModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Consent Banner & Preference Center Config</h3>
        <p class="text-xs text-gray-500 mb-4">Configure banner position, colors, theme, and legal policy links.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form id="bannerConfigForm" class="space-y-3 text-xs md:text-sm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="domain" value="privacyhq.com">

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Banner Title</label>
                    <input type="text" name="banner_title" id="banner_title" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Banner Notice Text</label>
                    <textarea name="banner_text" id="banner_text" rows="3" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Position</label>
                        <select name="position" id="banner_position" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                            <option value="bottom">Bottom Bar</option>
                            <option value="top">Top Bar</option>
                            <option value="floating">Floating Box</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Theme</label>
                        <select name="theme" id="banner_theme" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                            <option value="light">Light Theme</option>
                            <option value="dark">Dark Theme</option>
                            <option value="custom">Custom Colors</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Primary Color</label>
                        <input type="color" name="primary_color" id="banner_primary_color" class="w-full h-8 border border-gray-300 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Background</label>
                        <input type="color" name="background_color" id="banner_bg_color" class="w-full h-8 border border-gray-300 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Text Color</label>
                        <input type="color" name="text_color" id="banner_text_color" class="w-full h-8 border border-gray-300 rounded cursor-pointer">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Privacy Policy URL</label>
                        <input type="text" name="privacy_policy_url" id="privacy_policy_url" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Cookie Policy URL</label>
                        <input type="text" name="cookie_policy_url" id="cookie_policy_url" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs outline-none">
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-3 border-t">
                    <button type="button" onclick="closeBannerModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Configuration</button>
                </div>
            </form>

            <!-- LIVE PREVIEW CARD -->
            <div class="bg-gray-100 p-4 rounded-xl border border-gray-200 flex flex-col justify-between">
                <div>
                    <h4 class="font-bold text-gray-700 text-xs uppercase tracking-wider mb-3">Live Banner Preview</h4>
                    <div id="liveBannerPreview" class="p-4 rounded-xl shadow-md border space-y-2 transition-all">
                        <div class="font-bold text-sm" id="prevTitle">We Value Your Privacy</div>
                        <p class="text-xs leading-relaxed" id="prevText">We use cookies to enhance browsing, analyze traffic, and offer personalized content.</p>
                        <div class="flex gap-2 pt-2">
                            <button id="prevBtnAccept" class="px-3 py-1.5 text-white text-xs font-bold rounded-lg shadow-sm">Accept All</button>
                            <button class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg">Reject All</button>
                            <button class="px-3 py-1.5 border text-xs font-semibold rounded-lg">Customize</button>
                        </div>
                    </div>
                </div>
                <div class="text-[11px] text-gray-500 mt-4">
                    Banner responds to light/dark themes and remembers consent choices automatically.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 6: PREFERENCE CENTER MODAL -->
<div id="preferenceCenterModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl relative max-h-[90vh] overflow-y-auto">
        <button onclick="closePreferenceCenterModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        <h3 class="text-lg font-bold text-gray-900 mb-1">Privacy & Cookie Preference Center</h3>
        <p class="text-xs text-gray-500 mb-4">Manage your cookie consent preferences for privacyhq.com.</p>

        <form id="customPreferencesForm" class="space-y-4 text-xs md:text-sm">
            <div class="space-y-3">
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-gray-900 text-xs">Strictly Necessary Cookies</div>
                        <div class="text-[11px] text-gray-500">Required for website authentication and security.</div>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800">Always Active</span>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-gray-900 text-xs">Analytics Cookies</div>
                        <div class="text-[11px] text-gray-500">Gathers anonymous traffic statistics and usage trends.</div>
                    </div>
                    <input type="checkbox" id="pref_analytics" checked class="w-4 h-4 rounded text-indigo-600">
                </div>

                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-gray-900 text-xs">Marketing & Targeting Cookies</div>
                        <div class="text-[11px] text-gray-500">Delivers personalized advertising and campaign tracking.</div>
                    </div>
                    <input type="checkbox" id="pref_marketing" class="w-4 h-4 rounded text-indigo-600">
                </div>

                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-gray-900 text-xs">Preferences Cookies</div>
                        <div class="text-[11px] text-gray-500">Remembers interface language and regional settings.</div>
                    </div>
                    <input type="checkbox" id="pref_preferences" checked class="w-4 h-4 rounded text-indigo-600">
                </div>
            </div>

            <div class="pt-3 flex flex-wrap items-center justify-between gap-2 border-t">
                <div class="flex gap-2">
                    <button type="button" onclick="submitConsentChoice('reject_all', ['necessary'])" class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Reject All</button>
                    <button type="button" onclick="submitConsentChoice('accept_all', ['necessary','analytics','marketing','preferences'])" class="px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Accept All</button>
                </div>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Preferences</button>
            </div>
        </form>
    </div>
</div>

<!-- FLOATING FRONTEND CONSENT BANNER OVERLAY -->
<div id="frontendCookieBanner" class="hidden fixed bottom-4 left-4 right-4 md:left-6 md:right-auto md:max-w-md bg-white border border-gray-200 rounded-xl shadow-2xl p-4 z-40 space-y-3">
    <div class="flex items-start justify-between">
        <h4 class="font-bold text-sm text-gray-900">We Value Your Privacy</h4>
        <button onclick="hideFrontConsentBanner()" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
    </div>
    <p class="text-xs text-gray-600 leading-relaxed">We use cookies to enhance your browsing experience, analyze site traffic, and deliver personalized content.</p>
    <div class="flex items-center gap-2 pt-1">
        <button onclick="submitConsentChoice('accept_all', ['necessary','analytics','marketing','preferences'])" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-sm">Accept All</button>
        <button onclick="submitConsentChoice('reject_all', ['necessary'])" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-lg">Reject All</button>
        <button onclick="openPreferenceCenterModal()" class="px-3 py-1.5 border border-gray-300 text-gray-700 font-semibold text-xs rounded-lg">Customize</button>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/cookie-governance.js"></script>