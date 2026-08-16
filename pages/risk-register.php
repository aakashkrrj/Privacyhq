<?php
// governance/pages/risk-register.php
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
                <span class="material-symbols-outlined text-primary text-[32px]">warning</span>
                <h1 class="text-display font-display text-primary leading-tight">Privacy & Compliance Risk Register</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Log, assess, mitigate, and govern privacy risks across PrivacyHQ.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="openRiskModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm">
                + Add New Risk
            </button>
            <button onclick="exportRiskRegister('csv')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm">
                <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export CSV
            </button>
            <button onclick="exportRiskRegister('pdf')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm">
                <span class="material-symbols-outlined mr-1 text-[18px]">print</span> Print PDF
            </button>
        </div>
    </div>

    <!-- Executive Overview KPI Dashboard -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Risks</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">Critical & High</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-high">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-amber-600">Under Treatment</span>
            <div class="mt-base text-display font-bold text-amber-600" id="kpi-needs-action">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Mitigated</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-mitigated">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-primary">Avg Residual Score</span>
            <div class="mt-base text-display font-bold text-primary" id="kpi-avg-score">...</div>
        </div>
    </div>

    <!-- Interactive 5x5 Risk Matrix Card -->
    <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-sm border-b border-outline-variant pb-md">
            <div>
                <h3 class="font-display text-title-md text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[22px]">grid_on</span> 5&times;5 Risk Heatmap Matrix
                </h3>
                <p class="text-caption text-on-surface-variant">Click any grid cell to view and filter risks within that specific Likelihood &times; Impact zone.</p>
            </div>
            <div class="flex gap-sm items-center">
                <button onclick="loadRiskMatrix('residual')" class="px-3 py-1.5 rounded-lg border border-outline-variant text-caption font-semibold bg-surface-container-high hover:bg-surface-container-highest transition">
                    Residual View
                </button>
                <button onclick="loadRiskMatrix('inherent')" class="px-3 py-1.5 rounded-lg border border-outline-variant text-caption font-semibold bg-surface hover:bg-surface-container-high transition">
                    Inherent View
                </button>
            </div>
        </div>

        <div class="grid grid-cols-5 gap-2" id="riskMatrixGrid">
            <div class="text-caption text-on-surface-variant text-center py-6 col-span-5">Loading 5&times;5 risk matrix...</div>
        </div>
        
        <div class="flex justify-between items-center text-caption text-on-surface-variant pt-2">
            <span>&larr; Impact Scale (1: Low &rarr; 5: Critical) &rarr;</span>
            <div class="flex gap-4">
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Low (1-4)</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Medium (5-9)</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-orange-500"></span> High (10-16)</span>
                <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-600"></span> Critical (17-25)</span>
            </div>
        </div>
    </div>

    <!-- Main Risk Inventory Table Card -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">Risk Register Inventory</h2>
            
            <!-- Filters -->
            <form id="searchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search code, title, owner...">
                <select id="filter-category" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Categories</option>
                    <option value="Data Privacy">Data Privacy</option>
                    <option value="Data Security">Data Security</option>
                    <option value="Data Transfer">Data Transfer</option>
                    <option value="Access Control">Access Control</option>
                    <option value="Third-Party Vendor">Third-Party Vendor</option>
                    <option value="Data Retention">Data Retention</option>
                    <option value="Security Governance">Security Governance</option>
                </select>
                <select id="filter-risk-level" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Risk Levels</option>
                    <option value="Critical">Critical (17-25)</option>
                    <option value="High">High (10-16)</option>
                    <option value="Medium">Medium (5-9)</option>
                    <option value="Low">Low (1-4)</option>
                </select>
                <select id="filter-status" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Statuses</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Review / Treatment</option>
                    <option value="mitigated">Mitigated</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition">Search</button>
                <button type="button" onclick="clearRiskFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">Code</th>
                        <th class="px-lg py-md">Risk Title & Asset</th>
                        <th class="px-lg py-md">Category</th>
                        <th class="px-lg py-md">Inherent</th>
                        <th class="px-lg py-md">Residual</th>
                        <th class="px-lg py-md">Owner</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md">Target Date</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="riskTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="9" class="px-lg py-md text-center text-on-surface-variant">Loading risk inventory...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="riskPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="text-caption text-on-surface-variant">Showing risks</div>
        </div>
    </div>
</div>

