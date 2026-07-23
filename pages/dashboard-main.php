<?php
// Include DB Connection
require_once __DIR__ . '/../includes/db.php';

// Initialize default counts
$vendor_count = 0;
$incident_count = 0;
$assessment_count = 0;

if (isset($conn) && !$conn->connect_error) {
    // 1. Get total vendors
    $res = $conn->query("SELECT COUNT(*) AS total FROM vendors");
    if ($res) {
        $vendor_count = $res->fetch_assoc()['total'];
    }

    // 2. Get active incidents
    $res = $conn->query("SELECT COUNT(*) AS total FROM incidents WHERE status != 'Resolved'");
    if ($res) {
        $incident_count = $res->fetch_assoc()['total'];
    }

    // 3. Get total assessments
    $res = $conn->query("SELECT COUNT(*) AS total FROM assessments");
    if ($res) {
        $assessment_count = $res->fetch_assoc()['total'];
    }
}
?>

<!-- Welcome Header -->
<section class="mt-md">
    <h2 class="font-headline-lg-mobile text-headline-lg-mobile md:font-headline-lg md:text-headline-lg text-on-surface">Welcome back, Sarah</h2>
    <p class="font-body-md text-on-surface-variant">Here is your data protection summary for today.</p>
</section>

<!-- Section 1: Circular Progress Gauges -->
<section class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <!-- Privacy Compliance Score -->
    <div class="bg-surface-container-lowest p-lg rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex items-center justify-between group hover:shadow-md transition-shadow">
        <div>
            <h3 class="font-title-md text-on-surface">Privacy Compliance Score</h3>
            <p class="font-caption text-outline mt-xs">Across all business units</p>
            <div class="mt-md flex items-center gap-xs text-[#107C10]">
                <span class="material-symbols-outlined text-sm" data-icon="trending_up">trending_up</span>
                <span class="font-label-md">4.2% from last month</span>
            </div>
        </div>
        <div class="relative w-24 h-24">
            <svg class="gauge-svg w-24 h-24" viewBox="0 0 100 100">
                <circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
                <circle class="text-primary stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-dasharray="251.2" stroke-dashoffset="30.14" stroke-linecap="round" stroke-width="10"></circle>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="font-headline-lg-mobile text-headline-lg-mobile text-primary">88%</span>
            </div>
        </div>
    </div>

    <!-- DPDP Compliance Score -->
    <div class="bg-surface-container-lowest p-lg rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex items-center justify-between group hover:shadow-md transition-shadow">
        <div>
            <h3 class="font-title-md text-on-surface">DPDP Compliance Score</h3>
            <p class="font-caption text-outline mt-xs">India Regulatory Framework</p>
            <div class="mt-md flex items-center gap-xs text-[#107C10]">
                <span class="material-symbols-outlined text-sm" data-icon="verified">verified</span>
                <span class="font-label-md">Highly Compliant</span>
            </div>
        </div>
        <div class="relative w-24 h-24">
            <svg class="gauge-svg w-24 h-24" viewBox="0 0 100 100">
                <circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
                <circle class="text-secondary-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-dasharray="251.2" stroke-dashoffset="20.09" stroke-linecap="round" stroke-width="10"></circle>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="font-headline-lg-mobile text-headline-lg-mobile text-on-secondary-container">92%</span>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Summary Cards Grid (Dynamic PHP Values) -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-md">
    <div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
        <span class="material-symbols-outlined text-primary mb-xs" data-icon="assignment_turned_in">assignment_turned_in</span>
        <span class="font-display text-[24px] font-bold"><?php echo $assessment_count; ?></span>
        <span class="font-caption text-outline">DPIA Assessments</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
        <span class="material-symbols-outlined text-secondary mb-xs" data-icon="group">group</span>
        <span class="font-display text-[24px] font-bold"><?php echo $vendor_count; ?></span>
        <span class="font-caption text-outline">Total Vendors</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
        <span class="material-symbols-outlined text-error mb-xs" data-icon="warning">warning</span>
        <span class="inline-flex items-center gap-xs px-2 py-0.5 bg-error/10 text-error rounded-full w-fit">
            <span class="font-label-md font-bold">Medium</span>
        </span>
        <span class="font-caption text-outline">Vendor Risk</span>
    </div>
    <div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
        <span class="material-symbols-outlined text-tertiary mb-xs" data-icon="error">error</span>
        <span class="font-display text-[24px] font-bold"><?php echo $incident_count; ?></span>
        <span class="font-caption text-outline">Active Incidents</span>
    </div>
