<?php
// governance/database/migrations/005_assessment_questions_schema_fix.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Assessment Questions Schema Migration Fix...\n";

// Check if weight_yes column exists
$checkWeightYes = $conn->query("SHOW COLUMNS FROM assessment_questions LIKE 'weight_yes'");
if ($checkWeightYes && $checkWeightYes->num_rows == 0) {
    $conn->query("ALTER TABLE assessment_questions ADD COLUMN weight_yes INT DEFAULT 0 AFTER validation_rules_json");
    echo "Added weight_yes column to assessment_questions table.\n";
}

// Check if weight_no column exists
$checkWeightNo = $conn->query("SHOW COLUMNS FROM assessment_questions LIKE 'weight_no'");
if ($checkWeightNo && $checkWeightNo->num_rows == 0) {
    $conn->query("ALTER TABLE assessment_questions ADD COLUMN weight_no INT DEFAULT 0 AFTER weight_yes");
    echo "Added weight_no column to assessment_questions table.\n";
}

// Check if score_options_json column exists
$checkScoreOptions = $conn->query("SHOW COLUMNS FROM assessment_questions LIKE 'score_options_json'");
if ($checkScoreOptions && $checkScoreOptions->num_rows == 0) {
    $conn->query("ALTER TABLE assessment_questions ADD COLUMN score_options_json TEXT DEFAULT NULL AFTER weight_no");
    echo "Added score_options_json column to assessment_questions table.\n";
}

// Check if risk_category_id column exists
$checkRiskCat = $conn->query("SHOW COLUMNS FROM assessment_questions LIKE 'risk_category_id'");
if ($checkRiskCat && $checkRiskCat->num_rows == 0) {
    $conn->query("ALTER TABLE assessment_questions ADD COLUMN risk_category_id BIGINT UNSIGNED DEFAULT NULL AFTER score_options_json");
    echo "Added risk_category_id column to assessment_questions table.\n";
}

echo "Assessment Questions Schema Fix migration completed successfully!\n";
