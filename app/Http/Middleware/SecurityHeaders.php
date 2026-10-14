<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Header keamanan untuk seluruh respons web.
 *
 * Content-Security-Policy memakai nonce per request untuk skrip inline &
 * aset Vite. 'unsafe-eval' diperlukan oleh Alpine.js (build standar);
 * risikonya dibatasi karena satu-satunya HTML dari pengguna (konten admin)
 * disaring tanpa atribut. Mode diatur lewat CSP_MODE: enforce | report | off.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = config('studio.csp_mode', 'enforce');
        $nonce = $mode !== 'off' ? Vite::useCspNonce() : null;

        /** @var Response $response */
        $response = $next($request);

        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        $h->set('X-Permitted-Cross-Domain-Policies', 'none');
        $h->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $berkas = $response instanceof BinaryFileResponse || $response instanceof StreamedResponse;

        // Halaman ber-login tidak boleh disimpan cache browser/proxy
        // (komputer lab bersama, tombol Kembali setelah keluar).
        if (! $berkas && $request->user()) {
            $h->set('Cache-Control', 'no-store, private');
        }

        // Halaman galat debug (Ignition) memakai skrip inline; jangan dibatasi.
        $halamanDebug = config('app.debug') && $response->getStatusCode() >= 500;

        if ($nonce && ! $berkas && ! $halamanDebug) {
            $header = $mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $h->set($header, $this->kebijakan($nonce, $request));
        }

        return $response;
    }

    private function kebijakan(string $nonce, Request $request): string
    {
        // Saat `npm run dev`, aset dilayani server Vite di origin lain.
        $vite = '';
        if (Vite::isRunningHot()) {
            $hot = rtrim(trim((string) @file_get_contents(public_path('hot'))), '/');
            $ws = preg_replace('#^http#', 'ws', $hot);
            $vite = ' '.$hot.' '.$ws;
        }

        $arahan = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$vite}",
            "style-src 'self' 'unsafe-inline'{$vite}",
            "img-src 'self' data: blob:{$vite}",
            "font-src 'self' data:{$vite}",
            "connect-src 'self'{$vite}",
            'frame-src https://www.youtube-nocookie.com',
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if ($request->isSecure()) {
            $arahan[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $arahan);
    }
}
