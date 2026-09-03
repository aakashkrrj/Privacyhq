<?php
// governance/pages/dashboard-main.php
if (!defined('IN_APP')) { http_response_code(403); exit('Direct access not permitted'); }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/dashboard-components.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User greeting context
$user_name = $_SESSION['user_name'] ?? ($_SESSION['first_name'] ?? 'User');

// Initialize default counts and scores
$vendor_count = 0;
$incident_count = 0;
$assessment_count = 0;
$vendor_risk_level = 'Low';
$vendor_risk_bg = 'bg-emerald-100 text-emerald-800';

$policy_score = 0;
$assessment_score = 0;
$vendor_score = 0;

if (isset($conn) && !$conn->connect_error) {
    // 1. Get total vendors
    $res = $conn->query("SELECT COUNT(*) AS total FROM vendors WHERE deleted_at IS NULL");
    if ($res) {
        $vendor_count = (int)$res->fetch_assoc()['total'];
    }

    // 2. Get active incidents
    $res = $conn->query("SELECT COUNT(*) AS total FROM incidents WHERE status != 'Resolved' AND deleted_at IS NULL");
    if ($res) {
        $incident_count = (int)$res->fetch_assoc()['total'];
    }

    // 3. Get total assessments
    $res = $conn->query("SELECT COUNT(*) AS total FROM privacy_assessments WHERE deleted_at IS NULL");
    if ($res) {
        $assessment_count = (int)$res->fetch_assoc()['total'];
    }

    // 4. Vendor Risk Level Calculation
    $res = $conn->query("SELECT status, risk_score FROM vendor_assessments");
    if ($res && $res->num_rows > 0) {
        $has_critical = false;
        $has_audit = false;
        while ($r = $res->fetch_assoc()) {
            if ($r['status'] === 'Critical Review' || (int)$r['risk_score'] >= 70) {
                $has_critical = true;
            }
            if ($r['status'] === 'Under Audit' || (int)$r['risk_score'] >= 40) {
                $has_audit = true;
            }
        }
        if ($has_critical) {
            $vendor_risk_level = 'High';
            $vendor_risk_bg = 'bg-error/10 text-error';
        } elseif ($has_audit) {
            $vendor_risk_level = 'Medium';
            $vendor_risk_bg = 'bg-amber-100 text-amber-800';
        } else {
            $vendor_risk_level = 'Low';
            $vendor_risk_bg = 'bg-emerald-100 text-emerald-800';
        }
    }

    // 5. Compliance Scores Calculation
    // Policies
    $total_policies = 0;
    $active_policies = 0;
    $res = $conn->query("SELECT COUNT(*) AS total FROM privacy_policies");
    if ($res) {
        $total_policies = (int)$res->fetch_assoc()['total'];
    }
    $res = $conn->query("SELECT COUNT(*) AS total FROM privacy_policies WHERE LOWER(status) = 'active'");
    if ($res) {
        $active_policies = (int)$res->fetch_assoc()['total'];
    }
    $policy_score = ($total_policies > 0) ? ($active_policies / $total_policies) * 100 : 0;

    // Assessments
    $total_assessments = 0;
    $completed_assessments = 0;
    $res = $conn->query("SELECT COUNT(*) AS total FROM privacy_assessments WHERE deleted_at IS NULL");
    if ($res) {
        $total_assessments = (int)$res->fetch_assoc()['total'];
    }
    $res = $conn->query("
        SELECT COUNT(*) AS total 
        FROM privacy_assessments pa 
        INNER JOIN assessment_statuses s ON pa.status_id = s.id 
        WHERE LOWER(s.status_name) = 'completed' AND pa.deleted_at IS NULL
    ");
    if ($res) {
        $completed_assessments = (int)$res->fetch_assoc()['total'];
    }
    $assessment_score = ($total_assessments > 0) ? ($completed_assessments / $total_assessments) * 100 : 0;

    // Vendor Compliance
    $total_vendor_assessments = 0;
    $compliant_vendors = 0;
    $res = $conn->query("SELECT COUNT(*) AS total FROM vendor_assessments");
    if ($res) {
        $total_vendor_assessments = (int)$res->fetch_assoc()['total'];
    }
    $res = $conn->query("SELECT COUNT(*) AS total FROM vendor_assessments WHERE status = 'Compliant'");
    if ($res) {
        $compliant_vendors = (int)$res->fetch_assoc()['total'];
    }
    $vendor_score = ($total_vendor_assessments > 0) ? ($compliant_vendors / $total_vendor_assessments) * 100 : 0;
}

// Final Dashboard Scores
$privacy_score = round(($assessment_score + $policy_score + $vendor_score) / 3);
$dpdp_score = round(($policy_score + $vendor_score) / 2);

// Calculate gauge stroke dash offsets (circumference = 251.2)
$privacy_offset = round(251.2 * (1 - ($privacy_score / 100)), 2);
$dpdp_offset = round(251.2 * (1 - ($dpdp_score / 100)), 2);

// START NEW CODE - Dynamic Audit Logs (Recent Activities)
$recent_activities = [];
if (isset($conn) && !$conn->connect_error) {
    $res = $conn->query("
        SELECT module, action, created_at 
        FROM audit_logs 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
}
// END NEW CODE

// START NEW CODE - Dynamic Analytics Chart Data
$period_param = isset($_GET['period']) ? trim($_GET['period']) : '30d';
$period_days = ($period_param === '3m') ? 90 : 30;

$chart_buckets = [0, 0, 0, 0, 0];
$chart_labels = [];
$step = floor($period_days / 4);

for ($i = 0; $i < 5; $i++) {
    $days_ago = (4 - $i) * $step;
    $chart_labels[$i] = date('M d', strtotime("-$days_ago days"));
}

if (isset($conn) && !$conn->connect_error) {
    for ($i = 0; $i < 5; $i++) {
        $days_start = (4 - $i) * $step + $step;
        $days_end = (4 - $i) * $step;
        $res = $conn->query("
            SELECT COUNT(*) AS total 
            FROM data_requests 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days_start DAY)
              AND created_at <= DATE_SUB(NOW(), INTERVAL $days_end DAY)
        ");
        if ($res) {
            $chart_buckets[$i] = (int)$res->fetch_assoc()['total'];
        }
    }
}

$max_count = max(5, max($chart_buckets));
$x_coords = [0, 25, 50, 75, 100];
$coords = [];

for ($i = 0; $i < 5; $i++) {
    $val = $chart_buckets[$i];
    $y = round(36 - (($val / $max_count) * 30), 1);
    $coords[] = ['x' => $x_coords[$i], 'y' => $y, 'val' => $val];
}

$path_d = "M0 40 L0 {$coords[0]['y']}";
for ($i = 1; $i < 5; $i++) {
    $path_d .= " L{$coords[$i]['x']} {$coords[$i]['y']}";
}
$path_d .= " L100 40 Z";

$line_d = "M0 {$coords[0]['y']}";
for ($i = 1; $i < 5; $i++) {
    $line_d .= " L{$coords[$i]['x']} {$coords[$i]['y']}";
}
// END NEW CODE
?>

<!-- Welcome Header -->
<section class="mt-2 md:mt-4 mb-4 md:mb-6">
    <h2 class="font-headline-lg-mobile text-xl md:text-2xl lg:text-3xl font-semibold text-on-surface tracking-tight">
        Welcome back, <?= htmlspecialchars($user_name) ?>
    </h2>
    <p class="font-body-md text-on-surface-variant text-sm md:text-base mt-1">
        Here is your data protection summary for today.
    </p>
</section>

<!-- Section 1: Circular Progress Gauges -->
<section class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
    <!-- Privacy Compliance Score -->
    <div class="bg-surface-container-lowest p-5 md:p-6 rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex items-center justify-between gap-4 group hover:shadow-md transition-shadow">
        <div class="flex-1 min-w-0">
            <h3 class="font-title-md text-on-surface text-base md:text-lg font-semibold truncate">Privacy Compliance Score</h3>
            <p class="font-caption text-outline text-xs mt-0.5">Across all business units</p>
            <div class="mt-3 md:mt-4 flex items-center gap-1.5 text-[#107C10]">
                <span class="material-symbols-outlined text-sm" data-icon="trending_up">trending_up</span>
                <span class="font-label-md text-xs font-medium">Calculated dynamically</span>
            </div>
        </div>
        <div class="relative w-20 h-20 md:w-24 md:h-24 flex-shrink-0">
            <svg class="gauge-svg w-20 h-20 md:w-24 md:h-24" viewBox="0 0 100 100">
                <circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
                <circle class="text-primary stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-dasharray="251.2" stroke-dashoffset="<?= $privacy_offset ?>" stroke-linecap="round" stroke-width="10"></circle>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="font-headline-lg-mobile text-lg md:text-2xl font-bold text-primary"><?= (int)$privacy_score ?>%</span>
            </div>
        </div>
    </div>

    <!-- DPDP Compliance Score -->
    <div class="bg-surface-container-lowest p-5 md:p-6 rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex items-center justify-between gap-4 group hover:shadow-md transition-shadow">
        <div class="flex-1 min-w-0">
            <h3 class="font-title-md text-on-surface text-base md:text-lg font-semibold truncate">DPDP Compliance Score</h3>
            <p class="font-caption text-outline text-xs mt-0.5">India Regulatory Framework</p>
            <div class="mt-3 md:mt-4 flex items-center gap-1.5 text-[#107C10]">
                <span class="material-symbols-outlined text-sm" data-icon="verified">verified</span>
                <span class="font-label-md text-xs font-medium">Highly Compliant</span>
            </div>
        </div>
        <div class="relative w-20 h-20 md:w-24 md:h-24 flex-shrink-0">
            <svg class="gauge-svg w-20 h-20 md:w-24 md:h-24" viewBox="0 0 100 100">
                <circle class="text-surface-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-width="10"></circle>
                <circle class="text-secondary-container stroke-current" cx="50" cy="50" fill="transparent" r="40" stroke-dasharray="251.2" stroke-dashoffset="<?= $dpdp_offset ?>" stroke-linecap="round" stroke-width="10"></circle>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="font-headline-lg-mobile text-lg md:text-2xl font-bold text-on-secondary-container"><?= (int)$dpdp_score ?>%</span>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Summary Cards Grid (Dynamic PHP Values) -->
<section class="mt-4 md:mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
    <?php
    render_metric_card('assignment_turned_in', 'text-primary', $assessment_count, 'DPIA Assessments');
    render_metric_card('group', 'text-secondary', $vendor_count, 'Total Vendors');
    render_metric_card('warning', 'text-error', $vendor_risk_level, 'Vendor Risk', '<span class="inline-flex items-center px-2.5 py-0.5 ' . $vendor_risk_bg . ' rounded-full text-xs font-semibold">' . htmlspecialchars($vendor_risk_level) . '</span>');
    render_metric_card('error', 'text-tertiary', $incident_count, 'Active Incidents');
    ?>
</section>

<!-- Section 3: Analytics Card -->
<section class="mt-4 md:mt-6">
    <div class="bg-surface-container-lowest p-5 md:p-6 rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] hover:shadow-md transition-shadow">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 md:mb-6 gap-3">
            <div>
                <h3 class="font-title-md text-on-surface text-base md:text-lg font-semibold">Privacy Request Trends</h3>
                <p class="font-body-md text-on-surface-variant text-xs md:text-sm mt-0.5">DSAR and Erasure requests over time</p>
            </div>
            <select onchange="window.location.href='index.php?page=dashboard&period=' + this.value" class="bg-surface-container-low border-none rounded-lg text-xs md:text-sm font-label-md px-3 py-1.5 focus:ring-2 focus:ring-primary self-start sm:self-auto cursor-pointer">
                <option value="30d" <?= ($period_param === '30d') ? 'selected' : '' ?>>Last 30 days</option>
                <option value="3m" <?= ($period_param === '3m') ? 'selected' : '' ?>>Last 3 months</option>
            </select>
        </div>
        <div class="h-56 md:h-64 w-full relative">
            <svg class="w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 40">
                <defs>
                    <linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="5%" stop-color="#0078D4" stop-opacity="0.15"></stop>
                        <stop offset="95%" stop-color="#0078D4" stop-opacity="0"></stop>
                    </linearGradient>
                </defs>
                <line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="10" y2="10"></line>
                <line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="20" y2="20"></line>
                <line stroke="#EDEBE9" stroke-width="0.2" x1="0" x2="100" y1="30" y2="30"></line>
                <path d="<?= $path_d ?>" fill="url(#chartGradient)"></path>
                <path d="<?= $line_d ?>" fill="none" stroke="#0078D4" stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8"></path>
                <?php foreach ($coords as $pt): ?>
                    <circle cx="<?= $pt['x'] ?>" cy="<?= $pt['y'] ?>" fill="#0078D4" r="1.2"></circle>
                <?php endforeach; ?>
            </svg>
            <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-[10px] text-outline opacity-50">
                <span><?= $max_count ?></span>
                <span><?= round($max_count * 0.75) ?></span>
                <span><?= round($max_count * 0.5) ?></span>
                <span><?= round($max_count * 0.25) ?></span>
            </div>
        </div>
        <div class="flex justify-between mt-3 px-1 text-xs text-outline font-medium">
            <?php foreach ($chart_labels as $lbl): ?>
                <span><?= htmlspecialchars($lbl) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- START NEW CODE - Section 4: Recent Activities -->
<section class="mt-4 md:mt-6">
    <div class="bg-surface-container-lowest rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] p-5 md:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-title-md text-on-surface text-base md:text-lg font-semibold">Recent Activities</h3>
                <p class="font-caption text-outline text-xs mt-0.5">Latest system audit logs</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 bg-surface-container-low text-primary text-xs font-semibold rounded-full">
                Latest 5
            </span>
        </div>

        <?php if (!empty($recent_activities)): ?>
            <div class="divide-y divide-[#EDEBE9]">
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-surface-container-low/50 px-2 rounded-lg transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                                <span class="material-symbols-outlined text-base">history</span>
                            </div>
                            <div>
                                <div class="font-label-md text-xs md:text-sm font-semibold text-on-surface">
                                    <?= htmlspecialchars($activity['module'] ?? 'System') ?>
                                </div>
                                <div class="font-body-sm text-xs text-outline">
                                    <?= htmlspecialchars($activity['action'] ?? 'Performed Action') ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-left sm:text-right text-xs text-outline font-medium flex items-center sm:block gap-2">
                            <span><?= !empty($activity['created_at']) ? date("d M Y", strtotime($activity['created_at'])) : '' ?></span>
                            <span class="text-outline-variant sm:hidden">•</span>
                            <span class="text-on-surface-variant font-normal"><?= !empty($activity['created_at']) ? date("H:i", strtotime($activity['created_at'])) : '' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-6 text-center text-outline">
                <span class="material-symbols-outlined text-3xl mb-1 text-outline/50">history_toggle_off</span>
                <p class="text-xs font-medium">No recent activities found.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<!-- END NEW CODE - Section 4: Recent Activities -->

<!-- Section 5: Quick Actions with Functional Routing -->
<section class="mt-6 md:mt-8 pb-8">
    <h3 class="font-title-md text-on-surface text-base md:text-lg font-semibold mb-3 md:mb-4">Quick Actions</h3>
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        
        <!-- 1. New Assessment -->
        <a href="index.php?page=assessments" class="flex items-center justify-center gap-2 bg-primary text-on-primary px-4 py-3 rounded-xl shadow-sm hover:brightness-95 transition-all active:scale-95 text-center">
            <span class="material-symbols-outlined text-lg md:text-xl" data-icon="assignment_add">assignment_add</span>
            <span class="font-label-md text-xs md:text-sm font-semibold whitespace-nowrap">New Assessment</span>
        </a>

        <!-- 2. Add Vendor -->
        <a href="index.php?page=vendor-risk" class="flex items-center justify-center gap-2 bg-surface-container-lowest border border-[#D2D0CE] text-primary px-4 py-3 rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95 text-center">
            <span class="material-symbols-outlined text-lg md:text-xl" data-icon="person_add">person_add</span>
            <span class="font-label-md text-xs md:text-sm font-semibold whitespace-nowrap">Add Vendor</span>
        </a>

        <!-- 3. Report Incident -->
        <a href="index.php?page=incident-management" class="flex items-center justify-center gap-2 bg-surface-container-lowest border border-[#D2D0CE] text-error px-4 py-3 rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95 text-center">
            <span class="material-symbols-outlined text-lg md:text-xl" data-icon="report_gmailerrorred">report_gmailerrorred</span>
            <span class="font-label-md text-xs md:text-sm font-semibold whitespace-nowrap">Report Incident</span>
        </a>

        <!-- 4. Export Report -->
        <a href="index.php?page=reports" class="flex items-center justify-center gap-2 bg-surface-container-lowest border border-[#D2D0CE] text-primary px-4 py-3 rounded-xl shadow-sm hover:bg-surface-container-low transition-all active:scale-95 text-center">
            <span class="material-symbols-outlined text-lg md:text-xl" data-icon="export_notes">export_notes</span>
            <span class="font-label-md text-xs md:text-sm font-semibold whitespace-nowrap">Export Report</span>
        </a>

    </div>
</section>