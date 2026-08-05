<?php
// backend/api/reports/ropa.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/ReportSummary.php';
require_once __DIR__ . '/../../../backend/services/ReportService.php';
require_once __DIR__ . '/../../../backend/controllers/ReportController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\ReportSummary($pdo);
$service = new \Backend\Services\ReportService($pdo, $model);
$controller = new \Backend\Controllers\ReportController($service);

$data = $controller->ropa();
$kpis = $data['kpis'];
$activities = $data['activities'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article 30 ROPA Compliance Report</title>
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
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow border border-gray-100">
        <!-- Header -->
        <div class="flex justify-between items-start border-b pb-6 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Records of Processing Activities (ROPA) Report</h1>
                <p class="text-sm text-gray-500 mt-1">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p class="text-sm text-gray-500">Prepared for: GDPR Article 30 Compliance Audits</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition flex items-center gap-1">
                    Print / Save PDF
                </button>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                <span class="text-xs text-blue-800 uppercase font-bold tracking-wider">Total Activities</span>
                <h3 class="text-2xl font-bold text-blue-900 mt-1"><?php echo $kpis['total_activities']; ?></h3>
            </div>
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                <span class="text-xs text-green-800 uppercase font-bold tracking-wider">Active Operations</span>
                <h3 class="text-2xl font-bold text-green-900 mt-1"><?php echo $kpis['active_activities']; ?></h3>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <span class="text-xs text-gray-800 uppercase font-bold tracking-wider">Inactive Operations</span>
                <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo $kpis['inactive_activities']; ?></h3>
            </div>
        </div>

        <!-- Detail Table -->
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Processing Activities Catalog</h2>
            <table class="w-full text-xs text-left text-gray-600 border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 border-b text-gray-700 uppercase">
                    <tr>
                        <th class="p-3">Activity & Purpose</th>
                        <th class="p-3">Controller & Dept</th>
                        <th class="p-3">Categories & Subjects</th>
                        <th class="p-3">Recipients & Retention</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): 
                        $statusClass = $act['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700';
                    ?>
                        <tr class="border-b hover:bg-gray-50/50">
                            <td class="p-3 font-medium text-gray-900">
                                <div><?php echo htmlspecialchars($act['activity_name']); ?></div>
                                <div class="text-[10px] text-gray-400 mt-0.5"><?php echo htmlspecialchars($act['purpose']); ?></div>
                            </td>
                            <td class="p-3">
                                <div><?php echo htmlspecialchars($act['data_controller'] ?: 'N/A'); ?></div>
                                <div class="text-[10px] text-gray-400 mt-0.5">Dept: <?php echo htmlspecialchars($act['department'] ?: 'N/A'); ?></div>
                            </td>
                            <td class="p-3">
                                <div>Cats: <?php echo htmlspecialchars($act['data_categories'] ?: 'N/A'); ?></div>
                                <div class="text-[10px] text-gray-400 mt-0.5">Subjects: <?php echo htmlspecialchars($act['data_subjects'] ?: 'N/A'); ?></div>
                            </td>
                            <td class="p-3">
                                <div>Recipients: <?php echo htmlspecialchars($act['recipients'] ?: 'N/A'); ?></div>
                                <div class="text-[10px] text-gray-400 mt-0.5">Retention: <?php echo htmlspecialchars($act['retention_period'] ?: 'N/A'); ?></div>
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 text-[10px] rounded-full font-medium <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($act['status']); ?>
                                </span>
                            </td>
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
