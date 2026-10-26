<?php
// Veen Health: Full clinical care, EMR encounters, vitals, diagnostics, IPD beds, billing & insurance.
$e = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    'up' => [
        // 1. Dynamic Plan & Billing Cycle Extensions
        "ALTER TABLE plans ADD COLUMN billing_cycle VARCHAR(30) NOT NULL DEFAULT 'monthly' AFTER price_usd_cents",
        "ALTER TABLE plans ADD COLUMN trial_days INT UNSIGNED NOT NULL DEFAULT 14 AFTER billing_cycle",
        "ALTER TABLE tenants ADD COLUMN billing_cycle VARCHAR(30) NOT NULL DEFAULT 'free_trial' AFTER status",
        "ALTER TABLE tenants ADD COLUMN subscription_expires_at DATE NULL AFTER trial_ends_at",

        // 2. Clinical Encounters (Outpatient OPD, Emergency Triage, Longitudinal EMR)
        "CREATE TABLE clinical_encounters (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            patient_id INT UNSIGNED NOT NULL,
            ticket_id BIGINT UNSIGNED NULL,
            doctor_user_id INT UNSIGNED NULL,
            triage_nurse_id INT UNSIGNED NULL,
            encounter_type ENUM('outpatient','emergency','inpatient','follow_up') NOT NULL DEFAULT 'outpatient',
            status ENUM('waiting_triage','triaged','consulting','completed','admitted') NOT NULL DEFAULT 'waiting_triage',
            triage_category ENUM('emergency','very_urgent','urgent','standard','non_urgent') NOT NULL DEFAULT 'standard',
            presenting_complaints TEXT NULL,
            history_of_illness TEXT NULL,
            past_medical_history TEXT NULL,
            physical_examination TEXT NULL,
            systolic_bp INT NULL,
            diastolic_bp INT NULL,
            pulse_rate INT NULL,
            temperature_c DECIMAL(4,1) NULL,
            respiratory_rate INT NULL,
            spo2_percent INT NULL,
            weight_kg DECIMAL(5,2) NULL,
            height_cm DECIMAL(5,2) NULL,
            bmi DECIMAL(4,1) NULL,
            working_diagnosis VARCHAR(255) NULL,
            confirmed_diagnosis VARCHAR(255) NULL,
            icd10_code VARCHAR(30) NULL,
            treatment_plan TEXT NULL,
            doctor_notes TEXT NULL,
            follow_up_date DATE NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_enc_patient (tenant_id, patient_id),
            INDEX idx_enc_doctor (tenant_id, doctor_user_id),
            CONSTRAINT fk_enc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_enc_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) $e",

        // 3. Diagnostic Tests Catalog (Lab & Radiology)
        "CREATE TABLE diagnostic_tests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            code VARCHAR(30) NOT NULL,
            name VARCHAR(150) NOT NULL,
            category ENUM('hematology','biochemistry','microbiology','parasitology','radiology','ultrasound','ecg','cardiology') NOT NULL DEFAULT 'hematology',
            specimen_type VARCHAR(60) NULL,
            normal_range VARCHAR(100) NULL,
            units VARCHAR(30) NULL,
            price_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            turnaround_minutes INT UNSIGNED NOT NULL DEFAULT 60,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_diag_test (tenant_id, code),
            CONSTRAINT fk_diag_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        // 4. Diagnostic Orders (Requests from Clinicians)
        "CREATE TABLE diagnostic_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            encounter_id BIGINT UNSIGNED NULL,
            patient_id INT UNSIGNED NOT NULL,
            doctor_user_id INT UNSIGNED NULL,
            test_id INT UNSIGNED NOT NULL,
            order_no VARCHAR(30) NOT NULL,
            priority ENUM('routine','urgent','stat') NOT NULL DEFAULT 'routine',
            clinical_indication VARCHAR(255) NULL,
            status ENUM('ordered','sample_collected','processing','verified','released','cancelled') NOT NULL DEFAULT 'ordered',
            sample_collected_at DATETIME NULL,
            sample_collected_by INT UNSIGNED NULL,
            verified_at DATETIME NULL,
            verified_by INT UNSIGNED NULL,
            released_at DATETIME NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_do_patient (tenant_id, patient_id),
            INDEX idx_do_status (tenant_id, status),
            CONSTRAINT fk_do_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_do_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            CONSTRAINT fk_do_test FOREIGN KEY (test_id) REFERENCES diagnostic_tests(id) ON DELETE CASCADE
        ) $e",

        // 5. Diagnostic Results & Findings
        "CREATE TABLE diagnostic_results (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            parameter_name VARCHAR(100) NOT NULL,
            observed_value VARCHAR(255) NOT NULL,
            reference_range VARCHAR(100) NULL,
            unit VARCHAR(30) NULL,
            is_abnormal TINYINT(1) NOT NULL DEFAULT 0,
            technician_notes TEXT NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            CONSTRAINT fk_dr_order FOREIGN KEY (order_id) REFERENCES diagnostic_orders(id) ON DELETE CASCADE
        ) $e",

        // 6. Prescriptions (Clinical Orders to Pharmacy)
        "CREATE TABLE prescriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            encounter_id BIGINT UNSIGNED NULL,
            patient_id INT UNSIGNED NOT NULL,
            doctor_user_id INT UNSIGNED NOT NULL,
            prescription_no VARCHAR(30) NOT NULL,
            status ENUM('active','dispensed','partial','cancelled') NOT NULL DEFAULT 'active',
            notes VARCHAR(255) NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_rx_patient (tenant_id, patient_id),
            CONSTRAINT fk_rx_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_rx_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE prescription_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            prescription_id BIGINT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NULL,
            medicine_name VARCHAR(150) NOT NULL,
            dosage VARCHAR(60) NOT NULL,
            frequency VARCHAR(60) NOT NULL,
            duration_days INT UNSIGNED NOT NULL DEFAULT 5,
            qty_prescribed INT UNSIGNED NOT NULL,
            qty_dispensed INT UNSIGNED NOT NULL DEFAULT 0,
            instructions VARCHAR(255) NULL,
            status ENUM('pending','dispensed','unavailable') NOT NULL DEFAULT 'pending',
            created_at DATETIME NULL, updated_at DATETIME NULL,
            CONSTRAINT fk_rxi_presc FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
        ) $e",

        // 7. Wards & Inpatient Beds
        "CREATE TABLE wards (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) NOT NULL,
            type ENUM('general_male','general_female','pediatric','maternity','icu','hcu','surgical','private') NOT NULL DEFAULT 'general_male',
            floor_number INT NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_ward (tenant_id, code),
            CONSTRAINT fk_ward_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE beds (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            ward_id INT UNSIGNED NOT NULL,
            bed_number VARCHAR(30) NOT NULL,
            daily_rate_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('available','occupied','cleaning','maintenance','reserved') NOT NULL DEFAULT 'available',
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_bed (ward_id, bed_number),
            CONSTRAINT fk_bed_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE
        ) $e",

        // 8. Inpatient Admissions
        "CREATE TABLE admissions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            patient_id INT UNSIGNED NOT NULL,
            bed_id INT UNSIGNED NOT NULL,
            admitting_doctor_id INT UNSIGNED NULL,
            admission_no VARCHAR(30) NOT NULL,
            admission_date DATETIME NOT NULL,
            discharge_date DATETIME NULL,
            admission_reason VARCHAR(255) NOT NULL,
            discharge_summary TEXT NULL,
            status ENUM('admitted','discharged','transferred','deceased') NOT NULL DEFAULT 'admitted',
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_adm_patient (tenant_id, patient_id),
            CONSTRAINT fk_adm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_adm_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
            CONSTRAINT fk_adm_bed FOREIGN KEY (bed_id) REFERENCES beds(id) ON DELETE CASCADE
        ) $e",

        // 9. Billable Services Catalog & Central Patient Invoices
        "CREATE TABLE billable_services (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            category ENUM('consultation','procedure','nursing','bed_day','laboratory','radiology','dental','physiotherapy') NOT NULL DEFAULT 'consultation',
            code VARCHAR(30) NOT NULL,
            name VARCHAR(150) NOT NULL,
            price_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_bs_code (tenant_id, code),
            CONSTRAINT fk_bs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE invoices (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            patient_id INT UNSIGNED NOT NULL,
            encounter_id BIGINT UNSIGNED NULL,
            invoice_no VARCHAR(30) NOT NULL,
            subtotal_minor BIGINT NOT NULL DEFAULT 0,
            discount_minor BIGINT NOT NULL DEFAULT 0,
            tax_minor BIGINT NOT NULL DEFAULT 0,
            total_minor BIGINT NOT NULL DEFAULT 0,
            paid_minor BIGINT NOT NULL DEFAULT 0,
            status ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
            due_date DATE NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_invoice_no (tenant_id, invoice_no),
            INDEX idx_inv_patient (tenant_id, patient_id),
            CONSTRAINT fk_inv_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_inv_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE invoice_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            invoice_id BIGINT UNSIGNED NOT NULL,
            service_id INT UNSIGNED NULL,
            item_name VARCHAR(150) NOT NULL,
            qty INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price_minor BIGINT NOT NULL,
            total_minor BIGINT NOT NULL,
            created_at DATETIME NULL,
            CONSTRAINT fk_ii_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        ) $e",

        // 10. Insurance Providers & Claims
        "CREATE TABLE insurance_providers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            code VARCHAR(30) NOT NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(150) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_ins_code (tenant_id, code),
            CONSTRAINT fk_ins_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE insurance_claims (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            provider_id INT UNSIGNED NOT NULL,
            patient_id INT UNSIGNED NOT NULL,
            invoice_id BIGINT UNSIGNED NULL,
            claim_no VARCHAR(40) NOT NULL,
            policy_number VARCHAR(60) NOT NULL,
            member_name VARCHAR(120) NOT NULL,
            preauth_code VARCHAR(60) NULL,
            claim_amount_minor BIGINT NOT NULL,
            approved_amount_minor BIGINT NOT NULL DEFAULT 0,
            status ENUM('draft','submitted','under_review','approved','rejected','settled') NOT NULL DEFAULT 'draft',
            rejection_reason VARCHAR(255) NULL,
            submitted_at DATETIME NULL,
            settled_at DATETIME NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_ic_patient (tenant_id, patient_id),
            INDEX idx_ic_status (tenant_id, status),
            CONSTRAINT fk_ic_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_ic_provider FOREIGN KEY (provider_id) REFERENCES insurance_providers(id) ON DELETE CASCADE
        ) $e",
    ],
    'down' => [
        'DROP TABLE IF EXISTS insurance_claims',
        'DROP TABLE IF EXISTS insurance_providers',
        'DROP TABLE IF EXISTS invoice_items',
        'DROP TABLE IF EXISTS invoices',
        'DROP TABLE IF EXISTS billable_services',
        'DROP TABLE IF EXISTS admissions',
        'DROP TABLE IF EXISTS beds',
        'DROP TABLE IF EXISTS wards',
        'DROP TABLE IF EXISTS prescription_items',
        'DROP TABLE IF EXISTS prescriptions',
        'DROP TABLE IF EXISTS diagnostic_results',
        'DROP TABLE IF EXISTS diagnostic_orders',
        'DROP TABLE IF EXISTS diagnostic_tests',
        'DROP TABLE IF EXISTS clinical_encounters',
    ],
];
