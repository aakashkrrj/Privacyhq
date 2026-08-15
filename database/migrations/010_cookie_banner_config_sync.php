<?php
// governance/database/migrations/010_cookie_banner_config_sync.php
require_once __DIR__ . '/../../includes/db.php';

echo "Executing Idempotent Cookie Banner Config Sync (010)...\n";

/** @var mysqli $conn */

// Helper to execute query with strict error checking
function executeQuery($conn, $sql, $stepName) {
    $res = $conn->query($sql);
    if ($res === false) {
        echo "[ERROR] Step '$stepName' failed!\n";
        echo "SQL Error: " . $conn->error . "\n";
        exit(1);
    }
    return $res;
}

// Helper function to check if column exists
function hasColumn($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($res && $res->num_rows > 0);
}

// Helper function to check if index exists
function hasIndex($conn, $table, $index) {
    $res = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
    return ($res && $res->num_rows > 0);
}

$summary = [
    'Backup created/preserved' => 'FAIL',
    'Domain mapping' => 'FAIL',
    'Primary color mapping' => 'FAIL',
    'Duplicate domains' => 'FAIL',
    'NULL domains' => 'FAIL',
    'Unique index' => 'FAIL',
    'Existing row count preserved' => 'FAIL'
];

// Preflight Count Check
$initialCountRes = executeQuery($conn, "SELECT COUNT(*) FROM cookie_banner_configs", "Get initial row count");
$initialCount = $initialCountRes->fetch_row()[0];

// Step 1: Create backup table
executeQuery($conn, "CREATE TABLE IF NOT EXISTS cookie_banner_configs_backup AS SELECT * FROM cookie_banner_configs", "Create backup table");
$summary['Backup created/preserved'] = 'PASS';

// Step 2: Add missing columns if they don't exist
$columnsToAdd = [
    'domain' => "VARCHAR(255) DEFAULT NULL AFTER domain_id",
    'position' => "ENUM('bottom', 'top', 'floating') NOT NULL DEFAULT 'bottom'",
    'theme' => "ENUM('light', 'dark', 'custom') NOT NULL DEFAULT 'light'",
    'primary_color' => "VARCHAR(20) DEFAULT '#4F46E5'",
    'background_color' => "VARCHAR(20) NOT NULL DEFAULT '#FFFFFF'",
    'text_color' => "VARCHAR(20) NOT NULL DEFAULT '#1F2937'",
    'privacy_policy_url' => "VARCHAR(255) DEFAULT '/privacy-policy.php'",
    'cookie_policy_url' => "VARCHAR(255) DEFAULT '/cookie-policy.php'",
    'is_active' => "TINYINT(1) NOT NULL DEFAULT 1"
];

foreach ($columnsToAdd as $col => $definition) {
    if (!hasColumn($conn, 'cookie_banner_configs', $col)) {
        executeQuery($conn, "ALTER TABLE cookie_banner_configs ADD COLUMN `$col` $definition", "Add column $col");
        echo "Added column `$col` to cookie_banner_configs.\n";
    } else {
        echo "Column `$col` already exists. Skipping.\n";
    }
}

// Step 3: Verify mapping before updates
$mappingCheck = executeQuery($conn, "
    SELECT cbc.id, cbc.branding_color, cd.domain_name 
    FROM cookie_banner_configs cbc 
    LEFT JOIN cookie_domains cd ON cbc.domain_id = cd.id
", "Verify mapping domain check");

while ($row = $mappingCheck->fetch_assoc()) {
    if (empty($row['domain_name'])) {
        echo "[ERROR] Preflight validation failed: Banner configuration ID {$row['id']} has no matching domain in cookie_domains.\n";
        exit(1);
    }
}

// Step 4: Populate domain column
executeQuery($conn, "
    UPDATE cookie_banner_configs cbc
    JOIN cookie_domains cd ON cbc.domain_id = cd.id
    SET cbc.domain = cd.domain_name
    WHERE cbc.domain IS NULL OR cbc.domain = ''
", "Populate domain values");
$summary['Domain mapping'] = 'PASS';

// Step 5: Sync primary_color from branding_color
executeQuery($conn, "
    UPDATE cookie_banner_configs 
    SET primary_color = branding_color 
    WHERE (primary_color IS NULL OR primary_color = '#4F46E5') AND branding_color IS NOT NULL AND branding_color != ''
", "Sync colors");
$summary['Primary color mapping'] = 'PASS';

// Step 6: Preflight validations before constraints
$dupCheck = executeQuery($conn, "
    SELECT domain, COUNT(*) 
    FROM cookie_banner_configs 
    GROUP BY domain 
    HAVING COUNT(*) > 1
", "Check duplicate domains");

if ($dupCheck->num_rows > 0) {
    echo "[ERROR] Preflight validation failed: Duplicate domains detected.\n";
    exit(1);
}
$summary['Duplicate domains'] = 'PASS';

$nullCheck = executeQuery($conn, "
    SELECT COUNT(*) 
    FROM cookie_banner_configs 
    WHERE domain IS NULL OR domain = ''
", "Check null domains");

$nullCount = $nullCheck->fetch_row()[0];
if ($nullCount > 0) {
    echo "[ERROR] Preflight validation failed: NULL or empty domains detected.\n";
    exit(1);
}
$summary['NULL domains'] = 'PASS';

// Step 7: Enforce NOT NULL and UNIQUE Index
executeQuery($conn, "ALTER TABLE cookie_banner_configs MODIFY COLUMN domain VARCHAR(255) NOT NULL", "Enforce NOT NULL domain");

if (!hasIndex($conn, 'cookie_banner_configs', 'uk_banner_domain')) {
    executeQuery($conn, "ALTER TABLE cookie_banner_configs ADD UNIQUE KEY uk_banner_domain (domain)", "Add unique key uk_banner_domain");
    echo "Added unique key constraint 'uk_banner_domain'.\n";
} else {
    echo "Unique key constraint 'uk_banner_domain' already exists. Skipping.\n";
}
$summary['Unique index'] = 'PASS';

// Step 8: Validate post-migration counts
$finalCountRes = executeQuery($conn, "SELECT COUNT(*) FROM cookie_banner_configs", "Get final row count");
$finalCount = $finalCountRes->fetch_row()[0];

if ($finalCount !== $initialCount) {
    echo "[ERROR] Count mismatch: Initial row count was $initialCount, final row count is $finalCount.\n";
    exit(1);
}
$summary['Existing row count preserved'] = 'PASS';

echo "\n=== SYNC MIGRATION 010 COMPLETE SUMMARY ===\n";
foreach ($summary as $check => $status) {
    echo "- $check: $status\n";
}
