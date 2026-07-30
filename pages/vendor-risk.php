<!-- ===========================================================
    VENDOR RISK MANAGEMENT
    PART 1 : PAGE HEADER + EXECUTIVE DASHBOARD
=========================================================== -->

<section class="space-y-lg">

    <!-- Page Header -->

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-md">

        <div>

            <div class="flex items-center gap-sm">

                <span class="material-symbols-outlined text-primary text-[34px]">
                    business_center
                </span>

                <h1 class="font-headline-lg-mobile md:font-headline-lg text-on-surface">
                    Vendor Risk Management
                </h1>

            </div>

            <p class="font-body-md text-on-surface-variant mt-xs">
                Monitor vendor compliance, security posture and third-party risks across PrivacyHQ.
            </p>

        </div>

        <div class="flex flex-wrap gap-sm">

            <button
                id="btn-onboard-header"
                class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl shadow-sm hover:brightness-95 transition">

                <span class="material-symbols-outlined">
                    add
                </span>

                Onboard Vendor

            </button>

            <button
                id="btn-export-report-header"
                class="flex items-center gap-sm border border-outline-variant bg-surface-container-lowest px-lg py-md rounded-xl hover:bg-surface-container">

                <span class="material-symbols-outlined">
                    download
                </span>

                Export Report

            </button>

        </div>

    </div>



    <!-- Executive Overview -->

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-md">

        <!-- Total Vendors -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between items-start">

                <div>

                    <p class="font-caption text-outline">
                        Total Vendors
                    </p>

                    <h2 id="kpi-total-vendors" class="text-[42px] font-bold mt-sm text-on-surface">
                        0
                    </h2>

                </div>

                <span class="material-symbols-outlined text-primary text-[34px]">
                    apartment
                </span>

            </div>

        </div>



        <!-- Compliant -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <p class="font-caption text-outline">
                        Compliant
                    </p>

                    <h2 id="kpi-compliant" class="text-[42px] font-bold mt-sm text-[#107C10]">
                        0
                    </h2>

                </div>

                <span class="material-symbols-outlined text-[#107C10] text-[34px]">
                    verified
                </span>

            </div>

            <p class="font-caption text-[#107C10] mt-sm">
                Active & Signed
            </p>

        </div>



        <!-- High Risk -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <p class="font-caption text-outline">
                        High Risk Vendors
                    </p>

                    <h2 id="kpi-high-risk" class="text-[42px] font-bold mt-sm text-error">
                        0
                    </h2>

                </div>

                <span class="material-symbols-outlined text-error text-[34px]">
                    warning
                </span>

            </div>

            <p class="font-caption text-error mt-sm">
                Immediate review required
            </p>

        </div>



        <!-- Average Score -->

        <div class="bg-primary rounded-xl p-lg text-white shadow-lg">

            <div class="flex justify-between items-center">

                <div>

                    <p class="opacity-90">
                        Average Risk Score
                    </p>

                    <h2 id="kpi-avg-score" class="text-[42px] font-bold mt-sm">
                        0%
                    </h2>

                </div>

                <span class="material-symbols-outlined text-[38px]">
                    shield
                </span>

            </div>

            <div class="w-full bg-white/20 rounded-full h-2 mt-lg">

                <div id="kpi-avg-bar" class="bg-white rounded-full h-2 w-[0%]"></div>

            </div>

        </div>

    </div>



    <!-- Executive Insights -->

    <div class="grid lg:grid-cols-3 gap-md">

        <!-- Compliance Health -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <h3 class="font-title-md">
                        Compliance Health
                    </h3>

                    <p class="font-caption text-outline">
                        Overall Vendor Compliance
                    </p>

                </div>

                <span class="material-symbols-outlined text-[#107C10]">
                    health_and_safety
                </span>

            </div>

            <div class="mt-lg">

                <span class="text-[34px] font-bold text-[#107C10]">
                    Excellent
                </span>

            </div>

        </div>



        <!-- Reviews -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <h3 class="font-title-md">
                        Pending Reviews
                    </h3>

                    <p class="font-caption text-outline">
                        Due this week
                    </p>

                </div>

                <span class="material-symbols-outlined text-primary">
                    assignment
                </span>

            </div>

            <div class="mt-lg">

                <span class="text-[34px] font-bold">
                    14
                </span>

            </div>

        </div>



        <!-- Security -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <h3 class="font-title-md">
                        Security Rating
                    </h3>

                    <p class="font-caption text-outline">
                        Third-party Security
                    </p>

                </div>

                <span class="material-symbols-outlined text-primary">
                    security
                </span>

            </div>

            <div class="mt-lg">

                <span class="text-[34px] font-bold text-primary">
                    A+
                </span>

            </div>

        </div>

    </div>

