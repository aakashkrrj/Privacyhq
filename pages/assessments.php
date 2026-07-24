<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../backend/services/AssessmentService.php';
requireLogin();

$service = new AssessmentService();
$stats = $service->getDashboardStats();

// We get the active assessments by filtering out closed ones (assuming closed is not what we want to see here)
// For simplicity we just fetch the latest active ones
$assessments = $service->getAssessments([], 1, 50, 'due_date', 'ASC');

/**
 * Helper to determine risk badge theme
 */
function getRiskTheme($riskName) {
    $riskName = strtolower($riskName ?? '');
    if (strpos($riskName, 'high') !== false || strpos($riskName, 'critical') !== false) {
        return ['bg' => 'bg-error/10', 'text' => 'text-error'];
    } elseif (strpos($riskName, 'medium') !== false) {
        return ['bg' => 'bg-secondary/10', 'text' => 'text-secondary'];
    } else {
        return ['bg' => 'bg-on-tertiary-fixed-variant/10', 'text' => 'text-on-tertiary-fixed-variant'];
    }
}
?>
<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>Privacy Assessment | PrivacyHQ</title>
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
        .progress-ring__circle { transition: stroke-dashoffset 0.35s; transform: rotate(-90deg); transform-origin: 50% 50%; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(237, 235, 233, 0.5); }
        .safe-pb { padding-bottom: env(safe-area-inset-bottom); }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-surface text-on-surface min-h-screen pb-24">
<!-- TopAppBar -->
<header class="fixed top-0 left-0 w-full bg-surface dark:bg-background shadow-sm flex justify-between items-center px-container-padding h-16 z-50">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim" data-icon="security">security</span>
<h1 class="font-display text-display text-primary dark:text-primary-fixed-dim text-headline-lg-mobile">PrivacyHQ</h1>
</div>
<div class="flex items-center gap-md">
<button class="hover:bg-surface-container-low p-2 rounded-full transition-colors">
<span class="material-symbols-outlined text-on-surface-variant" data-icon="notifications">notifications</span>
</button>
<div class="w-8 h-8 rounded-full bg-secondary-fixed flex items-center justify-center overflow-hidden">
<img class="w-full h-full object-cover" data-alt="A professional headshot of a corporate compliance officer in a modern bright office environment. The person is wearing smart business attire, looking directly at the camera with a confident and enabling expression. High-key lighting, soft shadows, and a clean Microsoft Fluent design aesthetic with a shallow depth of field." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjWyT2gvFuYfJQ2HAOg6kCMiF__EeSEDSqUCbx3tSRicBCnBJrnAkFuk7LvZtuCM4C-tZA6GtPQJIqR2psEf75TkjkzxwfyV6owoFdcwJdrHnH3M8iVCBuycnTVXYC7lWvJk0cE52sj_MHYsrQNKHe0EsWx2PjKesasvzdVMo787xrPFMdACdOrjcf5YRmsRMgV2u9aJHXQhecix-yncQrYzM3xOT6-bS0mRE5i1b8YQ6YIC354wz-eo3tyPMoPp8qL2VKEa-6cOze"/>
</div>
</div>
</header>
<main class="pt-20 pb-32 px-container-padding max-w-4xl mx-auto space-y-lg">
<!-- Header Section -->
<section class="flex justify-between items-end">
<div class="space-y-base">
<h2 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface">Privacy Assessments</h2>
<p class="font-body-md text-body-md text-outline">Manage and track your DPIA workflows.</p>
</div>
<div class="hidden md:block">
<button class="bg-primary-container text-on-primary-container px-lg py-sm rounded-xl font-label-md flex items-center gap-sm hover:opacity-90 transition-opacity">
<span class="material-symbols-outlined" data-icon="add">add</span>
                    New DPIA
                </button>
</div>
</section>
<!-- Stats Overview (Asymmetric Layout) -->
<section class="grid grid-cols-12 gap-md">
<div class="col-span-12 md:col-span-8 glass-card rounded-xl p-md flex items-center justify-between shadow-sm">
<div class="space-y-base">
<span class="font-label-md text-label-md text-outline uppercase tracking-wider">Overall Progress</span>
<h3 class="font-headline-lg text-headline-lg text-primary"><?= htmlspecialchars($stats['compliance_percentage']) ?>% Compliant</h3>
<p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($stats['pending_reviews']) ?> Assessments pending review.</p>
</div>
<div class="relative w-24 h-24">
<svg class="w-full h-full" viewbox="0 0 100 100">
<circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
<circle class="text-primary stroke-current progress-ring__circle" cx="50" cy="50" fill="transparent" r="40" stroke-linecap="round" stroke-width="10" style="stroke-dasharray: 251.2; stroke-dashoffset: <?= 251.2 - (251.2 * ($stats['compliance_percentage'] / 100)) ?>;"></circle>
</svg>
<div class="absolute inset-0 flex items-center justify-center font-title-md text-primary"><?= htmlspecialchars($stats['compliance_percentage']) ?>%</div>
</div>
</div>
<div class="col-span-12 md:col-span-4 bg-tertiary-container text-on-tertiary-container rounded-xl p-md flex flex-col justify-between shadow-sm">
<span class="material-symbols-outlined text-3xl" data-icon="bolt">bolt</span>
<div>
<h4 class="font-title-md text-title-md">Quick Actions</h4>
<p class="font-body-md text-body-md opacity-90">Resume last audit: HR Data Flow</p>
</div>
</div>
</section>
<!-- Active Assessments List -->
<section class="space-y-md">
<div class="flex items-center justify-between">
<h3 class="font-title-md text-title-md text-on-surface">Active Assessments</h3>
<button class="text-primary font-label-md flex items-center gap-xs">
                    View All <span class="material-symbols-outlined text-sm" data-icon="arrow_forward">arrow_forward</span>
