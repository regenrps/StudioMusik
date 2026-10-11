<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Services\KetersediaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Endpoint JSON untuk kalender ketersediaan (wizard peminjaman & halaman publik).
 * Tidak mengandung identitas peminjam.
 */
class KetersediaanController extends Controller
{
    public function bulan(Request $request, Ruangan $ruangan, KetersediaanService $ketersediaan): JsonResponse
    {
        $data = $request->validate(['bulan' => ['nullable', 'date_format:Y-m']]);
        $bulan = isset($data['bulan']) ? Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth() : today()->startOfMonth();

        return response()->json([
            'bulan' => $bulan->format('Y-m'),
            'label' => tanggal($bulan, 'F Y'),
            'hari' => array_values($ketersediaan->ringkasanBulan($ruangan, $bulan)),
            'aturan' => $ketersediaan->aturan(),
        ]);
    }

    public function hari(Request $request, Ruangan $ruangan, KetersediaanService $ketersediaan): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'abaikan' => ['nullable', 'string'],
        ]);

        // Saat mengubah jadwal sendiri, slot milik peminjaman tsb tidak dihitung bentrok.
        $abaikan = null;
        if (filled($data['abaikan'] ?? null) && $request->user()) {
            $abaikan = Peminjaman::query()->where('kode', $data['abaikan'])
                ->where('id_user', $request->user()->getKey())->value('id_pesanan_jadwal_studio');
        }

        return response()->json(
            $ketersediaan->detailHari($ruangan, Carbon::parse($data['tanggal']), $abaikan)
        );
    }
}
