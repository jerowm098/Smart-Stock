-- Smart-Stock Database Schema
-- SQLite compatible (local development database)
--
-- NOTE: This file is a reference schema only. The live database is managed
-- by Laravel migrations (see database/migrations/). To rebuild the local
-- database from scratch:
--
--   del database\database.sqlite
--   php artisan migrate --force
--
-- The migrations create the users, products, alerts, sessions, cache, and
-- jobs tables, and add the username, role, and products.user_id columns.

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'cashier',
    email_verified_at DATETIME,
    remember_token VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME
);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INTEGER,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Products table (each product belongs to the user who created it)
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    current_stock INTEGER NOT NULL DEFAULT 0,
    reorder_threshold INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    severity VARCHAR(50) NOT NULL DEFAULT 'warning',
    message TEXT NOT NULL,
    is_resolved BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_products_user_id ON products(user_id);
CREATE INDEX IF NOT EXISTS idx_products_sku ON products(sku);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_products_current_stock ON products(current_stock);
CREATE INDEX IF NOT EXISTS idx_alerts_product_id ON alerts(product_id);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
