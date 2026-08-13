<?php
// governance/index.php
require_once __DIR__ . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session authentication check
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentPage = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';

// Route Mappings
$routes = [
    'dashboard'                => 'pages/dashboard-main.php',
    'consent'                  => 'pages/consent-management.php',
    'data-requests'            => 'pages/dsr-management.php',
    'dsr-management'           => 'pages/dsr-management.php',
    'assessments'              => 'pages/assessments.php',
    'cookie-governance'        => 'pages/cookie-governance.php',
    'data-discovery'           => 'pages/data-discovery.php',
    'data-mapping'             => 'pages/data-mapping.php',
    'incident-management'      => 'pages/incident-management.php',
    'vendor-risk'              => 'pages/vendor-risk.php',
    'vendor-management'        => 'pages/vendor-management.php',
    'risk-register'            => 'pages/risk-register.php',
    'ropa'                     => 'pages/ropa.php',
    'policies'                 => 'pages/policies.php',
    'reports'                  => 'pages/reports.php',
    'settings'                 => 'pages/settings.php',
    'user-management'          => 'pages/user-management.php',
    'audit-logs'               => 'pages/audit-logs.php',
    'more'                     => 'pages/more.php',
    'my-assessments'           => 'pages/my-assessments.php',
    'perform-assessment'       => 'pages/perform-assessment.php',
    'review-assessment'        => 'pages/review-assessment.php',
    'role-management'          => 'pages/role-management.php',
    'edit-profile'             => 'pages/edit-profile.php',
    'change-password'          => 'pages/change-password.php',
    'notification-preferences' => 'pages/notification-preferences.php',
    'my-tasks'                 => 'pages/my-tasks.php',
    'executive-dashboard'      => 'pages/executive-dashboard.php',
    'search'                   => 'pages/search.php'
];

$pagePermissions = [
    'dashboard'                => 'view_dashboard',
    'executive-dashboard'      => 'view_dashboard',
    'search'                   => 'view_dashboard',
    'consent'                  => 'manage_consents',
    'data-requests'            => 'manage_dsr',
    'dsr-management'           => 'manage_dsr',
    'assessments'              => 'manage_assessments',
    'cookie-governance'        => 'view_cookie_governance',
    'data-discovery'           => 'view_dashboard',
    'data-mapping'             => 'view_dashboard',
    'incident-management'      => 'manage_incidents',
    'vendor-risk'              => 'manage_vendors',
    'vendor-management'        => 'manage_vendors',
    'risk-register'            => 'view_dashboard',
    'ropa'                     => 'manage_ropa',
    'policies'                 => 'manage_policies',
    'reports'                  => 'view_reports',
    'settings'                 => 'view_dashboard',
    'user-management'          => 'manage_users',
    'audit-logs'               => 'view_audit_logs',
    'more'                     => 'view_dashboard',
    'my-assessments'           => 'view_dashboard',
    'perform-assessment'       => 'view_dashboard',
    'review-assessment'        => 'view_dashboard',
    'role-management'          => 'manage_users',
    'my-tasks'                 => 'view_dashboard',
    'edit-profile'             => 'view_dashboard',
    'change-password'          => 'view_dashboard',
    'notification-preferences' => 'view_dashboard'
];

$reqPermission = $pagePermissions[$currentPage] ?? null;
if ($reqPermission && function_exists('require_permission')) {
    require_permission($reqPermission);
}

$fileToInclude = isset($routes[$currentPage]) ? $routes[$currentPage] : 'pages/dashboard-main.php';

// START NEW CODE - Notifications & Dynamic User Profile Context
$current_user_id = (int)($_SESSION['user_id'] ?? 1);
$notifications_list = [];
$unread_notifications_count = 0;

