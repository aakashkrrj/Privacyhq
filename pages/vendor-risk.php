<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>Vendor Risk Management | PrivacyHQ</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F3F2F1;
        }
        .fluent-card {
            background-color: #ffffff;
            border: 1px solid #EDEBE9;
            box-shadow: 0px 2px 4px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .fluent-card:hover {
            box-shadow: 0px 4px 8px rgba(0,0,0,0.06);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .risk-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        /* Custom Heatmap Grid */
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }
        .heatmap-cell {
            aspect-ratio: 1;
            border-radius: 4px;
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-surface text-on-surface antialiased pb-24 md:pb-0 md:pt-16">
<!-- TopAppBar (Desktop/Mobile Header) -->
<header class="fixed top-0 left-0 w-full bg-surface shadow-sm h-16 flex justify-between items-center px-container-padding z-50 transition-colors duration-200 ease-in-out">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary text-[24px]" data-icon="security">security</span>
<h1 class="font-display text-headline-lg-mobile md:text-headline-lg text-primary">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-md">
<button class="p-2 hover:bg-surface-container-low rounded-full transition-colors">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="search">search</span>
</button>
<button class="p-2 hover:bg-surface-container-low rounded-full transition-colors relative">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
</button>
</div>
</header>
<div class="flex max-w-7xl mx-auto min-h-screen">
<!-- NavigationDrawer (Desktop Only) -->
<aside class="hidden md:flex flex-col py-lg px-md gap-stack-gap h-full w-80 bg-surface shadow-lg rounded-r-xl sticky top-16">
<div class="flex flex-col gap-sm mb-lg px-2">
<div class="flex items-center gap-md">
<img class="w-12 h-12 rounded-full object-cover" data-alt="Professional headshot of a female compliance officer in a modern office, soft bokeh background, high-key lighting, corporate professional aesthetic with blue and white tones." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBYpczt8Cuc5T1Wylg_FGNYtqvZIjb1pL2aAaMcdw3Ot3dnkhL368VfuWo7hzgJhU-B5N5RZLJ8cu9xSqahW-sPms43PCjnx3XVUNY59sxuvjDVtqSSQ9j5PB7JMjYNYL7H8psSli5xBQfu5AoCGRUuj8Wuf6TwlvItCA8tPeE--9COi9AS7__V0nFXIS-pcAaLp8UQtPxof4iV2FIE4HIQ0SFxmVrhicOGaKyq26WcKa62skbLnfBlC0xuXHgE66iA69Dj2JFTqtFU"/>
<div class="flex flex-col">
<span class="font-title-md text-on-surface">Admin User</span>
<span class="font-label-md text-outline">Data Protection Officer</span>
</div>
</div>
</div>
<nav class="flex flex-col gap-1">
<a class="flex items-center gap-md px-md py-sm rounded-lg text-primary font-bold bg-secondary-fixed transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="business_center">business_center</span>
<span class="font-body-md">Vendor Risk</span>
</a>
<a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="search_insights">search_insights</span>
<span class="font-body-md">Data Discovery</span>
</a>
<a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="emergency_home">emergency_home</span>
<span class="font-body-md">Incidents</span>
</a>
<a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="analytics">analytics</span>
<span class="font-body-md">Reports</span>
</a>
<hr class="my-2 border-surface-variant"/>
<a class="flex items-center gap-md px-md py-sm rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-all duration-200" href="#">
<span class="material-symbols-outlined" data-icon="settings">settings</span>
<span class="font-body-md">Settings</span>
</a>
</nav>
</aside>
<!-- Main Content Canvas -->
<main class="flex-1 p-container-padding md:p-lg overflow-x-hidden">
<!-- Bento Dashboard Header -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-md md:gap-lg mb-lg">
<!-- Total Vendors Summary -->
<div class="md:col-span-4 fluent-card rounded-xl p-md flex flex-col justify-between">
<div>
<div class="flex items-center justify-between mb-sm">
<span class="font-label-md text-outline">TOTAL VENDORS</span>
<span class="material-symbols-outlined text-primary" data-icon="corporate_fare">corporate_fare</span>
</div>
<div class="text-[48px] font-bold text-on-surface tracking-tight leading-none">142</div>
</div>
<div class="mt-md flex items-center gap-xs">
<span class="text-green-600 flex items-center font-label-md">
<span class="material-symbols-outlined text-[16px]" data-icon="arrow_upward">arrow_upward</span>
                            12%
                        </span>
<span class="font-label-md text-outline">vs last quarter</span>
</div>
</div>
<!-- Risk Heatmap (Mini) -->
<div class="md:col-span-8 fluent-card rounded-xl p-md grid grid-cols-1 md:grid-cols-2 gap-md">
<div>
<h3 class="font-title-md text-on-surface mb-sm">Risk Distribution</h3>
<div class="heatmap-grid">
<div class="heatmap-cell bg-error/10"></div>
<div class="heatmap-cell bg-error/40"></div>
<div class="heatmap-cell bg-error"></div>
<div class="heatmap-cell bg-secondary-container/20"></div>
<div class="heatmap-cell bg-secondary-container/60"></div>
<div class="heatmap-cell bg-error/30"></div>
<div class="heatmap-cell bg-green-500/10"></div>
<div class="heatmap-cell bg-green-500/30"></div>
<div class="heatmap-cell bg-green-500/60"></div>
</div>
</div>
<div class="flex flex-col justify-center gap-sm">
<div class="flex items-center justify-between">
<span class="font-body-md text-on-surface-variant flex items-center gap-sm">
<span class="w-3 h-3 rounded-full bg-error"></span> High Risk
                            </span>
<span class="font-label-md font-bold">14</span>
</div>
<div class="flex items-center justify-between">
<span class="font-body-md text-on-surface-variant flex items-center gap-sm">
<span class="w-3 h-3 rounded-full bg-secondary-container"></span> Medium Risk
                            </span>
<span class="font-label-md font-bold">48</span>
</div>
<div class="flex items-center justify-between">
<span class="font-body-md text-on-surface-variant flex items-center gap-sm">
<span class="w-3 h-3 rounded-full bg-green-500"></span> Low Risk
                            </span>
<span class="font-label-md font-bold">80</span>
</div>
</div>
</div>
</div>
<!-- Search and Actions Bar -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
<div class="relative flex-1 max-w-md">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
<input class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none text-body-md transition-all" placeholder="Search vendors, products, or scores..." type="text"/>
</div>
<div class="flex items-center gap-sm">
<button class="flex items-center gap-sm px-md py-2 border border-outline-variant rounded-lg font-label-md text-primary hover:bg-surface-container-high transition-colors">
<span class="material-symbols-outlined text-[18px]" data-icon="filter_list">filter_list</span>
                        Filters
                    </button>
<button class="flex items-center gap-sm px-md py-2 bg-primary text-white rounded-lg font-label-md shadow-sm hover:brightness-110 transition-all">
<span class="material-symbols-outlined text-[18px]" data-icon="add">add</span>
                        Onboard Vendor
                    </button>
</div>
</div>
<!-- Vendor List (Table Style in Card) -->
<div class="fluent-card rounded-xl overflow-hidden mb-xl">
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant">
<th class="px-md py-lg font-label-md text-outline uppercase tracking-wider">Vendor &amp; Service</th>
<th class="px-md py-lg font-label-md text-outline uppercase tracking-wider">Risk Score</th>
<th class="px-md py-lg font-label-md text-outline uppercase tracking-wider">Status</th>
<th class="px-md py-lg font-label-md text-outline uppercase tracking-wider">Last Assessment</th>
<th class="px-md py-lg font-label-md text-outline uppercase tracking-wider text-right">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-variant">
<!-- Vendor Item 1 -->
<tr class="hover:bg-surface-container-lowest transition-colors group">
<td class="px-md py-md">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="Minimalist vector logo for a cloud data warehouse company, featuring abstract geometric blue shapes on a clean white background, modern corporate style." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBNS5eNfh6JrvTzP3C7ScMjmuq6f7MHkmMc1C516R42NKY--nAXi9U0_rtrlsgcWZy0EXtRhXx5zaVzSsh0l9nDK7_noC783T9W36nFxF28QfxZMKfC7NYDx1EHYD0r1Je4GSKBVZd5FsXWkBDW-7aX1gy2DB1nfY0h4kIAKgnLeU18VIdGP-Xkq2ptMTt1swDMhW10KGwRHrpE3UOZoa05XoP7lM1OF7HvYiWmk1pRQ7xROjdh0iKhePYhtBP9y2MclBkycXvzDuoY"/>
</div>
<div>
<div class="font-title-md text-on-surface">Snowflake Inc.</div>
<div class="font-caption text-outline">Cloud Data Warehouse</div>
</div>
</div>
</td>
<td class="px-md py-md">
<div class="flex items-center gap-sm">
<div class="w-16 h-2 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-green-500 w-[15%]"></div>
</div>
<span class="font-label-md font-bold text-on-surface">15</span>
</div>
</td>
<td class="px-md py-md">
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Compliant
                                    </span>
</td>
<td class="px-md py-md text-body-md text-on-surface-variant">
                                    Oct 24, 2023
                                </td>
<td class="px-md py-md text-right">
<button class="p-2 text-outline hover:text-primary transition-colors">
<span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
</button>
</td>
</tr>
<!-- Vendor Item 2 -->
<tr class="hover:bg-surface-container-lowest transition-colors group">
<td class="px-md py-md">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="Logotype for a global technology infrastructure company, bold sans-serif lettering, high contrast professional design, corporate blue and charcoal color palette." src="https://lh3.googleusercontent.com/aida-public/AB6AXuA4QzyPL93WBSvXJVYzi8679LNJwzuhkmEeKIqvxIac1G4TMWJPm0u4YpA1nm58EQ5n-Dr7X6lkfj1jC9Lrxnwz8TpsPNHyKCLJx738PnK29eaTqcO8_no2dd5TrEZ899B9cJt0Dyhw5tDjitKVEnAew0B2yo25n4Zehs_yYl8K2Qkc5QAl0yrnQ5ePPUQUGfP8VJO4H86-fGOGlc4ygMev0b-g6KtBeK96dMiadwpoYbSD94yQaVMwvWmgFNLpGD3mL00JaDo7awvG"/>
</div>
<div>
<div class="font-title-md text-on-surface">GlobalLink CRM</div>
<div class="font-caption text-outline">Marketing Platforms</div>
</div>
</div>
</td>
<td class="px-md py-md">
<div class="flex items-center gap-sm">
<div class="w-16 h-2 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-error w-[72%]"></div>
</div>
<span class="font-label-md font-bold text-on-surface">72</span>
</div>
</td>
<td class="px-md py-md">
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Critical Review
                                    </span>
</td>
<td class="px-md py-md text-body-md text-on-surface-variant">
                                    Jan 12, 2024
                                </td>
<td class="px-md py-md text-right">
<button class="p-2 text-outline hover:text-primary transition-colors">
<span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
</button>
</td>
</tr>
<!-- Vendor Item 3 -->
<tr class="hover:bg-surface-container-lowest transition-colors group">
<td class="px-md py-md">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="Iconic logo for a major tech conglomerate, minimalist modern design, vibrant primary colors, professional and high-trust aesthetic for a global technology leader." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCGEXLnW_sqL4y8zQcJ5BLOujZASIMUVY9Q6zTrP7AI7GKwqa1wgtuz9-lbqaINn-k627f0zXR2PJFMneEMNb2ykbR9ZwGzg8r95W2ufwwOioK4bkNiW8IjG93d52UkA10bxDczq1KH08MIOa_n3BzdZgp3VOw8EXgCsS8KZHF6TSQ8TTbU3qKvpQHop7iA8WLBe7tIFhe-DrsbG2-bmc3XZzNPSjQh8h-uyfJCb2ioY1Vk0_hWI0WxrEWfSdVqenJZ0VaGpIXVFZo3"/>
</div>
<div>
<div class="font-title-md text-on-surface">Zenith AI</div>
<div class="font-caption text-outline">ML Operations</div>
</div>
</div>
</td>
<td class="px-md py-md">
<div class="flex items-center gap-sm">
<div class="w-16 h-2 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-secondary-container w-[45%]"></div>
</div>
<span class="font-label-md font-bold text-on-surface">45</span>
</div>
</td>
<td class="px-md py-md">
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Under Audit
                                    </span>
</td>
<td class="px-md py-md text-body-md text-on-surface-variant">
                                    Feb 02, 2024
                                </td>
<td class="px-md py-md text-right">
<button class="p-2 text-outline hover:text-primary transition-colors">
<span class="material-symbols-outlined" data-icon="chevron_right">chevron_right</span>
</button>
</td>
</tr>
</tbody>
</table>
</div>
<!-- Pagination -->
<div class="bg-surface-container-low px-md py-sm flex items-center justify-between">
<span class="font-label-md text-outline">Showing 1-10 of 142 vendors</span>
<div class="flex items-center gap-xs">
<button class="p-2 rounded-lg hover:bg-surface-container-high disabled:opacity-50" disabled="">
<span class="material-symbols-outlined" data-icon="navigate_before">navigate_before</span>
</button>
<button class="p-2 rounded-lg hover:bg-surface-container-high">
<span class="material-symbols-outlined" data-icon="navigate_next">navigate_next</span>
</button>
</div>
</div>
</div>
</main>
</div>
<!-- BottomNavBar (Mobile Only) -->
<!-- Standard Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 w-full z-50
            bg-surface shadow-[0px_-2px_4px_rgba(0,0,0,0.04)]
            flex justify-around items-center h-16 px-2">

    <!-- Dashboard -->
    <a href="/governance/index.php"
   class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-xl">

    <span class="material-symbols-outlined"
          style="font-variation-settings:'FILL' 0;">
        dashboard
    </span>

    <span class="font-label-md text-label-md">
        Dashboard
    </span>

</a>

    <!-- Consent -->
    <a href="/governance/pages/consent-management.php"
       class="nav-consent flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            verified_user
        </span>

        <span class="font-label-md text-label-md">Consent</span>
    </a>

    <!-- Requests -->
    <a href="/governance/pages/data-requests.php"
       class="nav-requests flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            gavel
        </span>

        <span class="font-label-md text-label-md">Requests</span>
    </a>

    <!-- Assess -->
    <a href="/governance/pages/assessments.php"
       class="nav-assess flex flex-col items-center justify-center
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
          text-primary bg-secondary-fixed
          rounded-xl px-4 py-1">
<span class="material-symbols-outlined"
      style="font-variation-settings:'FILL' 0;">
    menu
</span>

<span class="font-label-md text-label-md">More</span>
    </a>

</nav>
<script>
        // Simple Interaction logic
        document.querySelectorAll('tr').forEach(row => {
            row.addEventListener('click', () => {
                // In a real app, this would navigate to the vendor detail page
                console.log('Navigate to vendor detail');
            });
        });

        // Add active states to navigation items
        const navItems = document.querySelectorAll('nav a');
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                navItems.forEach(i => {
                    i.classList.remove('bg-secondary-fixed', 'text-primary');
                    i.classList.add('text-on-surface-variant');
                });
                item.classList.add('bg-secondary-fixed', 'text-primary');
                item.classList.remove('text-on-surface-variant');
            });
        });
    </script>
</body></html>