-- ============================================================
-- dev.sql
-- Smart-Stock PostgreSQL Schema (Supabase)
-- ============================================================
--
-- WORKFLOW:
--   1. Run migrations locally (SQLite):
--        php artisan migrate
--
--   2. Export SQLite data (optional backup):
--        sqlite3 database/database.sqlite ".dump" > backup.sql
--
--   3. Go to Supabase Dashboard → SQL Editor:
--      https://dashboard.supabase.com
--
--   4. Paste this file and click "Run" to create tables
--
--   5. (Optional) Import data from backup.sql
--
-- This matches exactly with database/migrations/
-- ============================================================

-- -----------------------------------------------------------
-- 1. Users & Authentication
-- -----------------------------------------------------------
-- Syncs with: 0001_01_01_000000_create_users_table.php

CREATE TABLE IF NOT EXISTS public.users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP WITH TIME ZONE NULL,
    password        VARCHAR(255) NOT NULL,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.password_reset_tokens (
    email         VARCHAR(255) NOT NULL PRIMARY KEY,
    token         VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- -----------------------------------------------------------
-- 2. Sessions
-- -----------------------------------------------------------
-- Syncs with: 0001_01_01_000000_create_users_table.php (sessions section)

CREATE TABLE IF NOT EXISTS public.sessions (
    id            VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id       BIGINT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS sessions_user_id_index ON public.sessions(user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON public.sessions(last_activity);

-- -----------------------------------------------------------
-- 3. Cache
-- -----------------------------------------------------------
-- Syncs with: 0001_01_01_000001_create_cache_table.php

CREATE TABLE IF NOT EXISTS public.cache (
    key        VARCHAR(255) NOT NULL PRIMARY KEY,
    value      TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS cache_key_index ON public.cache(key);

CREATE TABLE IF NOT EXISTS public.cache_locks (
    key        VARCHAR(255) NOT NULL PRIMARY KEY,
    owner      VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

-- -----------------------------------------------------------
-- 4. Jobs
-- -----------------------------------------------------------
-- Syncs with: 0001_01_01_000002_create_jobs_table.php

CREATE TABLE IF NOT EXISTS public.jobs (
    id          BIGSERIAL PRIMARY KEY,
    queue       VARCHAR(255) NOT NULL,
    payload     TEXT NOT NULL,
    attempts    SMALLINT NOT NULL DEFAULT 0,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at  INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS jobs_queue_index ON public.jobs(queue);

CREATE TABLE IF NOT EXISTS public.job_batches (
    id             VARCHAR(255) NOT NULL PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    total_jobs     INTEGER NOT NULL,
    pending_jobs   INTEGER NOT NULL,
    failed_jobs    INTEGER NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options        TEXT NULL,
    cancelled_at   INTEGER NULL,
    created_at     INTEGER NOT NULL,
    finished_at    INTEGER NULL
);

CREATE TABLE IF NOT EXISTS public.failed_jobs (
    id          BIGSERIAL PRIMARY KEY,
    uuid        VARCHAR(255) NOT NULL UNIQUE,
    connection  TEXT NOT NULL,
    queue       TEXT NOT NULL,
    payload     TEXT NOT NULL,
    exception   TEXT NOT NULL,
    failed_at   TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- -----------------------------------------------------------
-- 5. Products (inventory data stored in Supabase via PostgREST)
-- -----------------------------------------------------------
-- Syncs with: 2026_09_17_181118_create_products_table.php

CREATE TABLE IF NOT EXISTS public.products (
    id                  BIGSERIAL PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    sku                 VARCHAR(100) NOT NULL UNIQUE,
    category            VARCHAR(100) NULL,
    price               DECIMAL(10, 2) NOT NULL DEFAULT 0,
    current_stock       INTEGER NOT NULL DEFAULT 0,
    reorder_threshold   INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at          TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- -----------------------------------------------------------
-- 6. Alerts (low-stock notifications)
-- -----------------------------------------------------------
-- Syncs with: 2026_09_17_181119_create_alerts_table.php

CREATE TABLE IF NOT EXISTS public.alerts (
    id            BIGSERIAL PRIMARY KEY,
    product_id    BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    severity      VARCHAR(20) NOT NULL DEFAULT 'warning',
    message       TEXT NOT NULL,
    is_resolved   BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS alerts_product_id_index ON public.alerts(product_id);
CREATE INDEX IF NOT EXISTS alerts_is_resolved_index ON public.alerts(is_resolved);

-- -----------------------------------------------------------
-- 7. Migrations (Laravel migration tracker)
-- -----------------------------------------------------------
-- Required for php artisan migrate to work with pgsql

CREATE TABLE IF NOT EXISTS public.migrations (
    id          SERIAL PRIMARY KEY,
    migration   VARCHAR(255) NOT NULL,
    batch       INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS migrations_migration_index ON public.migrations(migration);

-- -----------------------------------------------------------
-- 8. Row Level Security (RLS) Policies
-- -----------------------------------------------------------

-- Enable RLS on all tables
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.password_reset_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.alerts ENABLE ROW LEVEL SECURITY;

-- Users: service_role can do everything (for Laravel backend)
DROP POLICY IF EXISTS "allow_service_role_all_users" ON public.users;
CREATE POLICY "allow_service_role_all_users"
    ON public.users FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

-- Password reset tokens: service_role access
DROP POLICY IF EXISTS "allow_service_role_tokens" ON public.password_reset_tokens;
CREATE POLICY "allow_service_role_tokens"
    ON public.password_reset_tokens FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

-- Sessions: service_role access (Laravel manages sessions server-side)
DROP POLICY IF EXISTS "allow_service_role_sessions" ON public.sessions;
CREATE POLICY "allow_service_role_sessions"
    ON public.sessions FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

-- Products: authenticated users can read, service_role can write
DROP POLICY IF EXISTS "allow_authenticated_read_products" ON public.products;
CREATE POLICY "allow_authenticated_read_products"
    ON public.products FOR SELECT
    TO authenticated
    USING (true);

DROP POLICY IF EXISTS "allow_service_role_products" ON public.products;
CREATE POLICY "allow_service_role_products"
    ON public.products FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

-- Alerts: authenticated users can read, service_role can manage
DROP POLICY IF EXISTS "allow_authenticated_read_alerts" ON public.alerts;
CREATE POLICY "allow_authenticated_read_alerts"
    ON public.alerts FOR SELECT
    TO authenticated
    USING (true);

DROP POLICY IF EXISTS "allow_service_role_alerts" ON public.alerts;
CREATE POLICY "allow_service_role_alerts"
    ON public.alerts FOR ALL
    TO service_role
    USING (true)
    WITH CHECK (true);

-- -----------------------------------------------------------
-- 9. Sample Data (optional - uncomment to seed)
-- -----------------------------------------------------------

-- INSERT INTO public.products (name, sku, category, price, current_stock, reorder_threshold) VALUES
--     ('Wireless Mouse', 'WM-001', 'Electronics', 299.00, 15, 5),
--     ('USB-C Cable', 'UC-002', 'Accessories', 149.00, 3, 10),
--     ('Mechanical Keyboard', 'MK-003', 'Electronics', 1499.00, 8, 3);
