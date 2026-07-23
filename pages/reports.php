<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>Reports &amp; Analytics | PrivacyHQ</title>
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
        body { font-family: 'Inter', sans-serif; background-color: #F3F2F1; }
        .fluent-card {
            background: white;
            border: 1px solid #EDEBE9;
            box-shadow: 0px 2px 4px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fluent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0px 8px 16px rgba(0,0,0,0.06);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .scroll-hide::-webkit-scrollbar { display: none; }
        .bar-chart-anim { animation: growUp 1s ease-out forwards; transform-origin: bottom; scale: 1 0; }
        @keyframes growUp { to { scale: 1 1; } }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-surface text-on-surface antialiased pb-24">
<!-- Top App Bar -->
<header class="fixed top-0 left-0 w-full bg-surface shadow-sm flex justify-between items-center px-container-padding h-16 z-50">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary" data-icon="security">security</span>
<h1 class="font-display text-headline-lg-mobile text-primary tracking-tight">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-md">
<button class="p-2 rounded-full hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
</button>
<div class="w-8 h-8 rounded-full bg-secondary-fixed flex items-center justify-center overflow-hidden border border-outline-variant">
<img class="w-full h-full object-cover" data-alt="A professional headshot of a female compliance officer with a friendly expression. She is wearing professional corporate attire in a bright, modern office with soft daylight and minimalist architectural details in the background." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBMWQcysWTYMgfCJ95Wm5BxAjkmNlkf6xjwvld0bt4cO010jEhWRPaquvY-Bi6OMiD5cU6IqxO2nZAxeiCi8WKCAPTfVXfcL8_7FUHvEwsNbOM4dORAab41yBWd6EFL2Uoh9-KiO9IqjJQziQl_mH9yx7P0l0Ig-KJ3xAVOafyq6kqjemUXFmgvTtKFw3nx77MEDv0s3phdqIq3mkRo593g48bt966BXnGuDxBhtMlxGAdw5pEAwMAmgmc-Ur4LbZQpgk0Sc1e1mA6U"/>
</div>
</div>
</header>
<main class="mt-20 px-container-padding max-w-5xl mx-auto space-y-lg">
<!-- Header & Action -->
<section class="flex flex-col gap-base">
<div class="flex justify-between items-end">
<div>
<p class="font-label-md text-label-md text-primary uppercase tracking-widest">Analytics Engine</p>
<h2 class="font-headline-lg text-headline-lg text-on-surface">Reports &amp; Insights</h2>
</div>
<button class="bg-primary text-on-primary px-md py-sm rounded-lg flex items-center gap-sm hover:opacity-90 transition-all shadow-md active:scale-95">
<span class="material-symbols-outlined !text-[18px]" data-icon="add_chart">add_chart</span>
<span class="font-label-md text-label-md">Schedule Report</span>
</button>
</div>
</section>
<!-- KPI Overview (Bento Style) -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-md">
<div class="fluent-card p-md rounded-xl flex flex-col gap-xs">
<span class="text-on-surface-variant font-label-md text-label-md">Active Audits</span>
<span class="text-display font-display text-primary">12</span>
<span class="text-caption font-caption text-error flex items-center gap-1">
<span class="material-symbols-outlined !text-[12px]" data-icon="trending_up">trending_up</span> 2% vs prev. month
                </span>
</div>
<div class="fluent-card p-md rounded-xl flex flex-col gap-xs">
<span class="text-on-surface-variant font-label-md text-label-md">DSAR Completion</span>
<span class="text-display font-display text-primary">98<span class="text-headline-lg">%</span></span>
<span class="text-caption font-caption text-green-600 flex items-center gap-1">
<span class="material-symbols-outlined !text-[12px]" data-icon="check_circle">check_circle</span> Target reached
                </span>
</div>
<div class="fluent-card p-md rounded-xl col-span-2 flex flex-col justify-between">
<div class="flex justify-between items-start">
<span class="text-on-surface-variant font-label-md text-label-md">Compliance Trend</span>
<span class="material-symbols-outlined text-outline" data-icon="insights">insights</span>
</div>
<div class="flex items-end gap-2 h-12 pt-2">
<div class="flex-1 bg-secondary-fixed h-1/2 rounded-t-sm bar-chart-anim" style="animation-delay: 0.1s"></div>
<div class="flex-1 bg-secondary-fixed h-3/4 rounded-t-sm bar-chart-anim" style="animation-delay: 0.2s"></div>
<div class="flex-1 bg-primary h-full rounded-t-sm bar-chart-anim" style="animation-delay: 0.3s"></div>
<div class="flex-1 bg-secondary-fixed h-2/3 rounded-t-sm bar-chart-anim" style="animation-delay: 0.4s"></div>
<div class="flex-1 bg-secondary-fixed h-4/5 rounded-t-sm bar-chart-anim" style="animation-delay: 0.5s"></div>
</div>
</div>
</section>
<!-- Reports List -->
<section class="space-y-md">
<h3 class="font-title-md text-title-md text-on-surface">Generated Reports</h3>
<div class="space-y-stack-gap">
<!-- Report Item 1 -->
<div class="fluent-card p-md rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-md">
<div class="flex items-center gap-md">
<div class="w-12 h-12 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
<span class="material-symbols-outlined !text-[28px]" data-icon="description">description</span>
</div>
<div>
<h4 class="font-title-md text-body-lg font-semibold text-on-surface">Monthly Compliance Report</h4>
<p class="font-body-md text-body-md text-on-surface-variant">Last generated: Oct 24, 2023 • 4.2 MB</p>
</div>
</div>
<div class="flex items-center gap-lg">
<div class="hidden sm:flex items-end gap-1 h-8 w-24">
<div class="w-2 bg-primary/20 h-3 rounded-t-sm"></div>
<div class="w-2 bg-primary/40 h-5 rounded-t-sm"></div>
<div class="w-2 bg-primary/60 h-4 rounded-t-sm"></div>
<div class="w-2 bg-primary/80 h-7 rounded-t-sm"></div>
<div class="w-2 bg-primary h-6 rounded-t-sm"></div>
</div>
<div class="flex items-center gap-base">
<button class="p-2 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors" title="Download PDF">
<span class="material-symbols-outlined" data-icon="picture_as_pdf">picture_as_pdf</span>
</button>
<button class="p-2 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors" title="Download CSV">
<span class="material-symbols-outlined" data-icon="csv">csv</span>
</button>
<button class="p-2 hover:bg-surface-container rounded-lg text-outline transition-colors">
<span class="material-symbols-outlined" data-icon="more_vert">more_vert</span>
</button>
</div>
</div>
</div>
<!-- Report Item 2 -->
<div class="fluent-card p-md rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-md">
<div class="flex items-center gap-md">
<div class="w-12 h-12 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
<span class="material-symbols-outlined !text-[28px]" data-icon="group_work">group_work</span>
</div>
<div>
<h4 class="font-title-md text-body-lg font-semibold text-on-surface">DSAR Performance</h4>
<p class="font-body-md text-body-md text-on-surface-variant">Last generated: Oct 22, 2023 • 1.8 MB</p>
</div>
</div>
<div class="flex items-center gap-lg">
<div class="hidden sm:flex items-end gap-1 h-8 w-24">
<div class="w-2 bg-primary h-7 rounded-t-sm"></div>
<div class="w-2 bg-primary/80 h-6 rounded-t-sm"></div>
<div class="w-2 bg-primary/60 h-8 rounded-t-sm"></div>
<div class="w-2 bg-primary/40 h-5 rounded-t-sm"></div>
<div class="w-2 bg-primary/20 h-4 rounded-t-sm"></div>
</div>
<div class="flex items-center gap-base">
<button class="p-2 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors" title="Download PDF">
<span class="material-symbols-outlined" data-icon="picture_as_pdf">picture_as_pdf</span>
</button>
<button class="p-2 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors" title="Download CSV">
<span class="material-symbols-outlined" data-icon="csv">csv</span>
</button>
<button class="p-2 hover:bg-surface-container rounded-lg text-outline transition-colors">
<span class="material-symbols-outlined" data-icon="more_vert">more_vert</span>
</button>
</div>
</div>
</div>
<!-- Report Item 3 -->
<div class="fluent-card p-md rounded-xl flex flex-col md:flex-row md:items-center justify-between gap-md">
<div class="flex items-center gap-md">
<div class="w-12 h-12 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
<span class="material-symbols-outlined !text-[28px]" data-icon="corporate_fare">corporate_fare</span>
</div>
<div>
<h4 class="font-title-md text-body-lg font-semibold text-on-surface">Vendor Risk Summary</h4>
<p class="font-body-md text-body-md text-on-surface-variant">Last generated: Oct 15, 2023 • 12.5 MB</p>
</div>
</div>
<div class="flex items-center gap-lg">
<div class="hidden sm:flex items-end gap-1 h-8 w-24">
<div class="w-2 bg-primary/30 h-2 rounded-t-sm"></div>
<div class="w-2 bg-primary/30 h-3 rounded-t-sm"></div>
<div class="w-2 bg-primary/30 h-2 rounded-t-sm"></div>
<div class="w-2 bg-primary/30 h-4 rounded-t-sm"></div>
<div class="w-2 bg-primary h-8 rounded-t-sm"></div>
</div>
<div class="flex items-center gap-base">
<button class="p-2 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors" title="Download PDF">
<span class="material-symbols-outlined" data-icon="picture_as_pdf">picture_as_pdf</span>
</button>
<button class="p-2 hover:bg-surface-container rounded-lg text-on-surface-variant transition-colors" title="Download CSV">
<span class="material-symbols-outlined" data-icon="csv">csv</span>
</button>
<button class="p-2 hover:bg-surface-container rounded-lg text-outline transition-colors">
<span class="material-symbols-outlined" data-icon="more_vert">more_vert</span>
</button>
</div>
</div>
</div>
</div>
</section>
<!-- Dynamic Visualization Section -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-md">
<div class="md:col-span-2 fluent-card rounded-xl overflow-hidden">
<div class="p-md border-b border-surface-variant flex justify-between items-center">
<h3 class="font-title-md text-on-surface">Data Processing Activity</h3>
<div class="flex gap-2">
<span class="w-3 h-3 rounded-full bg-primary"></span>
<span class="w-3 h-3 rounded-full bg-secondary-container"></span>
</div>
</div>
<div class="h-48 w-full relative p-md flex items-end justify-between gap-4">
<div class="absolute inset-0 opacity-10 bg-cover bg-center" data-alt="A sophisticated data visualization dashboard showing abstract data points connecting and pulsating. The style is modern corporate, using a palette of deep blues and soft greys with glowing highlight points. The lighting is low-key with high-contrast data lines creating a sense of technical precision and depth." style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuA7i36-CGXOJ_ra8lvec2HnQyQ_mvCKxJA-a_tjWudjbV8ti-vo2cfQ8uK-9XU37ezJbC5aOTYMPrwI7u-arJ2IjQgqW089ByBd8q7TJjXdD_n5h_geo3Mm6W9XYv396oL6KqOPAdzr8DM9qhaH3r_MO9waAqFqKJ-uR5wjNKbB7m644Q70mC1wqU0bwU1T6uypVNDhd1Mhcb0TN88V-KaMEzQdzNlwwYa9R-xPLQWq2SSAAo7AyvKhHgFhH2lxLd-7HVYpNUUc9XDd')"></div>
<div class="relative z-10 w-full h-full flex items-end gap-2">
<div class="flex-1 bg-primary/80 h-[40%] rounded-t-lg"></div>
<div class="flex-1 bg-primary/80 h-[65%] rounded-t-lg"></div>
<div class="flex-1 bg-primary h-[90%] rounded-t-lg"></div>
<div class="flex-1 bg-primary/80 h-[55%] rounded-t-lg"></div>
<div class="flex-1 bg-primary/80 h-[75%] rounded-t-lg"></div>
<div class="flex-1 bg-primary/80 h-[45%] rounded-t-lg"></div>
<div class="flex-1 bg-primary/80 h-[60%] rounded-t-lg"></div>
</div>
</div>
</div>
<div class="fluent-card p-md rounded-xl flex flex-col justify-center items-center text-center gap-md">
<div class="relative w-24 h-24 flex items-center justify-center">
<svg class="w-full h-full -rotate-90">
<circle class="text-surface-container-high" cx="48" cy="48" fill="transparent" r="40" stroke="currentColor" stroke-width="8"></circle>
<circle class="text-primary transition-all duration-1000" cx="48" cy="48" fill="transparent" r="40" stroke="currentColor" stroke-dasharray="251.2" stroke-dashoffset="62.8" stroke-width="8"></circle>
</svg>
<span class="absolute text-title-md font-display">75%</span>
</div>
<div>
<h4 class="font-title-md text-on-surface">Risk Mitigation</h4>
<p class="font-body-md text-on-surface-variant px-sm">Progress towards quarterly compliance safety goals.</p>
</div>
</div>
</section>
</main>
<!-- Bottom Navigation Bar -->
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
        // Micro-interaction for report item highlighting
        document.querySelectorAll('.fluent-card').forEach(card => {
            card.addEventListener('mousedown', () => {
                card.style.transform = 'scale(0.98)';
            });
            card.addEventListener('mouseup', () => {
                card.style.transform = 'translateY(-2px)';
            });
        });

        // Simple mock search or filter behavior if needed
        console.log("PrivacyHQ Analytics Engine Initialized");
    </script>
</body></html>