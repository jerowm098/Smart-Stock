# Database Setup Guide — Smart-Stock

## Overview

This project uses **two database strategies**:
1. **Local Development** → SQLite (via migrations)
2. **Production** → Supabase PostgreSQL (via REST API)

---

## Local Development (SQLite)

### Step 1: Setup Environment
```bash
# Copy example env file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### Step 2: Run Migrations
```bash
# This creates database/database.sqlite automatically
php artisan migrate
```

The following tables are created:
| Table | Purpose |
|-------|---------|
| `users` | User authentication |
| `password_reset_tokens` | Password recovery |
| `sessions` | Session storage |
| `products` | Inventory data |
| `alerts` | Low-stock notifications |
| `cache` / `cache_locks` | Caching system |
| `jobs` / `job_batches` / `failed_jobs` | Queue system |

### Step 3: (Optional) Seed Sample Data
```bash
php artisan db:seed
```

---

## Production Deployment (Supabase)

### Step 1: Set Up Supabase Project
1. Go to [supabase.com](https://supabase.com) → New Project
2. Note your project URL and API keys from **Settings → API**

### Step 2: Create Tables in Supabase
1. Go to **SQL Editor** in Supabase dashboard
2. Open [`database/dev.sql`](../database/dev.sql)
3. Paste the entire file and click **"Run"**

This creates all tables with proper RLS policies.

### Step 3: Update Environment Variables
In your `.env` (or deployment dashboard), set:
```
DB_CONNECTION=sqlite
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_KEY=your-service-role-key
```

### Step 4: Import Data (if migrating from SQLite)
```bash
# Export SQLite data
sqlite3 database/database.sqlite ".dump" > backup.sql

# Import to Supabase via SQL Editor
# (convert SQLite syntax to PostgreSQL if needed)
```

---

## Migration Workflow

```
┌─────────────────────────────────────────────────────────┐
│  Fresh Clone from GitHub                                │
├─────────────────────────────────────────────────────────┤
│  1. copy .env.example → .env                            │
│  2. php artisan key:generate                            │
│  3. php artisan migrate  ← creates SQLite + tables      │
│  4. (Optional) Add sample data                          │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Deploy to Supabase                                     │
├─────────────────────────────────────────────────────────┤
│  1. Run database/dev.sql in Supabase SQL Editor         │
│  2. Set SUPABASE_* env vars                             │
│  3. Products flow through Supabase REST API             │
│  4. Auth stays local (SQLite) or use Supabase PG        │
└─────────────────────────────────────────────────────────┘
```

---

## Architecture Diagram

```
┌──────────────────────────────────────────────────────┐
│                   Your Computer                       │
│                                                      │
│  ┌─────────────┐    ┌──────────────┐                │
│  │  Laravel     │    │  SQLite      │                │
│  │  (Local Dev) │    │  database/   │                │
│  │              │    │  database.sq │                │
│  └──────┬──────┘    └──────────────┘                │
│         │                                            │
│         │ HTTP POST/GET                              │
│         ▼                                            │
│  ┌─────────────┐    ┌──────────────┐                │
│  │  Laravel     │◄───│  Supabase    │                │
│  │  (Prod)     │    │  REST API    │                │
│  └──────┬──────┘    └──────┬───────┘                │
│         │                  │                         │
│         └──────────────────┘                         │
│                     │                                │
│                     ▼                                │
│            ┌────────────────┐                        │
│            │ PostgreSQL     │                        │
│            │ (Cloud DB)     │                        │
│            └────────────────┘                        │
└──────────────────────────────────────────────────────┘
```

---

## Quick Commands Reference

| Command | Purpose |
|---------|---------|
| `php artisan migrate` | Run migrations (create tables) |
| `php artisan migrate:rollback` | Undo last migration |
| `php artisan migrate:fresh` | Drop all tables & re-run |
| `php artisan db:seed` | Insert sample data |
| `sqlite3 database/database.sqlite "SELECT * FROM products;"` | Query SQLite directly |

---

## Important Notes

- **`.gitignore`** includes `database/database.sqlite` — don't commit your local DB
- **Migration files** are the source of truth for table structure
- **dev.sql** is exported from migrations for Supabase setup
- Use **SQLite locally**, **Supabase for production data**
