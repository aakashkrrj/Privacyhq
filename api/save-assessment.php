<?php
// governance/api/save-assessment.php
header("Content-Type: application/json");
require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'] ?? '';
    $focus_area = $_POST['focus_area'] ?? '';
    $lead_assessor = $_POST['lead_assessor'] ?? '';
    $risk_level = $_POST['risk_level'] ?? 'Low';

    if (empty($title) || empty($lead_assessor)) {
        echo json_encode(["status" => "error", "message" => "Required fields are missing."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO assessments (title, focus_area, lead_assessor, risk_level) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $focus_area, $lead_assessor, $risk_level);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Assessment created successfully!",
            "data" => [
                "id" => $stmt->insert_id,
                "title" => $title,
                "focus_area" => $focus_area,
                "lead_assessor" => $lead_assessor,
                "risk_level" => $risk_level
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save assessment."]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>