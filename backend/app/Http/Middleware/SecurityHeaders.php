<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security hardening (OWASP ZAP findings) — HTTP response headers.
 *
 * Laravel dito ay PURE JSON API, kaya pinaka-mahigpit na policy ang pwede:
 * walang HTML, walang script, walang frame. Ang SPA shell (index.html) ay
 * may sariling CSP sa nginx (production) at sa vite preview (local).
 *
 * Tinutugunan nito ang mga ZAP passive alerts:
 *   - 10021 X-Content-Type-Options Header Missing
 *   - 10020 Missing Anti-clickjacking Header
 *   - 10038 Content Security Policy (CSP) Header Not Set
 *   - 10037 Server Leaks Information via "X-Powered-By"
 *   - 10049 / 10015 Cache-control directives on sensitive responses
 *   - 10035 Strict-Transport-Security Header Not Set (HTTPS lang)
 *   - 90004 Insufficient Site Isolation Against Spectre (CORP/COOP)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'"
        );

        // Lahat ng API data (presyo, users, sales, backups) ay sensitive —
        // huwag i-store sa browser o shared proxy cache.
        $headers->set('Cache-Control', 'no-store, private');
        $headers->set('Pragma', 'no-cache');

        // HSTS lang kapag HTTPS (Render). Sa local http://, walang epekto at
        // maaari pang makasira ng ibang localhost apps kung i-set.
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Huwag ipakita ang PHP version (PHP mismo ang nagdadagdag nito).
        $headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
