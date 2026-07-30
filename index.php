<?php
// governance/index.php
require_once __DIR__ . '/includes/db.php';

$currentPage = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';

// Route Mappings
$routes = [
    'dashboard'           => 'pages/dashboard-main.php',
    'consent'             => 'pages/consent-management.php',
    'data-requests'       => 'pages/dsr-management.php',
    'dsr-management'      => 'pages/dsr-management.php',
    'assessments'         => 'pages/assessments.php',
    'cookie-governance'   => 'pages/cookie-governance.php',
    'data-discovery'      => 'pages/data-discovery.php',
    'data-mapping'        => 'pages/data-mapping.php',
    'incident-management' => 'pages/incident-management.php',
    'vendor-risk'         => 'pages/vendor-risk.php',
    'vendor-management'   => 'pages/vendor-management.php',
    'risk-register'       => 'pages/risk-register.php',
    'ropa'                => 'pages/ropa.php',
    'policies'            => 'pages/policies.php',
    'reports'             => 'pages/reports.php',
    'settings'            => 'pages/settings.php',
    'user-management'     => 'pages/user-management.php',
    'audit-logs'          => 'pages/audit-logs.php',
    'more'                => 'pages/more.php'
];

$fileToInclude = isset($routes[$currentPage]) ? $routes[$currentPage] : 'pages/dashboard-main.php';
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
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; min-height: 100dvh; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .gauge-svg { transform: rotate(-90deg); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F3F2F1] text-on-surface min-h-screen pb-24">

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
        if (file_exists(__DIR__ . '/' . $fileToInclude)) {
            include __DIR__ . '/' . $fileToInclude;
        } else {
            echo '<div class="p-6 bg-error-container text-on-error-container rounded-xl shadow-sm my-8">
                    <h3 class="font-title-md font-bold mb-2">Page Not Found</h3>
                    <p class="font-body-md">Unable to locate the file: <code class="bg-surface px-2 py-1 rounded text-error">' . htmlspecialchars($fileToInclude) . '</code></p>
                  </div>';
        }
        ?>
    </main>

    <!-- Bottom Navigation Bar -->
    <?php include_once __DIR__ . '/includes/bottom-nav.php'; ?>

    <div class="fixed top-0 right-0 -z-10 w-1/3 h-1/2 bg-gradient-to-bl from-primary/5 to-transparent blur-3xl pointer-events-none"></div>
    <div class="fixed bottom-0 left-0 -z-10 w-1/3 h-1/2 bg-gradient-to-tr from-secondary-container/5 to-transparent blur-3xl pointer-events-none"></div>

</body>
</html>