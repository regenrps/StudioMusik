<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /** Status HTTP yang menandakan kejadian keamanan dan perlu dicatat. */
    private const STATUS_KEAMANAN = [
        403 => 'akses_ditolak',
        419 => 'csrf_tidak_valid',
        429 => 'batas_permintaan',
    ];

    public function register(): void
    {
        // 403/419/429 tidak dilaporkan sebagai error, tetapi tetap dicatat
        // ke log keamanan untuk mendeteksi percobaan akses tidak sah/brute force.
        $this->renderable(function (Throwable $e, Request $request) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : null;

            if ($status && isset(self::STATUS_KEAMANAN[$status])) {
                try {
                    Log::channel('keamanan')->warning(self::STATUS_KEAMANAN[$status], [
                        'status' => $status,
                        'method' => $request->method(),
                        'path' => '/'.ltrim($request->path(), '/'),
                        'route' => $request->route()?->getName(),
                        'user_id' => $request->user()?->getKey(),
                        'ip' => $request->ip(),
                        'ua' => Str::limit((string) $request->userAgent(), 160, ''),
                    ]);
                } catch (Throwable) {
                    // Jangan sampai pencatatan menggagalkan respons galat.
                }
            }

            return null; // lanjutkan render bawaan (halaman galat kustom)
        });
    }

    /**
     * Konteks tambahan untuk setiap error yang dilaporkan ke log.
     *
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        $konteks = parent::context();

        try {
            $request = request();
            if (! app()->runningInConsole()) {
                $konteks['url'] = $request->method().' /'.ltrim($request->path(), '/');
                $konteks['ip'] = $request->ip();
                $konteks['request_id'] = $request->attributes->get('request_id');
            }
        } catch (Throwable) {
            // Konteks bersifat opsional.
        }

        return array_filter($konteks, fn ($v) => $v !== null);
    }
}
