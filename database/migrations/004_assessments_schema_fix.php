<?php
// governance/database/migrations/004_assessments_schema_fix.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Assessments Schema Migration Fix...\n";

// Check if reviewer_id column exists
$checkReviewer = $conn->query("SHOW COLUMNS FROM privacy_assessments LIKE 'reviewer_id'");
if ($checkReviewer && $checkReviewer->num_rows == 0) {
    $conn->query("ALTER TABLE privacy_assessments ADD COLUMN reviewer_id BIGINT UNSIGNED DEFAULT NULL AFTER assigned_to");
    echo "Added reviewer_id column to privacy_assessments table.\n";
}

// Check if priority column exists
$checkPriority = $conn->query("SHOW COLUMNS FROM privacy_assessments LIKE 'priority'");
if ($checkPriority && $checkPriority->num_rows == 0) {
    $conn->query("ALTER TABLE privacy_assessments ADD COLUMN priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium' AFTER reviewer_id");
    echo "Added priority column to privacy_assessments table.\n";
}

// Add foreign key constraint for reviewer_id if needed
$checkFk = $conn->query("
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'privacy_assessments' 
      AND COLUMN_NAME = 'reviewer_id'
");
if ($checkFk && $checkFk->num_rows == 0) {
    $conn->query("ALTER TABLE privacy_assessments ADD CONSTRAINT fk_privacy_assessments_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE SET NULL");
    echo "Added foreign key constraint for reviewer_id.\n";
}

echo "Assessments Schema Migration completed successfully!\n";
