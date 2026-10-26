<?php
// Pharmacy: products, batches (expiry/FEFO), stock ledger, POS sales, cash sessions.
// All money columns are integer minor units (e.g. cents).
$e = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    'up' => [
        "CREATE TABLE product_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_pc (tenant_id, name),
            CONSTRAINT fk_pc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE suppliers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            contact_person VARCHAR(100) NULL,
            phone VARCHAR(30) NULL,
            email VARCHAR(150) NULL,
            address VARCHAR(255) NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            CONSTRAINT fk_sup_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NULL,
            name VARCHAR(150) NOT NULL,
            generic_name VARCHAR(150) NULL,
            barcode VARCHAR(60) NULL,
            sku VARCHAR(60) NULL,
            form VARCHAR(40) NULL COMMENT 'tablet, syrup, capsule, injection, cream...',
            strength VARCHAR(40) NULL,
            unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
            schedule ENUM('otc','pom','controlled') NOT NULL DEFAULT 'otc',
            price_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            reorder_level INT UNSIGNED NOT NULL DEFAULT 10,
            stock_qty INT NOT NULL DEFAULT 0 COMMENT 'denormalised sum of batches.qty_on_hand',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_product_barcode (tenant_id, barcode),
            INDEX idx_product_name (tenant_id, name),
            CONSTRAINT fk_prod_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL
        ) $e",

        "CREATE TABLE product_batches (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            supplier_id INT UNSIGNED NULL,
            batch_no VARCHAR(60) NOT NULL,
            expiry_date DATE NOT NULL,
            cost_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            qty_received INT UNSIGNED NOT NULL,
            qty_on_hand INT NOT NULL,
            received_at DATETIME NOT NULL,
            received_by INT UNSIGNED NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_batch_fefo (tenant_id, product_id, expiry_date),
            CONSTRAINT fk_batch_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_batch_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            CONSTRAINT fk_batch_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        ) $e",

        // Immutable stock ledger. Never edit quantities without a movement row.
        "CREATE TABLE stock_movements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            batch_id INT UNSIGNED NULL,
            type ENUM('receive','sale','return','adjust','expire','void') NOT NULL,
            qty INT NOT NULL COMMENT 'signed: + in, - out',
            reference_type VARCHAR(30) NULL,
            reference_id BIGINT UNSIGNED NULL,
            note VARCHAR(255) NULL,
            user_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_sm_product (tenant_id, product_id, created_at),
            CONSTRAINT fk_sm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE cash_sessions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NOT NULL,
            status ENUM('open','closed') NOT NULL DEFAULT 'open',
            opening_float_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            total_sales_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            cash_sales_minor BIGINT UNSIGNED NOT NULL DEFAULT 0,
            expected_cash_minor BIGINT NULL,
            counted_cash_minor BIGINT NULL,
            opened_at DATETIME NOT NULL,
            closed_at DATETIME NULL,
            note VARCHAR(255) NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_cs_user (tenant_id, user_id, status),
            CONSTRAINT fk_cs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE sales (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            cash_session_id INT UNSIGNED NULL,
            receipt_no VARCHAR(30) NOT NULL,
            patient_id INT UNSIGNED NULL,
            customer_name VARCHAR(150) NULL,
            customer_phone VARCHAR(30) NULL,
            prescription_ref VARCHAR(100) NULL,
            subtotal_minor BIGINT NOT NULL DEFAULT 0,
            discount_minor BIGINT NOT NULL DEFAULT 0,
            tax_minor BIGINT NOT NULL DEFAULT 0,
            total_minor BIGINT NOT NULL DEFAULT 0,
            paid_minor BIGINT NOT NULL DEFAULT 0,
            change_minor BIGINT NOT NULL DEFAULT 0,
            status ENUM('completed','voided') NOT NULL DEFAULT 'completed',
            void_reason VARCHAR(255) NULL,
            user_id INT UNSIGNED NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_receipt (tenant_id, receipt_no),
            INDEX idx_sales_date (tenant_id, created_at),
            CONSTRAINT fk_sale_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_sale_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
        ) $e",

        "CREATE TABLE sale_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            sale_id BIGINT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            batch_id INT UNSIGNED NULL,
            name VARCHAR(190) NOT NULL,
            qty INT UNSIGNED NOT NULL,
            unit_price_minor BIGINT NOT NULL,
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            tax_minor BIGINT NOT NULL DEFAULT 0,
            line_total_minor BIGINT NOT NULL,
            CONSTRAINT fk_si_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE sale_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            sale_id BIGINT UNSIGNED NOT NULL,
            method VARCHAR(30) NOT NULL COMMENT 'cash, mpesa, mtn_momo, airtel_money, card, insurance, paystack, flutterwave',
            amount_minor BIGINT NOT NULL,
            reference VARCHAR(100) NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT fk_sp_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
        ) $e",
    ],
    'down' => [
        'DROP TABLE IF EXISTS sale_payments', 'DROP TABLE IF EXISTS sale_items', 'DROP TABLE IF EXISTS sales',
        'DROP TABLE IF EXISTS cash_sessions', 'DROP TABLE IF EXISTS stock_movements', 'DROP TABLE IF EXISTS product_batches',
        'DROP TABLE IF EXISTS products', 'DROP TABLE IF EXISTS suppliers', 'DROP TABLE IF EXISTS product_categories',
    ],
];
