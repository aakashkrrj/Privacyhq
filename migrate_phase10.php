<?php
require_once __DIR__ . '/backend/config/db.php';

try {
    // 1. Data Assets Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS data_assets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            asset_name VARCHAR(255) NOT NULL,
            description TEXT,
            asset_type VARCHAR(100),
            owner VARCHAR(100),
            business_unit VARCHAR(100),
            status VARCHAR(50) DEFAULT 'Active',
            criticality VARCHAR(50) DEFAULT 'Medium',
            source_id BIGINT UNSIGNED NULL, -- Links to discovery_sources
            environment VARCHAR(50) DEFAULT 'Production',
            discovery_method VARCHAR(50) DEFAULT 'Manual',
            last_discovered_at TIMESTAMP NULL,
            classification VARCHAR(50) DEFAULT 'Internal',
            sensitivity_indicators TEXT, -- JSON array of strings
            review_date DATE,
            created_by BIGINT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            CONSTRAINT fk_data_assets_source FOREIGN KEY (source_id) REFERENCES discovery_sources(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Data Asset <-> Personal Data Categories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS data_asset_categories (
            asset_id BIGINT UNSIGNED NOT NULL,
            category_name VARCHAR(100) NOT NULL, -- e.g., 'Name', 'Email', 'Financial Data'
            PRIMARY KEY (asset_id, category_name),
            CONSTRAINT fk_dac_asset FOREIGN KEY (asset_id) REFERENCES data_assets(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Data Asset <-> RoPA (processing_activities)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS data_asset_ropa (
            asset_id BIGINT UNSIGNED NOT NULL,
            processing_activity_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (asset_id, processing_activity_id),
            CONSTRAINT fk_dar_asset FOREIGN KEY (asset_id) REFERENCES data_assets(id) ON DELETE CASCADE,
            CONSTRAINT fk_dar_ropa FOREIGN KEY (processing_activity_id) REFERENCES processing_activities(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Data Asset <-> Risks (assessment_risks)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS data_asset_risks (
            asset_id BIGINT UNSIGNED NOT NULL,
            risk_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (asset_id, risk_id),
            CONSTRAINT fk_dari_asset FOREIGN KEY (asset_id) REFERENCES data_assets(id) ON DELETE CASCADE,
            CONSTRAINT fk_dari_risk FOREIGN KEY (risk_id) REFERENCES assessment_risks(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Phase 10 Database Migration Successful!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
