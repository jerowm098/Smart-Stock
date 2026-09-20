-- ============================================================
-- dev.sql
-- Smart-Stock PostgreSQL Schema (Supabase)
--
-- HOW TO RUN:
--   1. Go to your Supabase project dashboard
--      https://dashboard.supabase.com
--   2. Open the "SQL Editor" (left sidebar)
--   3. Paste this whole file and click "Run"
--
-- This creates the tables needed for:
--   - User authentication (users, password_reset_tokens)
--   - Session storage (sessions)
--   - Product inventory (products, alerts)
--
-- Laravel migrations will also run these automatically on deploy,
-- but running this manually ensures tables exist immediately.
-- ============================================================

-- -----------------------------------------------------------
-- 1. Users & Authentication
-- -----------------------------------------------------------

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
-- 2. Sessions (for Laravel database session driver)
-- -----------------------------------------------------------

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
-- 3. Products (inventory data stored in Supabase via PostgREST)
-- -----------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.products (
    id                    BIGSERIAL PRIMARY KEY,
    name                  VARCHAR(255) NOT NULL,
    sku                   VARCHAR(100) NOT NULL UNIQUE,
    category              VARCHAR(100) NULL,
    price                 DECIMAL(10, 2) NOT NULL DEFAULT 0,
    current_stock         INTEGER NOT NULL DEFAULT 0,
    reorder_threshold     INTEGER NOT NULL DEFAULT 5,
    created_at            TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at            TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- -----------------------------------------------------------
-- 4. Alerts (low-stock notifications)
-- -----------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.alerts (
    id            BIGSERIAL PRIMARY KEY,
    product_id    BIGINT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    message       TEXT NOT NULL,
    type          VARCHAR(20) NOT NULL DEFAULT 'low',  -- 'low' or 'critical'
    is_read       BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS alerts_product_id_index ON public.alerts(product_id);
CREATE INDEX IF NOT EXISTS alerts_is_read_index ON public.alerts(is_read);

-- -----------------------------------------------------------
-- 5. Row Level Security (RLS) Policies
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
-- 6. Optional: Seed sample data
-- -----------------------------------------------------------
-- Uncomment below to add sample products after running the script

-- INSERT INTO public.products (name, sku, category, price, current_stock, reorder_threshold) VALUES
--     ('Wireless Mouse', 'WM-001', 'Electronics', 299.00, 15, 5),
--     ('USB-C Cable', 'UC-002', 'Accessories', 149.00, 3, 10),
--     ('Mechanical Keyboard', 'MK-003', 'Electronics', 1499.00, 8, 3);
