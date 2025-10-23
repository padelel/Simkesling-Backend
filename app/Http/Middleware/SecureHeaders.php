<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    /**
     * Add security headers to all responses to reduce ZAP medium risks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Strong CSP: define default-src fallback and avoid unsafe-inline/unsafe-eval
        $csp = "default-src 'self'; "
            . "base-uri 'self'; "
            . "object-src 'none'; "
            . "frame-ancestors 'none'; "
            . "form-action 'self'; "
            . "img-src 'self' data:; "
            . "font-src 'self' data:; "
            . "script-src 'self'; "
            . "style-src 'self'; "
            . "connect-src 'self'";

        $response->headers->set('Content-Security-Policy', $csp);

        // Complementary security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Redundant with CSP frame-ancestors, but kept for legacy UA
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', "geolocation=(), microphone=(), camera=()");

        // Remove technology disclosing headers
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}