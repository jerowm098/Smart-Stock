# Smart-Stock

Intelligent inventory system for a hardware store: shared catalogue, POS checkout,
receiving, and demand-based restocking suggestions.

## Architecture

```
Browser (React SPA)  ──HTTP/JSON──►  Laravel API  ──PDO──►  MySQL / PostgreSQL
```

- **`backend/`** — Laravel 12 **pure JSON API** (walang Blade pages).
  Auth ay session cookie + CSRF (same-origin ang SPA at API sa production).
- **`frontend/`** — React 19 + Vite SPA. Ang buong UI ay nito.
- **One deployable**: sa production, ang built SPA ay nakalagay sa
  `backend/public/app/` at sine-serve ng Laravel sa `/` — isang Render
  service lang ang kailangan, walang CORS configuration.

## Structure

```
Smart-Stock/
├── backend/               Laravel 12 JSON API
│   ├── app/                 Controllers, Models, Services, Middleware
│   ├── bootstrap/  config/  app config (database.php: mysql local / pgsql prod)
│   ├── database/            migrations + seeders
│   ├── docker/              nginx, php-fpm, supervisor, entrypoint
│   ├── public/              front controller + built SPA (public/app, gitignored)
│   ├── routes/web.php       lahat ng API endpoints + SPA catch-all
│   ├── storage/  vendor/
│   ├── .env                 DB credentials (never committed)
│   └── DEPLOYMENT.md        Render + Supabase deploy guide
├── frontend/              React 19 + Vite SPA (ang buong UI)
│   └── src/
│       ├── lib/api.js        fetch wrapper (session + CSRF + helpers)
│       ├── auth/             session context
│       ├── components/       Modal, Pagination, guards, feedback
│       ├── layouts/          sidebar + topbar shell
│       └── pages/            Home, Login, admin/* (7), cashier/Pos
├── docs/                  legacy notes, diagrams, BRD extracts
├── Dockerfile             multi-stage: composer + SPA build + runtime
├── render.yaml            Render Blueprint (deploy: Supabase PostgreSQL)
└── README.md
```

## Local development

```bash
# 1. Start XAMPP MySQL (hindi na kailangan ang Apache)

# 2. Backend API (terminal 1)
cd backend
php artisan serve            # http://127.0.0.1:8000 (JSON only)

# 3. Frontend (terminal 2)
npm run dev                  # http://localhost:5173 (buong website)
```

## Production build & deploy

```bash
npm run build:spa            # frontend/dist -> kinokopya sa backend/public/app sa Docker build
```

Ang `render.yaml` ay nagbe-build ng image mula sa repo root:
composer install + SPA build + nginx/php-fpm. Isang service lang.

## Useful commands

```bash
php artisan db:copy-from-supabase --force   # refresh local data from Supabase (sa backend/)
php artisan migrate --force                 # run new migrations (sa backend/)
```

## Documentation

- [`backend/DEPLOYMENT.md`](backend/DEPLOYMENT.md) — production deploy (Render + Supabase)
- [`docs/`](docs/) — workflow diagrams at reference material
