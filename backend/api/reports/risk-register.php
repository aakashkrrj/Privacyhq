<?php
// backend/api/reports/risk-register.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/ReportSummary.php';
require_once __DIR__ . '/../../../backend/services/ReportService.php';
require_once __DIR__ . '/../../../backend/controllers/ReportController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\ReportSummary($pdo);
$service = new \Backend\Services\ReportService($pdo, $model);
$controller = new \Backend\Controllers\ReportController($service);

$data = $controller->riskRegister();
$kpis = $data['kpis'];
$risks = $data['risks'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy & Compliance Risk Assessment Report</title>
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
                <h1 class="text-2xl font-bold text-gray-900">Privacy & Compliance Risk Assessment Report</h1>
                <p class="text-sm text-gray-500 mt-1">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p class="text-sm text-gray-500">Prepared by: DPO Officer (Admin User)</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition flex items-center gap-1">
                    Print / Save PDF
                </button>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                <span class="text-xs text-blue-800 uppercase font-bold tracking-wider">Total Risks</span>
                <h3 class="text-2xl font-bold text-blue-900 mt-1"><?php echo $kpis['total_risks']; ?></h3>
            </div>
            <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                <span class="text-xs text-red-800 uppercase font-bold tracking-wider">High Risk Items</span>
                <h3 class="text-2xl font-bold text-red-900 mt-1"><?php echo $kpis['high_risks']; ?></h3>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                <span class="text-xs text-yellow-800 uppercase font-bold tracking-wider">In Review</span>
                <h3 class="text-2xl font-bold text-yellow-900 mt-1"><?php echo $kpis['needs_action']; ?></h3>
            </div>
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                <span class="text-xs text-green-800 uppercase font-bold tracking-wider">Mitigated</span>
                <h3 class="text-2xl font-bold text-green-900 mt-1"><?php echo $kpis['mitigated_risks']; ?></h3>
            </div>
        </div>

        <!-- Detail Table -->
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Risk Inventory Registry</h2>
            <table class="w-full text-sm text-left text-gray-600 border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 border-b text-xs text-gray-700 uppercase">
                    <tr>
                        <th class="p-4">Risk Title / Description</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Rating</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risks as $r): 
                        $riskClass = 'bg-green-100 text-green-700';
                        if ($r['risk_level'] === 'High') {
                            $riskClass = 'bg-red-100 text-red-700';
                        } elseif ($r['risk_level'] === 'Medium') {
                            $riskClass = 'bg-yellow-100 text-yellow-700';
                        }
                        
                        $statusClass = 'bg-red-100 text-red-700';
                        if ($r['status'] === 'mitigated') {
                            $statusClass = 'bg-green-100 text-green-700';
                        } elseif ($r['status'] === 'in review') {
                            $statusClass = 'bg-indigo-100 text-indigo-700';
                        }
                    ?>
                        <tr class="border-b hover:bg-gray-50/50">
                            <td class="p-4 font-medium text-gray-900">
                                <div><?php echo htmlspecialchars($r['title']); ?></div>
                                <div class="text-xs text-gray-400 mt-1">Mitigation: <?php echo htmlspecialchars($r['mitigation'] ?? 'None'); ?></div>
                            </td>
                            <td class="p-4"><?php echo htmlspecialchars($r['category']); ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium <?php echo $riskClass; ?>">
                                    <?php echo htmlspecialchars($r['risk_level']); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($r['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-8 border-t flex justify-between items-center text-xs text-gray-400">
            <p>PrivacyHQ Governance System</p>
            <p>&copy; <?php echo date('Y'); ?> PrivacyHQ. Confidential.</p>
        </div>
    </div>
</body>
</html>
