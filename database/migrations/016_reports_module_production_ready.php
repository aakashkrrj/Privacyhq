<?php
// governance/database/migrations/016_reports_module_production_ready.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Reports Module Production Ready Migration (016)...\n";

/** @var mysqli $conn */

// 1. Create report_executions table
$conn->query("
    CREATE TABLE IF NOT EXISTS report_executions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_code VARCHAR(50) NOT NULL,
        report_type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        execution_type VARCHAR(50) DEFAULT 'manual',
        schedule_id BIGINT UNSIGNED DEFAULT NULL,
        filters LONGTEXT DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'completed',
        file_path VARCHAR(500) DEFAULT NULL,
        file_name VARCHAR(255) DEFAULT NULL,
        file_size INT UNSIGNED DEFAULT 0,
        file_format VARCHAR(50) DEFAULT 'pdf',
        generated_by BIGINT UNSIGNED DEFAULT 1,
        error_message TEXT DEFAULT NULL,
        execution_time_ms INT UNSIGNED DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_re_type_status (report_type, status),
        INDEX idx_re_date (created_at),
        INDEX idx_re_gen (generated_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table report_executions verified.\n";

// 2. Create report_schedules table
$conn->query("
    CREATE TABLE IF NOT EXISTS report_schedules (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        schedule_code VARCHAR(50) NOT NULL,
        report_type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        frequency VARCHAR(50) DEFAULT 'weekly',
        filters LONGTEXT DEFAULT NULL,
        export_format VARCHAR(50) DEFAULT 'pdf',
        recipients TEXT DEFAULT NULL,
        next_run_at TIMESTAMP NULL DEFAULT NULL,
        last_run_at TIMESTAMP NULL DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'active',
        created_by BIGINT UNSIGNED DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_rs_status_next (status, next_run_at),
        INDEX idx_rs_type (report_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Table report_schedules verified.\n";

// 3. Seed initial report execution records if empty
$checkExec = $conn->query("SELECT COUNT(*) AS total FROM report_executions WHERE deleted_at IS NULL");
$rowExec = $checkExec ? $checkExec->fetch_assoc() : null;
if (($rowExec['total'] ?? 0) == 0) {
    $conn->query("
        INSERT INTO report_executions 
            (report_code, report_type, title, execution_type, schedule_id, filters, status, file_path, file_name, file_size, file_format, generated_by, execution_time_ms, created_at)
        VALUES 
            ('RPT-0001', 'ROPA Inventory', 'GDPR Article 30 ROPA Audit Summary Report', 'manual', NULL, '{\"department\":\"All\"}', 'completed', 'backend/api/reports/ropa.php', 'ropa_compliance_report.pdf', 4404019, 'pdf', 1, 180, NOW() - INTERVAL 5 DAY),
            ('RPT-0002', 'DSR Performance', 'Monthly Data Subject Rights Resolution Summary', 'scheduled', 1, '{\"status\":\"All\"}', 'completed', 'backend/api/reports/dsr.php', 'dsr_performance_report.pdf', 1887436, 'pdf', 1, 145, NOW() - INTERVAL 3 DAY),
            ('RPT-0003', 'Vendor Risk', 'Third-Party Vendor Risk & DPA Compliance Summary', 'manual', NULL, '{\"category\":\"All\"}', 'completed', 'backend/api/reports/vendor-risk.php', 'vendor_risk_summary.pdf', 13107200, 'pdf', 1, 210, NOW() - INTERVAL 1 DAY),
            ('RPT-0004', 'Policies Report', 'Privacy Policy Governance & Expiry Target Report', 'scheduled', 2, '{\"status\":\"active\"}', 'completed', 'backend/api/reports/policies.php', 'policies_governance_report.pdf', 943718, 'pdf', 1, 120, NOW())
    ");
    echo "Seeded default report execution records.\n";
}

// 4. Seed initial report schedules if empty
$checkSched = $conn->query("SELECT COUNT(*) AS total FROM report_schedules WHERE deleted_at IS NULL");
$rowSched = $checkSched ? $checkSched->fetch_assoc() : null;
if (($rowSched['total'] ?? 0) == 0) {
    $conn->query("
        INSERT INTO report_schedules 
            (schedule_code, report_type, title, frequency, filters, export_format, recipients, next_run_at, last_run_at, status, created_by, created_at, updated_at)
        VALUES 
            ('SCH-0001', 'DSR Performance', 'Weekly DSR Fulfillment Metrics Digest', 'weekly', '{\"status\":\"all\"}', 'pdf', 'dpo@privacyhq.com, compliance@privacyhq.com', DATE_ADD(NOW(), INTERVAL 7 DAY), NOW() - INTERVAL 3 DAY, 'active', 1, NOW(), NOW()),
            ('SCH-0002', 'Policies Report', 'Monthly Policy Expiry & Review Audit', 'monthly', '{\"status\":\"active\"}', 'excel', 'legal@privacyhq.com', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), 'active', 1, NOW(), NOW()),
            ('SCH-0003', 'Incident Summary', 'Daily Critical Security Incident Alert Summary', 'daily', '{\"severity\":\"Critical\"}', 'pdf', 'secops@privacyhq.com', DATE_ADD(NOW(), INTERVAL 1 DAY), NOW() - INTERVAL 1 DAY, 'active', 1, NOW(), NOW())
    ");
    echo "Seeded default report schedule records.\n";
}

echo "Reports Module Migration (016) completed successfully!\n";
