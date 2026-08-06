CREATE DATABASE IF NOT EXISTS privacy_governance;
USE privacy_governance;

-- 1. Assessments Table
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    focus_area VARCHAR(100) NOT NULL,
    lead_assessor VARCHAR(100) NOT NULL,
    risk_level ENUM('Low', 'Medium', 'High') NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending Review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Vendors Table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_name VARCHAR(255) NOT NULL,
    service_type VARCHAR(255) NOT NULL,
    data_shared VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Under Review',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Incidents Table
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    summary TEXT NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    impacted_records INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Under Investigation',
    containment_actions TEXT DEFAULT NULL,
    remediation_notes TEXT DEFAULT NULL,
    is_escalated TINYINT(1) DEFAULT 0,
    dpo_notified TINYINT(1) DEFAULT 0,
    regulatory_status VARCHAR(100) DEFAULT 'Not Required',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Role Permissions Table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
);

-- 7. Assessment Statuses Table
CREATE TABLE IF NOT EXISTS assessment_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 8. Assessment Templates Table
CREATE TABLE IF NOT EXISTS assessment_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_type_id BIGINT UNSIGNED NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    version_number VARCHAR(50) NOT NULL,
    is_current_version TINYINT(1) DEFAULT 1,
    description TEXT DEFAULT NULL,
    status_id BIGINT UNSIGNED NOT NULL,
    effective_date DATE NOT NULL,
    expiry_date DATE DEFAULT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_template_version (template_name, version_number)
);

-- 9. Assessment Sections Table
CREATE TABLE IF NOT EXISTS assessment_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id BIGINT UNSIGNED NOT NULL,
    section_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES assessment_templates (id) ON DELETE CASCADE
);

-- 10. Assessment Questions Table
CREATE TABLE IF NOT EXISTS assessment_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL,
    is_required TINYINT(1) DEFAULT 0,
    help_text TEXT DEFAULT NULL,
    placeholder VARCHAR(255) DEFAULT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    options_json LONGTEXT DEFAULT NULL,
    validation_rules_json LONGTEXT DEFAULT NULL,
    weight_yes INT DEFAULT 0,
    weight_no INT DEFAULT 0,
    score_options_json TEXT DEFAULT NULL,
    risk_category_id BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES assessment_sections (id) ON DELETE CASCADE
);

-- 11. Privacy Assessments Table
CREATE TABLE IF NOT EXISTS privacy_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    processing_activity_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NOT NULL,
    status_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    assigned_to BIGINT UNSIGNED DEFAULT NULL,
    reviewer_id BIGINT UNSIGNED DEFAULT NULL,
    priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    due_date DATE DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES assessment_templates (id),
    FOREIGN KEY (status_id) REFERENCES assessment_statuses (id),
    FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (reviewer_id) REFERENCES users (id) ON DELETE SET NULL
);

-- 12. Assessment Responses Table
CREATE TABLE IF NOT EXISTS assessment_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    response_text TEXT DEFAULT NULL,
    response_json LONGTEXT DEFAULT NULL,
    answered_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_assessment_question (assessment_id, question_id),
    FOREIGN KEY (assessment_id) REFERENCES privacy_assessments (id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES assessment_questions (id)
);

-- 13. Assessment Risks Table
CREATE TABLE IF NOT EXISTS assessment_risks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    risk_category_id BIGINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    inherent_risk_matrix_id BIGINT UNSIGNED DEFAULT NULL,
    residual_risk_matrix_id BIGINT UNSIGNED DEFAULT NULL,
    status ENUM('open', 'mitigated', 'accepted') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES privacy_assessments (id) ON DELETE CASCADE
);

-- 14. Assessment Notes Table
CREATE TABLE IF NOT EXISTS assessment_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    note_text TEXT NOT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES privacy_assessments (id) ON DELETE CASCADE
);

-- 15. Assessment Documents Table
CREATE TABLE IF NOT EXISTS assessment_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('evidence','approval_signoff','external_report') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES privacy_assessments (id) ON DELETE CASCADE
);