<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipeField;
use App\Http\Controllers\Controller;
use App\Models\JasaMusik;
use App\Models\LogAktivitas;
use App\Support\Berkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Katalog layanan jasa musik beserta formulir pemesanan dinamisnya.
 */
class LayananController extends Controller
{
    public function index(): View
    {
        $layanan = JasaMusik::query()->urut()
            ->withCount(['pesanan', 'pesanan as aktif_count' => fn ($q) => $q->aktif(), 'field'])
            ->withAvg('pesanan as rating_avg', 'rating')
            ->get();

        return view('admin.layanan.index', ['layanan' => $layanan]);
    }

    public function create(): View
    {
        return view('admin.layanan.form', ['jasa' => new JasaMusik(['is_active' => true, 'estimasi_hari' => 7])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $jasa = DB::transaction(function () use ($request, $data) {
            $jasa = new JasaMusik($this->atribut($data));
            if ($request->hasFile('gambar')) {
                $jasa->gambar = Berkas::simpan($request->file('gambar'), 'layanan', Berkas::PUBLIK);
            }
            $jasa->urutan = (int) JasaMusik::max('urutan') + 1;
            $jasa->save();
            $this->sinkronField($jasa, $data['fields'] ?? []);
            LogAktivitas::catat($jasa, 'dibuat', 'Menambah layanan '.$jasa->nama_jenis_jasa);

            return $jasa;
        });

        return redirect()->route('admin.layanan.index')
            ->with('toast', ['type' => 'success', 'message' => 'Layanan "'.$jasa->nama_jenis_jasa.'" ditambahkan.']);
    }

    public function edit(JasaMusik $jasa): View
    {
        return view('admin.layanan.form', ['jasa' => $jasa->load('field')]);
    }

    public function update(Request $request, JasaMusik $jasa): RedirectResponse
    {
        $data = $this->validasi($request, $jasa);

        DB::transaction(function () use ($request, $data, $jasa) {
            $jasa->fill($this->atribut($data));
            if ($request->hasFile('gambar')) {
                $lama = $jasa->gambar;
                $jasa->gambar = Berkas::simpan($request->file('gambar'), 'layanan', Berkas::PUBLIK);
                Berkas::hapus($lama, Berkas::PUBLIK);
            }
            $jasa->save();
            $this->sinkronField($jasa, $data['fields'] ?? []);
            LogAktivitas::catat($jasa, 'diubah', 'Memperbarui layanan '.$jasa->nama_jenis_jasa);
        });

        return redirect()->route('admin.layanan.index')
            ->with('toast', ['type' => 'success', 'message' => 'Layanan "'.$jasa->nama_jenis_jasa.'" diperbarui.']);
    }

    public function destroy(JasaMusik $jasa): RedirectResponse
    {
        if ($jasa->pesanan()->exists()) {
            $jasa->update(['is_active' => false]);

            return back()->with('toast', ['type' => 'warning', 'message' => 'Layanan memiliki riwayat pesanan sehingga dinonaktifkan, bukan dihapus.']);
        }

        Berkas::hapus($jasa->gambar, Berkas::PUBLIK);
        LogAktivitas::catat(null, 'dihapus', 'Menghapus layanan '.$jasa->nama_jenis_jasa);
        $jasa->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Layanan dihapus.']);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?JasaMusik $jasa = null): array
    {
        $upload = config('studio.upload');

        return $request->validate([
            'nama_jenis_jasa' => ['required', 'string', 'max:80', Rule::unique('master_jasa_musik', 'nama_jenis_jasa')->ignore($jasa?->getKey(), 'id_jasa_musik')],
            'ringkasan' => ['nullable', 'string', 'max:200'],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'sk' => ['required', 'string', 'max:5000'],
            'estimasi_hari' => ['required', 'integer', 'min:1', 'max:180'],
            'harga_mulai' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'is_active' => ['nullable', 'boolean'],
            'gambar' => [$jasa?->gambar ? 'nullable' : 'required', 'image', 'mimes:'.implode(',', $upload['gambar_mimes']), 'max:'.$upload['gambar_maks_kb']],
            'fields' => ['nullable', 'array', 'max:20'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.nama_field' => ['required', 'string', 'max:80'],
            'fields.*.jenis_field' => ['required', Rule::enum(TipeField::class)],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.bantuan' => ['nullable', 'string', 'max:200'],
        ], [], [
            'fields.*.nama_field' => 'nama field',
            'fields.*.jenis_field' => 'jenis field',
            'fields.*.bantuan' => 'teks bantuan',
        ]);
    }

    /** @return array<string, mixed> */
    private function atribut(array $data): array
    {
        return [
            'nama_jenis_jasa' => $data['nama_jenis_jasa'],
            'ringkasan' => $data['ringkasan'] ?? null,
            'deskripsi' => $data['deskripsi'],
            'sk' => $data['sk'],
            'estimasi_hari' => $data['estimasi_hari'],
            'harga_mulai' => $data['harga_mulai'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function sinkronField(JasaMusik $jasa, array $fields): void
    {
        $dipertahankan = [];

        foreach (array_values($fields) as $i => $f) {
            $atribut = [
                'nama_field' => trim($f['nama_field']),
                'jenis_field' => $f['jenis_field'],
                'is_required' => (bool) ($f['is_required'] ?? false),
                'bantuan' => $f['bantuan'] ?? null,
                'urutan' => $i + 1,
            ];

            $field = filled($f['id'] ?? null) ? $jasa->field()->whereKey($f['id'])->first() : null;
            if ($field) {
                $field->update($atribut);
            } else {
                $field = $jasa->field()->create($atribut);
            }
            $dipertahankan[] = $field->id;
        }

        $jasa->field()->whereNotIn('id', $dipertahankan ?: [0])->delete();
    }
}
