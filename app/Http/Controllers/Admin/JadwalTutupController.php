<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalTutup;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Penutupan studio: UTS/UAS, kegiatan UKMBSM, perawatan alat, libur nasional.
 */
class JadwalTutupController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'lalu' ? 'lalu' : 'mendatang';

        $jadwal = JadwalTutup::query()->with('ruangan:id_ruangan,nama_ruangan', 'pembuat:id_user,username')
            ->when($tab === 'mendatang',
                fn ($q) => $q->whereDate('tgl_selesai', '>=', today())->orderBy('tgl_mulai'),
                fn ($q) => $q->whereDate('tgl_selesai', '<', today())->orderByDesc('tgl_mulai'))
            ->paginate(15)->withQueryString();

        return view('admin.jadwal-tutup.index', [
            'jadwal' => $jadwal,
            'tab' => $tab,
            'ruangan' => Ruangan::query()->orderBy('nama_ruangan')->get(['id_ruangan', 'nama_ruangan']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $jadwal = JadwalTutup::create($data + ['dibuat_oleh' => $request->user()->getKey()]);
        LogAktivitas::catat(null, 'jadwal_tutup', 'Menutup studio: '.$jadwal->keterangan.' ('.$jadwal->rentang_label.')');

        return $this->kembali($jadwal, 'Jadwal penutupan ditambahkan.');
    }

    public function update(Request $request, JadwalTutup $jadwalTutup): RedirectResponse
    {
        $jadwalTutup->update($this->validasi($request));
        LogAktivitas::catat(null, 'jadwal_tutup_diubah', 'Mengubah penutupan: '.$jadwalTutup->keterangan.' ('.$jadwalTutup->rentang_label.')');

        return $this->kembali($jadwalTutup, 'Jadwal penutupan diperbarui.');
    }

    public function destroy(JadwalTutup $jadwalTutup): RedirectResponse
    {
        LogAktivitas::catat(null, 'jadwal_tutup_dihapus', 'Menghapus penutupan: '.$jadwalTutup->keterangan);
        $jadwalTutup->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Jadwal penutupan dihapus. Slot kembali dapat dipesan.']);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'id_ruangan' => ['nullable', 'exists:data_ruangan,id_ruangan'],
            'tgl_mulai' => ['required', 'date_format:Y-m-d'],
            'tgl_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tgl_mulai'],
            'sepanjang_hari' => ['nullable', 'boolean'],
            'jam_mulai' => ['nullable', 'required_unless:sepanjang_hari,1', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'required_unless:sepanjang_hari,1', 'date_format:H:i', 'after:jam_mulai'],
            'keterangan' => ['required', 'string', 'max:150'],
        ], [
            'jam_mulai.required_unless' => 'Isi jam mulai atau centang "Sepanjang hari".',
            'jam_selesai.required_unless' => 'Isi jam selesai atau centang "Sepanjang hari".',
        ]);

        $sepanjangHari = (bool) ($data['sepanjang_hari'] ?? false);

        return [
            'id_ruangan' => $data['id_ruangan'] ?? null,
            'tgl_mulai' => $data['tgl_mulai'],
            'tgl_selesai' => $data['tgl_selesai'],
            'jam_mulai' => $sepanjangHari ? null : $data['jam_mulai'],
            'jam_selesai' => $sepanjangHari ? null : $data['jam_selesai'],
            'keterangan' => $data['keterangan'],
        ];
    }

    /** Peringatkan admin bila ada peminjaman aktif di rentang penutupan. */
    private function kembali(JadwalTutup $jadwal, string $pesan): RedirectResponse
    {
        $terdampak = Peminjaman::query()->menempatiSlot()
            ->whereBetween('tgl_pinjam', [$jadwal->tgl_mulai->toDateString(), $jadwal->tgl_selesai->toDateString()])
            ->when($jadwal->id_ruangan, fn ($q) => $q->where('id_ruangan', $jadwal->id_ruangan))
            ->when(! $jadwal->sepanjangHari(), fn ($q) => $q->tumpangTindih($jadwal->jam_mulai, $jadwal->jam_selesai))
            ->count();

        $redirect = redirect()->route('admin.jadwal-tutup.index');

        if ($terdampak > 0) {
            return $redirect->with('toast', [
                'type' => 'warning',
                'message' => $pesan.' Perhatian: ada '.$terdampak.' peminjaman aktif pada rentang ini yang perlu ditinjau atau dibatalkan.',
            ])->with('terdampak', [
                'jumlah' => $terdampak,
                'url' => route('admin.peminjaman.index', [
                    'dari' => $jadwal->tgl_mulai->toDateString(),
                    'sampai' => $jadwal->tgl_selesai->toDateString(),
                ]),
            ]);
        }

        return $redirect->with('toast', ['type' => 'success', 'message' => $pesan]);
    }
}
