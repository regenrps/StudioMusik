<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Memberi setiap request ID korelasi (X-Request-Id) yang ikut tercatat di
 * semua baris log, lalu menulis satu baris log akses (JSON) setelah respons
 * dikirim. Query string sengaja tidak dicatat (bisa berisi token/email).
 */
class CatatAkses
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->headers->get('X-Request-Id');
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9._-]{8,64}$/', $id)) {
            $id = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $id);
        $request->attributes->set('mulai_request', microtime(true));
        Log::shareContext(['request_id' => $id]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('logging.akses_aktif') || $request->is('build/*', 'storage/*', 'assets/*', 'favicon.ico', 'api/health')) {
            return;
        }

        try {
            $mulai = (float) $request->attributes->get('mulai_request', defined('LARAVEL_START') ? LARAVEL_START : microtime(true));

            Log::channel('akses')->info($request->method().' /'.ltrim($request->path(), '/'), [
                'status' => $response->getStatusCode(),
                'durasi_ms' => (int) round((microtime(true) - $mulai) * 1000),
                'route' => $request->route()?->getName(),
                'user_id' => $request->user()?->getKey(),
                'ip' => $request->ip(),
                'ua' => Str::limit((string) $request->userAgent(), 160, ''),
            ]);
        } catch (Throwable) {
            // Log akses tidak boleh mengganggu respons.
        }
    }
}
