<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Notifications\LaporanBaru;
use App\Support\Berkas;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Laporan masalah/insiden studio. Dapat dibuat oleh admin dan K3L,
 * opsional dikaitkan dengan peminjaman tertentu.
 */
class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $jenis = $request->query('jenis');

        $laporan = Laporan::query()->with('pelapor:id_user,username', 'ruangan:id_ruangan,nama_ruangan', 'peminjaman:id_pesanan_jadwal_studio,kode')
            ->when(in_array($status, array_column(StatusLaporan::cases(), 'value'), true), fn ($q) => $q->where('status', $status))
            ->when(in_array($jenis, Laporan::JENIS, true), fn ($q) => $q->where('jenis_laporan', $jenis))
            ->orderByRaw("FIELD(status, 'open', 'in_progress', 'resolved')")
            ->latest('tgl_laporan')->latest('id_laporan')
            ->paginate(15)->withQueryString();

        return view('admin.laporan.index', [
            'laporan' => $laporan,
            'filter' => compact('status', 'jenis'),
            'hitung' => Laporan::query()->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
        ]);
    }

    public function create(Request $request): View
    {
        $peminjaman = $request->query('peminjaman')
            ? Peminjaman::query()->with('ruangan')->where('kode', $request->query('peminjaman'))->first()
            : null;

        return view('admin.laporan.form', [
            'laporan' => new Laporan([
                'tgl_laporan' => today(),
                'id_ruangan' => $peminjaman?->id_ruangan,
                'id_pesanan_jadwal_studio' => $peminjaman?->getKey(),
                'status' => StatusLaporan::Baru,
            ]),
            'ruangan' => Ruangan::query()->orderBy('nama_ruangan')->get(['id_ruangan', 'nama_ruangan']),
            'peminjaman' => $peminjaman,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $gambar = [];
        foreach ((array) $request->file('gambar', []) as $file) {
            $gambar[] = Berkas::simpan($file, 'laporan');
        }

        $laporan = Laporan::create([
            ...collect($data)->except(['gambar', 'kode_peminjaman'])->all(),
            'id_user' => $request->user()->getKey(),
            'id_pesanan_jadwal_studio' => $this->idPeminjaman($data['kode_peminjaman'] ?? null),
            'status' => StatusLaporan::Baru,
            'gambar' => $gambar,
        ]);

        LogAktivitas::catat($laporan, 'dibuat', 'Membuat laporan '.$laporan->jenis_laporan);
        Notifier::staf(new LaporanBaru($laporan), [Role::Admin, Role::K3L]);

        return redirect()->route('admin.laporan.show', $laporan)
            ->with('toast', ['type' => 'success', 'message' => 'Laporan tersimpan dan diteruskan ke admin.']);
    }

    public function show(Laporan $laporan): View
    {
        $laporan->load('pelapor', 'ruangan', 'peminjaman.user', 'log.user:id_user,username,user_role');

        return view('admin.laporan.show', ['laporan' => $laporan]);
    }

    public function edit(Laporan $laporan): View
    {
        return view('admin.laporan.form', [
            'laporan' => $laporan->load('peminjaman'),
            'ruangan' => Ruangan::query()->orderBy('nama_ruangan')->get(['id_ruangan', 'nama_ruangan']),
            'peminjaman' => $laporan->peminjaman,
        ]);
    }

    public function update(Request $request, Laporan $laporan): RedirectResponse
    {
        $data = $this->validasi($request, $laporan);
        $statusLama = $laporan->status;

        $gambar = $laporan->daftarGambar();
        foreach ((array) $request->input('hapus_gambar', []) as $indeks) {
            if (isset($gambar[(int) $indeks])) {
                Berkas::hapus($gambar[(int) $indeks]);
                unset($gambar[(int) $indeks]);
            }
        }
        foreach ((array) $request->file('gambar', []) as $file) {
            $gambar[] = Berkas::simpan($file, 'laporan');
        }

        $status = StatusLaporan::from($data['status'] ?? $laporan->status->value);
        $laporan->update([
            ...collect($data)->except(['gambar', 'kode_peminjaman', 'status'])->all(),
            'id_pesanan_jadwal_studio' => $this->idPeminjaman($data['kode_peminjaman'] ?? null),
            'status' => $status,
            'gambar' => array_values($gambar),
            'selesai_at' => $status === StatusLaporan::Selesai ? ($laporan->selesai_at ?? now()) : null,
        ]);

        LogAktivitas::catat($laporan, 'diubah', $statusLama !== $status
            ? 'Mengubah status laporan menjadi '.$status->label()
            : 'Memperbarui laporan');

        return redirect()->route('admin.laporan.show', $laporan)
            ->with('toast', ['type' => 'success', 'message' => 'Laporan diperbarui.']);
    }

    public function destroy(Laporan $laporan): RedirectResponse
    {
        Gate::authorize('admin');

        foreach ($laporan->daftarGambar() as $path) {
            Berkas::hapus($path);
        }
        LogAktivitas::catat(null, 'dihapus', 'Menghapus laporan #'.$laporan->getKey().' ('.$laporan->jenis_laporan.')');
        $laporan->delete();

        return redirect()->route('admin.laporan.index')->with('toast', ['type' => 'success', 'message' => 'Laporan dihapus.']);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Laporan $laporan = null): array
    {
        $upload = config('studio.upload');

        return $request->validate([
            'tgl_laporan' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'jenis_laporan' => ['required', Rule::in(Laporan::JENIS)],
            'id_ruangan' => ['nullable', 'exists:data_ruangan,id_ruangan'],
            'kode_peminjaman' => ['nullable', 'string', 'exists:pesanan_jadwal_studio,kode'],
            'keterangan' => ['required', 'string', 'min:10', 'max:3000'],
            'tindak_lanjut' => ['nullable', 'string', 'max:3000'],
            'status' => [$laporan ? 'required' : 'nullable', Rule::enum(StatusLaporan::class)],
            'gambar' => ['nullable', 'array', 'max:6'],
            'gambar.*' => ['image', 'mimes:'.implode(',', $upload['gambar_mimes']), 'max:'.$upload['gambar_maks_kb']],
            'hapus_gambar' => ['nullable', 'array'],
        ], ['kode_peminjaman.exists' => 'Kode peminjaman tidak ditemukan.'], ['kode_peminjaman' => 'kode peminjaman']);
    }

    private function idPeminjaman(?string $kode): ?int
    {
        return $kode ? Peminjaman::query()->where('kode', $kode)->value('id_pesanan_jadwal_studio') : null;
    }
}
