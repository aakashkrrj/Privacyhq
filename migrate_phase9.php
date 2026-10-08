<?php
require_once __DIR__ . '/backend/config/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grc_frameworks (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            version VARCHAR(50) DEFAULT '1.0',
            description TEXT,
            status VARCHAR(50) DEFAULT 'Draft',
            owner VARCHAR(100),
            effective_date DATE,
            review_date DATE,
            
            -- Scoring
            score DECIMAL(5,2) DEFAULT 0.00,
            assessed_count INT DEFAULT 0,
            compliant_count INT DEFAULT 0,
            partial_count INT DEFAULT 0,
            non_compliant_count INT DEFAULT 0,
            not_assessed_count INT DEFAULT 0,
            calculated_at TIMESTAMP NULL,
            
            created_by BIGINT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Alter requirements table to add Phase 9 columns
    $stmt = $pdo->query("SHOW COLUMNS FROM grc_compliance_requirements LIKE 'framework_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE grc_compliance_requirements ADD COLUMN framework_id BIGINT UNSIGNED NULL AFTER req_code;");
        $pdo->exec("ALTER TABLE grc_compliance_requirements ADD CONSTRAINT fk_grc_req_fw FOREIGN KEY (framework_id) REFERENCES grc_frameworks(id) ON DELETE SET NULL;");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM grc_compliance_requirements LIKE 'assessment_status'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE grc_compliance_requirements ADD COLUMN assessment_status VARCHAR(50) DEFAULT 'Not Assessed' AFTER status;");
        $pdo->exec("ALTER TABLE grc_compliance_requirements ADD COLUMN assessment_date DATE NULL AFTER assessment_status;");
        $pdo->exec("ALTER TABLE grc_compliance_requirements ADD COLUMN assessment_notes TEXT NULL AFTER assessment_date;");
    }

    // Evidence mappings will just use document_repository with linked_module = 'compliance_requirement' or 'grc_control'

    echo "Phase 9 Database Migration Successful!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
