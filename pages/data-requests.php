<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../backend/services/RequestService.php';
requireLogin();

$service = new RequestService();

// Fetch metrics
$activeCount = $service->countRequests(['status' => 'open']);
$activeCount += $service->countRequests(['status' => 'processing']);
$activeCount += $service->countRequests(['status' => 'verifying']);

$criticalCount = $service->countRequests(['priority' => RequestService::PRIORITY_URGENT]);
$criticalCount += $service->countRequests(['priority' => RequestService::PRIORITY_HIGH]);

// Get List
$requests = $service->getRequests([], 1, 50, 'due_date', 'ASC');

/**
 * Helper to determine card color theme based on type/priority
 */
function getThemeByPriority($priority, $type) {
    if ($priority === RequestService::PRIORITY_URGENT) return ['bg' => 'bg-error/10', 'text' => 'text-error', 'bar' => 'bg-error', 'btn' => 'bg-error', 'border' => 'border-error'];
    if ($type === RequestService::TYPE_ERASURE) return ['bg' => 'bg-error/10', 'text' => 'text-error', 'bar' => 'bg-error', 'btn' => 'bg-error', 'border' => 'border-error'];
    if ($type === RequestService::TYPE_PORTABILITY) return ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'bar' => 'bg-tertiary', 'btn' => 'bg-secondary', 'border' => 'border-secondary'];
    return ['bg' => 'bg-primary/10', 'text' => 'text-primary', 'bar' => 'bg-primary', 'btn' => 'bg-primary', 'border' => 'border-primary'];
}

/**
 * Helper to calculate days left
 */
function getDaysLeft($dueDate) {
    if (!$dueDate) return 'N/A';
    $now = new DateTime();
    $due = new DateTime($dueDate);
    $diff = $now->diff($due);
    if ($diff->invert && $diff->days > 0) {
        return $diff->days . ' Days Overdue';
    } elseif ($diff->days == 0) {
        return 'Today';
    }
    return $diff->days . ' Days Left';
}
?>
<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>PrivacyHQ - Data Requests</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
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
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    font-size: 24px;
    line-height: 1;
    letter-spacing: normal;
    text-transform: none;
    display: inline-block;
    white-space: nowrap;
    word-wrap: normal;
    direction: ltr;
    -webkit-font-feature-settings: 'liga';
    font-feature-settings: 'liga';
    -webkit-font-smoothing: antialiased;

    font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24;
        }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(8px); border: 1px solid #EDEBE9; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D2D0CE; border-radius: 10px; }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background text-on-background min-h-screen pb-24 md:pb-0">
<!-- Top App Bar -->
<header class="fixed top-0 left-0 w-full md:w-[calc(100%-320px)] h-16 bg-surface z-50 flex justify-between items-center px-container-padding shadow-sm">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary" data-icon="security">security</span>
<h1 class="font-display text-display text-primary text-xl">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-md">
<button class="p-base hover:bg-surface-container-low transition-colors rounded-full">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
</button>
<div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-xs">
                AU
            </div>
</div>
</header>
<!-- Main Content Canvas -->
<main class="pt-20 px-container-padding max-w-7xl mx-auto">
<!-- Header & Stats -->
<div class="flex flex-col md:flex-row md:items-end justify-between gap-lg mb-xl">
<div>
<h2 class="font-headline-lg-mobile text-headline-lg-mobile md:font-headline-lg md:text-headline-lg text-on-surface">Data Requests</h2>
<p class="font-body-md text-body-md text-on-surface-variant">Manage and respond to Subject Access Requests (DSAR)</p>
</div>
<div class="flex gap-sm">
<div class="bg-surface-container p-sm px-md rounded-lg flex items-center gap-sm">
<span class="w-2 h-2 rounded-full bg-error"></span>
<span class="font-label-md text-label-md"><?= $criticalCount ?> Critical</span>
</div>
<div class="bg-surface-container p-sm px-md rounded-lg flex items-center gap-sm">
<span class="w-2 h-2 rounded-full bg-primary"></span>
<span class="font-label-md text-label-md"><?= $activeCount ?> Active</span>
</div>
</div>
</div>
<!-- Filters Section -->
<div class="flex flex-col md:flex-row gap-md mb-lg">
<div class="flex-1 relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline" data-icon="search">search</span>
<input class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary bg-white transition-all text-body-md" placeholder="Search by ID or email..." type="text"/>
</div>
<div class="flex gap-sm overflow-x-auto pb-sm md:pb-0">
<select class="rounded-xl border-outline-variant bg-white text-body-md px-4 py-2.5 min-w-[140px] focus:ring-primary focus:border-primary">
<option>Type: All</option>
<option>Access</option>
<option>Deletion</option>
<option>Portability</option>
</select>
<select class="rounded-xl border-outline-variant bg-white text-body-md px-4 py-2.5 min-w-[140px] focus:ring-primary focus:border-primary">
<option>Priority: All</option>
<option>High</option>
<option>Medium</option>
<option>Low</option>
</select>
<button class="bg-primary text-white px-lg py-2.5 rounded-xl font-body-md flex items-center gap-sm hover:opacity-90 transition-opacity">
<span class="material-symbols-outlined text-[20px]" data-icon="add">add</span>
                    New Request
                </button>
