<?php
$e = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    'up' => [
        "CREATE TABLE patients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            mrn VARCHAR(30) NOT NULL,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            other_names VARCHAR(80) NULL,
            sex ENUM('male','female','other') NOT NULL,
            dob DATE NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(150) NULL,
            national_id VARCHAR(40) NULL,
            insurance_provider VARCHAR(100) NULL,
            insurance_number VARCHAR(60) NULL,
            address VARCHAR(255) NULL,
            blood_group VARCHAR(5) NULL,
            allergies TEXT NULL,
            next_of_kin_name VARCHAR(150) NULL,
            next_of_kin_phone VARCHAR(30) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL,
            UNIQUE KEY uq_patient_mrn (tenant_id, mrn),
            INDEX idx_patient_name (tenant_id, last_name, first_name),
            INDEX idx_patient_phone (tenant_id, phone),
            INDEX idx_patient_nid (tenant_id, national_id),
            CONSTRAINT fk_patient_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        // Per-tenant counters (MRN, receipts...). Row-locked for gap-free numbering.
        "CREATE TABLE tenant_sequences (
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(40) NOT NULL,
            value BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (tenant_id, name),
            CONSTRAINT fk_seq_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",
    ],
    'down' => ['DROP TABLE IF EXISTS tenant_sequences', 'DROP TABLE IF EXISTS patients'],
];
