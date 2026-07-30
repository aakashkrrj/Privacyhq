<?php
// backend/api/reports/vendor-risk.php

use Backend\Core\ApiBootstrap;
require_once __DIR__ . '/../../../backend/core/ApiBootstrap.php';
require_once __DIR__ . '/../../../backend/models/ReportSummary.php';
require_once __DIR__ . '/../../../backend/services/ReportService.php';
require_once __DIR__ . '/../../../backend/controllers/ReportController.php';

ApiBootstrap::requireMethod('GET');

$model = new \Backend\Models\ReportSummary($pdo);
$service = new \Backend\Services\ReportService($pdo, $model);
$controller = new \Backend\Controllers\ReportController($service);

$data = $controller->vendorRisk();
$kpis = $data['kpis'];
$vendors = $data['vendors'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Risk Compliance Report</title>
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
                <h1 class="text-2xl font-bold text-gray-900">Vendor Risk Compliance Report</h1>
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
                <span class="text-xs text-blue-800 uppercase font-bold tracking-wider">Total Vendors</span>
                <h3 class="text-2xl font-bold text-blue-900 mt-1"><?php echo $kpis['total']; ?></h3>
            </div>
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                <span class="text-xs text-green-800 uppercase font-bold tracking-wider">Compliant DPA</span>
                <h3 class="text-2xl font-bold text-green-900 mt-1"><?php echo $kpis['compliant_count']; ?></h3>
            </div>
            <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                <span class="text-xs text-red-800 uppercase font-bold tracking-wider">High & Critical Risk</span>
                <h3 class="text-2xl font-bold text-red-900 mt-1"><?php echo (int)$kpis['high_risk'] + (int)$kpis['critical_risk']; ?></h3>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                <span class="text-xs text-yellow-800 uppercase font-bold tracking-wider">Medium Risk</span>
                <h3 class="text-2xl font-bold text-yellow-900 mt-1"><?php echo $kpis['medium_risk']; ?></h3>
            </div>
        </div>

        <!-- Detail Table -->
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Vendor Risk Inventory Registry</h2>
            <table class="w-full text-sm text-left text-gray-600 border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 border-b text-xs text-gray-700 uppercase">
                    <tr>
                        <th class="p-4">Vendor Name</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Risk Level</th>
                        <th class="p-4">DPA Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $v): 
                        $riskClass = 'bg-green-100 text-green-700';
                        if ($v['risk_level'] === 'Critical' || $v['risk_level'] === 'High') {
                            $riskClass = 'bg-red-100 text-red-700';
                        } elseif ($v['risk_level'] === 'Medium') {
                            $riskClass = 'bg-yellow-100 text-yellow-700';
                        }
                        
                        $dpaClass = 'bg-green-100 text-green-700';
                        if ($v['dpa_status'] !== 'Compliant') {
                            $dpaClass = 'bg-yellow-100 text-yellow-700';
                        }
                    ?>
                        <tr class="border-b hover:bg-gray-50/50">
                            <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($v['vendor_name']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($v['category']); ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium <?php echo $riskClass; ?>">
                                    <?php echo htmlspecialchars($v['risk_level']); ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs rounded-full font-medium <?php echo $dpaClass; ?>">
                                    <?php echo htmlspecialchars($v['dpa_status']); ?>
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
