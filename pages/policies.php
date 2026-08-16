<?php
// governance/pages/policies.php
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
                <span class="material-symbols-outlined text-primary text-[32px]">description</span>
                <h1 class="text-display font-display text-primary leading-tight">Policy Library, Governance & Version Control</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Create, upload, manage version history, and govern compliance approvals for organizational policies.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openCreatePolicyModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm cursor-pointer">
                + Create Policy
            </button>
            <button onclick="openUploadPolicyModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">upload</span> Upload Document
            </button>
            <button onclick="exportPolicies('csv')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export CSV
            </button>
            <button onclick="exportPolicies('excel')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">table_chart</span> Excel
            </button>
            <button onclick="exportPolicies('pdf')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm cursor-pointer">
                <span class="material-symbols-outlined mr-1 text-[18px]">print</span> Print PDF
            </button>
        </div>
    </div>

    <!-- Executive KPI Dashboard Cards (Row 109) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Documents</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Active Policies</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-active">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Draft / In Review</span>
            <div class="mt-base text-display font-bold text-amber-600" id="kpi-draft">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-indigo-600">Pending Approval</span>
            <div class="mt-base text-display font-bold text-indigo-600" id="kpi-pending">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-blue-600">Approved Policies</span>
            <div class="mt-base text-display font-bold text-blue-600" id="kpi-approved">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Expired & Review Due</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-expired">...</div>
        </div>
    </div>

    <!-- Analytics & Distribution Visual Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <!-- Card 1: Category Breakdown -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">category</span>
                Policies by Category
            </h3>
            <div id="dist-category" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading category breakdown...</div>
            </div>
        </div>

        <!-- Card 2: Department Breakdown -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">corporate_fare</span>
                Policies by Department
            </h3>
            <div id="dist-department" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading department breakdown...</div>
            </div>
        </div>

        <!-- Card 3: Approval Lifecycle Breakdown -->
        <div class="bg-surface rounded-xl border border-outline-variant shadow-sm p-md">
            <h3 class="font-semibold text-on-surface text-title-md mb-3 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-primary text-[20px]">task_alt</span>
                Approval Lifecycle Distribution
            </h3>
            <div id="dist-approval" class="space-y-2.5 text-body-md text-on-surface-variant">
                <div class="text-caption text-gray-500">Loading approval status breakdown...</div>
            </div>
        </div>
    </div>

    <!-- Main Policy Repository Table Card (Row 110) -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">Policy Library Register</h2>
            
            <!-- Filters -->
            <form id="policySearchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search code, title, owner, dept...">
                <select id="filter-status" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
                <select id="filter-category" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Categories</option>
                    <option value="Data Privacy">Data Privacy</option>
                    <option value="Information Security">Information Security</option>
                    <option value="HR & Employee Privacy">HR & Employee Privacy</option>
                    <option value="Third-Party Management">Third-Party Management</option>
                    <option value="Regulatory Compliance">Regulatory Compliance</option>
                </select>
                <select id="filter-department" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Departments</option>
                    <option value="Legal & Governance">Legal & Governance</option>
                    <option value="Engineering & IT">Engineering & IT</option>
                    <option value="Human Resources">Human Resources</option>
                    <option value="Finance & Billing">Finance & Billing</option>
                    <option value="Customer Support">Customer Support</option>
                </select>
                <select id="filter-approval" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Approvals</option>
                    <option value="draft">Draft</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <label class="inline-flex items-center text-caption text-on-surface-variant px-1 cursor-pointer select-none">
                    <input type="checkbox" id="filter-review-due" class="mr-1 h-4 w-4 rounded accent-primary"> Review Due
                </label>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition cursor-pointer">Search</button>
                <button type="button" onclick="clearPolicyFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition cursor-pointer">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">Code</th>
                        <th class="px-lg py-md">Policy Title & Category</th>
                        <th class="px-lg py-md">Owner & Dept</th>
                        <th class="px-lg py-md">Version</th>
                        <th class="px-lg py-md">Effective Date</th>
                        <th class="px-lg py-md">Review Date</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md">Approval</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="policyTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="9" class="px-lg py-md text-center text-on-surface-variant">Loading policy library records...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="policyPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="text-caption text-on-surface-variant">Showing policy documents</div>
        </div>
    </div>
</div>

