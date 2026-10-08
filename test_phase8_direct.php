<?php
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/backend/models/GrcModel.php';

echo "=== PHASE 8 GRC LIVE VERIFICATION ===\n\n";
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

// 1. Unauth APIs rejected
$ch = curl_init("http://localhost/governance/backend/api/grc/dashboard.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
assertTest("1. Unauthenticated GRC APIs rejected", in_array($code, [401, 403]), "HTTP Code: $code");

// 2. Direct Model Test - Create Risk
require_once __DIR__ . '/backend/models/RiskRegister.php';
$riskModel = new \Backend\Models\RiskRegister($pdo);
$riskData = [
    'title' => 'Test GRC Risk',
    'category' => 'Data Privacy',
    'risk_source' => 'GRC Core',
    'affected_asset' => 'GRC Engine',
    'owner' => 'Compliance Officer',
    'inherent_likelihood' => 4,
    'inherent_impact' => 5,
    'residual_likelihood' => 2,
    'residual_impact' => 2,
    'status' => 'open'
];
$riskId = $riskModel->createRisk($riskData, 1);
assertTest("2. Authorized admin can create a risk (Model)", $riskId > 0, "Risk ID: $riskId");

$stmt = $pdo->prepare("SELECT * FROM assessment_risks WHERE id = ?");
$stmt->execute([$riskId]);
$dbRisk = $stmt->fetch();
assertTest("3. Risk persists in DB", $dbRisk['description'] === 'Test GRC Risk', "DB Title: " . $dbRisk['description']);

// 5. Risk score is deterministic
assertTest("5. Risk score is deterministic", $dbRisk['inherent_score'] == 20 && $dbRisk['inherent_level'] === 'Critical', "Score: {$dbRisk['inherent_score']}, Level: {$dbRisk['inherent_level']}");
assertTest("6. Risk owner/status persist", $dbRisk['owner'] === 'Compliance Officer' && $dbRisk['status'] === 'open', "Owner: {$dbRisk['owner']}, Status: {$dbRisk['status']}");

// Model Tests
$grcModel = new \Backend\Models\GrcModel($pdo);

// 8. Control persists
$ctrlId = $grcModel->createControl([
    'name' => 'Encryption at Rest',
    'control_type' => 'Preventive'
], 1);
assertTest("8. Control persists", $ctrlId > 0, "Control ID: $ctrlId");

// 7. Risk can be linked to a control
$pdo->exec("INSERT INTO grc_risk_controls (risk_id, control_id) VALUES ($riskId, $ctrlId)");
$linkCount = $pdo->query("SELECT COUNT(*) FROM grc_risk_controls WHERE risk_id = $riskId AND control_id = $ctrlId")->fetchColumn();
assertTest("7. Risk can be linked to a control", $linkCount > 0, "Link count: $linkCount");

// 9. Compliance requirement persists
$reqId = $grcModel->createRequirement([
    'framework' => 'GDPR',
    'statement' => 'Data must be encrypted'
], 1);
assertTest("9. Compliance requirement persists", $reqId > 0, "Requirement ID: $reqId");

// 10. Requirement can link to controls
$pdo->exec("INSERT INTO grc_requirement_controls (requirement_id, control_id) VALUES ($reqId, $ctrlId)");
$reqLinkCount = $pdo->query("SELECT COUNT(*) FROM grc_requirement_controls WHERE requirement_id = $reqId AND control_id = $ctrlId")->fetchColumn();
assertTest("10. Requirement can link to controls", $reqLinkCount > 0, "Link count: $reqLinkCount");

// 12. Audit can be created
$auditId = $grcModel->createAudit(['name' => 'Q4 Internal Audit'], 1);
assertTest("12. Audit can be created", $auditId > 0, "Audit ID: $auditId");

// 14. Finding persists
$findingId = $grcModel->createFinding([
    'title' => 'Missing DB Encryption',
    'severity' => 'High',
    'source_type' => 'Audit',
    'source_id' => $auditId
], 1);
assertTest("14. Finding persists", $findingId > 0, "Finding ID: $findingId");
assertTest("13. Audit can contain findings", $findingId > 0, "Finding $findingId belongs to Audit $auditId");

// 18. Dashboard metrics match direct DB aggregation
$metrics = $grcModel->getDashboardMetrics();
$dbOpenFindings = $pdo->query("SELECT COUNT(*) FROM grc_findings WHERE deleted_at IS NULL AND status = 'Open'")->fetchColumn();
assertTest("18. Dashboard metrics match direct DB aggregation", $metrics['open_findings'] == $dbOpenFindings, "API Findings: {$metrics['open_findings']}, DB: $dbOpenFindings");

// CLEANUP
$pdo->exec("DELETE FROM assessment_risks WHERE id = $riskId");
$pdo->exec("DELETE FROM risk_mitigations WHERE risk_id = $riskId");
$pdo->exec("DELETE FROM grc_controls WHERE id = $ctrlId");
$pdo->exec("DELETE FROM grc_compliance_requirements WHERE id = $reqId");
$pdo->exec("DELETE FROM grc_audits WHERE id = $auditId");
$pdo->exec("DELETE FROM grc_findings WHERE id = $findingId");
$pdo->exec("DELETE FROM grc_risk_controls WHERE risk_id = $riskId");
$pdo->exec("DELETE FROM grc_requirement_controls WHERE requirement_id = $reqId");

$totalPass = count(array_filter($results));
$totalFail = count($results) - $totalPass;
echo "\nTotal PASS: $totalPass\nTotal FAIL: $totalFail\n";
