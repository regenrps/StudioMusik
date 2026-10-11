<?php

namespace App\Http\Middleware;

use App\Services\PeminjamanService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Cadangan bila cron/scheduler tidak berjalan (mis. di shared hosting):
 * paling sering setiap 5 menit, status peminjaman disinkronkan setelah
 * respons dikirim ke pengguna sehingga tidak memperlambat halaman.
 */
class SinkronStatusOtomatis
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (app()->runningUnitTests() || ! config('studio.sinkron_via_request', true)) {
            return;
        }

        try {
            if (Cache::add('studio:sinkron-status', now()->timestamp, now()->addMinutes(5))) {
                app(PeminjamanService::class)->sinkronOtomatis();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
