<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/backend/services/DashboardService.php';

requireLogin();

$dashboardService = new DashboardService();
$stats = $dashboardService->getDashboardStats();
$compliance = $dashboardService->getComplianceOverview();

function formatNumber($num) {
    if ($num >= 1000) {
        return round($num / 1000, 1) . 'k';
    }
    return $num;
}
?>
<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>PrivacyHQ Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-surface-variant": "#404752",
                        "on-secondary-container": "#003f6d",
                        "secondary-fixed": "#d1e4ff",
                        "outline": "#717783",
                        "surface-variant": "#e3e2e1",
                        "on-secondary": "#ffffff",
                        "surface-dim": "#dadad9",
                        "on-tertiary-fixed-variant": "#004881",
                        "error": "#ba1a1a",
                        "tertiary-fixed": "#d3e4ff",
                        "secondary": "#0061a3",
                        "on-secondary-fixed-variant": "#00497d",
                        "surface": "#faf9f8",
                        "on-primary-container": "#ffffff",
                        "on-secondary-fixed": "#001d36",
                        "tertiary-container": "#2679c9",
                        "on-surface": "#1a1c1c",
                        "surface-container": "#efeeed",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-high": "#e9e8e7",
                        "secondary-fixed-dim": "#9ecaff",
                        "surface-bright": "#faf9f8",
                        "on-background": "#1a1c1c",
                        "error-container": "#ffdad6",
                        "on-tertiary": "#ffffff",
                        "background": "#faf9f8",
                        "on-primary": "#ffffff",
                        "inverse-surface": "#2f3130",
                        "tertiary": "#0060a9",
                        "on-tertiary-fixed": "#001c38",
                        "primary-container": "#0078d4",
                        "tertiary-fixed-dim": "#a2c9ff",
                        "primary-fixed": "#d3e3ff",
                        "secondary-container": "#5badff",
                        "surface-tint": "#0060ab",
                        "on-tertiary-container": "#ffffff",
                        "on-primary-fixed": "#001c39",
                        "primary": "#005faa",
                        "primary-fixed-dim": "#a3c9ff",
                        "inverse-primary": "#a3c9ff",
                        "on-error-container": "#93000a",
                        "on-primary-fixed-variant": "#004883",
                        "surface-container-low": "#f4f3f2",
                        "surface-container-highest": "#e3e2e1",
                        "inverse-on-surface": "#f1f0ef",
                        "on-error": "#ffffff",
                        "outline-variant": "#c0c7d4"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "sm": "8px",
                        "base": "4px",
                        "stack-gap": "12px",
                        "md": "16px",
                        "xs": "4px",
                        "container-padding": "16px",
                        "lg": "24px",
                        "xl": "32px"
                    },
                    "fontFamily": {
                        "headline-lg-mobile": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-lg": ["Inter"],
                        "display": ["Inter"],
                        "title-md": ["Inter"],
                        "body-md": ["Inter"],
                        "caption": ["Inter"],
                        "label-md": ["Inter"]
                    },
                    "fontSize": {
                        "headline-lg-mobile": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "display": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "title-md": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "caption": ["11px", {"lineHeight": "14px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.01em", "fontWeight": "500"}]
                    }
                },
            },
        }
    </script>
