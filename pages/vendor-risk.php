<?php
// governance/pages/vendor-risk.php
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
                <span class="material-symbols-outlined text-primary text-[32px]">shield_person</span>
                <h1 class="text-display font-display text-primary leading-tight">Vendor Risk & Security Audit Console</h1>
            </div>
            <p class="text-body-md text-on-surface-variant mt-xs">Monitor third-party vendor compliance, risk category scores, and security audit history across PrivacyHQ.</p>
        </div>
        <div class="flex flex-wrap gap-sm">
            <button onclick="exportRiskReport('csv')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-white bg-primary rounded-xl hover:opacity-90 transition shadow-sm">
                <span class="material-symbols-outlined mr-1 text-[18px]">download</span> Export CSV Report
            </button>
            <button onclick="exportRiskReport('pdf')" class="inline-flex items-center justify-center px-4 py-2.5 text-title-md font-semibold text-on-surface bg-surface border border-outline-variant rounded-xl hover:bg-surface-container-high transition shadow-sm">
                <span class="material-symbols-outlined mr-1 text-[18px]">print</span> Print PDF Report
            </button>
        </div>
    </div>

    <!-- Executive Overview KPI Telemetry -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-md">
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-on-surface-variant">Total Assessed Vendors</span>
            <div class="mt-base text-display font-bold text-on-surface" id="kpi-total-vendors">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-emerald-600">Compliant Posture</span>
            <div class="mt-base text-display font-bold text-emerald-600" id="kpi-compliant">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-red-600">High / Critical Risk</span>
            <div class="mt-base text-display font-bold text-red-600" id="kpi-high-risk">...</div>
        </div>
        <div class="p-md bg-surface rounded-xl border border-outline-variant shadow-sm">
            <span class="text-caption font-semibold uppercase tracking-wider text-primary">Average Risk Score</span>
            <div class="mt-base text-display font-bold text-primary flex items-baseline justify-between">
                <span id="kpi-avg-score">...%</span>
            </div>
            <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-2 overflow-hidden">
                <div id="kpi-avg-bar" class="bg-primary h-1.5 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Risk Categories Telemetry Breakdown -->
    <div class="bg-surface rounded-xl border border-outline-variant p-md shadow-sm space-y-md">
        <h3 class="font-display text-title-md text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">category</span> Risk Categories Telemetry Overview
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-md">
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/60">
                <span class="text-caption font-semibold text-on-surface-variant uppercase">Data Privacy & Consent</span>
                <div class="text-title-lg font-bold text-on-surface mt-1" id="cat-privacy-score">...%</div>
            </div>
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/60">
                <span class="text-caption font-semibold text-on-surface-variant uppercase">InfoSec & Encryption</span>
                <div class="text-title-lg font-bold text-on-surface mt-1" id="cat-security-score">...%</div>
            </div>
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/60">
                <span class="text-caption font-semibold text-on-surface-variant uppercase">Operational Continuity</span>
                <div class="text-title-lg font-bold text-on-surface mt-1" id="cat-operational-score">...%</div>
            </div>
            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant/60">
                <span class="text-caption font-semibold text-on-surface-variant uppercase">Legal & DPA Compliance</span>
                <div class="text-title-lg font-bold text-on-surface mt-1" id="cat-legal-score">...%</div>
            </div>
        </div>
    </div>

    <!-- Main Vendor Risk Inventory Table Card -->
    <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-md border-b border-outline-variant bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-md">
            <h2 class="font-semibold text-on-surface text-title-md">Vendor Risk Inventory & Audits</h2>
            
            <!-- Filters -->
            <form id="searchForm" class="flex flex-wrap items-center gap-sm">
                <input type="text" id="filter-search" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface" placeholder="Search vendor, category...">
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
                    <option value="Critical">Critical (>=80%)</option>
                    <option value="High">High (60-79%)</option>
                    <option value="Medium">Medium (40-59%)</option>
                    <option value="Low">Low (&lt;40%)</option>
                </select>
                <select id="filter-compliance" class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:border-primary focus:outline-none bg-surface">
                    <option value="">All Compliance Statuses</option>
                    <option value="Compliant">Compliant</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Non-Compliant">Non-Compliant</option>
                    <option value="Critical Audit">Critical Audit</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-body-md font-semibold rounded-lg hover:opacity-90 transition">Search</button>
                <button type="button" onclick="clearRiskFilters()" class="px-3 py-2 border border-outline-variant text-on-surface text-body-md font-semibold rounded-lg hover:bg-surface-container-high transition">Clear</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low text-on-surface-variant font-semibold text-label-md uppercase border-b border-outline-variant select-none">
                        <th class="px-lg py-md">ID</th>
                        <th class="px-lg py-md">Vendor Name</th>
                        <th class="px-lg py-md">Category</th>
                        <th class="px-lg py-md">Risk Rating</th>
                        <th class="px-lg py-md">Category Breakdown</th>
                        <th class="px-lg py-md">Compliance Status</th>
                        <th class="px-lg py-md">Last Assessed</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="vendorTableBody" class="divide-y divide-outline-variant text-body-md text-on-surface">
                    <tr><td colspan="9" class="px-lg py-md text-center text-on-surface-variant">Loading vendor risk inventory...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div id="vendorPagination" class="p-md border-t border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div class="text-caption text-on-surface-variant">Showing vendors</div>
        </div>
    </div>
</div>

