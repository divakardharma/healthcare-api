CREATE DATABASE IF NOT EXISTS heal_master_db;

USE heal_master_db;


-- ============================================
-- 1. TENANTS / HOSPITALS
-- ============================================

CREATE TABLE tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    subdomain VARCHAR(100) NOT NULL UNIQUE,

    status VARCHAR(30) NOT NULL DEFAULT 'provisioning',

    trial_end DATE NULL,

    subscription_status VARCHAR(30) NOT NULL DEFAULT 'trial',

    db_name VARCHAR(150) NULL,
    db_host VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ============================================
-- 2. SUBSCRIPTIONS
-- ============================================

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,

    plan_name VARCHAR(100) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'trial',

    start_date DATE NULL,
    end_date DATE NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id)
        REFERENCES tenants(id)
        ON DELETE CASCADE
);


-- ============================================
-- 3. BILLING
-- ============================================

CREATE TABLE billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,

    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,

    payment_status VARCHAR(30) NOT NULL DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id)
        REFERENCES tenants(id)
        ON DELETE CASCADE
);


-- ============================================
-- 4. TENANT METADATA
-- ============================================

CREATE TABLE tenant_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,

    meta_key VARCHAR(100) NOT NULL,
    meta_value TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id)
        REFERENCES tenants(id)
        ON DELETE CASCADE
);