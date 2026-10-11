<?php

namespace App\Http\Controllers;

use App\Models\TutorialAlat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorialController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('q'));
        $kategori = $request->query('kategori');

        $tutorial = TutorialAlat::query()
            ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_alat', 'like', '%'.$cari.'%')
                ->orWhere('deskripsi', 'like', '%'.$cari.'%')))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderBy('kategori')->orderBy('nama_alat')
            ->paginate(12)->withQueryString();

        return view('publik.tutorial', [
            'tutorial' => $tutorial,
            'kategori' => TutorialAlat::query()->whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori'),
            'cari' => $cari,
            'kategoriAktif' => $kategori,
        ]);
    }

    public function show(TutorialAlat $tutorial): View
    {
        return view('publik.tutorial-detail', [
            'tutorial' => $tutorial,
            'lainnya' => TutorialAlat::query()->whereKeyNot($tutorial->getKey())
                ->when($tutorial->kategori, fn ($q) => $q->orderByRaw('kategori = ? desc', [$tutorial->kategori]))
                ->limit(4)->get(),
        ]);
    }
}
