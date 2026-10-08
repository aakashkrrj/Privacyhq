<?php
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/backend/models/DataAsset.php';

echo "=== PHASE 10 DSPM / DATA DISCOVERY VERIFICATION ===\n\n";
$results = [];

function assertTest($name, $condition, $evidence = '') {
    global $results;
    if ($condition) {
        echo "[PASS] $name\nEvidence: $evidence\n\n";
        $results[] = true;
    } else {
        echo "[FAIL] $name\nEvidence: $evidence\n\n";
        $results[] = false;
    }
}

// 1. Unauth check
$ch = curl_init("http://localhost/governance/backend/api/dspm/assets.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assertTest("1. Unauthenticated DSPM API rejected", in_array($code, [401, 403]), "HTTP Code: $code");

$assetModel = new \Backend\Models\DataAsset($pdo);
$userId = 1;

// Prepare data: create a source
$pdo->exec("INSERT INTO discovery_sources (name, source_type, connection_uri, environment, risk_level, status) VALUES ('Test MySQL DB', 'MySQL', 'mysql://...', 'Production', 'Low', 'active')");
$sourceId = $pdo->lastInsertId();
assertTest("2. Data source creation", $sourceId > 0, "Source ID: $sourceId");

// 3. Asset Creation
$assetId = $assetModel->createAsset([
    'asset_name' => 'HR Employee Database',
    'asset_type' => 'Relational Database',
    'owner' => 'HR Admin',
    'source_id' => $sourceId,
    'classification' => 'Restricted',
    'criticality' => 'Critical',
    'sensitivity_indicators' => ['Personal', 'Financial']
], $userId);
assertTest("3. Data asset creation", $assetId > 0, "Asset ID: $assetId");

$asset = $assetModel->getAsset($assetId);
assertTest("4. Asset persistence", $asset['asset_name'] === 'HR Employee Database', "Name: {$asset['asset_name']}");
assertTest("6. Asset classification", $asset['classification'] === 'Restricted', "Class: {$asset['classification']}");
assertTest("7. Asset owner", $asset['owner'] === 'HR Admin', "Owner: {$asset['owner']}");
assertTest("8. Asset -> source relationship", $asset['source_id'] == $sourceId, "Source ID matches");

// 5. Update
$assetModel->updateAsset($assetId, array_merge($asset, ['classification' => 'Confidential']));
$updated = $assetModel->getAsset($assetId);
assertTest("5. Asset update", $updated['classification'] === 'Confidential', "Class updated");

// 9. Asset -> Category mapping
$assetModel->linkCategory($assetId, 'Health Data');
$catCount = $pdo->query("SELECT COUNT(*) FROM data_asset_categories WHERE asset_id = $assetId")->fetchColumn();
assertTest("9. Asset -> personal-data category relationship", $catCount == 1, "Category linked");

// 10. Asset -> RoPA mapping
$pdo->exec("INSERT INTO processing_activities (activity_name, purpose) VALUES ('Payroll Processing', 'Pay employees')");
$ropaId = $pdo->lastInsertId();
$assetModel->linkRopa($assetId, $ropaId);
$ropaCount = $pdo->query("SELECT COUNT(*) FROM data_asset_ropa WHERE asset_id = $assetId")->fetchColumn();
assertTest("10. Asset -> RoPA relationship", $ropaCount == 1, "RoPA linked");

// 11. Asset -> Risk mapping
$pdo->exec("INSERT INTO assessment_risks (description, risk_category_id, inherent_level, residual_level) VALUES ('Unauthorized access to HR', 1, 'High', 'Critical')");
$riskId = $pdo->lastInsertId();
$assetModel->linkRisk($assetId, $riskId);
$riskCount = $pdo->query("SELECT COUNT(*) FROM data_asset_risks WHERE asset_id = $assetId")->fetchColumn();
assertTest("11. Asset -> risk relationship", $riskCount == 1, "Risk linked");

// 12. Duplicate relationship
$assetModel->linkCategory($assetId, 'Health Data');
$catCount2 = $pdo->query("SELECT COUNT(*) FROM data_asset_categories WHERE asset_id = $assetId")->fetchColumn();
assertTest("12. Duplicate relationship prevention", $catCount2 == 1, "Count remained 1");

// 13. JSON import
$importData = [
    [
        'asset_name' => 'CRM Tool',
        'classification' => 'Restricted',
        'source_name' => 'Test MySQL DB'
    ],
    [
        'asset_name' => '', // invalid
    ],
    [
        'asset_name' => 'HR Employee Database', // duplicate
    ]
];
$importRes = $assetModel->importAssets($importData, $userId);
assertTest("13. JSON import successful for valid", $importRes['imported'] == 1, "Imported: {$importRes['imported']}");
assertTest("14. Invalid import validation", $importRes['failed'] == 2, "Failed: {$importRes['failed']}");

// 15. Dashboard metrics
$metrics = $assetModel->getDashboardMetrics();
assertTest("15. Dashboard metrics match direct DB aggregation", $metrics['total_assets'] >= 2 && $metrics['assets_with_high_risks'] >= 1, "Total: {$metrics['total_assets']}, High Risk Linked: {$metrics['assets_with_high_risks']}");

// CLEANUP
$pdo->exec("DELETE FROM data_asset_categories");
$pdo->exec("DELETE FROM data_asset_ropa");
$pdo->exec("DELETE FROM data_asset_risks");
$pdo->exec("DELETE FROM data_assets WHERE asset_name IN ('HR Employee Database', 'CRM Tool')");
$pdo->exec("DELETE FROM processing_activities WHERE id = $ropaId");
$pdo->exec("DELETE FROM assessment_risks WHERE id = $riskId");
$pdo->exec("DELETE FROM discovery_sources WHERE id = $sourceId");

$totalPass = count(array_filter($results));
$totalFail = count($results) - $totalPass;
echo "\nTotal PASS: $totalPass\nTotal FAIL: $totalFail\n";
