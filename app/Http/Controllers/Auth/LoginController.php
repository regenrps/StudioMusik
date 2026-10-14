<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.masuk');
    }

    public function store(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email:rfc,filter', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        // Akun nonaktif ditolak tanpa sempat login. Kejadian masuk/gagal masuk
        // dicatat oleh App\Listeners\CatatKejadianKeamanan.
        if (! Auth::attemptWhen($kredensial, fn (User $u) => $u->is_active, $request->boolean('ingat'))) {
            $provider = Auth::getProvider();
            $user = $provider->retrieveByCredentials(['email' => $kredensial['email']]);
            $nonaktif = $user && ! $user->is_active && $provider->validateCredentials($user, $kredensial);

            throw ValidationException::withMessages([
                'email' => $nonaktif ? 'Akun Anda dinonaktifkan. Hubungi admin studio untuk informasi lebih lanjut.' : __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended($user->homeRoute())
            ->with('toast', ['type' => 'success', 'message' => 'Selamat datang kembali, '.$user->username.'!']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('beranda')->with('toast', ['type' => 'success', 'message' => 'Anda telah keluar.']);
    }
}
