<?php
// governance/api/save-assessment.php
header("Content-Type: application/json");
require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $assessor = trim($_POST['assessor'] ?? '');
    $risk_level = trim($_POST['risk_level'] ?? 'Medium');
    $status = trim($_POST['status'] ?? 'Under Review');

    if (empty($title) || empty($assessor)) {
        echo json_encode(["status" => "error", "message" => "Required fields are missing."]);
        exit;
    }

    // 1. Validate Prerequisites (Processing Activity & Template)
    $processing_activity_id = null;
    $res = $conn->query("SELECT id FROM processing_activities LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $processing_activity_id = $row['id'];
    } else {
        echo json_encode(["status" => "error", "message" => "Missing prerequisite: No processing activity records exist."]);
        exit;
    }

    $template_id = null;
    $res = $conn->query("SELECT id FROM assessment_templates LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $template_id = $row['id'];
    } else {
        echo json_encode(["status" => "error", "message" => "Missing prerequisite: No assessment template records exist."]);
        exit;
    }

    // 2. Get or Create Status
    $status_id = null;
    $stmt = $conn->prepare("SELECT id FROM assessment_statuses WHERE status_name = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $status_id = $row['id'];
    } else {
        $stmt_i = $conn->prepare("INSERT INTO assessment_statuses (status_name) VALUES (?)");
        $stmt_i->bind_param("s", $status);
        $stmt_i->execute();
        $status_id = $conn->insert_id;
        $stmt_i->close();
    }
    $stmt->close();

    // 3. Get or Create User (Assessor)
    $user_id = null;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $assessor);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['id'];
    } else {
        $role_id = 1; // Default role
        $stmt_i = $conn->prepare("INSERT INTO users (email, role_id, password_hash) VALUES (?, ?, '')");
        $stmt_i->bind_param("si", $assessor, $role_id);
        $stmt_i->execute();
        $user_id = $conn->insert_id;
        $stmt_i->close();
    }
    $stmt->close();

    // 4. Get or Create Risk Matrix
    $risk_matrix_id = null;
    $stmt = $conn->prepare("SELECT id FROM risk_matrix WHERE risk_level_name = ?");
    $stmt->bind_param("s", $risk_level);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $risk_matrix_id = $row['id'];
    } else {
        $stmt_i = $conn->prepare("INSERT INTO risk_matrix (impact_level, likelihood_level, impact_name, likelihood_name, risk_score, risk_level_name) VALUES (1, 1, 'Default', 'Default', 1, ?)");
        $stmt_i->bind_param("s", $risk_level);
        $stmt_i->execute();
        $risk_matrix_id = $conn->insert_id;
        $stmt_i->close();
    }
    $stmt->close();

    // 5. Insert into privacy_assessments
    $creator_id = 1;
    $stmt = $conn->prepare("INSERT INTO privacy_assessments (processing_activity_id, template_id, status_id, title, assigned_to, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisiii", $processing_activity_id, $template_id, $status_id, $title, $user_id, $creator_id, $creator_id);

    if ($stmt->execute()) {
        $assessment_id = $stmt->insert_id;
        $stmt->close();

        // 6. Insert into assessment_risks
        $risk_category_id = 1; // Default
        $desc = '';
        $stmt_risk = $conn->prepare("INSERT INTO assessment_risks (assessment_id, risk_category_id, description, inherent_risk_matrix_id) VALUES (?, ?, ?, ?)");
        $stmt_risk->bind_param("iisi", $assessment_id, $risk_category_id, $desc, $risk_matrix_id);
        $stmt_risk->execute();
        $stmt_risk->close();

        echo json_encode([
            "status" => "success",
            "message" => "Assessment created successfully!",
            "data" => [
                "id" => $assessment_id,
                "title" => $title,
                "assessor" => $assessor,
                "risk_level" => $risk_level,
                "status" => $status
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save assessment: " . $stmt->error]);
        $stmt->close();
    }

    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>