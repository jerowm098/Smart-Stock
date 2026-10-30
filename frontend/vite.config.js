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
        // DEV: ang buong UI ay pinapatakbo ng Vite dev server (localhost:5173)
        // at walang frontend na sine-serve ang Laravel — pure API lang ito.
        //
        // PROD: ang built SPA ay nakalagay sa backend/public/app/ at ang
        // NGINX (hindi ang Laravel) ang nagse-serve nito, kaya naka-/app/
        // ang asset base. Tingnan ang backend/docker/nginx/default.conf.template.
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
