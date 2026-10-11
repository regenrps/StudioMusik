<?php

namespace App\Http\Controllers;

use App\Models\JasaMusik;
use App\Models\Ruangan;
use App\Services\KetersediaanService;
use Illuminate\View\View;

class KatalogController extends Controller
{
    public function ruangan(Ruangan $ruangan, KetersediaanService $ketersediaan): View
    {
        abort_unless($ruangan->is_active || auth()->user()?->isStaff(), 404);

        $ruangan->load('foto');

        $ulasan = $ruangan->peminjaman()->with('user:id_user,username')
            ->whereNotNull('rating')->latest('diulas_at')->paginate(6, ['*'], 'ulasan');

        return view('publik.ruangan', [
            'ruangan' => $ruangan,
            'ulasan' => $ulasan,
            'ringkasan' => $ruangan->ringkasanUlasan(),
            'aturan' => $ketersediaan->aturan(),
        ]);
    }

    public function layanan(): View
    {
        $layanan = JasaMusik::query()->aktif()->urut()->withCount(['pesanan as selesai_count' => fn ($q) => $q->where('status', 'completed')])
            ->withAvg('pesanan as rating_avg', 'rating')->get();

        return view('publik.layanan', ['layanan' => $layanan]);
    }

    public function layananShow(JasaMusik $jasa): View
    {
        abort_unless($jasa->is_active || auth()->user()?->isStaff(), 404);

        $jasa->load('field');

        $ulasan = $jasa->pesanan()->with('user:id_user,username')
            ->whereNotNull('rating')->latest('diulas_at')->paginate(6, ['*'], 'ulasan');

        return view('publik.layanan-detail', [
            'jasa' => $jasa,
            'ulasan' => $ulasan,
            'ringkasan' => $jasa->ringkasanUlasan(),
            'lainnya' => JasaMusik::query()->aktif()->whereKeyNot($jasa->getKey())->urut()->limit(3)->get(),
        ]);
    }
}
