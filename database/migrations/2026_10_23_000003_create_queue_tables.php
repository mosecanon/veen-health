<?php
// Queue management / ticketing / call displays with audio.
$e = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

return [
    'up' => [
        "CREATE TABLE queues (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            name VARCHAR(80) NOT NULL,
            prefix VARCHAR(5) NOT NULL,
            color VARCHAR(9) NOT NULL DEFAULT '#14b8a6',
            description VARCHAR(255) NULL,
            last_number INT UNSIGNED NOT NULL DEFAULT 0,
            last_reset_on DATE NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            UNIQUE KEY uq_queue_prefix (tenant_id, branch_id, prefix),
            CONSTRAINT fk_queue_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",

        "CREATE TABLE service_points (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            queue_id INT UNSIGNED NOT NULL,
            name VARCHAR(80) NOT NULL,
            announce_as VARCHAR(120) NULL,
            staff_user_id INT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_sp_tenant (tenant_id, queue_id),
            CONSTRAINT fk_sp_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_sp_queue FOREIGN KEY (queue_id) REFERENCES queues(id) ON DELETE CASCADE,
            CONSTRAINT fk_sp_user FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) $e",

        "CREATE TABLE tickets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            queue_id INT UNSIGNED NOT NULL,
            patient_id INT UNSIGNED NULL,
            number INT UNSIGNED NOT NULL,
            code VARCHAR(15) NOT NULL,
            customer_name VARCHAR(150) NULL,
            priority TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 normal, 1 priority (elderly/pregnant/disabled), 2 emergency',
            status ENUM('waiting','called','serving','completed','skipped','cancelled') NOT NULL DEFAULT 'waiting',
            service_point_id INT UNSIGNED NULL,
            served_by INT UNSIGNED NULL,
            call_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            issued_on DATE NOT NULL,
            called_at DATETIME NULL,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            transferred_from_id BIGINT UNSIGNED NULL,
            notes VARCHAR(255) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            INDEX idx_ticket_queue (tenant_id, queue_id, status, issued_on),
            INDEX idx_ticket_sp (tenant_id, service_point_id, status),
            CONSTRAINT fk_ticket_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            CONSTRAINT fk_ticket_queue FOREIGN KEY (queue_id) REFERENCES queues(id) ON DELETE CASCADE,
            CONSTRAINT fk_ticket_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
            CONSTRAINT fk_ticket_sp FOREIGN KEY (service_point_id) REFERENCES service_points(id) ON DELETE SET NULL
        ) $e",

        // Event log: drives displays (announcements) and wait-time analytics.
        "CREATE TABLE ticket_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            ticket_id BIGINT UNSIGNED NOT NULL,
            type ENUM('issued','called','recalled','started','completed','skipped','transferred','cancelled') NOT NULL,
            service_point_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_te_tenant (tenant_id, id),
            CONSTRAINT fk_te_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
        ) $e",

        // A TV/screen at a waiting area. Public URL authenticated by unguessable token.
        "CREATE TABLE displays (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id INT UNSIGNED NOT NULL,
            branch_id INT UNSIGNED NULL,
            name VARCHAR(80) NOT NULL,
            token CHAR(40) NOT NULL UNIQUE,
            queue_ids JSON NULL COMMENT 'null = all queues',
            voice_enabled TINYINT(1) NOT NULL DEFAULT 1,
            voice_lang VARCHAR(10) NOT NULL DEFAULT 'en-GB',
            announcement_template VARCHAR(255) NOT NULL DEFAULT 'Ticket {code}, please proceed to {room}',
            chime TINYINT(1) NOT NULL DEFAULT 1,
            repeat_count TINYINT UNSIGNED NOT NULL DEFAULT 2,
            ticker_text VARCHAR(255) NULL,
            created_at DATETIME NULL, updated_at DATETIME NULL,
            CONSTRAINT fk_display_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) $e",
    ],
    'down' => ['DROP TABLE IF EXISTS displays', 'DROP TABLE IF EXISTS ticket_events', 'DROP TABLE IF EXISTS tickets', 'DROP TABLE IF EXISTS service_points', 'DROP TABLE IF EXISTS queues'],
];
