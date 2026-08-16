<?php
// governance/pages/ropa.php
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
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-md">
        <div>
            <div class="flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary text-[32px]">menu_book</span>
                <h1 class="text-display font-display text-primary leading-tight">Article 30: Records of Processing Activities (ROPA)</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Maintain comprehensive documentation of all personal data processing operations across PrivacyHQ.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openRopaModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm cursor-pointer">
                + Add Processing Activity
            </button>
            <button onclick="exportRopa('csv')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export CSV
            </button>
            <button onclick="exportRopa('excel')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">table_chart</span> Excel
            </button>
            <button onclick="exportRopa('pdf')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">print</span> Print PDF
            </button>
        </div>
    </div>

    <!-- Executive KPI Dashboard Cards (Row 102) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Activities</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active Records</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-active">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-indigo-600">Under Review / Draft</span>
            <div class="mt-base text-display font-bold text-indigo-600" id="kpi-under-review">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-blue-600">Approved Records</span>
            <div class="mt-base text-display font-bold text-blue-600" id="kpi-approved">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Overdue Reviews</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-overdue">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-primary">Cross-Border Transfers</span>
            <div class="mt-base text-display font-bold text-primary" id="kpi-transfers">...</div>
        </div>
    </div>

    <!-- Analytics & Distribution Visual Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <!-- Card 1: Lawful Basis Distribution -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">gavel</span>
                Lawful Basis Distribution (Article 6)
            </h3>
            <div id="dist-lawful-basis" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading lawful basis breakdown...</div>
            </div>
        </div>

        <!-- Card 2: Department Breakdown -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">corporate_fare</span>
                Processing by Department
            </h3>
            <div id="dist-department" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading department breakdown...</div>
            </div>
        </div>

        <!-- Card 3: Controller vs Processor & Risk Profile -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">admin_panel_settings</span>
                Controller / Processor & Risk Profile
            </h3>
            <div id="dist-roles" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading role & risk profile...</div>
            </div>
        </div>
    </div>

    <!-- Main ROPA Inventory Table Card (Row 103) -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">Processing Activities Register</h2>
            
            <!-- Filters -->
            <form id="searchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search activity, purpose, code, dept...">
                <select id="filter-status" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="inactive">Inactive / Archived</option>
                </select>
                <select id="filter-department" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Departments</option>
                    <option value="Finance & Billing">Finance & Billing</option>
                    <option value="Human Resources">Human Resources</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Engineering & IT">Engineering & IT</option>
                    <option value="Customer Support">Customer Support</option>
                    <option value="Legal & Governance">Legal & Governance</option>
                </select>
                <select id="filter-legal-basis" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Lawful Bases</option>
                    <option value="Consent">Consent</option>
                    <option value="Contractual Necessity">Contractual Necessity</option>
                    <option value="Legal Obligation">Legal Obligation</option>
                    <option value="Legitimate Interest">Legitimate Interest</option>
                    <option value="Vital Interest">Vital Interest</option>
                    <option value="Public Task">Public Task</option>
                </select>
                <select id="filter-role" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Roles</option>
                    <option value="Controller">Controller</option>
                    <option value="Processor">Processor</option>
                    <option value="Joint Controller">Joint Controller</option>
                </select>
                <label class="inline-flex items-center text-caption text-on-surface-variant px-1 cursor-pointer select-none">
                    <input type="checkbox" id="filter-overdue" class="mr-1 h-4 w-4 rounded accent-primary"> Overdue Only
                </label>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">Search</button>
                <button type="button" onclick="clearRopaFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition cursor-pointer">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">Code</th>
                        <th class="px-lg py-md">Activity Name & Purpose</th>
                        <th class="px-lg py-md">Controller & Role</th>
                        <th class="px-lg py-md">Department</th>
                        <th class="px-lg py-md">Lawful Basis</th>
                        <th class="px-lg py-md">Data Categories</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md">Review Date</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="ropaTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="9" class="px-lg py-md text-center text-on-surface-variant">Loading ROPA inventory...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="ropaPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="text-caption text-on-surface-variant">Showing processing activities</div>
        </div>
    </div>
</div>

