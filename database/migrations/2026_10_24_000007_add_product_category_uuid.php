<?php
// Migration 000007: Add UUID to products and product_categories
return [
    'up' => [
        "ALTER TABLE products ADD COLUMN uuid VARCHAR(36) NULL UNIQUE AFTER id",
        "ALTER TABLE product_categories ADD COLUMN uuid VARCHAR(36) NULL UNIQUE AFTER id",
    ],
    'down' => [
        "ALTER TABLE products DROP COLUMN uuid",
        "ALTER TABLE product_categories DROP COLUMN uuid",
    ]
];