if (isset($conn) && !$conn->connect_error) {
    $notif_stmt = $conn->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    if ($notif_stmt) {
        $notif_stmt->bind_param("i", $current_user_id);
        $notif_stmt->execute();
        $notif_res = $notif_stmt->get_result();
        while ($n_row = $notif_res->fetch_assoc()) {
            if (empty($n_row['is_read'])) {
                $unread_notifications_count++;
            }
            $notifications_list[] = $n_row;
        }
        $notif_stmt->close();
    }
}
// END NEW CODE
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        } else {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }
    </script>
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
                        "on-surface-variant": "var(--on-surface-variant)",
                        "on-secondary-container": "var(--on-secondary-container)",
                        "secondary-fixed": "var(--secondary-fixed)",
                        "outline": "var(--outline)",
                        "surface-variant": "var(--surface-variant)",
                        "on-secondary": "var(--on-secondary)",
                        "surface-dim": "var(--surface-dim)",
                        "on-tertiary-fixed-variant": "var(--on-tertiary-fixed-variant)",
                        "error": "var(--error)",
                        "tertiary-fixed": "var(--tertiary-fixed)",
                        "secondary": "var(--secondary)",
                        "on-secondary-fixed-variant": "var(--on-secondary-fixed-variant)",
                        "surface": "var(--surface)",
                        "on-primary-container": "var(--on-primary-container)",
                        "on-secondary-fixed": "var(--on-secondary-fixed)",
                        "tertiary-container": "var(--tertiary-container)",
                        "on-surface": "var(--on-surface)",
                        "surface-container": "var(--surface-container)",
                        "surface-container-lowest": "var(--surface-container-lowest)",
                        "surface-container-high": "var(--surface-container-high)",
                        "secondary-fixed-dim": "var(--secondary-fixed-dim)",
                        "surface-bright": "var(--surface-bright)",
                        "on-background": "var(--on-background)",
                        "error-container": "var(--error-container)",
                        "on-tertiary": "var(--on-tertiary)",
                        "background": "var(--background)",
                        "on-primary": "var(--on-primary)",
                        "inverse-surface": "var(--inverse-surface)",
                        "tertiary": "var(--tertiary)",
                        "on-tertiary-fixed": "var(--on-tertiary-fixed)",
                        "primary-container": "var(--primary-container)",
                        "tertiary-fixed-dim": "var(--tertiary-fixed-dim)",
                        "primary-fixed": "var(--primary-fixed)",
                        "secondary-container": "var(--secondary-container)",
                        "surface-tint": "var(--surface-tint)",
                        "on-tertiary-container": "var(--on-tertiary-container)",
                        "on-primary-fixed": "var(--on-primary-fixed)",
                        "primary": "var(--primary)",
                        "primary-fixed-dim": "var(--primary-fixed-dim)",
                        "inverse-primary": "var(--inverse-primary)",
                        "on-error-container": "var(--on-error-container)",
                        "on-primary-fixed-variant": "var(--on-primary-fixed-variant)",
                        "surface-container-low": "var(--surface-container-low)",
                        "surface-container-highest": "var(--surface-container-highest)",
                        "inverse-on-surface": "var(--inverse-on-surface)",
                        "on-error": "var(--on-error)",
                        "outline-variant": "var(--outline-variant)"
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
        :root {
            --on-surface-variant: #404752;
            --on-secondary-container: #003f6d;
            --secondary-fixed: #d1e4ff;
            --outline: #717783;
            --surface-variant: #e3e2e1;
            --on-secondary: #ffffff;
            --surface-dim: #dadad9;
            --on-tertiary-fixed-variant: #004881;
            --error: #ba1a1a;
            --tertiary-fixed: #d3e4ff;
            --secondary: #0061a3;
            --on-secondary-fixed-variant: #00497d;
            --surface: #faf9f8;
            --on-primary-container: #ffffff;
            --on-secondary-fixed: #001d36;
            --tertiary-container: #2679c9;
            --on-surface: #1a1c1c;
            --surface-container: #efeeed;
            --surface-container-lowest: #ffffff;
            --surface-container-high: #e9e8e7;
            --secondary-fixed-dim: #9ecaff;
            --surface-bright: #faf9f8;
            --on-background: #1a1c1c;
            --error-container: #ffdad6;
            --on-tertiary: #ffffff;
            --background: #f4f3f2;
            --on-primary: #ffffff;
            --inverse-surface: #2f3130;
            --tertiary: #0060a9;
            --on-tertiary-fixed: #001c38;
            --primary-container: #0078d4;
            --tertiary-fixed-dim: #a2c9ff;
            --primary-fixed: #d3e3ff;
            --secondary-container: #5badff;
            --surface-tint: #0060ab;
            --on-tertiary-container: #ffffff;
            --on-primary-fixed: #001c39;
            --primary: #005faa;
            --primary-fixed-dim: #a3c9ff;
            --inverse-primary: #a3c9ff;
            --on-error-container: #93000a;
            --on-primary-fixed-variant: #004883;
            --surface-container-low: #f4f3f2;
            --surface-container-highest: #e3e2e1;
            --inverse-on-surface: #f1f0ef;
            --on-error: #ffffff;
            --outline-variant: #c0c7d4;
        }

        .dark {
            --on-surface-variant: #cbd5e1;
            --on-secondary-container: #d1e4ff;
            --secondary-fixed: #00497d;
            --outline: #94a3b8;
            --surface-variant: #334155;
            --on-secondary: #0f172a;
            --surface-dim: #1e293b;
            --on-tertiary-fixed-variant: #d3e4ff;
            --error: #ffb4ab;
            --tertiary-fixed: #004881;
            --secondary: #9ecaff;
            --on-secondary-fixed-variant: #d1e4ff;
            --surface: #1e293b;
            --on-primary-container: #0f172a;
            --on-secondary-fixed: #d1e4ff;
            --tertiary-container: #a2c9ff;
            --on-surface: #f8fafc;
            --surface-container: #1e293b;
            --surface-container-lowest: #0f172a;
            --surface-container-high: #334155;
            --secondary-fixed-dim: #00497d;
            --surface-bright: #1e293b;
            --on-background: #f8fafc;
            --error-container: #93000a;
            --on-tertiary: #0f172a;
            --background: #0f172a;
            --on-primary: #0f172a;
            --inverse-surface: #f1f0ef;
            --tertiary: #a2c9ff;
            --on-tertiary-fixed: #d3e4ff;
            --primary-container: #38bdf8;
            --tertiary-fixed-dim: #004881;
            --primary-fixed: #004883;
            --secondary-container: #00497d;
            --surface-tint: #a3c9ff;
            --on-tertiary-container: #0f172a;
            --on-primary-fixed: #d3e3ff;
            --primary: #38bdf8;
            --primary-fixed-dim: #004883;
            --inverse-primary: #005faa;
            --on-error-container: #ffb4ab;
            --on-primary-fixed-variant: #d3e3ff;
            --surface-container-low: #0f172a;
            --surface-container-highest: #475569;
            --inverse-on-surface: #1a1c1c;
            --on-error: #0f172a;
            --outline-variant: #475569;
        }

        /* Class Overrides for Non-Tailwind Colors or Hardcoded Elements */
        .dark body {
            background-color: var(--background) !important;
            color: var(--on-surface) !important;
        }
        .dark .bg-white {
            background-color: var(--surface) !important;
        }
        .dark .bg-gray-50, .dark .bg-gray-100 {
            background-color: var(--surface-container-low) !important;
        }
        .dark .border-gray-100, .dark .border-gray-200 {
            border-color: var(--outline-variant) !important;
        }
        .dark .text-gray-900, .dark .text-gray-800 {
            color: var(--on-surface) !important;
        }
        .dark .text-gray-700, .dark .text-gray-600 {
            color: var(--on-surface-variant) !important;
        }
        .dark .text-gray-500, .dark .text-gray-400 {
            color: var(--outline) !important;
        }
        .dark input, .dark select, .dark textarea {
            background-color: var(--surface-container-lowest) !important;
            color: var(--on-surface) !important;
            border-color: var(--outline-variant) !important;
        }
        .dark label {
            color: var(--on-surface-variant) !important;
        }
        .dark #editProfileModal, .dark #changePasswordModal, .dark .modal-content {
            background-color: var(--surface) !important;
            color: var(--on-surface) !important;
            border-color: var(--outline-variant) !important;
        }
        .dark table, .dark tr, .dark th, .dark td {
            color: var(--on-surface) !important;
            border-color: var(--outline-variant) !important;
        }
        .dark tr:hover {
            background-color: var(--surface-container-low) !important;
        }

        body, header, nav, main, div, p, span, input, select, textarea, button, a {
            transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
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
            
            <!-- START NEW CODE - Notification Bell & Dropdown -->
            <div class="relative">
                <button id="notifBellBtn" onclick="toggleNotifDropdown(event)" class="relative p-2 rounded-full hover:bg-surface-container-low transition-colors focus:outline-none" title="Notifications">
                    <span class="material-symbols-outlined text-on-surface-variant pointer-events-none" data-icon="notifications">notifications</span>
                    <?php if ($unread_notifications_count > 0): ?>
                        <span id="notifBadge" class="absolute top-1.5 right-1.5 min-w-[18px] h-[18px] bg-error text-white text-[10px] font-bold rounded-full border-2 border-surface flex items-center justify-center px-1">
                            <?= $unread_notifications_count ?>
                        </span>
                    <?php else: ?>
                        <span id="notifBadge" class="hidden absolute top-1.5 right-1.5 min-w-[18px] h-[18px] bg-error text-white text-[10px] font-bold rounded-full border-2 border-surface flex items-center justify-center px-1"></span>
                    <?php endif; ?>
                </button>

                <!-- Notification Dropdown Menu -->
                <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-surface-container-lowest border border-[#EDEBE9] rounded-xl shadow-lg z-50 overflow-hidden transition-all">
                    <div class="p-3 bg-surface-container-low border-b border-[#EDEBE9] flex justify-between items-center">
                        <span class="font-title-md font-semibold text-sm text-on-surface">Notifications</span>
                        <button onclick="markAllNotificationsRead(event)" class="text-xs text-primary font-medium hover:underline focus:outline-none">Mark all as read</button>
                    </div>
                    <div class="max-h-80 overflow-y-auto divide-y divide-[#EDEBE9]" id="notifListContainer">
                        <?php if (!empty($notifications_list)): ?>
                            <?php foreach ($notifications_list as $notif): ?>
                                <div id="notif-item-<?= (int)$notif['id'] ?>" onclick="markNotifRead(<?= (int)$notif['id'] ?>)" class="p-3 hover:bg-surface-container-low transition-colors cursor-pointer flex items-start gap-2.5 <?= empty($notif['is_read']) ? 'bg-primary/5 font-medium' : 'opacity-75' ?>">
                                    <span class="material-symbols-outlined text-base mt-0.5 <?= empty($notif['is_read']) ? 'text-primary' : 'text-outline' ?>">
                                        <?= empty($notif['is_read']) ? 'mail' : 'drafts' ?>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1">
                                            <p class="text-xs font-semibold text-on-surface truncate"><?= htmlspecialchars($notif['title']) ?></p>
                                            <span class="text-[10px] text-outline whitespace-nowrap"><?= date('H:i, M d', strtotime($notif['created_at'])) ?></span>
                                        </div>
                                        <p class="text-xs text-on-surface-variant line-clamp-2 mt-0.5"><?= htmlspecialchars($notif['message']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-6 text-center text-outline">
                                <span class="material-symbols-outlined text-3xl mb-1 text-outline/50">notifications_off</span>
                                <p class="text-xs font-medium">No notifications found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-2 text-center bg-surface-container-low border-t border-[#EDEBE9]">
                        <a href="index.php?page=my-tasks" class="text-xs font-semibold text-primary hover:underline">Go to Task Workspace</a>
                    </div>
                </div>
            </div>

            <!-- START NEW CODE - Profile Avatar & Dropdown -->
            <div class="relative hidden md:block">
                <button id="profileDropdownBtn" onclick="toggleProfileDropdown(event)" class="flex items-center gap-sm border-l border-outline-variant pl-md ml-base hover:opacity-90 focus:outline-none transition-opacity">
                    <div class="text-right">
                        <p class="font-title-md text-on-surface leading-tight font-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin User') ?></p>
                        <p class="font-caption text-outline text-xs"><?= htmlspecialchars($_SESSION['role_name'] ?? 'Super Admin') ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center border border-primary/20 overflow-hidden">
                        <?php 
                        $profileUrl = getProfileImageUrl($_SESSION['profile_image'] ?? null); 
                        $defaultAvatar = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';
                        ?>
                        <img id="headerAvatarImg" src="<?= htmlspecialchars($profileUrl) ?><?= ($profileUrl !== $defaultAvatar) ? '?t=' . time() : '' ?>" class="w-full h-full object-cover" alt="Profile" onerror="this.src='<?= $defaultAvatar ?>';">
                    </div>
                    <span class="material-symbols-outlined text-outline text-sm">expand_more</span>
                </button>

                <!-- Profile Menu Dropdown -->
                <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-56 bg-surface-container-lowest border border-[#EDEBE9] rounded-xl shadow-lg z-50 overflow-hidden py-1">
                    <div class="px-4 py-2 border-b border-[#EDEBE9] bg-surface-container-low">
                        <p class="text-xs font-semibold text-on-surface"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin User') ?></p>
                        <p class="text-[11px] text-outline truncate"><?= htmlspecialchars($_SESSION['email'] ?? 'admin@privacyhq.com') ?></p>
                    </div>
                    <a href="index.php?page=edit-profile" class="flex items-center gap-2 px-4 py-2.5 text-xs text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-base text-primary">person</span>
                        <span>My Profile</span>
                    </a>
                    <a href="index.php?page=edit-profile" class="flex items-center gap-2 px-4 py-2.5 text-xs text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-base text-secondary">edit</span>
                        <span>Edit Profile</span>
                    </a>
                    <a href="index.php?page=notification-preferences" class="flex items-center gap-2 px-4 py-2.5 text-xs text-on-surface hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-base text-tertiary">notifications</span>
                        <span>Notification Preferences</span>
                    </a>
                    <div class="border-t border-[#EDEBE9] my-1"></div>
                    <a href="logout.php" class="flex items-center gap-2 px-4 py-2.5 text-xs text-red-600 font-medium hover:bg-red-50 transition-colors">
                        <span class="material-symbols-outlined text-base">logout</span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
            <!-- END NEW CODE - Profile Avatar & Dropdown -->

        </div>
    </header>

    <!-- Main Dynamic Content Area -->
    <main class="pt-20 px-container-padding max-w-7xl mx-auto space-y-lg">
        <?php if (isset($_GET['error'])): ?>
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl flex items-center gap-2 mb-4 shadow-sm">
                <span class="material-symbols-outlined text-red-600">error</span>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>
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

    <script src="assets/js/notifications.js"></script>

    <div class="fixed top-0 right-0 -z-10 w-1/3 h-1/2 bg-gradient-to-bl from-primary/5 to-transparent blur-3xl pointer-events-none"></div>
    <div class="fixed bottom-0 left-0 -z-10 w-1/3 h-1/2 bg-gradient-to-tr from-secondary-container/5 to-transparent blur-3xl pointer-events-none"></div>

    <!-- START NEW CODE - Interactive Dropdown Scripts -->
    <script>
    function toggleNotifDropdown(e) {
        e.stopPropagation();
        const dd = document.getElementById('notifDropdown');
        const pdd = document.getElementById('profileDropdown');
        if (pdd) pdd.classList.add('hidden');
        if (dd) dd.classList.toggle('hidden');
    }

    function toggleProfileDropdown(e) {
        e.stopPropagation();
        const pdd = document.getElementById('profileDropdown');
        const dd = document.getElementById('notifDropdown');
        if (dd) dd.classList.add('hidden');
        if (pdd) pdd.classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        const dd = document.getElementById('notifDropdown');
        const pdd = document.getElementById('profileDropdown');
        if (dd && !dd.contains(e.target) && !e.target.closest('#notifBellBtn')) {
            dd.classList.add('hidden');
        }
        if (pdd && !pdd.contains(e.target) && !e.target.closest('#profileDropdownBtn')) {
            pdd.classList.add('hidden');
        }
    });

    function markNotifRead(id) {
        fetch('api/mark-notification-read.php?id=' + id, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const item = document.getElementById('notif-item-' + id);
                    if (item) {
                        item.classList.add('opacity-75');
                        item.classList.remove('bg-primary/5', 'font-medium');
                    }
                    updateBadge(data.unread_count);
                }
            }).catch(err => console.error(err));
    }

    function markAllNotificationsRead(e) {
        e.stopPropagation();
        fetch('api/mark-notification-read.php?all=1', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const items = document.querySelectorAll('#notifListContainer > div');
                    items.forEach(el => {
                        el.classList.add('opacity-75');
                        el.classList.remove('bg-primary/5', 'font-medium');
                    });
                    updateBadge(0);
                }
            }).catch(err => console.error(err));
    }

    function updateBadge(count) {
        const badge = document.getElementById('notifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
    </script>
    <!-- END NEW CODE - Interactive Dropdown Scripts -->
</body>
</html>