<!-- Modal 1: Audit Vendor Risk Factors -->
<div id="riskAssessmentModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-xl overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <div>
                <h3 class="font-bold text-on-surface text-title-md">Vendor Risk Assessment Audit</h3>
                <span id="assess_vendor_name_display" class="text-caption text-primary font-semibold">...</span>
                <span class="text-caption text-on-surface-variant font-mono"> (<span id="assess_category_display">...</span>)</span>
            </div>
            <button onclick="closeRiskAssessmentModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <form id="riskAssessmentForm" class="p-md space-y-md">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="vendor_id" id="assess_vendor_id">

            <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant flex justify-between items-center">
                <span class="text-caption font-semibold uppercase text-on-surface-variant">Live Deterministic Score Engine</span>
                <span id="live_score_preview" class="px-3 py-1 rounded-full text-caption font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                    Calculated: 20% (Low Risk)
                </span>
            </div>

            <!-- Risk Categories Factors (0-100%) -->
            <div class="space-y-sm">
                <div>
                    <div class="flex justify-between text-body-md font-semibold text-on-surface mb-1">
                        <span>1. Data Privacy & Consent Risk (0-100%)</span>
                        <span id="privacy_score_val" class="font-mono text-primary">20%</span>
                    </div>
                    <input type="range" name="privacy_score" id="privacy_score_input" min="0" max="100" value="20" class="w-full h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary">
                    <p class="text-caption text-on-surface-variant mt-0.5">Evaluates explicit consent mechanisms, data subject rights, and PII storage limitations.</p>
                </div>

                <div>
                    <div class="flex justify-between text-body-md font-semibold text-on-surface mb-1">
                        <span>2. InfoSec & Technical Safeguards Risk (0-100%)</span>
                        <span id="security_score_val" class="font-mono text-primary">20%</span>
                    </div>
                    <input type="range" name="security_score" id="security_score_input" min="0" max="100" value="20" class="w-full h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary">
                    <p class="text-caption text-on-surface-variant mt-0.5">Evaluates TLS 1.3/AES-256 encryption, SOC 2 compliance, and penetration test frequency.</p>
                </div>

                <div>
                    <div class="flex justify-between text-body-md font-semibold text-on-surface mb-1">
                        <span>3. Operational & Continuity Risk (0-100%)</span>
                        <span id="operational_score_val" class="font-mono text-primary">20%</span>
                    </div>
                    <input type="range" name="operational_score" id="operational_score_input" min="0" max="100" value="20" class="w-full h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary">
                    <p class="text-caption text-on-surface-variant mt-0.5">Evaluates system uptime SLA, disaster recovery backups, and incident response readiness.</p>
                </div>

                <div>
                    <div class="flex justify-between text-body-md font-semibold text-on-surface mb-1">
                        <span>4. Legal, DPA & Regulatory Risk (0-100%)</span>
                        <span id="legal_score_val" class="font-mono text-primary">20%</span>
                    </div>
                    <input type="range" name="legal_score" id="legal_score_input" min="0" max="100" value="20" class="w-full h-2 bg-surface-container-high rounded-lg appearance-none cursor-pointer accent-primary">
                    <p class="text-caption text-on-surface-variant mt-0.5">Evaluates executed Data Processing Agreements (DPA) and cross-border transfer safeguards.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md pt-sm border-t border-outline-variant">
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Compliance Status *</label>
                    <select name="compliance_status" id="assess_compliance_status" required class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none bg-surface">
                        <option value="Compliant">Compliant</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Non-Compliant">Non-Compliant</option>
                        <option value="Critical Audit">Critical Audit</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-on-surface-variant uppercase mb-1">Audit Notes / Mitigations</label>
                    <textarea name="assessment_notes" id="assess_notes" rows="2" class="w-full border border-outline-variant rounded-lg p-2.5 text-body-md focus:border-primary focus:outline-none" placeholder="Provide audit summary or mitigation requirements..."></textarea>
                </div>
            </div>

            <div class="border-t border-outline-variant pt-md flex justify-end gap-sm">
                <button type="button" onclick="closeRiskAssessmentModal()" class="px-4 py-2.5 text-body-md text-on-surface border border-outline-variant rounded-xl hover:bg-surface-container-high font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2.5 text-body-md text-white bg-primary rounded-xl hover:opacity-90 font-semibold">Save & Recalculate</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Vendor Risk History Log -->
<div id="riskHistoryModal" class="fixed inset-0 bg-gray-900/50 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-surface shadow-xl rounded-xl w-full max-w-lg overflow-hidden border border-outline-variant">
        <div class="p-md border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-title-md">Vendor Risk Audit History Log</h3>
            <button onclick="closeRiskHistoryModal()" class="text-on-surface-variant hover:text-on-surface text-xl font-bold">&times;</button>
        </div>
        <div class="p-md max-h-[70vh] overflow-y-auto" id="riskHistoryContent">
            <div class="text-center py-6 text-on-surface-variant text-body-md">Loading risk history logs...</div>
        </div>
        <div class="p-md border-t border-outline-variant flex justify-end bg-surface-container-low">
            <button onclick="closeRiskHistoryModal()" class="px-4 py-2 text-body-md text-on-surface border border-outline-variant rounded-lg hover:bg-surface-container-high font-semibold">Close</button>
        </div>
    </div>
</div>

<script src="assets/js/vendor-risk.js?v=<?= time() ?>"></script>