<!-- Modal 1: Create New Policy -->
<div id="createPolicyModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-2xl overflow-hidden border border-outline-variant max-h-[90vh] flex flex-col">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Create New Policy Document</h3>
            <button onclick="closeCreatePolicyModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="createPolicyForm" class="p-md space-y-md overflow-y-auto flex-1">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Policy Title *</label>
                    <input type="text" name="title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Global Data Protection Policy...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Category *</label>
                    <select name="category" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Data Privacy">Data Privacy</option>
                        <option value="Information Security">Information Security</option>
                        <option value="HR & Employee Privacy">HR & Employee Privacy</option>
                        <option value="Third-Party Management">Third-Party Management</option>
                        <option value="Regulatory Compliance">Regulatory Compliance</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Policy Owner</label>
                    <input type="text" name="policy_owner" value="DPO / Compliance Team" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Department</label>
                    <select name="department" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Legal & Governance">Legal & Governance</option>
                        <option value="Engineering & IT">Engineering & IT</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Finance & Billing">Finance & Billing</option>
                        <option value="Customer Support">Customer Support</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Version Number *</label>
                    <input type="text" name="version" value="1.0" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Effective Date</label>
                    <input type="date" name="effective_date" value="<?= date('Y-m-d') ?>" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Next Review Date</label>
                    <input type="date" name="review_date" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?= date('Y-m-d', strtotime('+2 years')) ?>" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Description / Summary</label>
                <textarea name="description" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Brief summary of policy scope and compliance targets..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Policy Document Content (Text)</label>
                <textarea name="content" rows="4" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none font-mono text-sm" placeholder="Paste full policy text or legal framework clauses here..."></textarea>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeCreatePolicyModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold cursor-pointer">Save Policy Document</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Upload Policy Document / New Version (Row 111) -->
<div id="uploadPolicyModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md" id="uploadModalTitle">Upload Policy File Document</h3>
            <button onclick="closeUploadPolicyModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="uploadPolicyForm" enctype="multipart/form-data" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="policy_id" id="upload_policy_id" value="">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Policy Title *</label>
                <input type="text" name="title" id="upload_policy_title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="e.g. Information Security Framework">
            </div>

            <div class="grid grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Category</label>
                    <select name="category" id="upload_policy_category" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Data Privacy">Data Privacy</option>
                        <option value="Information Security">Information Security</option>
                        <option value="HR & Employee Privacy">HR & Employee Privacy</option>
                        <option value="Third-Party Management">Third-Party Management</option>
                        <option value="Regulatory Compliance">Regulatory Compliance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Version Number *</label>
                    <input type="text" name="version" id="upload_policy_version" value="1.0" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Select Policy File (PDF, DOCX, TXT — Max 10MB) *</label>
                <input type="file" name="policy_file" required accept=".pdf,.docx,.txt" class="w-full border border-outline-variant rounded-lg p-2 text-body-md focus:border-primary focus:outline-none bg-surface">
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Version Change Summary</label>
                <textarea name="change_summary" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Describe changes introduced in this version..."></textarea>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeUploadPolicyModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 font-semibold cursor-pointer">Upload File</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Policy Profile Details -->
<div id="policyDetailsModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-2xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Policy Document Profile Details</h3>
            <button onclick="closePolicyDetailsModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md max-h-[75vh] overflow-y-auto" id="policyDetailsContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading policy details...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closePolicyDetailsModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Close</button>
        </div>
    </div>
</div>

<!-- Modal 4: Version History Modal (Row 113) -->
<div id="versionHistoryModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-3xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Document Version History Timeline</h3>
            <button onclick="closeVersionHistoryModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="p-md max-h-[70vh] overflow-y-auto" id="versionHistoryContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading version history...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeVersionHistoryModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold cursor-pointer">Close</button>
        </div>
    </div>
</div>

<!-- Modal 5: Approval Workflow Modal (Row 114) -->
<div id="approvalWorkflowModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Policy Compliance Approval Workflow</h3>
            <button onclick="closeApprovalWorkflowModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <form id="approvalWorkflowForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="policy_id" id="approval_policy_id" value="">

            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant">
                <span class="text-caption text-primary font-mono font-bold" id="approval_policy_code">POL-0000</span>
                <h4 class="font-bold text-on-surface text-title-md" id="approval_policy_title">Policy Title</h4>
                <div class="text-caption text-on-surface-variant mt-1">Current Status: <strong id="approval_policy_status" class="text-amber-600 uppercase font-bold">Draft</strong></div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Approval Comments / Audit Notes *</label>
                <textarea name="comments" id="approval_comments" rows="3" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Provide justification or review feedback for this compliance action..."></textarea>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="submitApprovalAction('request_changes')" class="px-3 py-2 text-caption text-amber-800 bg-amber-100 rounded-lg hover:bg-amber-200 font-semibold cursor-pointer">Request Changes</button>
                <button type="button" onclick="submitApprovalAction('reject')" class="px-3 py-2 text-caption text-red-800 bg-red-100 rounded-lg hover:bg-red-200 font-semibold cursor-pointer">Reject Policy</button>
                <button type="button" onclick="submitApprovalAction('approve')" class="px-4 py-2 text-body-md text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 font-semibold cursor-pointer">Approve Policy</button>
            </div>
        </form>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/policies.js?v=<?= time() ?>"></script>