<?php
// governance/pages/vendor-management.php
require_once __DIR__ . '/../includes/db.php';

// Authenticated check
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
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-md">
        <div>
            <h1 class="text-display font-display text-primary leading-tight">Vendor Management & Inventory</h1>
            <p class="text-body-md text-on-surface-variant">Monitor third-party vendor risks, Data Processing Agreements (DPA), and compliance status.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openVendorModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm">
                + Add Vendor
            </button>
            <button onclick="exportVendors('csv')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm">
                Export CSV
            </button>
            <button onclick="exportVendors('pdf')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm">
                Print Report
            </button>
        </div>
    </div>

    <!-- Stats KPI Telemetry Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Vendors</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-active">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Pending DPA</span>
            <div class="mt-base text-display font-bold text-amber-600" id="kpi-pending">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">High Risk</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-high">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-800">Critical Risk</span>
            <div class="mt-base text-display font-bold text-red-800" id="kpi-critical">...</div>
        </div>
    </div>

    <!-- Main Vendor Inventory Table Card -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">Third-Party Vendor Inventory</h2>
            
            <!-- Filters -->
            <form id="searchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search vendor name, contact, data...">
                <select id="filter-category" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Categories</option>
                    <option value="Cloud Storage">Cloud Storage</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Analytics">Analytics</option>
                    <option value="HR / Payroll">HR / Payroll</option>
                    <option value="Software">Software</option>
                    <option value="Other">Other</option>
                </select>
                <select id="filter-risk" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Risk Levels</option>
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
                <select id="filter-status" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Pending Review">Pending Review</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition">Search</button>
                <button type="button" onclick="clearFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="p-md">ID</th>
                        <th class="p-md">Vendor Name</th>
                        <th class="p-md">Category</th>
                        <th class="p-md">Contact</th>
                        <th class="p-md">DPA Status</th>
                        <th class="p-md">Risk Level</th>
                        <th class="p-md">Status</th>
                        <th class="p-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="vendorTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="8" class="text-center py-8 text-on-surface-variant">Loading vendor inventory...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="paginationControls" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low hidden">
            <span class="text-caption text-on-surface-variant" id="pageInfo">Showing page 1</span>
            <div class="flex gap-sm">
                <button id="btnPrev" class="px-3 py-1.5 border border-outline-variant text-body-md font-semibold rounded-lg bg-surface hover:bg-surface-container-high">Previous</button>
                <button id="btnNext" class="px-3 py-1.5 border border-outline-variant text-body-md font-semibold rounded-lg bg-surface hover:bg-surface-container-high">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Add Vendor -->
<div id="addVendorModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Add Third-Party Vendor</h3>
            <button onclick="closeVendorModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="addVendorForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Vendor Name *</label>
                    <input type="text" name="vendor_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g., Amazon Web Services">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Service Category *</label>
                    <select name="category" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Cloud Storage">Cloud Storage</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Analytics">Analytics</option>
                        <option value="HR / Payroll">HR / Payroll</option>
                        <option value="Software">Software</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Contact Name</label>
                    <input type="text" name="contact_name" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Jane Doe">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Contact Email</label>
                    <input type="email" name="contact_email" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. privacy@vendor.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">DPA Status</label>
                    <select name="dpa_status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Pending">Pending Signature</option>
                        <option value="Signed">Signed / Executed</option>
                        <option value="Not Required">Not Required</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Inherent Risk Level</label>
                    <select name="risk_level" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Vendor Status</label>
                    <select name="status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Pending Review">Pending Review</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Next Review Date</label>
                    <input type="date" name="next_assessment_date" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Data Shared / Processed</label>
                    <textarea name="data_shared" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Describe customer PII, telemetry, or data elements shared..."></textarea>
                </div>
            </div>
            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeVendorModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold">Save Vendor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Vendor -->
<div id="editVendorModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Edit Vendor Record</h3>
            <button onclick="closeEditVendorModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="editVendorForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="vendor_id" id="edit_vendor_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Vendor Name *</label>
                    <input type="text" name="vendor_name" id="edit_vendor_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Service Category *</label>
                    <select name="category" id="edit_category" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Cloud Storage">Cloud Storage</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Analytics">Analytics</option>
                        <option value="HR / Payroll">HR / Payroll</option>
                        <option value="Software">Software</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Contact Name</label>
                    <input type="text" name="contact_name" id="edit_contact_name" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Contact Email</label>
                    <input type="email" name="contact_email" id="edit_contact_email" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">DPA Status</label>
                    <select name="dpa_status" id="edit_dpa_status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Pending">Pending Signature</option>
                        <option value="Signed">Signed / Executed</option>
                        <option value="Not Required">Not Required</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Inherent Risk Level</label>
                    <select name="risk_level" id="edit_risk_level" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Vendor Status</label>
                    <select name="status" id="edit_status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Pending Review">Pending Review</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Next Review Date</label>
                    <input type="date" name="next_assessment_date" id="edit_next_assessment_date" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Data Shared / Processed</label>
                    <textarea name="data_shared" id="edit_data_shared" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none"></textarea>
                </div>
            </div>
            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeEditVendorModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold">Update Vendor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: View Vendor Details -->
<div id="viewVendorModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Vendor Profile & Risk Overview</h3>
            <button onclick="closeViewVendorModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <div class="p-md max-h-[75vh] overflow-y-auto" id="viewVendorContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading vendor information...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeViewVendorModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/vendor-management.js?v=<?= time() ?>"></script>