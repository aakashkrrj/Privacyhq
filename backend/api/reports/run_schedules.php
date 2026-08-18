<?php
// governance/backend/api/reports/run_schedules.php
require_once __DIR__ . '/bootstrap.php';
$reportController = $controller;

require_once __DIR__ . '/../audit-logs/bootstrap.php';
$auditServiceInstance = $service;

// 1. Run due report schedules
$reportController->runDueSchedules();

// 2. Check and run automated audit log retention purge if due
try {
    $settings = $auditServiceInstance->getRetentionSettings();

    if (!empty($settings['auto_purge_enabled'])) {
        $lastPurge = $settings['last_purge_at'] ? strtotime($settings['last_purge_at']) : 0;
        // Run auto purge if not executed in past 24 hours
        if ((time() - $lastPurge) >= 86400) {
            $purgeRes = $auditServiceInstance->purgeOldLogs($settings['retention_days'], 1);
            if (php_sapi_name() === 'cli') {
                echo json_encode(['status' => 'auto_purge_success', 'data' => $purgeRes]) . "\n";
            }
        }
    }
} catch (\Throwable $t) {
    if (php_sapi_name() === 'cli') {
        echo json_encode(['status' => 'auto_purge_notice', 'message' => $t->getMessage()]) . "\n";
    }
}
