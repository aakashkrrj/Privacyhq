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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);