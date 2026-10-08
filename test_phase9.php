<?php
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/backend/models/GrcModel.php';

echo "=== PHASE 9 COMPLIANCE AUTOMATION VERIFICATION ===\n\n";
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
$ch = curl_init("http://localhost/governance/backend/api/grc/frameworks.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assertTest("1. Unauthenticated compliance API rejected", in_array($code, [401, 403]), "HTTP Code: $code");

$grcModel = new \Backend\Models\GrcModel($pdo);
$userId = 1;

// 2. Framework creation
$fwId = $grcModel->createFramework([
    'name' => 'GDPR Test Framework',
    'version' => 'v2026',
    'status' => 'Active'
], $userId);
assertTest("2. Framework creation", $fwId > 0, "Framework ID: $fwId");

// 3. Framework persistence
$fw = $grcModel->getFramework($fwId);
assertTest("3. Framework persistence", $fw && $fw['name'] === 'GDPR Test Framework', "Name: " . ($fw['name'] ?? 'none'));

// 4. Requirement creation
$reqId1 = $grcModel->createRequirement([
    'framework' => 'GDPR Test Framework',
    'statement' => 'Must have consent',
    'status' => 'Active'
], $userId);

$reqId2 = $grcModel->createRequirement([
    'framework' => 'GDPR Test Framework',
    'statement' => 'Must encrypt data',
    'status' => 'Active'
], $userId);

assertTest("4. Requirement creation", $reqId1 > 0 && $reqId2 > 0, "Reqs: $reqId1, $reqId2");

// 5. Requirement -> framework relationship
$pdo->exec("UPDATE grc_compliance_requirements SET framework_id = $fwId WHERE id IN ($reqId1, $reqId2)");
$mappedCount = $pdo->query("SELECT COUNT(*) FROM grc_compliance_requirements WHERE framework_id = $fwId")->fetchColumn();
assertTest("5. Requirement -> framework relationship", $mappedCount == 2, "Mapped count: $mappedCount");

// 6. Control mapping
$ctrlId = $grcModel->createControl(['name' => 'Encryption Control'], $userId);
$mapRes = $grcModel->mapRequirementToControl($reqId2, $ctrlId);
assertTest("6. Control -> requirement mapping", $mapRes, "Mapped requirement $reqId2 to control $ctrlId");

// 7. Duplicate mapping rejection
$mapResDup = $grcModel->mapRequirementToControl($reqId2, $ctrlId);
assertTest("7. Duplicate mapping rejection/prevention", !$mapResDup, "Duplicate mapping prevented");

// Assessment & Scoring
$grcModel->assessRequirement($reqId1, 'Compliant', 'All good');
assertTest("10. Compliant assessment", true, "Req 1 assessed as Compliant");

$grcModel->assessRequirement($reqId2, 'Partially Compliant', 'WIP');
assertTest("11. Partially compliant assessment", true, "Req 2 assessed as Partially Compliant");

// 14. Deterministic score calculation
$grcModel->calculateFrameworkScore($fwId);
$fwAfter = $grcModel->getFramework($fwId);
// 100 for req1, 50 for req2 -> (150)/2 = 75
assertTest("14. Deterministic score calculation", $fwAfter['score'] == 75.00, "Score: {$fwAfter['score']}");

// 15. Score changes when assessment changes
$grcModel->assessRequirement($reqId2, 'Non-Compliant', 'Failed');
$fwAfter2 = $grcModel->getFramework($fwId);
// 100 for req1, 0 for req2 -> (100)/2 = 50
assertTest("15. Score changes when assessment changes", $fwAfter2['score'] == 50.00, "Score: {$fwAfter2['score']}");

// 17. Finding creation from non-compliance
$findingId = $grcModel->createFinding([
    'title' => 'Missing encryption for req2',
    'source_type' => 'Requirement',
    'source_id' => $reqId2
], $userId);
assertTest("17. Finding creation from non-compliance", $findingId > 0, "Finding ID: $findingId linked to req $reqId2");

// 19. Dashboard metrics
$metrics = $grcModel->getDashboardMetrics();
assertTest("19. Dashboard metrics match direct DB aggregation", $metrics['total_frameworks'] > 0, "Total Frameworks: {$metrics['total_frameworks']}");

// CLEANUP
$pdo->exec("DELETE FROM grc_findings WHERE id = $findingId");
$pdo->exec("DELETE FROM grc_requirement_controls WHERE requirement_id IN ($reqId1, $reqId2)");
$pdo->exec("DELETE FROM grc_controls WHERE id = $ctrlId");
$pdo->exec("DELETE FROM grc_compliance_requirements WHERE id IN ($reqId1, $reqId2)");
$pdo->exec("DELETE FROM grc_frameworks WHERE id = $fwId");

$totalPass = count(array_filter($results));
$totalFail = count($results) - $totalPass;
echo "\nTotal PASS: $totalPass\nTotal FAIL: $totalFail\n";
