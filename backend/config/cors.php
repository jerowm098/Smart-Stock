<?php

/*
|--------------------------------------------------------------------------
| CORS — Security hardening (OWASP ZAP 10098 Cross-Domain Misconfiguration)
|--------------------------------------------------------------------------
|
| Kung walang config/cors.php, ang default ng Laravel ay
| `Access-Control-Allow-Origin: *` para sa lahat ng api/* routes.
|
| Hindi kailangan ng Smart-Stock ang CORS: SAME-ORIGIN ang SPA
|   - local:      vite preview/dev ay nagpo-proxy ng /api  -> iisang origin
|   - production: nginx ang nagsi-serve ng SPA at /api     -> iisang origin
|
| Kaya walang cross-origin na pinapayagan by default. Kung sakaling kailangan
| (hal. hiwalay na frontend domain), ilagay sa .env:
|   CORS_ALLOWED_ORIGINS=https://frontend.example.com,https://other.example.com
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

return [

    'paths' => $origins ? ['api/*'] : [],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 600,

    // Cookie-based session auth: kailangan ng credentials kung may
    // explicit cross-origin na pinayagan sa itaas (hindi kailanman '*').
    'supports_credentials' => (bool) $origins,

];