</section>
<!-- ===========================================================
    PART 2 : ANALYTICS & RISK OVERVIEW
=========================================================== -->

<section class="mt-lg space-y-lg">

    <!-- Analytics Row -->

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-md">

        <!-- Risk Distribution -->

        <div class="xl:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

            <div class="flex justify-between items-center">

                <div>

                    <h3 class="font-title-md">
                        Vendor Risk Distribution
                    </h3>

                    <p class="font-body-md text-on-surface-variant">
                        Overall vendor portfolio classification
                    </p>

                </div>

                <span class="material-symbols-outlined text-primary">
                    analytics
                </span>

            </div>

            <div class="grid grid-cols-4 gap-md mt-lg">

                <div class="rounded-xl p-lg bg-[#E8F5E9] text-center">
                    <div class="text-4xl font-bold text-[#107C10]">74</div>
                    <div class="mt-sm text-sm">Low Risk</div>
                </div>

                <div class="rounded-xl p-lg bg-[#FFF8E1] text-center">
                    <div class="text-4xl font-bold text-[#FFB900]">36</div>
                    <div class="mt-sm text-sm">Medium</div>
                </div>

                <div class="rounded-xl p-lg bg-[#FFE7D6] text-center">
                    <div class="text-4xl font-bold text-[#D83B01]">20</div>
                    <div class="mt-sm text-sm">High</div>
                </div>

                <div class="rounded-xl p-lg bg-[#FDE7E9] text-center">
                    <div class="text-4xl font-bold text-[#C50F1F]">12</div>
                    <div class="mt-sm text-sm">Critical</div>
                </div>

            </div>

        </div>

        <!-- Risk Score -->

        <div class="bg-primary rounded-xl text-white p-lg shadow-lg">

            <div class="flex justify-between">

                <div>

                    <p class="opacity-90">
                        Overall Vendor Score
                    </p>

                    <h2 class="text-[60px] font-bold mt-lg">
                        91
                    </h2>

                    <p class="opacity-90">
                        Excellent
                    </p>

                </div>

                <span class="material-symbols-outlined text-[42px]">
                    shield
                </span>

            </div>

            <div class="w-full bg-white/20 rounded-full h-3 mt-xl">
                <div class="bg-white h-3 rounded-full w-[91%]"></div>
            </div>

        </div>

    </div>





    <!-- Heatmap -->

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

        <div class="flex justify-between items-center">

            <div>

                <h3 class="font-title-md">

                    Risk Heatmap

                </h3>

                <p class="font-body-md text-on-surface-variant">

                    Vendor exposure by department

                </p>

            </div>

            <span class="material-symbols-outlined text-primary">

                grid_view

            </span>

        </div>

        <div class="grid grid-cols-5 gap-sm mt-lg">

            <div class="aspect-square rounded-lg bg-green-200"></div>
            <div class="aspect-square rounded-lg bg-green-300"></div>
            <div class="aspect-square rounded-lg bg-yellow-300"></div>
            <div class="aspect-square rounded-lg bg-orange-300"></div>
            <div class="aspect-square rounded-lg bg-red-500"></div>

            <div class="aspect-square rounded-lg bg-green-100"></div>
            <div class="aspect-square rounded-lg bg-green-300"></div>
            <div class="aspect-square rounded-lg bg-yellow-400"></div>
            <div class="aspect-square rounded-lg bg-orange-400"></div>
            <div class="aspect-square rounded-lg bg-red-400"></div>

            <div class="aspect-square rounded-lg bg-green-300"></div>
            <div class="aspect-square rounded-lg bg-yellow-300"></div>
            <div class="aspect-square rounded-lg bg-yellow-400"></div>
            <div class="aspect-square rounded-lg bg-orange-500"></div>
            <div class="aspect-square rounded-lg bg-red-600"></div>

        </div>

    </div>





    <!-- Bottom Row -->

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-md">

        <!-- Categories -->

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <h3 class="font-title-md">

                        Vendor Categories

                    </h3>

                    <p class="font-body-md text-on-surface-variant">

                        Distribution by business type

                    </p>

                </div>

                <span class="material-symbols-outlined text-primary">

                    category

                </span>

            </div>

            <div class="space-y-md mt-lg">

                <div class="flex justify-between">
                    <span>Cloud Services</span>
                    <strong>42</strong>
                </div>

                <div class="flex justify-between">
                    <span>Payroll</span>
                    <strong>16</strong>
                </div>

                <div class="flex justify-between">
                    <span>Finance</span>
                    <strong>18</strong>
                </div>

                <div class="flex justify-between">
                    <span>Marketing</span>
                    <strong>23</strong>
                </div>

                <div class="flex justify-between">
                    <span>IT Support</span>
                    <strong>43</strong>
                </div>

            </div>

        </div>





        <!-- Review Timeline -->

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <h3 class="font-title-md">

                        Upcoming Reviews

                    </h3>

                    <p class="font-body-md text-on-surface-variant">

                        Scheduled vendor assessments

                    </p>

                </div>

                <span class="material-symbols-outlined text-primary">

                    event

                </span>

            </div>

            <div class="space-y-lg mt-lg">

                <div class="flex justify-between">

                    <div>

                        <strong>Microsoft Azure</strong>

                        <p class="text-sm text-outline">
                            Security Review
                        </p>

                    </div>

                    <span class="text-primary">
                        02 Aug
                    </span>

                </div>

                <div class="flex justify-between">

                    <div>

                        <strong>AWS</strong>

                        <p class="text-sm text-outline">
                            Compliance Audit
                        </p>

                    </div>

                    <span class="text-primary">
                        06 Aug
                    </span>

                </div>

                <div class="flex justify-between">

                    <div>

                        <strong>Razorpay</strong>

                        <p class="text-sm text-outline">
                            Annual Assessment
                        </p>

                    </div>

                    <span class="text-primary">
                        14 Aug
                    </span>

                </div>

            </div>

        </div>

    </div>

