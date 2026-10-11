<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use App\Services\KetersediaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Jadwal studio terbuka untuk umum. Identitas peminjam disembunyikan;
 * yang ditampilkan hanya slot "Terisi" / "Menunggu konfirmasi" / "Tutup".
 */
class JadwalPublikController extends Controller
{
    public function index(KetersediaanService $ketersediaan): View
    {
        return view('publik.jadwal', [
            'ruangan' => Ruangan::query()->aktif()->orderBy('nama_ruangan')->get(['id_ruangan', 'nama_ruangan', 'slug']),
            'aturan' => $ketersediaan->aturan(),
        ]);
    }

    public function data(Request $request, KetersediaanService $ketersediaan): JsonResponse
    {
        $data = $request->validate([
            'mulai' => ['required', 'date_format:Y-m-d'],
            'selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:mulai'],
            'ruangan' => ['nullable', 'string', 'exists:data_ruangan,slug'],
        ]);

        $mulai = Carbon::parse($data['mulai'])->startOfDay();
        $selesai = Carbon::parse($data['selesai'])->startOfDay();
        if ($mulai->diffInDays($selesai) > 42) {
            $selesai = $mulai->copy()->addDays(42);
        }

        $ruangan = filled($data['ruangan'] ?? null) ? Ruangan::where('slug', $data['ruangan'])->first() : null;

        return response()->json([
            'events' => $ketersediaan->jadwal($mulai, $selesai, $ruangan, anonim: true),
            'aturan' => $ketersediaan->aturan(),
        ]);
    }
}
