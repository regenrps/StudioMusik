<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Models\FotoRuangan;
use App\Models\LogAktivitas;
use App\Models\Ruangan;
use App\Support\Berkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RuanganController extends Controller
{
    public function index(): View
    {
        $ruangan = Ruangan::query()->withCount([
            'peminjaman',
            'peminjaman as aktif_count' => fn ($q) => $q->menempatiSlot(),
        ])->withAvg('peminjaman as rating_avg', 'rating')->with('foto')->orderBy('nama_ruangan')->get();

        return view('admin.ruangan.index', ['ruangan' => $ruangan]);
    }

    public function create(): View
    {
        return view('admin.ruangan.form', ['ruangan' => new Ruangan(['is_active' => true, 'kapasitas' => 10])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $ruangan = DB::transaction(function () use ($request, $data) {
            $ruangan = new Ruangan($this->atribut($data));
            $ruangan->thumbnail = Berkas::simpan($request->file('thumbnail'), 'ruangan', Berkas::PUBLIK);
            $ruangan->save();
            $this->simpanFoto($request, $ruangan);
            LogAktivitas::catat($ruangan, 'dibuat', 'Menambah ruangan '.$ruangan->nama_ruangan);

            return $ruangan;
        });

        return redirect()->route('admin.ruangan.index')
            ->with('toast', ['type' => 'success', 'message' => 'Ruangan "'.$ruangan->nama_ruangan.'" ditambahkan.']);
    }

    public function edit(Ruangan $ruangan): View
    {
        return view('admin.ruangan.form', ['ruangan' => $ruangan->load('foto')]);
    }

    public function update(Request $request, Ruangan $ruangan): RedirectResponse
    {
        $data = $this->validasi($request, $ruangan);

        DB::transaction(function () use ($request, $data, $ruangan) {
            $ruangan->fill($this->atribut($data));
            if ($request->hasFile('thumbnail')) {
                $lama = $ruangan->thumbnail;
                $ruangan->thumbnail = Berkas::simpan($request->file('thumbnail'), 'ruangan', Berkas::PUBLIK);
                Berkas::hapus($lama, Berkas::PUBLIK);
            }
            $ruangan->save();
            $this->simpanFoto($request, $ruangan);
            LogAktivitas::catat($ruangan, 'diubah', 'Memperbarui ruangan '.$ruangan->nama_ruangan);
        });

        return redirect()->route('admin.ruangan.index')
            ->with('toast', ['type' => 'success', 'message' => 'Ruangan "'.$ruangan->nama_ruangan.'" diperbarui.']);
    }

    public function destroy(Ruangan $ruangan): RedirectResponse
    {
        if ($ruangan->peminjaman()->exists()) {
            $ruangan->update(['is_active' => false]);
            $aktif = $ruangan->peminjaman()->whereIn('status', StatusPeminjaman::blockingValues())->count();

            return back()->with('toast', ['type' => 'warning', 'message' => 'Ruangan memiliki riwayat peminjaman sehingga dinonaktifkan, bukan dihapus.'
                .($aktif ? ' Masih ada '.$aktif.' peminjaman aktif yang perlu ditindaklanjuti.' : '')]);
        }

        foreach ($ruangan->foto as $foto) {
            Berkas::hapus($foto->img_ruangan, Berkas::PUBLIK);
        }
        Berkas::hapus($ruangan->thumbnail, Berkas::PUBLIK);
        LogAktivitas::catat(null, 'dihapus', 'Menghapus ruangan '.$ruangan->nama_ruangan);
        $ruangan->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Ruangan dihapus.']);
    }

    public function hapusFoto(Ruangan $ruangan, FotoRuangan $foto): RedirectResponse
    {
        abort_unless($foto->id_ruangan === $ruangan->id_ruangan, 404);
        Berkas::hapus($foto->img_ruangan, Berkas::PUBLIK);
        $foto->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Foto dihapus.']);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Ruangan $ruangan = null): array
    {
        $upload = config('studio.upload');
        $gambar = ['image', 'mimes:'.implode(',', $upload['gambar_mimes']), 'max:'.$upload['gambar_maks_kb']];

        return $request->validate([
            'nama_ruangan' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:3000'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:200'],
            'fasilitas' => ['nullable', 'string', 'max:3000'],
            'lokasi' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
            'thumbnail' => [$ruangan?->thumbnail ? 'nullable' : 'required', ...$gambar],
            'foto' => ['nullable', 'array', 'max:10'],
            'foto.*' => $gambar,
        ]);
    }

    /** @return array<string, mixed> */
    private function atribut(array $data): array
    {
        return [
            'nama_ruangan' => $data['nama_ruangan'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'kapasitas' => $data['kapasitas'],
            'fasilitas' => $data['fasilitas'] ?? null,
            'lokasi' => $data['lokasi'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function simpanFoto(Request $request, Ruangan $ruangan): void
    {
        $urutan = (int) $ruangan->foto()->max('urutan');
        foreach ((array) $request->file('foto', []) as $file) {
            $ruangan->foto()->create([
                'img_ruangan' => Berkas::simpan($file, 'ruangan', Berkas::PUBLIK),
                'urutan' => ++$urutan,
            ]);
        }
    }
}
