<?php
// governance/api/save-assessment.php
header("Content-Type: application/json");
require_once "../includes/db.php";
require_permission('manage_assessments');

require_once __DIR__ . '/../backend/models/PrivacyAssessment.php';
require_once __DIR__ . '/../backend/services/AssessmentService.php';
require_once __DIR__ . '/../backend/services/WorkflowService.php';
require_once __DIR__ . '/../backend/services/TaskService.php';
require_once __DIR__ . '/../backend/services/NotificationService.php';
require_once __DIR__ . '/../backend/services/ActivityService.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. CSRF Validation
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed.']);
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $assessor = trim($_POST['assessor'] ?? '');
    $risk_level = trim($_POST['risk_level'] ?? 'Medium');
    $status = trim($_POST['status'] ?? 'Under Review');

    if (empty($title) || empty($assessor)) {
        echo json_encode(["status" => "error", "message" => "Required fields are missing."]);
        exit;
    }

    try {
        // Find processing activity and template IDs
        $activityId = (int)$pdo->query("SELECT id FROM processing_activities LIMIT 1")->fetchColumn();
        $templateId = (int)$pdo->query("SELECT id FROM assessment_templates LIMIT 1")->fetchColumn();

        if (!$activityId || !$templateId) {
            throw new Exception("Missing prerequisite database records.");
        }

        // Get or Create Assessor User
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmtUser->execute([$assessor]);
        $assessorId = $stmtUser->fetchColumn();

        if (!$assessorId) {
            $stmtInsUser = $pdo->prepare("INSERT INTO users (email, role_id, password_hash) VALUES (?, 1, '')");
            $stmtInsUser->execute([$assessor]);
            $assessorId = $pdo->lastInsertId();
        }

        $creatorId = $_SESSION['user_id'] ?? 1;

        $model = new \Backend\Models\PrivacyAssessment($pdo);
        $service = new \Backend\Services\AssessmentService($model, $pdo);

        // Create
        $id = $service->createAssessment($activityId, $templateId, $title, $assessorId, $creatorId, $risk_level, date('Y-m-d', strtotime('+30 days')), $creatorId);

        echo json_encode([
            "status" => "success",
            "message" => "Assessment created successfully!",
            "data" => [
                "id" => $id,
                "title" => $title,
                "assessor" => $assessor,
                "risk_level" => $risk_level,
                "status" => $status
            ]
        ]);
    } catch (\Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}

?>