</section>
<!-- ===========================================================
    PART 3 : SEARCH + VENDOR REGISTRY
=========================================================== -->

<section class="mt-lg space-y-lg">

    <!-- Search & Filters -->

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

        <div class="flex flex-col xl:flex-row gap-md justify-between">

            <div class="relative flex-1">

                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">
                    search
                </span>

                <input
                    type="text"
                    id="filter-search"
                    placeholder="Search vendor, category or service..."
                    class="w-full pl-12 pr-4 py-3 rounded-xl bg-surface-container-low border border-outline-variant focus:ring-2 focus:ring-primary outline-none">

            </div>

            <div class="flex flex-wrap gap-sm">

                <select id="filter-risk" class="rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3">

                    <option value="">All Risks</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>

                </select>

                <select id="filter-status" class="rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3">

                    <option value="">All Status</option>
                    <option value="Compliant">Compliant</option>
                    <option value="Under Audit">Under Audit</option>

                </select>

                <button id="btn-search" class="bg-primary text-white rounded-xl px-lg py-md hover:brightness-95">

                    Apply Filters

                </button>

            </div>

        </div>

    </div>





    <!-- Vendor Registry -->

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">

        <div class="flex justify-between items-center p-lg border-b border-outline-variant">

            <div>

                <h3 class="font-title-md">

                    Vendor Registry

                </h3>

                <p class="font-body-md text-on-surface-variant">

                    Third-party vendors and their latest risk assessments.

                </p>

            </div>

            <button class="flex items-center gap-sm px-lg py-md rounded-xl bg-primary text-white hover:brightness-95">

                <span class="material-symbols-outlined">

                    download

                </span>

                Export

            </button>

        </div>





        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-surface-container-low">

                    <tr>

                        <th class="text-left px-lg py-md">Vendor</th>

                        <th class="text-left px-lg py-md">Category</th>

                        <th class="text-left px-lg py-md">Risk Level</th>

                        <th class="text-left px-lg py-md">Compliance</th>

                        <th class="text-center px-lg py-md">Actions</th>

                    </tr>

                </thead>

                <tbody id="vendorTableBody" class="divide-y divide-outline-variant">
                    <tr><td colspan="5" class="px-lg py-md text-center text-gray-500">Loading...</td></tr>
                </tbody>

            </table>

        </div>

        <!-- Pagination -->
        <div id="vendorPagination" class="flex flex-col md:flex-row justify-between items-center p-lg border-t border-outline-variant gap-md">
            <!-- Dynamic controls loaded here -->
        </div>

    </div>

