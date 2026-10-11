<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $aksi = $request->query('aksi');
        $dari = $request->query('dari');
        $sampai = $request->query('sampai');

        $log = LogAktivitas::query()->with('user:id_user,username,user_role', 'subjek')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('deskripsi', 'like', '%'.$q.'%')
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', '%'.$q.'%'))))
            ->when($aksi, fn ($query) => $query->where('aksi', $aksi))
            ->when($dari, fn ($query) => $query->whereDate('created_at', '>=', $dari))
            ->when($sampai, fn ($query) => $query->whereDate('created_at', '<=', $sampai))
            ->latest('created_at')->latest('id')
            ->paginate(30)->withQueryString();

        return view('admin.log', [
            'log' => $log,
            'daftarAksi' => LogAktivitas::query()->distinct()->orderBy('aksi')->pluck('aksi'),
            'filter' => compact('q', 'aksi', 'dari', 'sampai'),
        ]);
    }
}