<!-- Modal 1: Add / Edit ROPA Record (Rows 104 & 105) -->
<div id="ropaModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-3xl overflow-hidden border border-outline-variant max-h-[90vh] flex flex-col">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md" id="ropaModalTitle">Add New Processing Activity (ROPA)</h3>
            <button onclick="closeRopaModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="ropaForm" class="p-md space-y-md overflow-y-auto flex-1">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="ropa_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Processing Activity Name *</label>
                    <input type="text" name="activity_name" id="ropa_activity_name" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Customer Account & Billing Management...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Department *</label>
                    <select name="department" id="ropa_department" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Finance & Billing">Finance & Billing</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Engineering & IT">Engineering & IT</option>
                        <option value="Customer Support">Customer Support</option>
                        <option value="Legal & Governance">Legal & Governance</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Purpose of Processing *</label>
                <textarea name="purpose" id="ropa_purpose" rows="2" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Describe why this personal data is collected and processed..."></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Data Controller *</label>
                    <input type="text" name="data_controller" id="ropa_data_controller" value="PrivacyHQ Inc" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Business Owner</label>
                    <input type="text" name="business_owner" id="ropa_business_owner" value="Data Owner" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Controller / Processor Role</label>
                    <select name="controller_role" id="ropa_controller_role" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Controller">Controller</option>
                        <option value="Processor">Processor</option>
                        <option value="Joint Controller">Joint Controller</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Lawful Basis (Article 6) *</label>
                    <select name="legal_basis" id="ropa_legal_basis" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Legitimate Interest">Legitimate Interest</option>
                        <option value="Consent">Consent</option>
                        <option value="Contractual Necessity">Contractual Necessity</option>
                        <option value="Legal Obligation">Legal Obligation</option>
                        <option value="Vital Interest">Vital Interest</option>
                        <option value="Public Task">Public Task</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Data Categories</label>
                    <input type="text" name="data_categories" id="ropa_data_categories" placeholder="e.g. Name, Email, Payment Info, IP Address..." class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Categories of Data Subjects</label>
                    <input type="text" name="data_subjects" id="ropa_data_subjects" placeholder="e.g. Customers, Employees, Leads..." class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Processing Operations</label>
                    <input type="text" name="processing_operations" id="ropa_processing_operations" value="Collection, Storage, Analysis" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Source of Personal Data</label>
                    <input type="text" name="data_source" id="ropa_data_source" value="Direct User Entry" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Recipients / Processors</label>
                    <input type="text" name="recipients" id="ropa_recipients" placeholder="e.g. Stripe, AWS, Mailchimp..." class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Third Parties Shared With</label>
                    <input type="text" name="third_parties" id="ropa_third_parties" placeholder="e.g. External Auditor, Tax Agencies..." class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">International Transfers?</label>
                    <select name="international_transfers" id="ropa_international_transfers" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Transfer Safeguards</label>
                    <input type="text" name="transfer_safeguards" id="ropa_transfer_safeguards" placeholder="e.g. SCCs, DPF, Adequacy Decision..." class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Retention Period</label>
                    <input type="text" name="retention_period" id="ropa_retention_period" placeholder="e.g. 7 Years..." class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Disposal Mechanism</label>
                    <input type="text" name="disposal_mechanism" id="ropa_disposal_mechanism" value="Automated Secure Erasure" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Storage Location / System</label>
                    <input type="text" name="storage_location" id="ropa_storage_location" value="AWS Cloud Server" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Technical Measures</label>
                    <input type="text" name="technical_measures" id="ropa_technical_measures" value="TLS 1.3, AES-256 Encryption" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Organizational Safeguards</label>
                    <input type="text" name="organizational_measures" id="ropa_organizational_measures" value="RBAC Access Control Policies" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Risk Level</label>
                    <select name="risk_level" id="ropa_risk_level" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Status</label>
                    <select name="status" id="ropa_status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="inactive">Inactive / Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Review Date</label>
                    <input type="date" name="review_date" id="ropa_review_date" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeRopaModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Save Processing Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: ROPA Profile Details -->
<div id="ropaDetailsModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-2xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Article 30 Processing Activity Profile</h3>
            <button onclick="closeRopaDetailsModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md max-h-[75vh] overflow-y-auto" id="ropaDetailsContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading activity details...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeRopaDetailsModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Close</button>
        </div>
    </div>
</div>

<!-- Modal 3: ROPA Audit History Log (Section 11) -->
<div id="ropaHistoryModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">ROPA Audit History Logs</h3>
            <button onclick="closeRopaHistoryModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md max-h-[70vh] overflow-y-auto" id="ropaHistoryContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading history logs...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeRopaHistoryModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/ropa.js?v=<?= time() ?>"></script>