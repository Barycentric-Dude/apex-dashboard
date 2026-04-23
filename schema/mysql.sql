CREATE TABLE companies (
    id VARCHAR(40) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    subscription_status VARCHAR(20) NOT NULL,
    subscription_ends_at DATE NULL,
    panel_limit INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL
);

CREATE TABLE users (
    id VARCHAR(40) PRIMARY KEY,
    company_id VARCHAR(40) NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE panels (
    id VARCHAR(40) PRIMARY KEY,
    company_id VARCHAR(40) NOT NULL,
    name VARCHAR(150) NOT NULL,
    site_name VARCHAR(150) NULL,
    device_id VARCHAR(120) NOT NULL UNIQUE,
    token VARCHAR(255) NOT NULL UNIQUE,
    water_level_threshold DECIMAL(10,2) NOT NULL DEFAULT 30.00,
    reporting_interval_minutes INT NOT NULL DEFAULT 12,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_panels_company FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE telemetry_logs (
    id VARCHAR(40) PRIMARY KEY,
    panel_id VARCHAR(40) NOT NULL,
    panel_input VARCHAR(20) NOT NULL,
    event_type VARCHAR(30) NOT NULL,
    current DECIMAL(10,2) NULL,
    device_status TINYINT(1) NOT NULL,
    water_level DECIMAL(10,2) NULL,
    mains_status TINYINT(1) NOT NULL,
    batt_status TINYINT(1) NOT NULL,
    reported_at DATETIME NOT NULL,
    raw_payload JSON NOT NULL,
    received_at DATETIME NOT NULL,
    INDEX idx_telemetry_panel_reported (panel_id, reported_at),
    CONSTRAINT fk_telemetry_panel FOREIGN KEY (panel_id) REFERENCES panels(id)
);

CREATE TABLE panel_latest_state (
    id VARCHAR(40) PRIMARY KEY,
    panel_id VARCHAR(40) NOT NULL,
    panel_input VARCHAR(20) NOT NULL,
    event_type VARCHAR(30) NOT NULL,
    current DECIMAL(10,2) NULL,
    device_status TINYINT(1) NOT NULL,
    water_level DECIMAL(10,2) NULL,
    mains_status TINYINT(1) NOT NULL,
    batt_status TINYINT(1) NOT NULL,
    reported_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_panel_input (panel_id, panel_input),
    CONSTRAINT fk_latest_state_panel FOREIGN KEY (panel_id) REFERENCES panels(id)
);

CREATE TABLE alerts (
    id VARCHAR(40) PRIMARY KEY,
    panel_id VARCHAR(40) NOT NULL,
    panel_input VARCHAR(20) NOT NULL,
    type VARCHAR(40) NOT NULL,
    status VARCHAR(20) NOT NULL,
    message VARCHAR(255) NOT NULL,
    reported_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_alerts_panel_status (panel_id, status),
    CONSTRAINT fk_alerts_panel FOREIGN KEY (panel_id) REFERENCES panels(id)
);
