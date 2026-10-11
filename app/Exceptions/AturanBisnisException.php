<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Pelanggaran aturan bisnis (jadwal bentrok, status tidak valid, dsb).
 * Ditampilkan ke pengguna sebagai pesan kesalahan biasa, bukan error 500.
 */
class AturanBisnisException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput($request->except(['password', 'password_confirmation']))
            ->with('toast', ['type' => 'error', 'message' => $this->getMessage()])
            ->withErrors(['aturan' => $this->getMessage()]);
    }

    public function report(): bool
    {
        // Bukan bug – tidak perlu dicatat di log error.
        return true;
    }
}
