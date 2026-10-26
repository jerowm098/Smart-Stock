# Smart-Stock

Intelligent inventory system for a hardware store: shared catalogue, POS checkout,
receiving, and demand-based restocking suggestions.

## Structure

```
Smart-Stock/
├── backend/     Laravel 12 API + MySQL (XAMPP MariaDB locally, Supabase in prod)
│   ├── app/           Controllers, Models, Services, Console commands
│   ├── config/        database.php (mysql local / pgsql prod)
│   ├── database/      migrations + seeders
│   ├── public/        front controller (index.php) for the Blade app
│   ├── resources/     Blade views (legacy UI, kept until Phase 4)
│   ├── routes/        web.php (API + page routes)
│   └── .env           DB credentials (never committed)
├── frontend/    React 19 + Vite SPA (the new UI, talks to the backend API)
│   └── src/
│       ├── lib/api.js       fetch wrapper (session cookies + CSRF)
│       ├── auth/            session context
│       ├── layouts/         sidebar + topbar shell
│       └── pages/           Home, Login, admin/*, cashier/*
├── docs/        Legacy notes, diagrams and workflow documentation
├── render.yaml  Render Blueprint (production deploy on Supabase PostgreSQL)
└── LICENSE
```

## Local development

```bash
# 1. Start XAMPP MySQL (Apache only needed for the legacy Blade app)

# 2. Backend API (terminal 1)
cd backend
php artisan serve            # http://127.0.0.1:8000 (JSON only)

# 3. Frontend (terminal 2)
npm run dev                  # http://localhost:5173 (the website)
```

Or from the repo root: `npm run dev` (frontend) and `npm run dev:backend` (API).

## Useful commands

```bash
php artisan db:copy-from-supabase --force   # refresh local data from Supabase
php artisan migrate --force                 # run new migrations
npm run build:spa                           # production build of the SPA
```

## Documentation

- [`backend/DEPLOYMENT.md`](backend/DEPLOYMENT.md) — production deploy (Render + Supabase)
- [`backend/LARAVEL.md`](backend/LARAVEL.md) — Laravel notes
- [`docs/`](docs/) — workflow diagrams and reference material
