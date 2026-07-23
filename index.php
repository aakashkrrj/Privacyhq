<?php
// governance/index.php
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
    <title>PrivacyHQ Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
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
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; min-height: max(884px, 100dvh); }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .gauge-svg { transform: rotate(-90deg); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F3F2F1] text-on-surface min-h-screen pb-24 md:pb-0">

    <!-- Top Header -->
    <header class="fixed top-0 left-0 w-full bg-surface shadow-sm flex justify-between items-center px-container-padding h-16 z-50">
        <a href="index.php?page=dashboard" class="flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary" data-icon="security">security</span>
            <h1 class="font-display text-display text-primary leading-none">PrivacyHQ</h1>
        </a>
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

    <!-- Main Dynamic Content Area -->
    <main class="pt-20 px-container-padding max-w-7xl mx-auto space-y-lg">
        <?php
        switch ($currentPage) {
            case 'consent':
                include 'pages/consent-management.php';
                break;
            case 'data-requests':
                include 'pages/data-requests.php';
                break;
            case 'assessments':
                include 'pages/assessments.php';
                break;
            case 'cookie-governance':
                include 'pages/cookie-governance.php';
                break;
            case 'data-discovery':
                include 'pages/data-discovery.php';
                break;
            case 'incident-management':
                include 'pages/incident-management.php';
                break;
            case 'vendor-risk':
                include 'pages/vendor-risk.php';
                break;
            case 'reports':
                include 'pages/reports.php';
                break;
            case 'settings':
                include 'pages/settings.php';
                break;
            case 'more':
                include 'pages/more.php';
                break;
            case 'dashboard':
            default:
                include 'pages/dashboard-main.php';
                break;
        }
        ?>
    </main>

    <!-- Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 w-full z-50 bg-surface shadow-[0px_-2px_4px_rgba(0,0,0,0.04)] flex justify-around items-center h-16 px-2">
        <!-- Dashboard -->
        <a href="index.php?page=dashboard" class="flex flex-col items-center justify-center px-4 py-1 rounded-xl <?php echo ($currentPage == 'dashboard') ? 'text-primary bg-secondary-fixed' : 'text-on-surface-variant'; ?>">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="font-label-md text-label-md">Dashboard</span>
        </a>

        <!-- Consent -->
        <a href="index.php?page=consent" class="flex flex-col items-center justify-center px-4 py-1 rounded-xl <?php echo ($currentPage == 'consent') ? 'text-primary bg-secondary-fixed' : 'text-on-surface-variant'; ?>">
            <span class="material-symbols-outlined">verified_user</span>
            <span class="font-label-md text-label-md">Consent</span>
        </a>

        <!-- Requests -->
        <a href="index.php?page=data-requests" class="flex flex-col items-center justify-center px-4 py-1 rounded-xl <?php echo ($currentPage == 'data-requests') ? 'text-primary bg-secondary-fixed' : 'text-on-surface-variant'; ?>">
            <span class="material-symbols-outlined">gavel</span>
            <span class="font-label-md text-label-md">Requests</span>
        </a>

        <!-- Assess -->
        <a href="index.php?page=assessments" class="flex flex-col items-center justify-center px-4 py-1 rounded-xl <?php echo ($currentPage == 'assessments') ? 'text-primary bg-secondary-fixed' : 'text-on-surface-variant'; ?>">
            <span class="material-symbols-outlined">assignment_turned_in</span>
            <span class="font-label-md text-label-md">Assess</span>
        </a>

        <!-- More -->
        <a href="index.php?page=more" class="flex flex-col items-center justify-center px-4 py-1 rounded-xl <?php echo ($currentPage == 'more' || in_array($currentPage, array('cookie-governance','data-discovery','incident-management','vendor-risk','reports','settings'))) ? 'text-primary bg-secondary-fixed' : 'text-on-surface-variant'; ?>">
            <span class="material-symbols-outlined">menu</span>
            <span class="font-label-md text-label-md">More</span>
        </a>
    </nav>

    <div class="fixed top-0 right-0 -z-10 w-1/3 h-1/2 bg-gradient-to-bl from-primary/5 to-transparent blur-3xl pointer-events-none"></div>
    <div class="fixed bottom-0 left-0 -z-10 w-1/3 h-1/2 bg-gradient-to-tr from-secondary-container/5 to-transparent blur-3xl pointer-events-none"></div>

</body>
</html>