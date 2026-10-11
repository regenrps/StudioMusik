<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('akun.profil', [
            'user' => $user,
            'statistik' => $user->isStaff() ? null : [
                'peminjaman' => $user->peminjaman()->count(),
                'selesai' => $user->peminjaman()->where('status', 'completed')->count(),
                'jasa' => $user->pesananJasa()->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Terima format umum seperti "0812-3456-7890" atau "+62 812 3456 7890".
        $request->merge(['no_wa' => preg_replace('/[^\d+]/', '', (string) $request->input('no_wa'))]);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:80'],
            'nim_nip' => ['nullable', 'string', 'max:30', 'regex:/^[0-9A-Za-z.\-]+$/'],
            'email' => ['required', 'email:rfc', 'max:120', Rule::unique('users', 'email')->ignore($user->getKey(), 'id_user')],
            'no_wa' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
        ], ['no_wa.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.']);

        $data['email'] = strtolower($data['email']);
        $data['no_wa'] = preg_replace('/\D+/', '', $data['no_wa']);

        $user->fill($data);
        $diubah = array_keys($user->getDirty());
        $user->save();

        if ($diubah) {
            LogAktivitas::catat($user, 'profil_diubah', 'Memperbarui profil', ['kolom' => $diubah]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Profil berhasil diperbarui.']);
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $request->input('password')]);
        LogAktivitas::catat($request->user(), 'password_diubah', 'Mengganti kata sandi');

        return back()->with('toast', ['type' => 'success', 'message' => 'Kata sandi berhasil diganti.']);
    }
}
