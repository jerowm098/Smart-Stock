import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Standalone SPA frontend.
 *
 * Laravel stays the API only; this app owns the whole UI. During development
 * `/api` is proxied to the Laravel backend so the browser sees a single
 * origin (cookies + CSRF work without any CORS configuration).
 *
 * Backend target is configurable:
 *   VITE_API_PROXY=http://127.0.0.1:8000                      (php artisan serve)
 *   VITE_API_PROXY=http://localhost/Smart-Stock/public        (XAMPP Apache)
 */
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const target = env.VITE_API_PROXY || 'http://127.0.0.1:8000';

    return {
        // Production build is copied to backend/public/app/, served by the
        // Laravel container at /app/ on the same origin as the API.
        // Dev keeps the plain root so localhost:5173 stays clean.
        base: mode === 'production' ? '/app/' : '/',
        plugins: [react()],
        server: {
            host: 'localhost',
            port: 5173,
            strictPort: true,
            proxy: {
                '/api': {
                    target,
                    changeOrigin: true,
                },
            },
        },
    };
});
