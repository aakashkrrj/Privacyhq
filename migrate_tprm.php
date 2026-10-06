<?php
require 'backend/config/db.php';

try {
    // 1. Add criticality to vendors
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS criticality ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium' AFTER service_type");
    
    // 2. Add vendor_assessment_responses
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vendor_assessment_responses` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `vendor_assessment_id` bigint(20) unsigned NOT NULL,
          `question_id` bigint(20) unsigned NOT NULL,
          `response_text` text DEFAULT NULL,
          `response_json` longtext DEFAULT NULL,
          `answered_by` bigint(20) unsigned DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_vendor_assessment_question` (`vendor_assessment_id`,`question_id`),
          CONSTRAINT `fk_var_va` FOREIGN KEY (`vendor_assessment_id`) REFERENCES `vendor_assessments` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_var_q` FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Add vendor_assessment_risks (Findings)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vendor_assessment_risks` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `vendor_assessment_id` bigint(20) unsigned NOT NULL,
          `risk_category_id` bigint(20) unsigned NOT NULL,
          `description` text NOT NULL,
          `inherent_score` int(11) DEFAULT 0,
          `inherent_level` varchar(20) DEFAULT 'Low',
          `status` enum('open','mitigated','accepted') DEFAULT 'open',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          CONSTRAINT `fk_vrisk_va` FOREIGN KEY (`vendor_assessment_id`) REFERENCES `vendor_assessments` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_vrisk_cat` FOREIGN KEY (`risk_category_id`) REFERENCES `risk_categories` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Create TPRM risk categories
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

    // 5. Create Vendor template if not exists
    $stmt = $pdo->query("SELECT id FROM assessment_templates WHERE template_name = 'Standard Vendor Due Diligence'");
    $templateId = $stmt->fetchColumn();
    
    if (!$templateId) {
        $pdo->exec("INSERT INTO assessment_templates (assessment_type_id, template_name, version_number, status_id, effective_date, created_by, updated_by) VALUES (1, 'Standard Vendor Due Diligence', '1.0', 1, CURDATE(), 1, 1)");
        $templateId = $pdo->lastInsertId();

        // Add section
        $pdo->exec("INSERT INTO assessment_sections (template_id, title, display_order, created_by, updated_by) VALUES ($templateId, 'Vendor Due Diligence Questionnaire', 1, 1, 1)");
        $sectionId = $pdo->lastInsertId();

        // Add questions mapped to categories
        $questions = [
            ['Does the vendor process personal data (PII)?', 'yes_no', 0, 10, $catIds['TPRM - Privacy']],
            ['Does the vendor process special category data (e.g., health, biometric)?', 'yes_no', 0, 15, $catIds['TPRM - Privacy']],
            ['Is the vendor SOC 2 or ISO 27001 certified?', 'yes_no', 10, 0, $catIds['TPRM - Security']], // Yes reduces risk, No increases risk
            ['Does the vendor encrypt data at rest and in transit?', 'yes_no', 15, 0, $catIds['TPRM - Security']],
            ['Is the vendor a critical dependency for core business operations?', 'yes_no', 0, 10, $catIds['TPRM - Operational']],
            ['Does the vendor rely heavily on subcontractors for core delivery?', 'yes_no', 0, 5, $catIds['TPRM - Operational']],
            ['Has a Data Processing Agreement (DPA) been executed?', 'yes_no', 15, 0, $catIds['TPRM - Legal']],
            ['Is data transferred outside of the approved geographic boundary?', 'yes_no', 0, 15, $catIds['TPRM - Legal']],
        ];

        $stmtQ = $pdo->prepare("INSERT INTO assessment_questions (section_id, question_text, question_type, display_order, weight_yes, weight_no, risk_category_id, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)");
        foreach ($questions as $i => $q) {
            $stmtQ->execute([$sectionId, $q[0], $q[1], $i + 1, $q[3], $q[4], $q[5]]);
        }
    }

    echo "Migration successful.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
