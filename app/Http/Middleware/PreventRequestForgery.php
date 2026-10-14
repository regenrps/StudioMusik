<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

/**
 * Proteksi CSRF Laravel 13: token CSRF + verifikasi asal request
 * lewat header Sec-Fetch-Site (pengganti VerifyCsrfToken).
 */
class PreventRequestForgery extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
