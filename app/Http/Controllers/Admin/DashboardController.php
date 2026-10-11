<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusLaporan;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusPesananJasa;
use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\User;
use App\Services\KetersediaanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, KetersediaanService $ketersediaan): View
    {
        $user = $request->user();
        $aturan = $ketersediaan->aturan();

        $hariIni = Peminjaman::query()->with('user:id_user,username,no_wa', 'ruangan:id_ruangan,nama_ruangan')
            ->whereDate('tgl_pinjam', today())
            ->whereIn('status', [...StatusPeminjaman::blockingValues(), StatusPeminjaman::Selesai->value, StatusPeminjaman::TidakHadir->value])
            ->orderBy('waktu_mulai')->get();

        $antrean = Peminjaman::query()->with('user:id_user,username', 'ruangan:id_ruangan,nama_ruangan')
            ->status(StatusPeminjaman::Menunggu)->urutJadwal()->limit(6)->get();

        $berlangsung = Peminjaman::query()->with('user:id_user,username', 'ruangan:id_ruangan,nama_ruangan')
            ->status(StatusPeminjaman::Berlangsung)->urutJadwal()->get();

        $kpi = [
            'menunggu' => Peminjaman::query()->status(StatusPeminjaman::Menunggu)->count(),
            'hari_ini' => $hariIni->whereIn('status', StatusPeminjaman::blocking())->count(),
            'utilisasi' => $this->utilisasiMingguIni($aturan),
            'laporan' => Laporan::query()->where('status', StatusLaporan::Baru->value)->count(),
        ];

        $jasa = null;
        if ($user->isAdmin()) {
            $kpi['jasa_menunggu'] = PesananJasa::query()->status(StatusPesananJasa::Menunggu)->count();
            $kpi['jasa_aktif'] = PesananJasa::query()->aktif()->count();
            $kpi['pengguna'] = User::query()->where('user_role', 'user')->count();

            $jasa = PesananJasa::query()->with('user:id_user,username', 'jasa:id_jasa_musik,nama_jenis_jasa')
                ->aktif()->orderBy('tenggat_produksi')->limit(5)->get();
        }

        return view('admin.dashboard', [
            'kpi' => $kpi,
            'hariIni' => $hariIni,
            'antrean' => $antrean,
            'berlangsung' => $berlangsung,
            'jasa' => $jasa,
            'grafik' => $this->grafik(),
            'aktivitas' => LogAktivitas::query()->with('user:id_user,username,user_role')->latest('created_at')->latest('id')->limit(8)->get(),
            'aturan' => $aturan,
        ]);
    }

    /** Persentase menit operasional minggu ini yang terpakai. */
    private function utilisasiMingguIni(array $aturan): int
    {
        $awal = today()->startOfWeek(Carbon::MONDAY);
        $akhir = today()->endOfWeek(Carbon::SUNDAY);

        $hariOperasional = 0;
        for ($d = $awal->copy(); $d->lte($akhir); $d->addDay()) {
            if (in_array($d->dayOfWeekIso, $aturan['hari_operasional'], true)) {
                $hariOperasional++;
            }
        }

        [$bh, $bm] = array_map('intval', explode(':', $aturan['jam_buka']));
        [$th, $tm] = array_map('intval', explode(':', $aturan['jam_tutup']));
        $kapasitas = $hariOperasional * max(0, ($th * 60 + $tm) - ($bh * 60 + $bm));
        if ($kapasitas === 0) {
            return 0;
        }

        $terpakai = (int) Peminjaman::query()
            ->whereBetween('tgl_pinjam', [$awal->toDateString(), $akhir->toDateString()])
            ->whereIn('status', [StatusPeminjaman::Disetujui->value, StatusPeminjaman::Berlangsung->value, StatusPeminjaman::Selesai->value])
            ->sum(DB::raw('TIME_TO_SEC(TIMEDIFF(waktu_selesai, waktu_mulai)) / 60'));

        return (int) min(100, round($terpakai / $kapasitas * 100));
    }

    /** @return array<string, mixed> */
    private function grafik(): array
    {
        $awal = today()->subDays(29);

        $harian = Peminjaman::query()->toBase()
            ->selectRaw('DATE(tgl_pinjam) as tgl, status, COUNT(*) as jumlah')
            ->where('tgl_pinjam', '>=', $awal->toDateString())
            ->where('tgl_pinjam', '<=', today()->toDateString())
            ->groupBy('tgl', 'status')
            ->get()
            ->groupBy('tgl');

        $label = [];
        $terlaksana = [];
        $batal = [];
        for ($d = $awal->copy(); $d->lte(today()); $d->addDay()) {
            $baris = $harian->get($d->toDateString(), collect());
            $label[] = tanggal($d, 'd M');
            $terlaksana[] = (int) $baris->whereIn('status', ['completed', 'in_use', 'approved'])->sum('jumlah');
            $batal[] = (int) $baris->whereIn('status', ['cancelled', 'rejected', 'expired', 'no_show'])->sum('jumlah');
        }

        $komposisi = Peminjaman::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->whereBetween('tgl_pinjam', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->groupBy('status')->pluck('jumlah', 'status');

        $status = collect(StatusPeminjaman::cases())
            ->filter(fn ($s) => ($komposisi[$s->value] ?? 0) > 0)
            ->map(fn ($s) => ['label' => $s->shortLabel(), 'jumlah' => (int) $komposisi[$s->value], 'tone' => $s->tone()])
            ->values();

        return ['label' => $label, 'terlaksana' => $terlaksana, 'batal' => $batal, 'status' => $status];
    }
}
