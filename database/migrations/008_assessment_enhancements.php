<?php
// governance/database/migrations/008_assessment_enhancements.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Assessment Enhancements Migration...\n";

/** @var mysqli $conn */

// 1. Add risk_score and calculated_risk_level to privacy_assessments if missing
$cols = [
    'risk_score' => 'INT UNSIGNED DEFAULT 0',
    'calculated_risk_level' => "VARCHAR(50) DEFAULT 'Low'"
];
foreach ($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM privacy_assessments LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE privacy_assessments ADD COLUMN $col $def");
        echo "Added column $col to privacy_assessments.\n";
    }
}

// 2. Ensure assessment_status_history table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS assessment_status_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        assessment_id BIGINT UNSIGNED NOT NULL,
        previous_status_id BIGINT UNSIGNED DEFAULT NULL,
        new_status_id BIGINT UNSIGNED NOT NULL,
        changed_by BIGINT UNSIGNED DEFAULT NULL,
        reason TEXT DEFAULT NULL,
        changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_assessment_hist (assessment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table assessment_status_history verified.\n";

// 3. Ensure Default Template (ID = 1) exists
$checkTemplate = $conn->query("SELECT COUNT(*) FROM assessment_templates WHERE id = 1")->fetch_row()[0];
if ($checkTemplate == 0) {
    $conn->query("
        INSERT INTO assessment_templates (id, template_name, description, is_active, created_at)
        VALUES (1, 'General Data Protection Impact Assessment (DPIA)', 'Standard DPIA template under GDPR and DPDP regulations.', 1, NOW());
    ");
    echo "Inserted default DPIA template (ID = 1).\n";
}

// 4. Ensure Sections exist for Template 1
$checkSections = $conn->query("SELECT COUNT(*) FROM assessment_sections WHERE template_id = 1 AND deleted_at IS NULL")->fetch_row()[0];
if ($checkSections == 0) {
    $conn->query("
        INSERT INTO assessment_sections (id, template_id, section_name, description, display_order, created_at) VALUES
        (1, 1, 'Section 1: Data Collection & Processing Nature', 'Identify the scope and categories of personal data collected.', 1, NOW()),
        (2, 1, 'Section 2: Legal Basis & Purpose Specification', 'Assess legal grounds and legitimate processing purposes.', 2, NOW()),
        (3, 1, 'Section 3: Security & Encryption Safeguards', 'Review technical and organizational measures protecting personal data.', 3, NOW()),
        (4, 1, 'Section 4: Data Retention & Minimization', 'Evaluate storage limitation and data destruction schedules.', 4, NOW()),
        (5, 1, 'Section 5: Third-Party Sharing & Cross-Border Transfers', 'Examine vendor processing, cross-border flows, and safeguards.', 5, NOW());
    ");
    echo "Inserted DPIA sections.\n";
}

// 5. Seed Questions for Template 1 if question count < 8
$qCount = $conn->query("SELECT COUNT(*) FROM assessment_questions WHERE deleted_at IS NULL")->fetch_row()[0];
if ($qCount < 8) {
    $conn->query("
        INSERT INTO assessment_questions (section_id, question_text, question_type, is_required, help_text, placeholder, display_order, options_json, weight_yes, weight_no, score_options_json, risk_category_id) VALUES
        (1, 'Does this processing activity collect Special Categories of Personal Data (SPII/Biometric/Health/Financial)?', 'yes_no', 1, 'Special category data requires explicit consent and heightened safeguards.', '', 1, '[\"Yes\",\"No\"]', 4, 0, '{\"Yes\":4,\"No\":0}', 1),
        (1, 'What is the estimated volume of data subjects impacted by this processing activity?', 'dropdown', 1, 'Higher volume increases overall risk exposure.', '', 2, '[\"Under 10,000 Data Subjects\",\"10,000 to 100,000 Data Subjects\",\"Over 100,000 Data Subjects\"]', 0, 0, '{\"Under 10,000 Data Subjects\":1,\"10,000 to 100,000 Data Subjects\":2,\"Over 100,000 Data Subjects\":4}', 1),
        (2, 'Is processing based on a documented Legal Basis (Consent, Contract, Legal Obligation)?', 'yes_no', 1, 'Processing without a valid legal basis violates privacy compliance.', '', 3, '[\"Yes\",\"No\"]', 0, 5, '{\"Yes\":0,\"No\":5}', 2),
        (3, 'Is personal data encrypted both in transit (TLS 1.3) and at rest (AES-256)?', 'yes_no', 1, 'Unencrypted storage exposes personal data to unauthorized access.', '', 4, '[\"Yes\",\"No\"]', 0, 4, '{\"Yes\":0,\"No\":4}', 3),
        (3, 'Are Multi-Factor Authentication (MFA) and Role-Based Access Controls (RBAC) enforced for system admins?', 'yes_no', 1, 'Admin access must be strictly protected.', '', 5, '[\"Yes\",\"No\"]', 0, 3, '{\"Yes\":0,\"No\":3}', 3),
        (4, 'Is a formal Data Retention & Erasure Schedule enforced for this processing activity?', 'yes_no', 1, 'Data stored indefinitely increases compliance liability.', '', 6, '[\"Yes\",\"No\"]', 0, 3, '{\"Yes\":0,\"No\":3}', 4),
        (5, 'Is personal data transferred to third-party vendors or cross-border cloud locations?', 'yes_no', 1, 'Third-party sharing requires Data Processing Agreements (DPA) and transfer safeguards.', '', 7, '[\"Yes\",\"No\"]', 3, 0, '{\"Yes\":3,\"No\":0}', 5);
    ");
    echo "Inserted DPIA questions.\n";
}

// 6. Ensure all assessment status names exist in assessment_statuses
$statuses = ['Draft', 'Under Review', 'Approved', 'Rejected', 'In Progress', 'Submitted', 'Pending Review'];
foreach ($statuses as $s) {
    $check = $conn->query("SELECT id FROM assessment_statuses WHERE status_name = '$s'");
    if ($check && $check->num_rows == 0) {
        $conn->query("INSERT INTO assessment_statuses (status_name) VALUES ('$s')");
        echo "Inserted status '$s' into assessment_statuses.\n";
    }
}

echo "Assessment Enhancements Migration executed successfully!\n";
