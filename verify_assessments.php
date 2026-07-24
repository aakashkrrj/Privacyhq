<?php
$_SESSION['user_id'] = 1;

require_once 'config/database.php';
require_once 'backend/services/AssessmentService.php';

$svc = new AssessmentService();
$db = Database::getInstance()->getConnection();

echo "--- PRIVACY ASSESSMENTS MODULE VERIFICATION ---\n\n";

// 1. Create Assessment
echo "1. Creating Assessment...\n";
$reqId = $svc->createAssessment([
    'processing_activity_id' => 1,
    'template_id' => 1,
    'status_id' => 1,
    'title' => 'Test Assessment Verification',
    'assigned_to' => 1,
    'due_date' => date('Y-m-d', strtotime('+15 days')),
], 1);
echo "Created ID: $reqId\n";

// 2. Fetch Assessment
echo "2. Verifying Creation State...\n";
$req = $svc->getAssessmentById($reqId);
echo "Title: {$req['title']}\n";
echo "Owner: {$req['owner_name']}\n";

$history = $svc->getAssessmentHistory($reqId);
echo "History Count: " . count($history) . "\n";

// 3. Update Assessment
echo "3. Updating Assessment...\n";
$svc->updateAssessment($reqId, [
    'status_id' => 2,
    'title' => 'Updated Title Verification'
], 1);

$reqUpdated = $svc->getAssessmentById($reqId);
echo "Updated Title: {$reqUpdated['title']}\n";
$history2 = $svc->getAssessmentHistory($reqId);
echo "History Count after update: " . count($history2) . "\n";

// 4. Test Filters & Pagination
echo "4. Testing List/Filters...\n";
$listFiltered = $svc->getAssessments(['status_id' => 2]);
echo "Total with status filter (2): " . count($listFiltered) . "\n";

$listSearch = $svc->getAssessments(['keyword' => 'Updated Title Verification']);
echo "Total with search filter: " . count($listSearch) . "\n";
if (count($listSearch) > 0) {
    echo "Progress Percentage calculated: {$listSearch[0]['progress_percentage']}%\n";
}

// 5. Test Transaction Rollback
echo "5. Testing Transaction Rollback...\n";
try {
    $svc->createAssessment([
        'processing_activity_id' => 99999, // Invalid foreign key
        'template_id' => 1,
        'status_id' => 1,
        'title' => 'Fail Test'
    ], 1);
    echo "FAIL: Should have thrown FK exception.\n";
} catch (Exception $e) {
    echo "SUCCESS: Caught exception -> " . $e->getMessage() . "\n";
}

// 6. Test Dashboard Stats
echo "6. Testing Dashboard Stats...\n";
$stats = $svc->getDashboardStats();
echo "Pending Reviews: {$stats['pending_reviews']}\n";
echo "Compliance %: {$stats['compliance_percentage']}\n";

// Cleanup using raw SQL since hard delete isn't exposed (soft deletes used)
echo "7. Cleaning Up...\n";
$db->query("DELETE FROM assessment_status_history WHERE assessment_id = $reqId");
$db->query("DELETE FROM privacy_assessments WHERE id = $reqId");
$db->query("DELETE FROM audit_logs WHERE record_id = $reqId AND module = 'PrivacyAssessments'");

echo "\n--- ALL TESTS COMPLETED ---\n";
