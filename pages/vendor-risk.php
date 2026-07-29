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
                id="openVendorModal"
                class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl shadow-sm hover:brightness-95 transition">

                <span class="material-symbols-outlined">
                    add
                </span>

                Onboard Vendor

            </button>

            <button
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

                    <h2 class="text-[42px] font-bold mt-sm text-on-surface">
                        <?php
                        $vendorCount = $conn->query("SELECT COUNT(*) AS total FROM vendors");
                        echo ($vendorCount) ? $vendorCount->fetch_assoc()['total'] : 0;
                        ?>
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

                    <h2 class="text-[42px] font-bold mt-sm text-[#107C10]">
                        118
                    </h2>

                </div>

                <span class="material-symbols-outlined text-[#107C10] text-[34px]">
                    verified
                </span>

            </div>

            <p class="font-caption text-[#107C10] mt-sm">
                ▲ 8% this month
            </p>

        </div>



        <!-- High Risk -->

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">

            <div class="flex justify-between">

                <div>

                    <p class="font-caption text-outline">
                        High Risk Vendors
                    </p>

                    <h2 class="text-[42px] font-bold mt-sm text-error">
                        12
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

                    <h2 class="text-[42px] font-bold mt-sm">
                        91%
                    </h2>

                </div>

                <span class="material-symbols-outlined text-[38px]">
                    shield
                </span>

            </div>

            <div class="w-full bg-white/20 rounded-full h-2 mt-lg">

                <div class="bg-white rounded-full h-2 w-[91%]"></div>

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
                    placeholder="Search vendor, category or service..."
                    class="w-full pl-12 pr-4 py-3 rounded-xl bg-surface-container-low border border-outline-variant focus:ring-2 focus:ring-primary outline-none">

            </div>

            <div class="flex flex-wrap gap-sm">

                <select class="rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3">

                    <option>All Risks</option>
                    <option>Low</option>
                    <option>Medium</option>
                    <option>High</option>
                    <option>Critical</option>

                </select>

                <select class="rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3">

                    <option>All Status</option>
                    <option>Compliant</option>
                    <option>Pending</option>
                    <option>Review</option>

                </select>

                <button class="bg-primary text-white rounded-xl px-lg py-md hover:brightness-95">

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

                        <th class="text-left px-lg py-md">Risk Score</th>

                        <th class="text-left px-lg py-md">Risk Level</th>

                        <th class="text-left px-lg py-md">Compliance</th>

                        <th class="text-left px-lg py-md">Review</th>

                        <th class="text-center px-lg py-md">Actions</th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-outline-variant">

                    <tr class="hover:bg-surface-container-low">

                        <td class="px-lg py-md">

                            <div>

                                <div class="font-semibold">

                                    Microsoft Azure

                                </div>

                                <div class="text-sm text-outline">

                                    Cloud Infrastructure

                                </div>

                            </div>

                        </td>

                        <td class="px-lg py-md">

                            Cloud

                        </td>

                        <td class="px-lg py-md font-bold text-green-600">

                            96%

                        </td>

                        <td class="px-lg py-md">

                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm">

                                Low

                            </span>

                        </td>

                        <td class="px-lg py-md">

                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm">

                                Compliant

                            </span>

                        </td>

                        <td class="px-lg py-md">

                            02 Aug 2026

                        </td>

                        <td class="px-lg py-md text-center">

                            <button class="text-primary">

                                <span class="material-symbols-outlined">

                                    visibility

                                </span>

                            </button>

                        </td>

                    </tr>





                    <tr class="hover:bg-surface-container-low">

                        <td class="px-lg py-md">

                            <div>

                                <div class="font-semibold">

                                    AWS

                                </div>

                                <div class="text-sm text-outline">

                                    Cloud Platform

                                </div>

                            </div>

                        </td>

                        <td class="px-lg py-md">

                            Cloud

                        </td>

                        <td class="px-lg py-md font-bold text-yellow-600">

                            82%

                        </td>

                        <td class="px-lg py-md">

                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm">

                                Medium

                            </span>

                        </td>

                        <td class="px-lg py-md">

                            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm">

                                Under Review

                            </span>

                        </td>

                        <td class="px-lg py-md">

                            08 Aug 2026

                        </td>

                        <td class="px-lg py-md text-center">

                            <button class="text-primary">

                                <span class="material-symbols-outlined">

                                    edit

                                </span>

                            </button>

                        </td>

                    </tr>





                    <tr class="hover:bg-surface-container-low">

                        <td class="px-lg py-md">

                            <div>

                                <div class="font-semibold">

                                    Razorpay

                                </div>

                                <div class="text-sm text-outline">

                                    Payment Gateway

                                </div>

                            </div>

                        </td>

                        <td class="px-lg py-md">

                            Finance

                        </td>

                        <td class="px-lg py-md font-bold text-red-600">

                            61%

                        </td>

                        <td class="px-lg py-md">

                            <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm">

                                High

                            </span>

                        </td>

                        <td class="px-lg py-md">

                            <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm">

                                Review Required

                            </span>

                        </td>

                        <td class="px-lg py-md">

                            15 Aug 2026

                        </td>

                        <td class="px-lg py-md text-center">

                            <button class="text-primary">

                                <span class="material-symbols-outlined">

                                    assignment

                                </span>

                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>





        <!-- Pagination -->

        <div class="flex flex-col md:flex-row justify-between items-center p-lg border-t border-outline-variant gap-md">

            <div class="text-sm text-outline">

                Showing <strong>1–10</strong> of <strong>142</strong> Vendors

            </div>

            <div class="flex items-center gap-sm">

                <button class="px-4 py-2 rounded-lg border border-outline-variant">

                    Previous

                </button>

                <button class="w-10 h-10 rounded-lg bg-primary text-white">

                    1

                </button>

                <button class="w-10 h-10 rounded-lg border border-outline-variant">

                    2

                </button>

                <button class="w-10 h-10 rounded-lg border border-outline-variant">

                    3

                </button>

                <button class="px-4 py-2 rounded-lg border border-outline-variant">

                    Next

                </button>

            </div>

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

            <button class="rounded-xl bg-primary text-white p-lg hover:brightness-95 transition">

                <span class="material-symbols-outlined text-3xl block mb-sm">
                    add_business
                </span>

                Add Vendor

            </button>

            <button class="rounded-xl bg-surface-container-low border border-outline-variant p-lg">

                <span class="material-symbols-outlined text-primary text-3xl block mb-sm">
                    fact_check
                </span>

                Start Assessment

            </button>

            <button class="rounded-xl bg-surface-container-low border border-outline-variant p-lg">

                <span class="material-symbols-outlined text-primary text-3xl block mb-sm">
                    download
                </span>

                Export Report

            </button>

            <button class="rounded-xl bg-surface-container-low border border-outline-variant p-lg">

                <span class="material-symbols-outlined text-primary text-3xl block mb-sm">
                    settings
                </span>

                Configure

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