<!-- Modal 1: Add / Edit Risk Item -->
<div id="riskModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-2xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md" id="riskModalTitle">Add New Risk Item</h3>
            <button onclick="closeRiskModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="riskForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="risk_id">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Risk Title / Description *</label>
                <input type="text" name="title" id="risk_title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Describe the risk event or vulnerability...">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Category *</label>
                    <select name="category" id="risk_category" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Data Privacy">Data Privacy</option>
                        <option value="Data Security">Data Security</option>
                        <option value="Data Transfer">Data Transfer</option>
                        <option value="Access Control">Access Control</option>
                        <option value="Third-Party Vendor">Third-Party Vendor</option>
                        <option value="Data Retention">Data Retention</option>
                        <option value="Security Governance">Security Governance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Risk Source</label>
                    <input type="text" name="risk_source" id="risk_source" value="Internal Audit" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Affected System/Asset</label>
                    <input type="text" name="affected_asset" id="affected_asset" value="Core System" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Owner *</label>
                    <input type="text" name="owner" id="risk_owner" value="Compliance Team" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Department</label>
                    <input type="text" name="department" id="department" value="Privacy Governance" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <!-- Score Evaluation Grid -->
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant space-y-3">
                <div class="flex justify-between items-center border-b border-outline-variant pb-2">
                    <span class="text-caption font-semibold text-primary uppercase">Risk Score Evaluation Engine (1-5 Scale)</span>
                    <div class="flex gap-3 text-caption font-semibold">
                        <span id="inh_score_preview" class="text-red-600">Inherent: 9 (Medium)</span>
                        <span id="res_score_preview" class="text-emerald-600">Residual: 4 (Low)</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-sm">
                    <div>
                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Inherent Likelihood (1-5)</label>
                        <select name="inherent_likelihood" id="inherent_likelihood" class="w-full border border-outline-variant rounded p-1.5 text-caption bg-surface">
                            <option value="1">1 - Rare</option>
                            <option value="2">2 - Unlikely</option>
                            <option value="3" selected>3 - Moderate</option>
                            <option value="4">4 - Likely</option>
                            <option value="5">5 - Almost Certain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Inherent Impact (1-5)</label>
                        <select name="inherent_impact" id="inherent_impact" class="w-full border border-outline-variant rounded p-1.5 text-caption bg-surface">
                            <option value="1">1 - Insignificant</option>
                            <option value="2">2 - Minor</option>
                            <option value="3" selected>3 - Moderate</option>
                            <option value="4">4 - Major</option>
                            <option value="5">5 - Catastrophic</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Residual Likelihood (1-5)</label>
                        <select name="residual_likelihood" id="residual_likelihood" class="w-full border border-outline-variant rounded p-1.5 text-caption bg-surface">
                            <option value="1">1 - Rare</option>
                            <option value="2" selected>2 - Unlikely</option>
                            <option value="3">3 - Moderate</option>
                            <option value="4">4 - Likely</option>
                            <option value="5">5 - Almost Certain</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Residual Impact (1-5)</label>
                        <select name="residual_impact" id="residual_impact" class="w-full border border-outline-variant rounded p-1.5 text-caption bg-surface">
                            <option value="1">1 - Insignificant</option>
                            <option value="2" selected>2 - Minor</option>
                            <option value="3">3 - Moderate</option>
                            <option value="4">4 - Major</option>
                            <option value="5">5 - Catastrophic</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Treatment Strategy</label>
                    <select name="treatment_strategy" id="treatment_strategy" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Mitigate / Reduce">Mitigate / Reduce</option>
                        <option value="Avoid / Terminate">Avoid / Terminate</option>
                        <option value="Transfer / Share">Transfer / Share</option>
                        <option value="Accept / Retain">Accept / Retain</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Target Remediation Date</label>
                    <input type="date" name="target_date" id="target_date" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Status</label>
                    <select name="status" id="risk_status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="open">Open</option>
                        <option value="in_progress">In Review / Treatment</option>
                        <option value="mitigated">Mitigated</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Initial Mitigation Strategy</label>
                <textarea name="mitigation" id="mitigation_plan" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Detail planned controls or remediation steps..."></textarea>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeRiskModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold">Save Risk Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Risk Profile Details -->
<div id="riskDetailsModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Risk Item Profile</h3>
            <button onclick="closeRiskDetailsModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <div class="p-md max-h-[75vh] overflow-y-auto" id="riskDetailsContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading risk details...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeRiskDetailsModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold">Close</button>
        </div>
    </div>
</div>

<!-- Modal 3: Mitigation Plan Management -->
<div id="mitigationModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div>
                <h3 class="font-bold text-on-surface text-title-md">Mitigation Plan & Control Implementation</h3>
                <span id="mitigation_risk_code_display" class="text-caption text-primary font-mono font-bold">RSK-0000</span>
            </div>
            <button onclick="closeMitigationModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="mitigationForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="risk_id" id="mitigation_risk_id">

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Mitigation Action Title *</label>
                <input type="text" name="mitigation_title" id="mitigation_title" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Mitigation Owner</label>
                    <input type="text" name="mitigation_owner" id="mitigation_owner" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Target Date</label>
                    <input type="date" name="target_date" id="mitigation_target_date" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
                <div>
                    <div class="flex justify-between text-xs font-semibold text-on-surface-variant uppercase mb-1">
                        <span>Progress (0-100%)</span>
                        <span id="mitigation_progress_val" class="text-primary font-mono font-bold">0%</span>
                    </div>
                    <input type="range" name="progress" id="mitigation_progress" min="0" max="100" value="0" class="w-full h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Status</label>
                    <select name="status" id="mitigation_status" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Planned">Planned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Implementation Details *</label>
                <textarea name="implementation_details" id="mitigation_details" rows="2" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Describe concrete controls, policies, or technical safeguards..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Control Verification Details</label>
                <textarea name="control_details" id="control_details" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Verification testing, audit log proof, or verification evidence..."></textarea>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeMitigationModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold">Save Mitigation Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 4: Risk Audit History Log -->
<div id="riskHistoryModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Risk Re-evaluation Audit Logs</h3>
            <button onclick="closeRiskHistoryModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <div class="p-md max-h-[70vh] overflow-y-auto" id="riskHistoryContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading history logs...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeRiskHistoryModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/risk-register.js?v=<?= time() ?>"></script>