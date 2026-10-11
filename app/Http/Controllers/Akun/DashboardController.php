<?php

namespace App\Http\Controllers\Akun;

use App\Enums\StatusPeminjaman;
use App\Enums\StatusPesananJasa;
use App\Http\Controllers\Controller;
use App\Models\JasaMusik;
use App\Models\Ruangan;
use App\Services\KetersediaanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, KetersediaanService $ketersediaan): View
    {
        $user = $request->user();

        // Peminjaman yang paling relevan sekarang: sedang berlangsung, lalu jadwal terdekat.
        $berikutnya = $user->peminjaman()->with('ruangan')
            ->whereIn('status', StatusPeminjaman::blockingValues())
            ->where(fn ($q) => $q->where('status', StatusPeminjaman::Berlangsung->value)
                ->orWhereDate('tgl_pinjam', '>', today())
                ->orWhere(fn ($q) => $q->whereDate('tgl_pinjam', today())->where('waktu_selesai', '>', now()->format('H:i:s'))))
            ->orderByRaw("status = 'in_use' desc")
            ->urutJadwal()
            ->first();

        $perluUlasan = $user->peminjaman()->with('ruangan')->where('status', StatusPeminjaman::Selesai->value)
            ->whereNull('rating')->latest('checkout_at')->first();

        $jasaAktif = $user->pesananJasa()->with('jasa')->aktif()->latest()->limit(3)->get();
        $jasaPerluUlasan = $user->pesananJasa()->with('jasa')->status(StatusPesananJasa::Selesai)->whereNull('rating')->latest('selesai_at')->first();

        $riwayat = $user->peminjaman()->with('ruangan')->latest()->limit(5)->get();

        return view('akun.dashboard', [
            'user' => $user,
            'berikutnya' => $berikutnya,
            'perluUlasan' => $perluUlasan,
            'jasaAktif' => $jasaAktif,
            'jasaPerluUlasan' => $jasaPerluUlasan,
            'riwayat' => $riwayat,
            'ruangan' => Ruangan::query()->aktif()->orderBy('nama_ruangan')->get(),
            'layanan' => JasaMusik::query()->aktif()->urut()->limit(3)->get(),
            'statistik' => [
                'total' => $user->peminjaman()->count(),
                'selesai' => $user->peminjaman()->where('status', StatusPeminjaman::Selesai->value)->count(),
                'jasa' => $user->pesananJasa()->count(),
            ],
            'aturan' => $ketersediaan->aturan(),
            'pengumuman' => pengaturan('pengumuman'),
        ]);
    }
}
