<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\TutorialAlat;
use App\Support\Berkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorialController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $tutorial = TutorialAlat::query()
            ->when($q !== '', fn ($query) => $query->where('nama_alat', 'like', '%'.$q.'%'))
            ->orderBy('kategori')->orderBy('nama_alat')
            ->paginate(12)->withQueryString();

        return view('admin.tutorial.index', ['tutorial' => $tutorial, 'q' => $q]);
    }

    public function create(): View
    {
        return view('admin.tutorial.form', ['tutorial' => new TutorialAlat(), 'kategori' => $this->kategori()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $tutorial = new TutorialAlat(collect($data)->except('gambar_alat')->all());
        $tutorial->gambar_alat = Berkas::simpan($request->file('gambar_alat'), 'tutorial', Berkas::PUBLIK);
        $tutorial->save();
        LogAktivitas::catat(null, 'tutorial_dibuat', 'Menambah tutorial '.$tutorial->nama_alat);

        return redirect()->route('admin.tutorial.index')->with('toast', ['type' => 'success', 'message' => 'Tutorial ditambahkan.']);
    }

    public function edit(TutorialAlat $tutorial): View
    {
        return view('admin.tutorial.form', ['tutorial' => $tutorial, 'kategori' => $this->kategori()]);
    }

    public function update(Request $request, TutorialAlat $tutorial): RedirectResponse
    {
        $data = $this->validasi($request, $tutorial);
        $tutorial->fill(collect($data)->except('gambar_alat')->all());
        if ($request->hasFile('gambar_alat')) {
            $lama = $tutorial->gambar_alat;
            $tutorial->gambar_alat = Berkas::simpan($request->file('gambar_alat'), 'tutorial', Berkas::PUBLIK);
            Berkas::hapus($lama, Berkas::PUBLIK);
        }
        $tutorial->save();
        LogAktivitas::catat(null, 'tutorial_diubah', 'Memperbarui tutorial '.$tutorial->nama_alat);

        return redirect()->route('admin.tutorial.index')->with('toast', ['type' => 'success', 'message' => 'Tutorial diperbarui.']);
    }

    public function destroy(TutorialAlat $tutorial): RedirectResponse
    {
        Berkas::hapus($tutorial->gambar_alat, Berkas::PUBLIK);
        LogAktivitas::catat(null, 'tutorial_dihapus', 'Menghapus tutorial '.$tutorial->nama_alat);
        $tutorial->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Tutorial dihapus.']);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?TutorialAlat $tutorial = null): array
    {
        $upload = config('studio.upload');

        return $request->validate([
            'nama_alat' => ['required', 'string', 'max:120'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'deskripsi' => ['required', 'string', 'max:20000'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'gambar_alat' => [$tutorial?->gambar_alat ? 'nullable' : 'required', 'image', 'mimes:'.implode(',', $upload['gambar_mimes']), 'max:'.$upload['gambar_maks_kb']],
        ]);
    }

    private function kategori()
    {
        return TutorialAlat::query()->whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori');
    }
}
