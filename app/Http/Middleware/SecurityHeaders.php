<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan standar (target securityheaders.com rank A).
 *
 * Sengaja TIDAK ketat berlebihan supaya aplikasi tetap jalan:
 *  - CSP masih mengizinkan 'unsafe-inline'/'unsafe-eval' dan sumber https: apa pun,
 *    karena tema Metronic, KaTeX/MathLive, dan beberapa pustaka CDN memakai skrip
 *    serta gaya inline. Yang tetap dilarang: sumber http polos (cegah mixed content),
 *    <object>/<embed>, dan pembingkaian oleh situs lain.
 *  - HSTS tanpa preload & tanpa includeSubDomains agar subdomain lain tidak ikut
 *    dipaksa HTTPS (aman untuk dicabut kembali bila perlu).
 *
 * Header hanya dipasang bila belum ada, jadi web server / proxy di depan tetap
 * bisa menimpanya tanpa duplikasi.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self' https: data: blob:",
            "script-src 'self' https: 'unsafe-inline' 'unsafe-eval' blob:",
            "style-src 'self' https: 'unsafe-inline'",
            "img-src 'self' https: data: blob:",
            "font-src 'self' https: data:",
            "connect-src 'self' https: wss: blob: data:",
            "media-src 'self' https: data: blob:",
            "worker-src 'self' blob:",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https:",
        ]);

        $headers = [
            'Strict-Transport-Security' => 'max-age=31536000',
            'Content-Security-Policy'   => $csp,
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-Content-Type-Options'    => 'nosniff',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Permissions-Policy'        => 'accelerometer=(), autoplay=(self), camera=(), display-capture=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
            'X-XSS-Protection'          => '0',
        ];

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