<div id="vendorModal"
class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100]">

<div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl">

<div class="flex justify-between items-center p-6 border-b">

<h2 class="text-xl font-bold">

Onboard New Vendor

</h2>

<button id="closeVendorModal">

<span class="material-symbols-outlined">

close

</span>

</button>

</div>

<form id="vendorForm" class="p-6 space-y-4">

<input
type="text"
name="vendor_name"
placeholder="Vendor Name"
required
class="w-full border rounded-xl px-4 py-3">

<input
type="text"
name="service_type"
placeholder="Service Category"
required
class="w-full border rounded-xl px-4 py-3">

<input
type="text"
name="data_shared"
placeholder="Data Shared"
required
class="w-full border rounded-xl px-4 py-3">

<div class="flex justify-end gap-md pt-lg">

<button
type="button"
id="cancelVendorModal"
class="px-lg py-md rounded-xl border">

Cancel

</button>

<button
type="submit"
class="bg-primary text-white rounded-xl px-lg py-md">

Save Vendor

</button>

</div>

</form>

</div>

</div>

<script>

const modal=document.getElementById('vendorModal');

document.getElementById('openVendorModal')?.addEventListener('click',()=>{

modal.classList.remove('hidden');

modal.classList.add('flex');

});

function closeVendor(){

modal.classList.add('hidden');

modal.classList.remove('flex');

}

document.getElementById('closeVendorModal')?.addEventListener('click',closeVendor);

document.getElementById('cancelVendorModal')?.addEventListener('click',closeVendor);

window.addEventListener('click',(e)=>{

if(e.target===modal){

closeVendor();

}

});

document.getElementById('vendorForm')?.addEventListener('submit',function(e){

e.preventDefault();

const fd=new FormData(this);

fetch('api/save-vendor.php',{

method:'POST',

body:fd

})

.then(r=>r.json())

.then(data=>{

alert(data.message);

if(data.status==='success'){

location.reload();

}

});

});

</script>