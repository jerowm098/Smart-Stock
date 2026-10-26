import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // Full page reload whenever a Blade view changes. The views keep
            // their CSS inline, so this is what makes `npm run dev` useful
            // even without moving styles into resources/css yet.
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // This Vite instance only serves the legacy Blade assets (npm run
        // dev:blade). The React SPA lives in frontend/ and owns port 5173,
        // so this one steps aside to 5174 to avoid a strictPort clash.
        host: 'localhost',
        port: 5174,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
