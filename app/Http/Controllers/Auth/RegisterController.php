<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.daftar');
    }

    public function store(Request $request): RedirectResponse
    {
        // Terima format umum seperti "0812-3456-7890" atau "+62 812 3456 7890".
        $request->merge(['no_wa' => preg_replace('/[^\d+]/', '', (string) $request->input('no_wa'))]);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:80'],
            'nim_nip' => ['nullable', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-]+$/'],
            'email' => ['required', 'email:rfc,filter', 'max:120', 'unique:users,email'],
            'no_wa' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'setuju' => ['accepted'],
        ], [
            'no_wa.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
            'setuju.accepted' => 'Anda harus menyetujui SOP dan ketentuan penggunaan studio.',
        ]);

        $user = User::create([
            'username' => trim($data['username']),
            'nim_nip' => $data['nim_nip'] ?? null,
            'email' => strtolower($data['email']),
            'no_wa' => preg_replace('/\D+/', '', $data['no_wa']),
            'password' => $data['password'],
            'user_role' => Role::User,
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        LogAktivitas::catat($user, 'daftar', 'Mendaftar akun baru', [], $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('akun.dashboard')
            ->with('toast', ['type' => 'success', 'message' => 'Akun berhasil dibuat. Selamat datang, '.$user->username.'!']);
    }
}
