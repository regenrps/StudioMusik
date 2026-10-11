<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $peran = $request->query('peran');
        $status = $request->query('status');

        $pengguna = User::query()
            ->withCount(['peminjaman', 'pesananJasa', 'peminjaman as tidak_hadir_count' => fn ($q) => $q->where('status', 'no_show')])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('username', 'like', '%'.$q.'%')
                ->orWhere('email', 'like', '%'.$q.'%')
                ->orWhere('nim_nip', 'like', '%'.$q.'%')
                ->orWhere('no_wa', 'like', '%'.$q.'%')))
            ->when(in_array($peran, array_column(Role::cases(), 'value'), true), fn ($query) => $query->where('user_role', $peran))
            ->when($status === 'aktif', fn ($query) => $query->where('is_active', true))
            ->when($status === 'nonaktif', fn ($query) => $query->where('is_active', false))
            ->orderByRaw("FIELD(user_role, 'admin', 'k3l', 'user')")
            ->orderBy('username')
            ->paginate(20)->withQueryString();

        return view('admin.pengguna.index', [
            'pengguna' => $pengguna,
            'filter' => compact('q', 'peran', 'status'),
            'hitung' => User::query()->selectRaw('user_role, COUNT(*) as jumlah')->groupBy('user_role')->pluck('jumlah', 'user_role'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Terima format umum seperti "0812-3456-7890" atau "+62 812 3456 7890".
        $request->merge(['no_wa' => preg_replace('/[^\d+]/', '', (string) $request->input('no_wa'))]);

        $data = $request->validateWithBag('tambah', [
            'username' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'no_wa' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'nim_nip' => ['nullable', 'string', 'max:30'],
            'user_role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ], ['no_wa.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.']);

        $user = User::create([
            ...$data,
            'email' => strtolower($data['email']),
            'no_wa' => preg_replace('/\D+/', '', $data['no_wa']),
            'is_active' => true,
        ]);

        LogAktivitas::catat($user, 'pengguna_dibuat', 'Membuat akun '.$user->username.' sebagai '.$user->user_role->label());

        return back()->with('toast', ['type' => 'success', 'message' => 'Akun '.$user->username.' berhasil dibuat.']);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'user_role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
        ]);

        if ($user->is($request->user()) && $data['user_role'] !== Role::Admin->value) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Anda tidak dapat menurunkan peran akun sendiri.']);
        }

        $lama = $user->user_role;
        $user->user_role = Role::from($data['user_role']);
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }
        $user->save();

        LogAktivitas::catat($user, 'pengguna_diubah', 'Mengubah akun '.$user->username, array_filter([
            'peran' => $lama !== $user->user_role ? $lama->label().' → '.$user->user_role->label() : null,
            'password' => filled($data['password'] ?? null) ? 'diatur ulang' : null,
        ]));

        return back()->with('toast', ['type' => 'success', 'message' => 'Akun '.$user->username.' diperbarui.']);
    }

    public function status(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);
        LogAktivitas::catat($user, $user->is_active ? 'pengguna_diaktifkan' : 'pengguna_dinonaktifkan',
            ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan').' akun '.$user->username);

        return back()->with('toast', ['type' => 'success', 'message' => 'Akun '.$user->username.' '.($user->is_active ? 'diaktifkan' : 'dinonaktifkan').'.']);
    }
}