</section>
<!-- ===========================================================
    PART 4 : AI INSIGHTS + QUICK ACTIONS + MODAL
=========================================================== -->

<section class="mt-lg space-y-lg">

    <!-- AI Insights & Risk Alerts -->

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-md">

        <!-- AI Recommendations -->

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

            <div class="flex justify-between items-center mb-lg">

                <div>
                    <h3 class="font-title-md">
                        AI Risk Recommendations
                    </h3>

                    <p class="text-sm text-outline">
                        Smart recommendations based on current vendor posture.
                    </p>

                </div>

                <span class="material-symbols-outlined text-primary text-3xl">
                    auto_awesome
                </span>

            </div>

            <div class="space-y-md">

                <div class="flex gap-md">

                    <span class="material-symbols-outlined text-error">
                        warning
                    </span>

                    <div>

                        <strong>Review Razorpay</strong>

                        <p class="text-sm text-outline">
                            Risk score dropped below acceptable threshold.
                        </p>

                    </div>

                </div>

                <div class="flex gap-md">

                    <span class="material-symbols-outlined text-primary">
                        security
                    </span>

                    <div>

                        <strong>Azure is fully compliant</strong>

                        <p class="text-sm text-outline">
                            No action required.
                        </p>

                    </div>

                </div>

                <div class="flex gap-md">

                    <span class="material-symbols-outlined text-[#FFB900]">
                        schedule
                    </span>

                    <div>

                        <strong>14 Reviews Pending</strong>

                        <p class="text-sm text-outline">
                            Schedule vendor assessments this week.
                        </p>

                    </div>

                </div>

            </div>

        </div>





        <!-- Executive Summary -->

        <div class="bg-primary rounded-xl text-white p-lg shadow-lg">

            <h3 class="text-xl font-bold mb-lg">

                Executive Summary

            </h3>

            <div class="space-y-md">

                <div class="flex justify-between">

                    <span>Total Vendors</span>

                    <strong>142</strong>

                </div>

                <div class="flex justify-between">

                    <span>Compliant Vendors</span>

                    <strong>118</strong>

                </div>

                <div class="flex justify-between">

                    <span>High Risk</span>

                    <strong>12</strong>

                </div>

                <div class="flex justify-between">

                    <span>Average Score</span>

                    <strong>91%</strong>

                </div>

            </div>

            <div class="mt-xl border-t border-white/20 pt-lg">

                Overall vendor ecosystem is healthy.
                Continue reviewing high-risk vendors to maintain compliance.

            </div>

        </div>

    </div>





    <!-- Quick Actions -->

    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-lg shadow-sm">

        <h3 class="font-title-md mb-lg">
            Quick Actions
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-md">

            <button id="btn-add-vendor" class="rounded-xl bg-primary text-white p-lg hover:brightness-95 transition">
                <span class="material-symbols-outlined text-3xl block mb-sm">
                    add_business
                </span>
                Add Vendor
            </button>

            <button id="btn-start-assessment" class="rounded-xl bg-surface-container-low border border-outline-variant p-lg">
                <span class="material-symbols-outlined text-primary text-3xl block mb-sm">
                    fact_check
                </span>
                Start Assessment
            </button>

            <button id="btn-download-report" class="rounded-xl bg-surface-container-low border border-outline-variant p-lg">
                <span class="material-symbols-outlined text-primary text-3xl block mb-sm">
                    download
                </span>
                Download Report
            </button>

            <button id="btn-review-flags" class="rounded-xl bg-surface-container-low border border-outline-variant p-lg">
                <span class="material-symbols-outlined text-primary text-3xl block mb-sm">
                    flag
                </span>
                Review Flags
            </button>

        </div>

    </div>





    <!-- Footer -->

    <div class="text-center text-outline text-sm py-lg">

        Last Vendor Risk Assessment :
        <strong>28 July 2026</strong>

        •

        Next Scheduled Review :
        <strong>02 August 2026</strong>

    </div>

</section>





<!-- ===========================================================
    ONBOARD VENDOR MODAL
