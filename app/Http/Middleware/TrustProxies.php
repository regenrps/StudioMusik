<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Proxy tepercaya diatur lewat env TRUSTED_PROXIES:
 *  - kosong      : tidak memercayai proxy mana pun (akses langsung)
 *  - "*"         : percayai semua (mis. di balik load balancer/tunnel tepercaya)
 *  - daftar IP   : "10.0.0.1,172.16.0.0/12"
 *
 * Diperlukan agar IP pengguna (log & rate limit) dan deteksi HTTPS benar
 * saat aplikasi berjalan di balik reverse proxy/Docker.
 */
class TrustProxies extends Middleware
{
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    protected function proxies()
    {
        $nilai = trim((string) config('app.trusted_proxies'));

        if ($nilai === '') {
            return null;
        }

        return $nilai === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $nilai))));
    }
}
