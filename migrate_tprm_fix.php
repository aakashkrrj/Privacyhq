<?php
require 'backend/config/db.php';

try {
    $categories = [
        'TPRM - Privacy',
        'TPRM - Security',
        'TPRM - Operational',
        'TPRM - Legal'
    ];
    $catIds = [];
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("SELECT id FROM risk_categories WHERE category_name = ?");
        $stmt->execute([$cat]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $pdo->prepare("INSERT INTO risk_categories (category_name) VALUES (?)")->execute([$cat]);
            $id = $pdo->lastInsertId();
        }
        $catIds[$cat] = $id;
    }

    $stmt = $pdo->query("SELECT id FROM assessment_templates WHERE template_name = 'Standard Vendor Due Diligence'");
    $templateId = $stmt->fetchColumn();
    
    if (!$templateId) {
        $pdo->exec("INSERT INTO assessment_templates (assessment_type_id, template_name, version_number, status_id, effective_date, created_by, updated_by) VALUES (1, 'Standard Vendor Due Diligence', '1.0', 1, CURDATE(), 1, 1)");
        $templateId = $pdo->lastInsertId();
    }
    
    // Clear old sections and questions if any
    $pdo->exec("DELETE FROM assessment_sections WHERE template_id = $templateId");
    
    // Add section
    $pdo->exec("INSERT INTO assessment_sections (template_id, section_name, display_order, created_by, updated_by) VALUES ($templateId, 'Vendor Due Diligence Questionnaire', 1, 1, 1)");
    $sectionId = $pdo->lastInsertId();

    // Add questions mapped to categories
    $questions = [
        ['Does the vendor process personal data (PII)?', 'yes_no', 0, 10, $catIds['TPRM - Privacy']],
        ['Does the vendor process special category data (e.g., health, biometric)?', 'yes_no', 0, 15, $catIds['TPRM - Privacy']],
        ['Is the vendor SOC 2 or ISO 27001 certified?', 'yes_no', 10, 0, $catIds['TPRM - Security']], 
        ['Does the vendor encrypt data at rest and in transit?', 'yes_no', 15, 0, $catIds['TPRM - Security']],
        ['Is the vendor a critical dependency for core business operations?', 'yes_no', 0, 10, $catIds['TPRM - Operational']],
        ['Does the vendor rely heavily on subcontractors for core delivery?', 'yes_no', 0, 5, $catIds['TPRM - Operational']],
        ['Has a Data Processing Agreement (DPA) been executed?', 'yes_no', 15, 0, $catIds['TPRM - Legal']],
        ['Is data transferred outside of the approved geographic boundary?', 'yes_no', 0, 15, $catIds['TPRM - Legal']],
    ];

    $stmtQ = $pdo->prepare("INSERT INTO assessment_questions (section_id, question_text, question_type, display_order, weight_yes, weight_no, risk_category_id, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)");
    foreach ($questions as $i => $q) {
        $stmtQ->execute([$sectionId, $q[0], $q[1], $i + 1, $q[2], $q[3], $q[4]]);
    }

    echo "Migration successful.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
