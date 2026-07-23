<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>Consent Management | PrivacyHQ</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .glass-effect { backdrop-filter: blur(8px); background-color: rgba(250, 249, 248, 0.8); }
        .consent-card-shadow { box-shadow: 0px 2px 4px rgba(0,0,0,0.04); }
        .fab-shadow { box-shadow: 0px 8px 16px rgba(0,0,0,0.08); }
    </style>
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
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background text-on-surface min-h-screen pb-24">
<!-- TopAppBar -->
<header class="fixed top-0 left-0 w-full bg-surface shadow-sm flex justify-between items-center px-container-padding h-16 z-50">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-primary" data-icon="security">security</span>
<h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-sm">
<button class="p-base hover:bg-surface-container-low transition-colors rounded-full">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
</button>
<div class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant">
<img class="w-full h-full object-cover" data-alt="A professional headshot of a corporate compliance officer in a modern, bright office setting. The person is smiling warmly, wearing a professional blazer. High-key lighting and a soft-focus office background with glass partitions and indoor plants emphasize a modern, professional corporate aesthetic." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBcMdT2lQnFq-JCKRdd5mxwEjutv4-tH9QQ0qjS3YtjJ45S-Kdz22_M_7OvhmQ4BM7oUk4A2bASCvrfsMp6UoiKKwKbxKhaKGtEBrB10T8CbSx63JaSO7cKRrsCoiZHRc3EMj5gc_tXJGQjKFi9qHdHQahe4ZrdE5kysDMAqvRkSQ4q321UcmXTb4_UAnFxNz7nEZ6LziCxGpj1bvDegiCYkkdAB9-JjF8coSz81qJqHQbWOng3rQbRz5nizi4opOsg83ClV8okoQTg"/>
</div>
</div>
</header>
<!-- Main Canvas -->
<main class="pt-20 px-container-padding flex flex-col gap-lg max-w-2xl mx-auto">
<!-- Search & Branding Section -->
<section class="flex flex-col gap-md">
<div class="relative w-full">
<span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
<input class="w-full pl-12 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all font-body-md text-body-md" placeholder="Search consent records..." type="text"/>
</div>
</section>
<!-- Tab Filters -->
<nav class="flex overflow-x-auto gap-sm pb-2 no-scrollbar">
<button class="px-md py-2 rounded-full bg-primary text-on-primary font-label-md text-label-md whitespace-nowrap">All</button>
<button class="px-md py-2 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest transition-colors font-label-md text-label-md whitespace-nowrap">Active</button>
<button class="px-md py-2 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest transition-colors font-label-md text-label-md whitespace-nowrap">Expired</button>
<button class="px-md py-2 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest transition-colors font-label-md text-label-md whitespace-nowrap">Withdrawn</button>
</nav>
<!-- Stats Overview (Bento Hint) -->
<div class="grid grid-cols-2 gap-md">
<div class="bg-surface-container-lowest p-md rounded-xl border border-surface-variant consent-card-shadow">
<p class="font-label-md text-label-md text-outline uppercase tracking-wider">Total Active</p>
<p class="font-display text-display text-primary mt-base">1,284</p>
</div>
<div class="bg-surface-container-lowest p-md rounded-xl border border-surface-variant consent-card-shadow">
<p class="font-label-md text-label-md text-outline uppercase tracking-wider">Renewal Rate</p>
<p class="font-display text-display text-secondary mt-base">92%</p>
</div>
</div>
<!-- Consent Records List -->
<section class="flex flex-col gap-stack-gap">
<h2 class="font-title-md text-title-md text-on-surface px-base">Recent Records</h2>
<!-- Record Item 1 -->
<div class="bg-surface-container-lowest p-md rounded-xl border border-surface-variant consent-card-shadow flex items-center justify-between hover:border-primary-fixed-dim transition-all cursor-pointer">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center">
<span class="material-symbols-outlined text-on-primary-fixed" data-icon="person">person</span>
</div>
<div>
<p class="font-title-md text-body-md font-semibold text-on-surface">Eleanor Shellstrop</p>
<p class="font-caption text-caption text-outline">Marketing &amp; Newsletters</p>
</div>
</div>
<div class="flex flex-col items-end gap-xs">
<span class="px-2 py-0.5 rounded-full bg-green-500/10 text-green-700 text-[10px] font-bold uppercase tracking-tighter">Active</span>
<p class="font-caption text-caption text-outline">Oct 24, 2023</p>
</div>
</div>
<!-- Record Item 2 -->
<div class="bg-surface-container-lowest p-md rounded-xl border border-surface-variant consent-card-shadow flex items-center justify-between hover:border-primary-fixed-dim transition-all cursor-pointer">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-full bg-secondary-fixed flex items-center justify-center">
<span class="material-symbols-outlined text-on-secondary-fixed" data-icon="analytics">analytics</span>
</div>
<div>
<p class="font-title-md text-body-md font-semibold text-on-surface">Chidi Anagonye</p>
<p class="font-caption text-caption text-outline">Behavioral Analytics</p>
</div>
</div>
<div class="flex flex-col items-end gap-xs">
<span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-700 text-[10px] font-bold uppercase tracking-tighter">Withdrawn</span>
<p class="font-caption text-caption text-outline">Oct 22, 2023</p>
</div>
</div>
<!-- Record Item 3 -->
<div class="bg-surface-container-lowest p-md rounded-xl border border-surface-variant consent-card-shadow flex items-center justify-between hover:border-primary-fixed-dim transition-all cursor-pointer">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-full bg-tertiary-fixed flex items-center justify-center">
<span class="material-symbols-outlined text-on-tertiary-fixed" data-icon="cloud">cloud</span>
</div>
<div>
<p class="font-title-md text-body-md font-semibold text-on-surface">Tahani Al-Jamil</p>
<p class="font-caption text-caption text-outline">Third-party Cloud Storage</p>
</div>
</div>
<div class="flex flex-col items-end gap-xs">
<span class="px-2 py-0.5 rounded-full bg-green-500/10 text-green-700 text-[10px] font-bold uppercase tracking-tighter">Active</span>
<p class="font-caption text-caption text-outline">Oct 20, 2023</p>
</div>
</div>
<!-- Record Item 4 -->
<div class="bg-surface-container-lowest p-md rounded-xl border border-surface-variant consent-card-shadow flex items-center justify-between hover:border-primary-fixed-dim transition-all cursor-pointer opacity-70">
<div class="flex items-center gap-md">
<div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center">
<span class="material-symbols-outlined text-outline" data-icon="history">history</span>
</div>
<div>
<p class="font-title-md text-body-md font-semibold text-on-surface">Jason Mendoza</p>
<p class="font-caption text-caption text-outline">Customer Support Logs</p>
</div>
</div>
<div class="flex flex-col items-end gap-xs">
<span class="px-2 py-0.5 rounded-full bg-outline-variant/30 text-outline text-[10px] font-bold uppercase tracking-tighter">Expired</span>
<p class="font-caption text-caption text-outline">Sep 15, 2023</p>
</div>
</div>
</section>
</main>
<!-- FAB: Add Consent -->
<button class="fixed bottom-24 right-6 w-14 h-14 bg-primary-container text-on-primary-container rounded-2xl fab-shadow flex items-center justify-center active:scale-95 transition-transform z-40">
<span class="material-symbols-outlined" data-icon="add" style="font-variation-settings: 'FILL' 0, 'wght' 600;">add</span>
</button>
<!-- BottomNavBar -->
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
       class="nav-more flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            menu
        </span>

        <span class="font-label-md text-label-md">More</span>
    </a>

</nav>
<script>
        // Micro-interaction for tabs
        const tabs = document.querySelectorAll('nav.no-scrollbar button');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => {
                    t.classList.remove('bg-primary', 'text-on-primary');
                    t.classList.add('bg-surface-container-high', 'text-on-surface-variant');
                });
                tab.classList.remove('bg-surface-container-high', 'text-on-surface-variant');
                tab.classList.add('bg-primary', 'text-on-primary');
            });
        });

        // Simple input interaction effect
        const searchInput = document.querySelector('input');
        searchInput.addEventListener('focus', () => {
            searchInput.parentElement.classList.add('scale-[1.02]');
        });
        searchInput.addEventListener('blur', () => {
            searchInput.parentElement.classList.remove('scale-[1.02]');
        });
    </script>
</body></html>