<?php

namespace App\Http\Controllers;

use App\Enums\StatusPeminjaman;
use App\Enums\StatusPesananJasa;
use App\Models\JasaMusik;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\Ruangan;
use App\Models\TutorialAlat;
use App\Services\KetersediaanService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function index(KetersediaanService $ketersediaan): View
    {
        $ruangan = Ruangan::query()->aktif()->with('foto')->orderBy('nama_ruangan')->get();
        $layanan = JasaMusik::query()->aktif()->urut()->get();
        $tutorial = TutorialAlat::query()->latest('id_tutorial')->limit(4)->get();

        // Isi cache hanya array & skalar: Laravel 13 menolak unserialize objek
        // dari cache (cache.serializable_classes = false), termasuk Carbon.
        $ulasan = Cache::remember('beranda:ulasan', now()->addMinutes(10), function () {
            $studio = Peminjaman::query()->with('user:id_user,username', 'ruangan:id_ruangan,nama_ruangan')
                ->whereNotNull('review')->where('rating', '>=', 4)->latest('diulas_at')->limit(6)->get()
                ->map(fn (Peminjaman $p) => [
                    'nama' => $p->user?->username,
                    'jenis' => 'Peminjaman '.($p->ruangan?->nama_ruangan ?? 'Studio'),
                    'rating' => $p->rating,
                    'teks' => $p->review,
                    'waktu' => ($p->diulas_at ?? $p->updated_at)?->toIso8601String(),
                ]);

            $jasa = PesananJasa::query()->with('user:id_user,username', 'jasa:id_jasa_musik,nama_jenis_jasa')
                ->whereNotNull('review')->where('rating', '>=', 4)->latest('diulas_at')->limit(6)->get()
                ->map(fn (PesananJasa $p) => [
                    'nama' => $p->user?->username,
                    'jenis' => 'Jasa '.($p->jasa?->nama_jenis_jasa ?? 'Musik'),
                    'rating' => $p->rating,
                    'teks' => $p->review,
                    'waktu' => ($p->diulas_at ?? $p->updated_at)?->toIso8601String(),
                ]);

            return $studio->concat($jasa)->sortByDesc('waktu')->take(6)->values()->all();
        });

        $statistik = Cache::remember('beranda:statistik', now()->addMinutes(10), fn () => [
            'peminjaman' => Peminjaman::query()->status(StatusPeminjaman::Selesai)->count(),
            'jasa' => PesananJasa::query()->status(StatusPesananJasa::Selesai)->count(),
            'rating' => round((float) Peminjaman::query()->whereNotNull('rating')->avg('rating'), 1) ?: null,
        ]);

        // Status studio hari ini untuk ruangan pertama (studio utama).
        $hariIni = $ruangan->first() ? $ketersediaan->detailHari($ruangan->first(), today()) : null;
        $sedangDipakai = $ruangan->first()
            ? Peminjaman::query()->where('id_ruangan', $ruangan->first()->id_ruangan)
                ->status(StatusPeminjaman::Berlangsung)->whereDate('tgl_pinjam', today())->exists()
            : false;

        return view('publik.beranda', [
            'ruangan' => $ruangan,
            'layanan' => $layanan,
            'tutorial' => $tutorial,
            'ulasan' => $ulasan,
            'statistik' => $statistik,
            'hariIni' => $hariIni,
            'sedangDipakai' => $sedangDipakai,
            'aturan' => $ketersediaan->aturan(),
            'pengumuman' => pengaturan('pengumuman'),
        ]);
    }
}
