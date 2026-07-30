<?php
require_once __DIR__ . '/../../../backend/config/db.php';
require_once __DIR__ . '/../../../backend/models/Consent.php';

// Initialize Model
$consentModel = new \Backend\Models\Consent($pdo);
$metrics = $consentModel->getDashboardMetrics();

// Fetch latest 20 consent records for the report detail
$query = "SELECT c.id, ds.identifier_hash AS user_identifier, p.purpose_name AS category, c.status AS db_status, c.created_at AS captured_at 
          FROM consents c 
          JOIN data_subjects ds ON c.data_subject_id = ds.id 
          JOIN consent_purposes p ON c.consent_purpose_id = p.id 
          ORDER BY c.created_at DESC 
          LIMIT 20";
$stmt = $pdo->query($query);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Category breakdown counts
$catQuery = "SELECT p.purpose_name AS category, COUNT(*) as count 
             FROM consents c 
             JOIN consent_purposes p ON c.consent_purpose_id = p.id 
             GROUP BY p.purpose_name";
$catStmt = $pdo->query($catQuery);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consent Compliance Summary Report</title>
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
        <!-- Report Header -->
        <div class="flex justify-between items-start border-b pb-6 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Consent Compliance Report</h1>
                <p class="text-sm text-gray-500 mt-1">Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                <p class="text-sm text-gray-500">Prepared by: DPO Officer (Admin User)</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition flex items-center gap-1">
                    Print / Save PDF
                </button>
            </div>
        </div>

        <!-- Compliance KPI Metrics -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                <span class="text-xs text-blue-800 uppercase font-bold tracking-wider">Total Consents</span>
                <h3 class="text-2xl font-bold text-blue-900 mt-1"><?php echo $metrics['total']; ?></h3>
            </div>
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                <span class="text-xs text-green-800 uppercase font-bold tracking-wider">Active (Granted)</span>
                <h3 class="text-2xl font-bold text-green-900 mt-1"><?php echo $metrics['active_consents']; ?></h3>
            </div>
            <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                <span class="text-xs text-red-800 uppercase font-bold tracking-wider">Revoked</span>
                <h3 class="text-2xl font-bold text-red-900 mt-1"><?php echo $metrics['revoked_consents']; ?></h3>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                <span class="text-xs text-yellow-800 uppercase font-bold tracking-wider">Compliance Rate</span>
                <h3 class="text-2xl font-bold text-yellow-900 mt-1"><?php echo $metrics['opt_in_rate']; ?></h3>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Consent Category breakdown</h2>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($categories as $cat): ?>
                    <div class="p-3 bg-gray-50 border rounded-lg flex justify-between items-center">
                        <span class="font-medium text-gray-700"><?php echo htmlspecialchars($cat['category']); ?></span>
                        <span class="text-sm font-bold bg-white px-3 py-1 rounded shadow-sm border"><?php echo $cat['count']; ?> logs</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Consent Logs Section -->
        <div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Latest Consent Activity (Up to 20 logs)</h2>
            <table class="w-full text-sm text-left text-gray-600 border rounded-lg overflow-hidden">
                <thead class="bg-gray-50 border-b text-xs text-gray-700 uppercase">
                    <tr>
                        <th class="p-4">User Identifier</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Captured At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): 
                        $statusLabel = 'Granted';
                        $statusClass = 'bg-green-100 text-green-700';
                        if ($log['db_status'] === 'withdrawn') {
                            $statusLabel = 'Revoked';
                            $statusClass = 'bg-red-100 text-red-700';
                        } elseif ($log['db_status'] === 'opt_out') {
                            $statusLabel = 'Pending';
                            $statusClass = 'bg-yellow-100 text-yellow-700';
                        }
                    ?>
                        <tr class="border-b hover:bg-gray-50/50">
                            <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($log['user_identifier']); ?></td>
                            <td class="p-4"><?php echo htmlspecialchars($log['category']); ?></td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs rounded-full font-medium <?php echo $statusClass; ?>">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>
                            <td class="p-4 text-xs text-gray-500"><?php echo htmlspecialchars($log['captured_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer Signoff -->
        <div class="mt-12 pt-8 border-t flex justify-between items-center text-xs text-gray-400">
            <p>PrivacyHQ Governance System</p>
            <p>&copy; <?php echo date('Y'); ?> PrivacyHQ. Confidential.</p>
        </div>
    </div>
</body>
</html>
