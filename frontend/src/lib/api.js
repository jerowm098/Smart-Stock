/**
 * Tiny fetch wrapper for the Laravel API.
 *
 * - `credentials: 'include'` so the Laravel session cookie travels with every
 *   request (same-origin thanks to the Vite proxy).
 * - Laravel's web routes verify CSRF, so every non-GET request resends the
 *   XSRF-TOKEN cookie as an X-XSRF-TOKEN header (Laravel decrypts it).
 * - `Accept: application/json` makes the auth middleware answer 401 JSON
 *   instead of redirecting to an HTML login page.
 */

const BASE = import.meta.env.VITE_API_BASE || '';

function readCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[1]) : null;
}

/** Make sure a CSRF cookie exists before the first state-changing request. */
async function ensureCsrfCookie() {
    if (readCookie('XSRF-TOKEN')) {
        return;
    }

    await fetch(`${BASE}/api/auth/user`, {
        credentials: 'include',
        headers: { Accept: 'application/json' },
    });
}

export async function api(path, { method = 'GET', body, headers = {}, signal } = {}) {
    if (method !== 'GET' && method !== 'HEAD') {
        await ensureCsrfCookie();
    }

    const finalHeaders = { Accept: 'application/json', ...headers };
    const token = readCookie('XSRF-TOKEN');

    if (token) {
        finalHeaders['X-XSRF-TOKEN'] = token;
    }

    if (body !== undefined) {
        finalHeaders['Content-Type'] = 'application/json';
    }

    const response = await fetch(`${BASE}${path}`, {
        method,
        credentials: 'include',
        headers: finalHeaders,
        body: body === undefined ? undefined : JSON.stringify(body),
        signal,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error(data?.message || `Request failed (${response.status})`);
        error.status = response.status;
        error.errors = data?.errors ?? null;
        error.payload = data;
        throw error;
    }

    return data;
}

/** Landing page for each role after signing in. */
export function homeFor(user) {
    return user?.role === 'admin' ? '/products' : '/pos';
}
