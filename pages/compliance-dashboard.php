<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>PrivacyHQ - Compliance Dashboard</title>
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Google Font: Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "surface-container-low": "#f4f3f2",
                    "surface-container-highest": "#e3e2e1",
                    "tertiary-container": "#2679c9",
                    "primary": "#005faa",
                    "on-tertiary-container": "#ffffff",
                    "on-primary-fixed-variant": "#004883",
                    "surface-container-high": "#e9e8e7",
                    "surface-container": "#efeeed",
                    "on-tertiary-fixed-variant": "#004881",
                    "on-primary-container": "#ffffff",
                    "inverse-on-surface": "#f1f0ef",
                    "surface-bright": "#faf9f8",
                    "surface": "#faf9f8",
                    "on-secondary-container": "#003f6d",
                    "secondary-fixed-dim": "#9ecaff",
                    "secondary-fixed": "#d1e4ff",
                    "background": "#faf9f8",
                    "secondary": "#0061a3",
                    "inverse-primary": "#a3c9ff",
                    "primary-fixed-dim": "#a3c9ff",
                    "outline-variant": "#c0c7d4",
                    "on-error": "#ffffff",
                    "tertiary-fixed-dim": "#a2c9ff",
                    "on-primary": "#ffffff",
                    "on-background": "#1a1c1c",
                    "tertiary-fixed": "#d3e4ff",
                    "primary-fixed": "#d3e3ff",
                    "surface-container-lowest": "#ffffff",
                    "on-primary-fixed": "#001c39",
                    "primary-container": "#0078d4",
                    "on-surface": "#1a1c1c",
                    "on-secondary-fixed-variant": "#00497d",
                    "surface-dim": "#dadad9",
                    "error-container": "#ffdad6",
                    "on-secondary-fixed": "#001d36",
                    "on-error-container": "#93000a",
                    "on-tertiary": "#ffffff",
                    "error": "#ba1a1a",
                    "on-tertiary-fixed": "#001c38",
                    "tertiary": "#0060a9",
                    "on-surface-variant": "#404752",
                    "on-secondary": "#ffffff",
                    "outline": "#717783",
                    "inverse-surface": "#2f3130",
                    "surface-tint": "#0060ab",
                    "surface-variant": "#e3e2e1",
                    "secondary-container": "#5badff"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "stack-gap": "12px",
                    "xl": "32px",
                    "base": "4px",
                    "xs": "4px",
                    "md": "16px",
                    "lg": "24px",
                    "container-padding": "16px",
                    "sm": "8px"
            },
            "fontFamily": {
                    "headline-lg": ["Inter"],
                    "caption": ["Inter"],
                    "body-md": ["Inter"],
                    "body-lg": ["Inter"],
                    "title-md": ["Inter"],
                    "label-md": ["Inter"],
                    "headline-lg-mobile": ["Inter"],
                    "display": ["Inter"]
            },
            "fontSize": {
                    "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "caption": ["11px", {"lineHeight": "14px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "title-md": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                    "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.01em", "fontWeight": "500"}],
                    "headline-lg-mobile": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "display": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}]
            }
          },
        },
      }
    </script>
<style>
        body { font-family: 'Inter', sans-serif; background-color: #faf9f8; color: #1a1c1c; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid #EDEBE9;
        }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
<style>
    body {
      min-height: 100dvh;
    }
  </style>
  </head>
