import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Standalone SPA frontend.
 *
 * Laravel stays the API only; this app owns the whole UI. Both the dev
 * server (`npm run dev`, port 5173) and the production preview
 * (`npx vite preview`, port 4173) proxy `/api` to the Laravel backend so
 * the browser sees a single origin (cookies + CSRF work without any CORS
 * configuration).
 *
 * Backend target is configurable:
 *   VITE_API_PROXY=http://127.0.0.1:8000   (php artisan serve — default)
 *
 * Asset base:
 *   Local builds use '/' so `npx vite preview` serves the app at the root
 *   (http://localhost:4173). The Docker production build needs the assets
 *   under /app/ (nginx serves /app/assets inside the container) — it runs
 *   `npm run build:spa`, which passes --base=/app/ on top of this config.
 */
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const target = env.VITE_API_PROXY || 'http://127.0.0.1:8000';

    // Shared by the dev server and the preview server.
    const proxy = {
        '/api': {
            target,
            changeOrigin: true,
        },
    };

    return {
        plugins: [react()],
        base: '/',
        server: {
            host: 'localhost',
            port: 5173,
            strictPort: true,
            proxy,
        },
        // Serves the production build (dist/) without Apache or nginx —
        // the carelink-style local flow:
        //   cd frontend && npm run build && npx vite preview
        // Same /api proxy as the dev server, so login + CSRF just work.
        preview: {
            port: 4173,
            strictPort: true,
            proxy,
        },
    };
});