=========================================================== -->
<div id="vendorModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden">
        <div class="flex justify-between items-center p-6 border-b bg-gray-50">
            <h2 class="text-xl font-bold">Onboard New Vendor</h2>
            <button id="closeVendorModal" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        </div>
        <form id="vendorForm" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Vendor Name</label>
                <input type="text" name="vendor_name" placeholder="Vendor Name" required class="w-full border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Service Category</label>
                <select name="category" required class="w-full border rounded-xl px-4 py-3 bg-white">
                    <option value="Cloud Storage">Cloud Storage</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Analytics">Analytics</option>
                    <option value="HR / Payroll">HR / Payroll</option>
                    <option value="Software">Software</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">DPA Status</label>
                <select name="dpa_status" required class="w-full border rounded-xl px-4 py-3 bg-white">
                    <option value="Pending">Pending Signature</option>
                    <option value="Signed">Signed / Executed</option>
                    <option value="Not Required">Not Required</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Inherent Risk Level</label>
                <select name="risk_level" required class="w-full border rounded-xl px-4 py-3 bg-white">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Shared / Processed</label>
                <textarea name="data_shared" placeholder="e.g. Customer email, payment tokens..." class="w-full border rounded-xl px-4 py-3" rows="2"></textarea>
            </div>

            <div class="flex justify-end gap-md pt-lg border-t mt-4">
                <button type="button" id="cancelVendorModal" class="px-lg py-md rounded-xl border">Cancel</button>
                <button type="submit" class="bg-primary text-white rounded-xl px-lg py-md">Save Vendor</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
    START ASSESSMENT MODAL
=========================================================== -->
<div id="startAssessmentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden">
        <div class="flex justify-between items-center p-6 border-b bg-gray-50">
            <h2 class="text-xl font-bold">Start Vendor DPIA Assessment</h2>
            <button id="closeAssessmentModal" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        </div>
        <form id="assessmentForm" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Select Vendor</label>
                <select id="assessment_vendor_select" name="vendor_id" required class="w-full border rounded-xl px-4 py-3 bg-white">
                    <!-- Loaded dynamically -->
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Assessment Title</label>
                <input type="text" name="title" id="assessment_title" placeholder="e.g. AWS Security Audit" required class="w-full border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Assessor Email</label>
                <input type="email" name="assessor" placeholder="assessor@company.com" required class="w-full border rounded-xl px-4 py-3">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Assessment Risk Level</label>
                    <select name="risk_level" required class="w-full border rounded-xl px-4 py-3 bg-white">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Assessment Status</label>
                    <select name="status" required class="w-full border rounded-xl px-4 py-3 bg-white">
                        <option value="Draft">Draft</option>
                        <option value="Under Review">Under Review</option>
                        <option value="Approved">Approved</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-md pt-lg border-t mt-4">
                <button type="button" id="cancelAssessmentModal" class="px-lg py-md rounded-xl border">Cancel</button>
                <button type="submit" class="bg-primary text-white rounded-xl px-lg py-md">Save Assessment</button>
            </div>
        </form>
    </div>
</div>

<!-- ===========================================================
    REVIEW FLAGS MODAL
=========================================================== -->
<div id="reviewFlagsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden">
        <div class="flex justify-between items-center p-6 border-b bg-gray-50">
            <h2 class="text-xl font-bold flex items-center gap-2 text-error">
                <span class="material-symbols-outlined">warning</span>
                Review Critical & High Risk Flags
            </h2>
            <button id="closeFlagsModal" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
        </div>
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            <p class="text-sm text-gray-500">The following third-party vendors are flagged for having Critical/High risk, or non-compliant DPA status.</p>
            <div class="overflow-x-auto border rounded-xl">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-4">Vendor</th>
                            <th class="text-left p-4">Risk Level</th>
                            <th class="text-left p-4">DPA Status</th>
                        </tr>
                    </thead>
                    <tbody id="flaggedVendorsTableBody">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="p-6 border-t bg-gray-50 flex justify-end">
            <button type="button" id="closeFlagsModalBtn" class="px-lg py-md rounded-xl border bg-white">Close</button>
        </div>
    </div>
</div>

<script>
    const G_CSRF_TOKEN = '<?= $csrfToken ?>';
</script>
<script src="assets/js/vendor-risk.js"></script>