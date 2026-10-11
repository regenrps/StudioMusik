<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Services\PengaturanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(private readonly PengaturanService $pengaturan)
    {
    }

    public function edit(): View
    {
        return view('admin.pengaturan', ['nilai' => $this->pengaturan->all()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hari_operasional' => ['required', 'array', 'min:1'],
            'hari_operasional.*' => ['integer', 'between:1,7'],
            'jam_buka' => ['required', 'date_format:H:i'],
            'jam_tutup' => ['required', 'date_format:H:i', 'after:jam_buka'],
            'interval_menit' => ['required', 'integer', 'in:5,10,15,30,60'],
            'durasi_min_menit' => ['required', 'integer', 'min:15', 'max:720'],
            'durasi_maks_menit' => ['required', 'integer', 'gte:durasi_min_menit', 'max:720'],
            'batas_hari_pemesanan' => ['required', 'integer', 'min:1', 'max:180'],
            'jeda_minimal_menit' => ['required', 'integer', 'min:0', 'max:2880'],
            'maks_booking_aktif' => ['required', 'integer', 'min:0', 'max:20'],
            'maks_orang' => ['required', 'integer', 'min:1', 'max:200'],
            'batas_pembatalan_jam' => ['required', 'integer', 'min:0', 'max:168'],
            'pengingat_menit' => ['required', 'integer', 'min:0', 'max:2880'],
            'jasa_tenggat_min_hari' => ['required', 'integer', 'min:1', 'max:60'],
            'jasa_tenggat_maks_hari' => ['required', 'integer', 'gte:jasa_tenggat_min_hari', 'max:365'],
            'email_notifikasi' => ['nullable', 'string', 'max:500'],
            'pengumuman' => ['nullable', 'string', 'max:500'],
        ], [], [
            'hari_operasional' => 'hari operasional',
            'jam_buka' => 'jam buka',
            'jam_tutup' => 'jam tutup',
            'interval_menit' => 'interval jam mulai',
            'durasi_min_menit' => 'durasi minimum',
            'durasi_maks_menit' => 'durasi maksimum',
            'batas_hari_pemesanan' => 'batas hari pemesanan',
            'jeda_minimal_menit' => 'jeda pengajuan',
            'maks_booking_aktif' => 'maksimal peminjaman aktif',
            'maks_orang' => 'maksimal orang',
            'batas_pembatalan_jam' => 'batas pembatalan',
            'pengingat_menit' => 'waktu pengingat',
            'jasa_tenggat_min_hari' => 'tenggat minimum jasa',
            'jasa_tenggat_maks_hari' => 'tenggat maksimum jasa',
            'email_notifikasi' => 'email notifikasi',
        ]);

        $data['hari_operasional'] = array_values(array_unique(array_map('intval', $data['hari_operasional'])));
        sort($data['hari_operasional']);

        foreach (preg_split('/[\s,;]+/', (string) ($data['email_notifikasi'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withInput()->withErrors(['email_notifikasi' => '"'.$email.'" bukan alamat email yang valid.']);
            }
        }

        $lama = $this->pengaturan->all();
        $this->pengaturan->set($data);

        $berubah = collect($data)->filter(fn ($v, $k) => ($lama[$k] ?? null) != $v)->keys()->all();
        if ($berubah) {
            LogAktivitas::catat(null, 'pengaturan_diubah', 'Memperbarui pengaturan studio', ['kolom' => $berubah]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Pengaturan disimpan dan langsung berlaku.']);
    }
}