</div>
</div>
<!-- Request List (Asymmetric Bento/Card Layout) -->
<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-md mb-24">
<?php foreach ($requests as $req): 
    $theme = getThemeByPriority($req['priority'], $req['request_type']);
    $typeLabel = RequestService::getTypeLabel($req['request_type']);
    $statusLabel = RequestService::getStatusLabel($req['status']);
    $daysLeft = getDaysLeft($req['due_date']);
    $dueStr = $req['due_date'] ? date('M d', strtotime($req['due_date'])) : 'N/A';
    $assignee = $req['assigned_to_name'] ?: 'Unassigned';
    $assigneeInitials = strtoupper(substr($assignee, 0, 2));
    $progress = (int)$req['progress_percentage'];
    
    // Adjust layout for urgent
    $colSpan = ($req['priority'] === RequestService::PRIORITY_URGENT) ? 'lg:col-span-1 xl:col-span-2' : '';
?>
<div class="glass-card rounded-xl p-md flex flex-col shadow-sm border border-outline-variant hover:shadow-md transition-all <?= $colSpan ?>">
    
    <div class="flex <?= $colSpan ? 'flex-col md:flex-row md:items-center' : '' ?> justify-between items-start gap-md mb-md">
        <div>
            <span class="inline-block font-label-md text-label-md <?= $theme['bg'] ?> <?= $theme['text'] ?> px-sm py-0.5 rounded-full mb-xs">
                <?= $colSpan ? 'Priority: Urgent' : htmlspecialchars($typeLabel) ?>
            </span>
            <h3 class="font-title-md text-title-md text-on-surface"><?= htmlspecialchars($req['request_id_code']) ?></h3>
            <p class="font-body-md text-body-md text-on-surface-variant truncate"><?= htmlspecialchars($req['user_email']) ?></p>
        </div>
        <div class="flex flex-col items-end">
            <?php if ($req['status'] === RequestService::STATUS_COMPLETED): ?>
                <span class="material-symbols-outlined text-tertiary" data-icon="check_circle">check_circle</span>
                <span class="font-caption text-caption text-outline">Done</span>
            <?php else: ?>
                <span class="font-label-md text-label-md <?= ($req['priority'] == 'Urgent' || strpos($daysLeft, 'Overdue') !== false) ? 'text-error' : 'text-on-surface-variant' ?> font-bold"><?= $daysLeft ?></span>
                <span class="font-caption text-caption text-outline">Due: <?= $dueStr ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-auto space-y-sm">
        <div class="flex justify-between items-center mb-xs">
            <span class="font-label-md text-label-md text-on-surface-variant"><?= htmlspecialchars($statusLabel) ?></span>
            <span class="font-label-md text-label-md font-bold"><?= $progress ?>%</span>
        </div>
        <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
            <div class="<?= $theme['bar'] ?> h-full rounded-full transition-all duration-1000" style="width: <?= $progress ?>%"></div>
        </div>
    </div>
    
    <div class="mt-md pt-md border-t border-surface-variant flex justify-between items-center">
        <?php if ($colSpan): ?>
            <div class="flex items-center gap-sm">
                <span class="material-symbols-outlined text-error" data-icon="warning">warning</span>
                <span class="font-caption text-caption text-error font-semibold">Immediate action required</span>
            </div>
            <button class="bg-error text-white px-lg py-1.5 rounded-lg font-label-md hover:opacity-90 transition-opacity">
                Escalate
            </button>
        <?php else: ?>
            <div class="flex -space-x-2">
                <div class="w-6 h-6 rounded-full border-2 border-white bg-primary-container flex items-center justify-center text-[10px] font-bold text-white">
                    <?= htmlspecialchars($assigneeInitials) ?>
                </div>
            </div>
            <button class="text-primary font-label-md text-label-md flex items-center gap-xs">
                Details <span class="material-symbols-outlined text-[16px]" data-icon="chevron_right">chevron_right</span>
            </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
</main>
<!-- Bottom Nav Bar (Mobile) -->
<!-- Standard Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 w-full z-50
            bg-surface shadow-[0px_-2px_4px_rgba(0,0,0,0.04)]
            flex justify-around items-center h-16 px-2">

    <!-- Dashboard -->
    <a href="../index.php"
       class="nav-dashboard flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            dashboard
        </span>

        <span class="font-label-md text-label-md">Dashboard</span>
    </a>

    <!-- Consent -->
    <a href="consent-management.php"
       class="nav-consent flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            verified_user
        </span>

        <span class="font-label-md text-label-md">Consent</span>
    </a>

    <!-- Requests -->
    <a href="data-requests.php"
       class="nav-requests flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            gavel
        </span>

        <span class="font-label-md text-label-md">Requests</span>
    </a>

    <!-- Assess -->
    <a href="assessments.php"
       class="nav-assess flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            assignment_turned_in
        </span>

        <span class="font-label-md text-label-md">Assess</span>
    </a>

    <!-- More -->
    <a href="more.php"
       class="nav-more flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            menu
        </span>

        <span class="font-label-md text-label-md">More</span>
    </a>

</nav>
<!-- Floating Action Button (Mobile) -->
<button class="md:hidden fixed right-md bottom-20 w-14 h-14 bg-primary text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform z-40">
<span class="material-symbols-outlined" data-icon="add">add</span>
</button>
<script>
        // Micro-interaction for cards
        document.querySelectorAll('.glass-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-2px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0px)';
            });
        });
    </script>
</body></html>