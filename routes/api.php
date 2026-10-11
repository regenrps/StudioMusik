<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Pemeriksaan kesehatan untuk monitoring/uptime checker.
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $db = 'ok';
    } catch (Throwable) {
        $db = 'error';
    }

    return response()->json(['status' => $db === 'ok' ? 'ok' : 'degraded', 'database' => $db, 'waktu' => now()->toIso8601String()], $db === 'ok' ? 200 : 503);
});
