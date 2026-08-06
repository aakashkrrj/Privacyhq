<?php
// backend/api/reports/incident.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/ReportSummary.php';
require_once __DIR__ . '/../../../backend/services/ReportService.php';
require_once __DIR__ . '/../../../backend/controllers/ReportController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\ReportSummary($pdo);
$service = new \Backend\Services\ReportService($pdo, $model);
$controller = new \Backend\Controllers\ReportController($service);

$data = $controller->incident();
$kpis = $data['kpis'];
$sev = $data['severity_distribution'];
$incidents = $data['incidents'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Incident Compliance Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none; }
            body { background: white; color: black; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 p-8 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow border border-gray-100">
        <!-- Header -->
        <div class="flex justify-between items-start border-b pb-6 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Privacy Incident & SLA Compliance Report</h1>
                <p class="text-sm text-gray-500 mt-1">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p class="text-sm text-gray-500">Security Operation Center (SOC) Audit</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition flex items-center gap-1">
                    Print / Save PDF
                </button>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                <span class="text-xs text-indigo-800 uppercase font-bold tracking-wider">Total logged</span>
                <h3 class="text-2xl font-bold text-indigo-900 mt-1"><?php echo $kpis['total']; ?></h3>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                <span class="text-xs text-yellow-800 uppercase font-bold tracking-wider">Investigating</span>
                <h3 class="text-2xl font-bold text-yellow-900 mt-1"><?php echo $kpis['investigating']; ?></h3>
            </div>
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                <span class="text-xs text-green-800 uppercase font-bold tracking-wider">Resolved</span>
                <h3 class="text-2xl font-bold text-green-900 mt-1"><?php echo $kpis['resolved']; ?></h3>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                <span class="text-xs text-blue-800 uppercase font-bold tracking-wider">Avg Resolution</span>
                <h3 class="text-2xl font-bold text-blue-900 mt-1"><?php echo $kpis['avg_resolution']; ?></h3>
            </div>
        </div>

        <!-- Details & Performance -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="p-5 bg-gray-50 rounded-xl border">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Escalations & Alerts Summary</h3>
                <ul class="space-y-2 text-xs">
                    <li class="flex justify-between">
                        <span class="text-gray-500">High / Critical Incidents:</span>
                        <strong class="text-red-600"><?php echo $kpis['high_critical']; ?></strong>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-gray-500">Escalated to Management:</span>
                        <strong class="text-indigo-600"><?php echo $kpis['escalated']; ?></strong>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-gray-500">DPO Notified:</span>
                        <strong class="text-green-600"><?php echo $kpis['dpo_notified']; ?></strong>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-gray-500">Authorities Notified:</span>
                        <strong class="text-blue-600"><?php echo $kpis['regulatory_notified']; ?></strong>
                    </li>
                </ul>
            </div>
            <div class="p-5 bg-gray-50 rounded-xl border">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Severity Distribution</h3>
                <ul class="space-y-2 text-xs">
                    <li class="flex justify-between">
                        <span class="text-red-600 font-semibold">CRITICAL:</span>
                        <strong><?php echo $sev['Critical']; ?></strong>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-orange-500 font-semibold">HIGH:</span>
                        <strong><?php echo $sev['High']; ?></strong>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-yellow-600 font-semibold">MEDIUM:</span>
                        <strong><?php echo $sev['Medium']; ?></strong>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-green-600 font-semibold">LOW:</span>
                        <strong><?php echo $sev['Low']; ?></strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Detail Table -->
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Incident Catalog Details</h2>
            <table class="w-full text-xs text-left text-gray-600 border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 border-b text-gray-700 uppercase">
                    <tr>
                        <th class="p-3">Summary</th>
                        <th class="p-3">Severity</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Escalated</th>
                        <th class="p-3">DPO Notified</th>
                        <th class="p-3">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidents as $inc): 
                        $statusClass = $inc['status'] === 'Resolved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                        $sevClass = $inc['severity'] === 'Critical' ? 'text-red-600 font-bold' : ($inc['severity'] === 'High' ? 'text-orange-500' : 'text-gray-700');
                    ?>
                        <tr class="border-b hover:bg-gray-50/50">
                            <td class="p-3 font-medium text-gray-900">
                                <div><?php echo htmlspecialchars($inc['summary']); ?></div>
                                <?php if ($inc['containment_actions']): ?>
                                    <div class="text-[10px] text-green-600 mt-1">Containment: <?php echo htmlspecialchars($inc['containment_actions']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 <?php echo $sevClass; ?>"><?php echo htmlspecialchars($inc['severity']); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-full font-medium <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($inc['status']); ?>
                                </span>
                            </td>
                            <td class="p-3"><?php echo $inc['is_escalated'] ? 'YES' : 'NO'; ?></td>
                            <td class="p-3"><?php echo $inc['dpo_notified'] ? 'YES' : 'NO'; ?></td>
                            <td class="p-3 text-gray-500"><?php echo htmlspecialchars($inc['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-8 border-t flex justify-between items-center text-xs text-gray-400">
            <p>PrivacyHQ Compliance Framework</p>
            <p>&copy; <?php echo date('Y'); ?> PrivacyHQ. Confidential.</p>
        </div>
    </div>
</body>
</html>