</button>
</div>
<div class="space-y-stack-gap">
<?php foreach ($assessments as $assessment): 
    $progress = $assessment['progress_percentage'];
    $dashOffset = 251.2 - (251.2 * ($progress / 100));
    $riskTheme = getRiskTheme($assessment['risk_level_name']);
    $owner = $assessment['owner_name'] ?: 'Unassigned';
    $dueDate = $assessment['due_date'] ? date('M d, Y', strtotime($assessment['due_date'])) : 'N/A';
?>
<!-- Assessment Card -->
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-md shadow-sm hover:shadow-md transition-shadow group cursor-pointer">
<div class="flex items-start justify-between mb-md">
<div class="flex items-center gap-md">
<div class="relative w-12 h-12">
<svg class="w-full h-full" viewbox="0 0 100 100">
<circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="8"></circle>
<circle class="text-secondary stroke-current progress-ring__circle" cx="50" cy="50" fill="transparent" r="40" stroke-linecap="round" stroke-width="8" style="stroke-dasharray: 251.2; stroke-dashoffset: <?= $dashOffset ?>;"></circle>
</svg>
<div class="absolute inset-0 flex items-center justify-center font-caption text-secondary"><?= $progress ?>%</div>
</div>
<div>
<h4 class="font-title-md text-title-md text-on-surface group-hover:text-primary transition-colors"><?= htmlspecialchars($assessment['title']) ?></h4>
<p class="font-body-md text-body-md text-outline"><?= htmlspecialchars($assessment['activity_name']) ?></p>
</div>
</div>
<span class="<?= $riskTheme['bg'] ?> <?= $riskTheme['text'] ?> px-sm py-1 rounded-full font-label-md"><?= htmlspecialchars($assessment['risk_level_name'] ?: 'Unassessed') ?></span>
</div>
<div class="grid grid-cols-2 gap-md pt-md border-t border-surface-container">
<div class="flex items-center gap-sm">
<span class="material-symbols-outlined text-outline text-lg" data-icon="person">person</span>
<span class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($owner) ?></span>
</div>
<div class="flex items-center gap-sm justify-end">
<span class="material-symbols-outlined text-outline text-lg" data-icon="calendar_today">calendar_today</span>
<span class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($dueDate) ?></span>
</div>
</div>
</div>
<?php endforeach; ?>
<?php if (empty($assessments)): ?>
<div class="text-center py-lg text-outline">No active assessments found.</div>
<?php endif; ?>
</div>
</section>
</main>
<!-- FAB for Start New Assessment (Mobile) -->
<div class="md:hidden fixed bottom-20 right-6 z-40">
<button class="w-14 h-14 bg-primary-container text-on-primary-container rounded-2xl shadow-lg flex items-center justify-center active:scale-95 transition-transform">
<span class="material-symbols-outlined text-2xl" data-icon="add">add</span>
</button>
</div>
<!-- Bottom Navigation Bar -->
<!-- Standard Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 w-full z-50
            bg-surface shadow-[0px_-2px_4px_rgba(0,0,0,0.04)]
            flex justify-around items-center h-16 px-2">

    <!-- Dashboard -->
    <a href="/governance/index.php"
       class="nav-dashboard flex flex-col items-center justify-center
              text-on-surface-variant px-4 py-1 rounded-xl">

        <span class="material-symbols-outlined"
              style="font-variation-settings:'FILL' 0;">
            dashboard
        </span>

        <span class="font-label-md text-label-md">Dashboard</span>
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
        // Micro-interaction for cards
        document.querySelectorAll('.group').forEach(card => {
            card.addEventListener('mousedown', () => {
                card.style.transform = 'scale(0.98)';
            });
            card.addEventListener('mouseup', () => {
                card.style.transform = 'scale(1)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
            });
        });
    </script>
</body></html>