</section>

<!-- Section 3: Analytics Card -->
<section>
    <div class="bg-surface-container-lowest p-lg rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9]">
        <div class="flex justify-between items-center mb-lg">
            <div>
                <h3 class="font-title-md text-on-surface">Privacy Request Trends</h3>
                <p class="font-body-md text-on-surface-variant">DSAR and Erasure requests over last 30 days</p>
            </div>
            <select class="bg-surface-container-low border-none rounded-lg text-sm font-label-md px-3 py-1 focus:ring-2 focus:ring-primary">
                <option>Last 30 days</option>
                <option>Last 3 months</option>
            </select>
        </div>
        <div class="h-64 w-full relative">
            <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 40">
                <defs>
                    <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="5%" stop-color="#0078D4" stop-opacity="0.1"></stop>
                        <stop offset="95%" stop-color="#0078D4" stop-opacity="0"></stop>
                    </linearGradient>
                </defs>
                <line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="10" y2="10"></line>
                <line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="20" y2="20"></line>
                <line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="30" y2="30"></line>
                <path d="M0 40 L0 30 L10 32 L20 25 L30 28 L40 15 L50 20 L60 10 L70 12 L80 5 L90 8 L100 4 L100 40 Z" fill="url(#chartGradient)"></path>
                <path d="M0 30 L10 32 L20 25 L30 28 L40 15 L50 20 L60 10 L70 12 L80 5 L90 8 L100 4" fill="none" stroke="#0078D4" stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8"></path>
                <circle cx="20" cy="25" fill="#0078D4" r="1"></circle>
                <circle cx="40" cy="15" fill="#0078D4" r="1"></circle>
                <circle cx="60" cy="10" fill="#0078D4" r="1"></circle>
                <circle cx="80" cy="5" fill="#0078D4" r="1"></circle>
            </svg>
            <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-[10px] text-outline opacity-50">
                <span>100</span>
                <span>75</span>
                <span>50</span>
                <span>25</span>
            </div>
        </div>
        <div class="flex justify-between mt-md px-base text-caption text-outline">
            <span>Oct 01</span>
            <span>Oct 08</span>
            <span>Oct 15</span>
            <span>Oct 22</span>
            <span>Oct 30</span>
        </div>
    </div>
</section>

<!-- Section 4: Quick Actions with Functional Routing -->
<section class="pb-lg">
    <h3 class="font-title-md text-on-surface mb-md">Quick Actions</h3>
    <div class="flex gap-md overflow-x-auto hide-scrollbar pb-xs">
        
        <!-- 1. New Assessment -->
        <a href="index.php?page=assessments" class="flex-shrink-0 flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl shadow-sm hover:brightness-95 transition-all active:scale-95">
            <span class="material-symbols-outlined" data-icon="assignment_add">assignment_add</span>
            <span class="font-label-md whitespace-nowrap">New Assessment</span>
        </a>

        <!-- 2. Add Vendor -->
        <a href="index.php?page=vendor-risk" class="flex-shrink-0 flex items-center gap-sm bg-surface-container-lowest border border-[#D2D0CE] text-primary px-lg py-md rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95">
            <span class="material-symbols-outlined" data-icon="person_add">person_add</span>
            <span class="font-label-md whitespace-nowrap">Add Vendor</span>
        </a>

        <!-- 3. Report Incident -->
        <a href="index.php?page=incident-management" class="flex-shrink-0 flex items-center gap-sm bg-surface-container-lowest border border-[#D2D0CE] text-error px-lg py-md rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95">
            <span class="material-symbols-outlined" data-icon="report_gmailerrorred">report_gmailerrorred</span>
            <span class="font-label-md whitespace-nowrap">Report Incident</span>
        </a>

        <!-- 4. Export Report -->
        <a href="index.php?page=reports" class="flex-shrink-0 flex items-center gap-sm bg-surface-container-lowest border border-[#D2D0CE] text-primary px-lg py-md rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95">
            <span class="material-symbols-outlined" data-icon="export_notes">export_notes</span>
            <span class="font-label-md whitespace-nowrap">Export Report</span>
        </a>

    </div>
</section>