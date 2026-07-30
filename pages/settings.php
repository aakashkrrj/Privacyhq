<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>PrivacyHQ - Settings</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .fluent-list-item:active {
            background-color: #f3f2f1;
            transform: scale(0.995);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #EDEBE9;
            border-radius: 10px;
        }
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
      min-height: 100dvh;
    }
  </style>
  </head>
<body class="bg-background text-on-background min-h-screen flex flex-col">
<!-- TopAppBar -->
<header class="fixed top-0 left-0 w-full bg-surface shadow-sm z-50 flex justify-between items-center px-container-padding h-16 transition-colors duration-200 ease-in-out">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary" data-icon="security">security</span>
<h1 class="font-display text-display text-primary text-[20px] leading-tight">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-md">
<button class="p-2 rounded-full hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
</button>
</div>
</header>
<main class="flex-grow pt-20 pb-24 px-container-padding max-w-2xl mx-auto w-full">
<!-- Profile Header -->
<section class="mb-lg animate-in fade-in slide-in-from-top-4 duration-500">
<div class="flex items-center gap-md p-md bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
<div class="relative">
<img class="w-16 h-16 rounded-full object-cover border-2 border-primary" data-alt="A professional close-up portrait of a tech executive in a brightly lit modern office. The lighting is soft and natural, emphasizing a clean and authoritative corporate aesthetic with blue and white tones. High-quality photography style with a shallow depth of field, highlighting clear skin and a confident, approachable expression." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCqcibGKhcEzvK4QI1qX8QOaAAwItpRm-EDYxc_kMriyIzdj4cgOHmN17QUYLuW332q3npiv2HCXT-MAyuOFu4lpVhKoPP1zdP839adaUYQQcDB44IyVvdnzpxGmbc1TypIT2vSHgiKLUJY6DUo64yWFMrgjHehjsdqd5sY0ykdWwbyZZa7EKMExTgiQmdby_pKfen6HTBD7Xl_B5LfeUfMljZdYkbke-wnur2AKefH_F9GLfJEOKpgMf3WoXSELzQ8ZIPrpW3Dw-sk"/>
<div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-surface rounded-full"></div>
</div>
<div class="flex-grow">
<h2 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface">Admin User</h2>
<p class="font-body-md text-body-md text-on-surface-variant">Data Protection Officer</p>
<div class="mt-1">
<span class="bg-tertiary-fixed text-on-tertiary-fixed text-[10px] uppercase font-bold px-2 py-0.5 rounded-full tracking-wider">Premium Enterprise</span>
</div>
</div>
<button class="text-primary hover:bg-primary-fixed p-2 rounded-lg transition-colors">
<span class="material-symbols-outlined" data-icon="edit">edit</span>
</button>
</div>
</section>
<!-- Settings Categories -->
<div class="space-y-md">
<!-- Security Category -->
<div class="space-y-base">
<h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Security</h3>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
<div class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="lock">lock</span>
<span class="font-body-lg text-body-lg">Two-Factor Authentication</span>
</div>
<div class="flex items-center gap-sm">
<span class="font-body-md text-body-md text-green-600">Enabled</span>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
</div>
<div class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="password">password</span>
<span class="font-body-lg text-body-lg">Change Password</span>
</div>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
</div>
</div>
<!-- Notifications Category -->
<div class="space-y-base">
<h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Preferences</h3>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
<div class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="notifications_active">notifications_active</span>
<span class="font-body-lg text-body-lg">Notification Channels</span>
</div>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
<div class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="dark_mode">dark_mode</span>
<span class="font-body-lg text-body-lg">Dark Mode</span>
</div>
<div class="relative inline-flex items-center cursor-pointer">
<input class="sr-only peer" type="checkbox" value=""/>
<div class="w-11 h-6 bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
</div>
</div>
</div>
</div>
<!-- Team & API -->
<div class="space-y-base">
<h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Organization</h3>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
<div class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="groups">groups</span>
<span class="font-body-lg text-body-lg">Team Permissions</span>
</div>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
<div class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="api">api</span>
<span class="font-body-lg text-body-lg">API Keys</span>
</div>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
</div>
</div>
<!-- Compliance Category -->
<div class="space-y-base">
<h3 class="px-base font-label-md text-label-md text-outline uppercase tracking-widest">Compliance</h3>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
<div class="fluent-list-item flex items-center justify-between p-md border-b border-surface-variant cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="policy">policy</span>
<span class="font-body-lg text-body-lg">Legal &amp; Compliance Docs</span>
</div>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
<div class="fluent-list-item flex items-center justify-between p-md cursor-pointer transition-all">
<div class="flex items-center gap-md">
<span class="material-symbols-outlined text-outline" data-icon="history">history</span>
<span class="font-body-lg text-body-lg">Audit Logs</span>
</div>
<span class="material-symbols-outlined text-outline" data-icon="chevron_right">chevron_right</span>
</div>
</div>
</div>
<!-- Sign Out -->
<div class="pt-lg">
<button class="w-full flex items-center justify-center gap-sm bg-surface-container-lowest text-error border border-error/20 py-md rounded-xl font-body-lg hover:bg-error-container/20 transition-all active:scale-95">
<span class="material-symbols-outlined" data-icon="logout">logout</span>
                    Sign Out
                </button>
<p class="text-center font-caption text-caption text-outline mt-md">Version 2.4.0 • Build 882</p>
</div>
</div>
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
    <a href="index.php?page=data-requests"
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
        // Micro-interaction for list items
        document.querySelectorAll('.fluent-list-item').forEach(item => {
            item.addEventListener('click', () => {
                const label = item.querySelector('.font-body-lg').innerText;
                console.log(`Navigating to: ${label}`);
                // Simple ripple/pulse effect feedback
                item.style.backgroundColor = 'rgba(0, 120, 212, 0.05)';
                setTimeout(() => {
                    item.style.backgroundColor = '';
                }, 200);
            });
        });

        // Dark mode toggle logic (UI only)
        const darkModeToggle = document.querySelector('input[type="checkbox"]');
        darkModeToggle.addEventListener('change', (e) => {
            if(e.target.checked) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
</body></html>