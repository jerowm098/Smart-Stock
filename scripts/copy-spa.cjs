/**
 * Copies frontend/dist -> backend/public/app after a production build.
 *
 * Bakit kailangan: ang Laravel backend (port 8000 / Render) ang nagsi-serve
 * ng built SPA sa "/" at deep links. Ang `npm run dev` (:5173) ay live na
 * hindi na kailangan nito — ito ay para sa served/built version lamang.
 *
 * Usage: node scripts/copy-spa.cjs  (tinatawag na ng `npm run build:spa`)
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const dist = path.join(root, 'frontend', 'dist');
const dest = path.join(root, 'backend', 'public', 'app');

if (!fs.existsSync(path.join(dist, 'index.html'))) {
    console.error('ERROR: frontend/dist/index.html not found. Run `npm run build:spa` first.');
    process.exit(1);
}

fs.rmSync(dest, { recursive: true, force: true });
fs.cpSync(dist, dest, { recursive: true });

console.log('SPA copied: frontend/dist -> backend/public/app');