<body class="bg-background min-h-screen pb-24">
<!-- TopAppBar -->
<header class="bg-surface dark:bg-surface-dim shadow-[0px_2px_4px_rgba(0,0,0,0.04)] docked full-width top-0 sticky z-50 flex justify-between items-center px-container-padding h-16 w-full">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary dark:text-primary-fixed" style="font-variation-settings: 'FILL' 1;">security</span>
<span class="font-headline-lg-mobile text-headline-lg-mobile font-bold text-primary dark:text-primary-fixed">PrivacyHQ</span>
</div>
<div class="flex items-center gap-4">
<div class="hidden md:flex bg-surface-container rounded-lg px-3 py-1.5 items-center gap-2 border border-outline-variant/30">
<span class="material-symbols-outlined text-on-surface-variant text-sm">search</span>
<input class="bg-transparent border-none focus:ring-0 text-body-md w-48" placeholder="Search data silos..." type="text"/>
</div>
<button class="material-symbols-outlined text-on-surface-variant p-2 rounded-full hover:bg-surface-container-high transition-colors">notifications</button>
<div class="w-8 h-8 rounded-full bg-primary-container overflow-hidden border border-primary/20">
<img class="w-full h-full object-cover" data-alt="A professional headshot of a female data privacy officer in a corporate setting. She is smiling confidently, wearing a navy blue blazer. The background is a brightly lit, modern office with soft-focus glass walls and minimalist decor, emphasizing an authoritative and professional persona." src="https://lh3.googleusercontent.com/aida-public/AB6AXuB8LoaV63fC6fTRQs_oo8ncxGIwI9qUXVzwG3in_ayrPefjA4iwdqkvpU_M5XyQumuXshBwfd6UJPBaMFvYefF6BrscGRAGzl2THmL6_PwFoLhFwivrK-fvwJ0N5zIIGYKb0O69AV8IP2Q4nGoaXwKbhEMSiQD1V2dDw3kjI1Ur1-KwqINAfw0WMzB2pDfnXrGeM-oJF9nbAqZMQSBhNillHkGPsJbSA75FZbfjpLAamEb4j3VhYYJpiVBaye8D9OvnZ0ES79VsBmXv"/>
</div>
</div>
</header>
<main class="max-w-7xl mx-auto px-4 py-6 md:px-8">
<!-- Quick Actions Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
<button onclick="window.location='index.php?page=assessments'" class="flex items-center justify-center gap-3 p-4 bg-primary text-white rounded-xl shadow-sm hover:shadow-md transition-all active:scale-95 group">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">add_moderator</span>
<span class="font-title-md text-body-md font-semibold">Start Assessment</span>
</button>
<button onclick="alert('Coming Soon: Feature under development.');" class="flex items-center justify-center gap-3 p-4 bg-white border border-outline-variant text-primary rounded-xl shadow-sm hover:bg-surface-container transition-all active:scale-95">
<span class="material-symbols-outlined">description</span>
<span class="font-title-md text-body-md font-semibold">Create Policy</span>
</button>
<button onclick="alert('Coming Soon: Feature under development.');" class="flex items-center justify-center gap-3 p-4 bg-white border border-outline-variant text-primary rounded-xl shadow-sm hover:bg-surface-container transition-all active:scale-95">
<span class="material-symbols-outlined">warning</span>
<span class="font-title-md text-body-md font-semibold">Review Risks</span>
</button>
<button onclick="alert('Coming Soon: Feature under development.');" class="flex items-center justify-center gap-3 p-4 bg-white border border-outline-variant text-primary rounded-xl shadow-sm hover:bg-surface-container transition-all active:scale-95">
<span class="material-symbols-outlined">analytics</span>
<span class="font-title-md text-body-md font-semibold">Generate Report</span>
</button>
</div>
<!-- KPI Bento Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
<div class="col-span-1 md:col-span-2 lg:col-span-2 bg-white p-5 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)] flex flex-col justify-between">
<div class="flex justify-between items-start mb-2">
<span class="text-label-md font-medium text-on-surface-variant uppercase tracking-wider">Overall Compliance</span>
<span class="text-green-600 bg-green-50 px-2 py-0.5 rounded text-xs font-bold">+2.4%</span>
</div>
<div class="flex items-end justify-between">
<h2 class="text-display font-bold text-primary">94%</h2>
<div class="w-16 h-1 bg-surface-container rounded-full overflow-hidden mb-2">
<div class="bg-primary h-full" style="width: 94%"></div>
</div>
</div>
</div>
<div class="bg-white p-5 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
<span class="text-label-md font-medium text-on-surface-variant uppercase block mb-3">GDPR Status</span>
<div class="flex items-center gap-2">
<span class="text-headline-lg font-bold">92%</span>
<span class="material-symbols-outlined text-green-500 text-sm">check_circle</span>
</div>
</div>
<div class="bg-white p-5 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
<span class="text-label-md font-medium text-on-surface-variant uppercase block mb-3">DPDP Status</span>
<div class="flex items-center gap-2">
<span class="text-headline-lg font-bold">88%</span>
<span class="material-symbols-outlined text-amber-500 text-sm">pending</span>
</div>
</div>
<div class="bg-white p-5 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
<span class="text-label-md font-medium text-on-surface-variant uppercase block mb-3">Open Risks</span>
<div class="flex items-center gap-2">
<span class="text-headline-lg font-bold text-error">14</span>
<span class="material-symbols-outlined text-error text-sm">error</span>
</div>
</div>
<div class="bg-white p-5 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
<span class="text-label-md font-medium text-on-surface-variant uppercase block mb-3">Incidents</span>
<div class="flex items-center gap-2">
<span class="text-headline-lg font-bold text-error">2</span>
<span class="bg-error/10 text-error px-1.5 py-0.5 rounded text-[10px] font-bold">CRITICAL</span>
</div>
</div>
</div>
<!-- Middle Section: Trends & Distribution -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
<!-- Trend Chart Container -->
<div class="lg:col-span-8 bg-white p-6 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)]">
<div class="flex justify-between items-center mb-6">
<h3 class="font-title-md text-primary">Compliance Trend (12 Months)</h3>
<select class="text-label-md border-none bg-surface-container px-3 py-1 rounded-lg">
<option>Full Year 2024</option>
<option>Quarterly</option>
</select>
</div>
<div class="h-64 flex items-end justify-between gap-2 px-2">
<!-- Faux Bar Chart / Line representation -->
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[70%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[85%] rounded-t transition-all duration-500 group-hover:h-[90%]"></div>
</div>
<span class="text-[10px] text-on-surface-variant font-medium">JAN</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[72%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[82%] rounded-t"></div>
</div>
<span class="text-[10px] text-on-surface-variant">FEB</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[75%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[88%] rounded-t"></div>
</div>
<span class="text-[10px] text-on-surface-variant">MAR</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[70%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[80%] rounded-t"></div>
</div>
<span class="text-[10px] text-on-surface-variant">APR</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[80%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[92%] rounded-t"></div>
</div>
<span class="text-[10px] text-on-surface-variant">MAY</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[85%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[90%] rounded-t"></div>
</div>
<span class="text-[10px] text-on-surface-variant">JUN</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary/20 rounded-t h-[82%] relative group">
<div class="absolute inset-x-0 bottom-0 bg-primary h-[94%] rounded-t"></div>
</div>
<span class="text-[10px] text-on-surface-variant">JUL</span>
</div>
</div>
</div>
<!-- Risk Donut & Gauge -->
<div class="lg:col-span-4 flex flex-col gap-4">
<div class="bg-white p-6 rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)] flex items-center justify-between">
<div>
<h4 class="text-label-md font-bold text-on-surface-variant uppercase tracking-wide">Risk Level</h4>
<div class="mt-2 space-y-1">
<div class="flex items-center gap-2">
<div class="w-2 h-2 rounded-full bg-error"></div>
<span class="text-body-md font-medium">Critical (1)</span>
</div>
<div class="flex items-center gap-2">
<div class="w-2 h-2 rounded-full bg-orange-500"></div>
<span class="text-body-md">High (3)</span>
</div>
</div>
</div>
<div class="relative w-24 h-24">
<svg class="w-full h-full transform -rotate-90">
<circle class="text-surface-container" cx="48" cy="48" fill="transparent" r="40" stroke="currentColor" stroke-width="8"></circle>
<circle class="text-primary" cx="48" cy="48" fill="transparent" r="40" stroke="currentColor" stroke-dasharray="251.2" stroke-dashoffset="62.8" stroke-width="8"></circle>
</svg>
<div class="absolute inset-0 flex items-center justify-center font-bold text-lg">94%</div>
</div>
</div>
<div class="bg-error/5 p-4 rounded-xl border border-error/20 flex flex-col gap-3">
<div class="flex items-center gap-2 text-error">
<span class="material-symbols-outlined text-sm">campaign</span>
<h4 class="font-bold text-label-md">HIGH PRIORITY ALERTS</h4>
</div>
<p class="text-body-md text-on-surface">Data Breach detected in Marketing Silo #4. Immediate audit required.</p>
<button class="text-error text-label-md font-bold text-left underline underline-offset-4">INVESTIGATE NOW</button>
</div>
</div>
</div>
<!-- Tasks, Audits, & Departments -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
<!-- Pending Tasks -->
<div class="bg-white rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)] overflow-hidden">
<div class="p-4 border-b border-surface-container flex justify-between items-center">
<h3 class="font-title-md">Pending Tasks</h3>
<span class="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full">12 TOTAL</span>
</div>
<div class="divide-y divide-surface-container">
<div class="p-4 flex items-start gap-3 hover:bg-surface-container-low transition-colors cursor-pointer group">
<div class="mt-1 w-5 h-5 rounded border-2 border-outline-variant group-hover:border-primary transition-colors"></div>
<div>
<p class="text-body-md font-medium">Review Vendor Data Agreement</p>
<p class="text-caption text-on-surface-variant">Due in 2 days • Legal Team</p>
</div>
</div>
<div class="p-4 flex items-start gap-3 hover:bg-surface-container-low transition-colors cursor-pointer group">
<div class="mt-1 w-5 h-5 rounded border-2 border-outline-variant group-hover:border-primary transition-colors"></div>
<div>
<p class="text-body-md font-medium">Update Cookie Banner</p>
<p class="text-caption text-on-surface-variant">Due tomorrow • Dev Ops</p>
</div>
</div>
<div class="p-4 flex items-start gap-3 hover:bg-surface-container-low transition-colors cursor-pointer group">
<div class="mt-1 w-5 h-5 rounded border-2 border-outline-variant group-hover:border-primary transition-colors"></div>
<div>
<p class="text-body-md font-medium">DPIA for Project Phoenix</p>
<p class="text-caption text-on-surface-variant">Overdue • Product</p>
</div>
</div>
</div>
</div>
<!-- Recent Activity -->
<div class="bg-white rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)] overflow-hidden">
<div class="p-4 border-b border-surface-container">
<h3 class="font-title-md">Audit Activity</h3>
</div>
<div class="p-4 space-y-6">
<div class="flex gap-3 relative">
<div class="absolute left-[9px] top-5 bottom-0 w-0.5 bg-surface-container"></div>
<div class="w-5 h-5 rounded-full bg-green-500 flex-shrink-0 flex items-center justify-center z-10">
<span class="material-symbols-outlined text-white text-[12px]">done</span>
</div>
<div>
<p class="text-body-md font-medium">DPO Assessment Completed</p>
<p class="text-caption text-on-surface-variant">Today, 10:45 AM</p>
</div>
</div>
<div class="flex gap-3 relative">
<div class="absolute left-[9px] top-5 bottom-0 w-0.5 bg-surface-container"></div>
<div class="w-5 h-5 rounded-full bg-primary flex-shrink-0 flex items-center justify-center z-10">
<span class="material-symbols-outlined text-white text-[12px]">sync</span>
</div>
<div>
<p class="text-body-md font-medium">Quarterly Policy Review Started</p>
<p class="text-caption text-on-surface-variant">Yesterday</p>
</div>
</div>
<div class="flex gap-3">
<div class="w-5 h-5 rounded-full bg-amber-500 flex-shrink-0 flex items-center justify-center z-10">
<span class="material-symbols-outlined text-white text-[12px]">flag</span>
</div>
<div>
<p class="text-body-md font-medium">External Audit Requested</p>
<p class="text-caption text-on-surface-variant">Oct 24, 2024</p>
</div>
</div>
</div>
</div>
<!-- Dept Performance -->
<div class="bg-white rounded-xl border border-surface-container-highest shadow-[0px_2px_4px_rgba(0,0,0,0.04)] overflow-hidden">
<div class="p-4 border-b border-surface-container">
<h3 class="font-title-md">Low Performing Departments</h3>
</div>
<div class="p-4 space-y-4">
<div class="flex items-center justify-between">
<div class="flex flex-col">
<span class="text-body-md font-medium">Marketing</span>
<div class="w-32 h-1.5 bg-surface-container rounded-full mt-1">
<div class="bg-error h-full rounded-full" style="width: 82%"></div>
</div>
</div>
<span class="text-body-md font-bold text-error">82%</span>
</div>
<div class="flex items-center justify-between">
<div class="flex flex-col">
<span class="text-body-md font-medium">Sales Ops</span>
<div class="w-32 h-1.5 bg-surface-container rounded-full mt-1">
<div class="bg-amber-500 h-full rounded-full" style="width: 85%"></div>
</div>
</div>
<span class="text-body-md font-bold text-amber-500">85%</span>
</div>
<div class="flex items-center justify-between">
<div class="flex flex-col">
<span class="text-body-md font-medium">Human Resources</span>
<div class="w-32 h-1.5 bg-surface-container rounded-full mt-1">
<div class="bg-green-500 h-full rounded-full" style="width: 89%"></div>
</div>
</div>
<span class="text-body-md font-bold text-green-600">89%</span>
</div>
</div>
</div>
</div>
<!-- Compliance Calendar / Mini View -->
<section class="mt-8">
<h3 class="font-title-md mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">calendar_today</span>
                Compliance Calendar
            </h3>
<div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
<div class="lg:col-span-1 bg-white p-4 rounded-xl border border-surface-container-highest flex flex-col items-center">
<span class="text-primary font-bold text-label-md uppercase mb-2">NOVEMBER</span>
<div class="grid grid-cols-7 gap-1 text-[10px] w-full text-center">
<span class="text-on-surface-variant font-bold">M</span>
<span class="text-on-surface-variant font-bold">T</span>
<span class="text-on-surface-variant font-bold">W</span>
<span class="text-on-surface-variant font-bold">T</span>
<span class="text-on-surface-variant font-bold">F</span>
<span class="text-on-surface-variant font-bold">S</span>
<span class="text-on-surface-variant font-bold">S</span>
<!-- Simulating some dates -->
<span class="p-1">1</span><span class="p-1">2</span><span class="p-1">3</span><span class="p-1">4</span><span class="p-1">5</span><span class="p-1">6</span><span class="p-1">7</span>
<span class="p-1">8</span><span class="p-1">9</span><span class="p-1 bg-primary text-white rounded-full">10</span><span class="p-1">11</span><span class="p-1">12</span><span class="p-1">13</span><span class="p-1">14</span>
<span class="p-1">15</span><span class="p-1">16</span><span class="p-1">17</span><span class="p-1 bg-error text-white rounded-full">18</span><span class="p-1">19</span><span class="p-1">20</span><span class="p-1">21</span>
</div>
</div>
<div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="bg-white p-4 rounded-xl border-l-4 border-l-primary shadow-sm flex justify-between items-center">
<div>
<span class="text-caption text-on-surface-variant uppercase font-bold">Nov 10</span>
<p class="text-body-md font-semibold">DPDP Readiness Audit</p>
</div>
<button class="material-symbols-outlined text-on-surface-variant">chevron_right</button>
</div>
<div class="bg-white p-4 rounded-xl border-l-4 border-l-error shadow-sm flex justify-between items-center">
<div>
<span class="text-caption text-error uppercase font-bold">Nov 18</span>
<p class="text-body-md font-semibold">GDPR Consent Deadline</p>
</div>
<button class="material-symbols-outlined text-on-surface-variant">chevron_right</button>
</div>
</div>
</div>
</section>
</main>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-0 w-full flex justify-around items-center h-20 px-4 pb-safe bg-surface dark:bg-surface-dim border-t border-surface-container-highest dark:border-outline-variant z-50">
<a class="flex flex-col items-center justify-center text-primary dark:text-primary-fixed font-bold active:scale-90 transition-transform duration-150" href="/governance/index.php">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">dashboard</span>
<span class="font-label-md text-label-md">Dashboard</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-outline hover:bg-surface-container transition-colors active:scale-90 duration-150 p-2 rounded-xl" href="#">
<span class="material-symbols-outlined">gavel</span>
<span class="font-label-md text-label-md">Governance</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-outline hover:bg-surface-container transition-colors active:scale-90 duration-150 p-2 rounded-xl" href="#">
<span class="material-symbols-outlined">fact_check</span>
<span class="font-label-md text-label-md">Compliance</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-outline hover:bg-surface-container transition-colors active:scale-90 duration-150 p-2 rounded-xl" href="#">
<span class="material-symbols-outlined">analytics</span>
<span class="font-label-md text-label-md">Reports</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-outline hover:bg-surface-container transition-colors active:scale-90 duration-150 p-2 rounded-xl" href="#">
<span class="material-symbols-outlined">settings</span>
<span class="font-label-md text-label-md">Settings</span>
</a>
</nav>
<!-- Micro-interactions Script -->
<script>
        // Simple animation for numbers (Optional micro-interaction)
        document.querySelectorAll('.text-display, .text-headline-lg').forEach(el => {
            const finalVal = parseInt(el.innerText);
            if (isNaN(finalVal)) return;
            let startVal = 0;
            const duration = 1000;
            const step = (timestamp) => {
                if (!startTime) startTime = timestamp;
                const progress = Math.min((timestamp - startTime) / duration, 1);
                el.innerText = Math.floor(progress * finalVal) + (el.innerText.includes('%') ? '%' : '');
                if (progress < 1) window.requestAnimationFrame(step);
            };
            let startTime = null;
            window.requestAnimationFrame(step);
        });
    </script>
</body></html>