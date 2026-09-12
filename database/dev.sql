-- ============================================================
-- dev.sql
-- Supabase schema for the "inputs" app
--
-- HOW TO RUN:
--   1. Go to your Supabase project dashboard
--   2. Open the "SQL Editor" (left sidebar)
--   3. Paste this whole file and click "Run"
--
-- This creates the `inputs` table used by the Laravel app
-- (data is stored ONLY in Supabase, never in Laravel's DB).
-- ============================================================

-- Create the inputs table
create table if not exists public.inputs (
    id          bigint generated always as identity primary key,
    first_name  text not null,
    last_name   text not null,
    age         integer not null check (age > 0 and age <= 150),
    address     text not null,
    created_at  timestamptz not null default now()
);

-- Turn on Row Level Security (Supabase keeps this on by default)
alter table public.inputs enable row level security;

-- Allow anyone to insert rows (public form, no auth needed)
drop policy if exists "inputs_allow_insert" on public.inputs;
create policy "inputs_allow_insert"
on public.inputs for insert
to anon, authenticated
with check (true);

-- Allow anyone to select rows
drop policy if exists "inputs_allow_select" on public.inputs;
create policy "inputs_allow_select"
on public.inputs for select
to anon, authenticated
using (true);

-- ============================================================
-- Seed data (optional) - run these to have sample rows
-- ============================================================
insert into public.inputs (first_name, last_name, age, address) values
('Juan', 'Dela Cruz', 25, '123 Mabini St., Manila'),
('Maria', 'Santos', 30, '456 Rizal Ave., Quezon City');