<style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid #EDEBE9;
        }
        .gauge-svg { transform: rotate(-90deg); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-[#F3F2F1] text-on-surface min-h-screen pb-24 md:pb-0">
<!-- TopAppBar Mapping -->
<header class="fixed top-0 left-0 w-full bg-surface shadow-sm flex justify-between items-center px-container-padding h-16 z-50 transition-colors duration-200 ease-in-out">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary" data-icon="security">security</span>
<h1 class="font-display text-display text-primary leading-none">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-md">
<button class="relative p-2 rounded-full hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full border-2 border-surface"></span>
</button>
<div class="hidden md:flex items-center gap-sm border-l border-outline-variant pl-md ml-base">
<div class="text-right">
<p class="font-title-md text-on-surface leading-tight">Admin User</p>
<p class="font-caption text-outline">DPO Officer</p>
</div>
<div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center">
<span class="material-symbols-outlined text-on-primary-fixed" data-icon="person">person</span>
</div>
</div>
</div>
</header>
<main class="pt-20 px-container-padding max-w-7xl mx-auto space-y-lg">
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
<svg class="gauge-svg w-24 h-24" viewbox="0 0 100 100">
<circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
<circle class="text-primary stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-dasharray="251.2" stroke-dashoffset="30.14" stroke-linecap="round" stroke-width="10"></circle>
</svg>
<div class="absolute inset-0 flex items-center justify-center">
<span class="font-headline-lg-mobile text-headline-lg-mobile text-primary"><?= $compliance['privacy_score'] ?>%</span>
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
<svg class="gauge-svg w-24 h-24" viewbox="0 0 100 100">
<circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
<circle class="text-secondary-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-dasharray="251.2" stroke-dashoffset="20.09" stroke-linecap="round" stroke-width="10"></circle>
</svg>
<div class="absolute inset-0 flex items-center justify-center">
<span class="font-headline-lg-mobile text-headline-lg-mobile text-on-secondary-container"><?= $compliance['dpdp_score'] ?>%</span>
</div>
</div>
</div>
</section>
<!-- Section 2: Summary Cards Grid -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-md">
<div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
<span class="material-symbols-outlined text-primary mb-xs" data-icon="task_alt">task_alt</span>
<span class="font-display text-[24px] font-bold"><?= formatNumber($stats['active_consents']) ?></span>
<span class="font-caption text-outline">Active Consents</span>
</div>
<div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
<span class="material-symbols-outlined text-secondary mb-xs" data-icon="pending_actions">pending_actions</span>
<span class="font-display text-[24px] font-bold"><?= $stats['pending_requests'] ?></span>
<span class="font-caption text-outline">Pending Requests</span>
</div>
<div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
<span class="material-symbols-outlined text-error mb-xs" data-icon="warning">warning</span>
<span class="inline-flex items-center gap-xs px-2 py-0.5 bg-error/10 text-error rounded-full w-fit">
<span class="font-label-md font-bold"><?= htmlspecialchars($stats['vendor_risk_label']) ?></span>
</span>
<span class="font-caption text-outline">Vendor Risk</span>
</div>
<div class="bg-surface-container-lowest p-md rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col gap-xs">
<span class="material-symbols-outlined text-tertiary mb-xs" data-icon="error">error</span>
<span class="font-display text-[24px] font-bold"><?= $stats['active_incidents'] ?></span>
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
<!-- Placeholder for Chart - Visualized with SVG for stability -->
<svg class="w-full h-full" preserveaspectratio="none" viewbox="0 0 100 40">
<defs>
<lineargradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
<stop offset="5%" stop-color="#0078D4" stop-opacity="0.1"></stop>
<stop offset="95%" stop-color="#0078D4" stop-opacity="0"></stop>
</lineargradient>
</defs>
<!-- Grid Lines -->
<line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="10" y2="10"></line>
<line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="20" y2="20"></line>
<line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="30" y2="30"></line>
<!-- Area -->
<path d="M0 40 L0 30 L10 32 L20 25 L30 28 L40 15 L50 20 L60 10 L70 12 L80 5 L90 8 L100 4 L100 40 Z" fill="url(#chartGradient)"></path>
<!-- Line -->
<path d="M0 30 L10 32 L20 25 L30 28 L40 15 L50 20 L60 10 L70 12 L80 5 L90 8 L100 4" fill="none" stroke="#0078D4" stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8"></path>
<!-- Data Points -->
<circle cx="20" cy="25" fill="#0078D4" r="1"></circle>
<circle cx="40" cy="15" fill="#0078D4" r="1"></circle>
<circle cx="60" cy="10" fill="#0078D4" r="1"></circle>
<circle cx="80" cy="5" fill="#0078D4" r="1"></circle>
</svg>
<!-- Y-Axis labels -->
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
<!-- Section 4: Quick Actions -->
<section class="pb-lg">
<h3 class="font-title-md text-on-surface mb-md">Quick Actions</h3>
<div class="flex gap-md overflow-x-auto hide-scrollbar pb-xs">
<button class="flex-shrink-0 flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl shadow-sm hover:brightness-95 transition-all active:scale-95">
<span class="material-symbols-outlined" data-icon="assignment_add">assignment_add</span>
<span class="font-label-md whitespace-nowrap">New Assessment</span>
</button>
<button class="flex-shrink-0 flex items-center gap-sm bg-surface-container-lowest border border-[#D2D0CE] text-primary px-lg py-md rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95">
<span class="material-symbols-outlined" data-icon="person_add">person_add</span>
<span class="font-label-md whitespace-nowrap">Add Vendor</span>
</button>
<button class="flex-shrink-0 flex items-center gap-sm bg-surface-container-lowest border border-[#D2D0CE] text-primary px-lg py-md rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95">
<span class="material-symbols-outlined" data-icon="report_gmailerrorred">report_gmailerrorred</span>
<span class="font-label-md whitespace-nowrap">Report Incident</span>
</button>
<button class="flex-shrink-0 flex items-center gap-sm bg-surface-container-lowest border border-[#D2D0CE] text-primary px-lg py-md rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95">
<span class="material-symbols-outlined" data-icon="export_notes">export_notes</span>
<span class="font-label-md whitespace-nowrap">Export Report</span>
</button>
</div>
</section>
</main>
<!-- BottomNavBar Mapping -->
<!-- Standard Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 w-full z-50
            bg-surface shadow-[0px_-2px_4px_rgba(0,0,0,0.04)]
            flex justify-around items-center h-16 px-2">

    <!-- Dashboard Active -->
    <a href="/governance/index.php"
       class="flex flex-col items-center justify-center text-primary
              bg-secondary-fixed rounded-xl px-4 py-1">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            dashboard
        </span>

        <span class="font-label-md text-label-md">Dashboard</span>
    </a>

    <!-- Consent -->
    <a href="/governance/pages/consent-management.php"
       class="flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            verified_user
        </span>

        <span class="font-label-md text-label-md">Consent</span>
    </a>

    <!-- Requests -->
    <a href="/governance/pages/data-requests.php"
       class="flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            gavel
        </span>

        <span class="font-label-md text-label-md">Requests</span>
    </a>

    <!-- Assess -->
    <a href="/governance/pages/assessments.php"
       class="flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            assignment_turned_in
        </span>

        <span class="font-label-md text-label-md">Assess</span>
    </a>

    <!-- More -->
    <a href="/governance/pages/more.php"
       class="flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            menu
        </span>

        <span class="font-label-md text-label-md">More</span>
    </a>

</nav>
<!-- Visual Polish: Decorative Element -->
<div class="fixed top-0 right-0 -z-10 w-1/3 h-1/2 bg-gradient-to-bl from-primary/5 to-transparent blur-3xl pointer-events-none"></div>
<div class="fixed bottom-0 left-0 -z-10 w-1/3 h-1/2 bg-gradient-to-tr from-secondary-container/5 to-transparent blur-3xl pointer-events-none"></div>
<script>
        // Micro-interactions
        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('mouseenter', () => {
                // Subtle lift or feedback could be added here
            });
        });
    </script>
</body></html>