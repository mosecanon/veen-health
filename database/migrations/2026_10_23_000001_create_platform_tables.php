<?php
// Central (platform) tables: countries, plans, tenants, branches, users, auth, audit.
$e = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    'up' => [
        "CREATE TABLE countries (
            code CHAR(2) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            currency CHAR(3) NOT NULL,
            phone_code VARCHAR(6) NOT NULL,
            timezone VARCHAR(64) NOT NULL,
            locale VARCHAR(10) NOT NULL DEFAULT 'en',
            default_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            tax_name VARCHAR(30) NOT NULL DEFAULT 'VAT',
            payment_methods JSON NULL
        ) $e",

        "CREATE TABLE plans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            price_usd_cents INT UNSIGNED NOT NULL DEFAULT 0,
            max_users INT UNSIGNED NULL,
            max_branches INT UNSIGNED NULL,
            features JSON NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL
        ) $e",

        "CREATE TABLE tenants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(60) NOT NULL UNIQUE,
            domain VARCHAR(191) NULL UNIQUE,
            country_code CHAR(2) NOT NULL,
            currency CHAR(3) NOT NULL,
            timezone VARCHAR(64) NOT NULL,
            locale VARCHAR(10) NOT NULL DEFAULT 'en',
            plan_id INT UNSIGNED NULL,
            status ENUM('trial','active','suspended') NOT NULL DEFAULT 'trial',
            trial_ends_at DATE NULL,
            email VARCHAR(150) NULL,
            phone VARCHAR(30) NULL,
            address VARCHAR(255) NULL,
            tax_pin VARCHAR(50) NULL,
            primary_color VARCHAR(9) NOT NULL DEFAULT '#14b8a6',
            logo VARCHAR(255) NULL,
            settings JSON NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            CONSTRAINT fk_tenant_country FOREIGN KEY (country_code) REFERENCES countries(code),
            CONSTRAINT fk_tenant_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
        ) $e",

        "CREATE TABLE branches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            code VARCHAR(20) NULL,
            address VARCHAR(255) NULL,
            phone VARCHAR(30) NULL,
            is_main TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_branch_tenant (tenant_id),
            CONSTRAINT fk_branch_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NULL,
            branch_id INT UNSIGNED NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            phone VARCHAR(30) NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL,
            title VARCHAR(60) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_user_tenant (tenant_id, role),
            CONSTRAINT fk_user_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_user_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
        ) $e",

        "CREATE TABLE login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_la_email (email, created_at),
            INDEX idx_la_ip (ip, created_at)
        ) $e",

        "CREATE TABLE audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            action VARCHAR(60) NOT NULL,
            entity VARCHAR(60) NULL,
            entity_id VARCHAR(40) NULL,
            meta JSON NULL,
            ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_audit_tenant (tenant_id, created_at),
            INDEX idx_audit_entity (tenant_id, entity, entity_id)
        ) $e",
    ],
    'down' => [
        'DROP TABLE IF EXISTS audit_logs', 'DROP TABLE IF EXISTS login_attempts', 'DROP TABLE IF EXISTS users',
        'DROP TABLE IF EXISTS branches', 'DROP TABLE IF EXISTS tenants', 'DROP TABLE IF EXISTS plans', 'DROP TABLE IF EXISTS countries',
    ],
];
