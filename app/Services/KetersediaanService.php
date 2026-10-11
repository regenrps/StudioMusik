<?php

namespace App\Services;

use App\Enums\StatusPeminjaman;
use App\Models\JadwalTutup;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Sumber kebenaran tunggal untuk ketersediaan jadwal studio.
 *
 * Semua perhitungan waktu memakai "menit sejak 00.00" agar sederhana
 * dan bebas masalah zona waktu. Rentang jadwal bersifat setengah-terbuka
 * [mulai, selesai) sehingga jadwal yang bersambung tidak dianggap bentrok.
 */
class KetersediaanService
{
    private const NAMA_HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function __construct(private readonly PengaturanService $pengaturan)
    {
    }

    /** @return array<string, mixed> */
    public function aturan(): array
    {
        $p = $this->pengaturan;

        return [
            'hari_operasional' => $p->hariOperasional(),
            'label_hari' => $this->labelHari(),
            'jam_buka' => substr((string) $p->get('jam_buka'), 0, 5),
            'jam_tutup' => substr((string) $p->get('jam_tutup'), 0, 5),
            'interval_menit' => max(5, (int) $p->get('interval_menit')),
            'durasi_min_menit' => max(15, (int) $p->get('durasi_min_menit')),
            'durasi_maks_menit' => max(15, (int) $p->get('durasi_maks_menit')),
            'batas_hari_pemesanan' => max(1, (int) $p->get('batas_hari_pemesanan')),
            'jeda_minimal_menit' => max(0, (int) $p->get('jeda_minimal_menit')),
            'maks_orang' => max(1, (int) $p->get('maks_orang')),
            'maks_booking_aktif' => max(0, (int) $p->get('maks_booking_aktif')),
        ];
    }

    public function labelHari(): string
    {
        $hari = $this->pengaturan->hariOperasional();
        sort($hari);
        if ($hari === []) {
            return '—';
        }
        if (count($hari) > 2 && $hari === range(min($hari), max($hari))) {
            return self::NAMA_HARI[min($hari)].'–'.self::NAMA_HARI[max($hari)];
        }

        return implode(', ', array_map(fn ($h) => self::NAMA_HARI[$h] ?? $h, $hari));
    }

    public function isHariOperasional(Carbon $tanggal): bool
    {
        return in_array($tanggal->dayOfWeekIso, $this->pengaturan->hariOperasional(), true);
    }

    public function tanggalTerakhir(): Carbon
    {
        return today()->addDays($this->aturan()['batas_hari_pemesanan']);
    }

    /** @return Collection<int, JadwalTutup> */
    public function penutupan(?Ruangan $ruangan, Carbon $dari, Carbon $sampai): Collection
    {
        return JadwalTutup::query()->berlaku($ruangan?->id_ruangan, $dari, $sampai)->get();
    }

    // ------------------------------------------------------------------
    // Validasi slot
    // ------------------------------------------------------------------

    /**
     * Periksa apakah slot dapat dipesan. Mengembalikan pesan kesalahan
     * yang ramah pengguna, atau null jika slot tersedia.
     *
     * @param  bool  $modeAdmin  Lewati aturan waktu pengajuan (jeda, batas hari, kelipatan interval).
     */
    public function cekSlot(Ruangan $ruangan, Carbon $tanggal, string $mulai, int $durasi, ?int $abaikanId = null, bool $modeAdmin = false): ?string
    {
        $aturan = $this->aturan();
        $tanggal = $tanggal->copy()->startOfDay();

        if (! $ruangan->is_active) {
            return 'Ruangan sedang tidak tersedia untuk dipinjam.';
        }

        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', substr($mulai, 0, 5))) {
            return 'Format jam mulai tidak valid.';
        }

        if (! $modeAdmin && ($durasi < $aturan['durasi_min_menit'] || $durasi > $aturan['durasi_maks_menit'])) {
            return 'Durasi peminjaman harus antara '.durasi_label($aturan['durasi_min_menit']).' dan '.durasi_label($aturan['durasi_maks_menit']).'.';
        }

        if (! $this->isHariOperasional($tanggal)) {
            return 'Peminjaman hanya bisa diajukan pada hari '.$aturan['label_hari'].'.';
        }

        $buka = $this->menit($aturan['jam_buka']);
        $tutup = $this->menit($aturan['jam_tutup']);
        $m = $this->menit($mulai);
        $s = $m + $durasi;

        if ($m < $buka || $s > $tutup) {
            return 'Jam peminjaman harus berada di antara '.jam($aturan['jam_buka']).' dan '.jam($aturan['jam_tutup']).'.';
        }

        if (! $modeAdmin) {
            if (($m - $buka) % $aturan['interval_menit'] !== 0) {
                return 'Jam mulai harus kelipatan '.$aturan['interval_menit'].' menit.';
            }

            $waktuMulai = $tanggal->copy()->addMinutes($m);
            if ($waktuMulai->lt(now()->addMinutes($aturan['jeda_minimal_menit']))) {
                return $aturan['jeda_minimal_menit'] > 0
                    ? 'Pengajuan paling lambat '.durasi_label($aturan['jeda_minimal_menit']).' sebelum jam mulai.'
                    : 'Jam mulai sudah lewat.';
            }

            if ($tanggal->gt($this->tanggalTerakhir())) {
                return 'Peminjaman hanya bisa diajukan maksimal '.$aturan['batas_hari_pemesanan'].' hari ke depan.';
            }
        }

        foreach ($this->penutupan($ruangan, $tanggal, $tanggal) as $tutupan) {
            if ($tutupan->sepanjangHari()
                || ($m < $this->menit($tutupan->jam_selesai) && $this->menit($tutupan->jam_mulai) < $s)) {
                return 'Studio ditutup pada jadwal tersebut: '.$tutupan->keterangan.'.';
            }
        }

        $bentrok = Peminjaman::query()
            ->where('id_ruangan', $ruangan->id_ruangan)
            ->whereDate('tgl_pinjam', $tanggal->toDateString())
            ->menempatiSlot()
            ->tumpangTindih($this->hhmm($m).':00', $this->hhmm($s).':00')
            ->when($abaikanId, fn ($q) => $q->whereKeyNot($abaikanId))
            ->exists();

        if ($bentrok) {
            return 'Jadwal '.$this->hhmm($m).'–'.$this->hhmm($s).' sudah terisi. Silakan pilih jam lain.';
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Data kalender
    // ------------------------------------------------------------------

    /**
     * Ringkasan ketersediaan setiap tanggal dalam satu bulan.
     *
     * @return array<string, array<string, mixed>>
     */
    public function ringkasanBulan(Ruangan $ruangan, Carbon $bulan): array
    {
        $aturan = $this->aturan();
        $awal = $bulan->copy()->startOfMonth();
        $akhir = $bulan->copy()->endOfMonth()->startOfDay();

        $booking = Peminjaman::query()
            ->where('id_ruangan', $ruangan->id_ruangan)
            ->whereBetween('tgl_pinjam', [$awal->toDateString(), $akhir->toDateString()])
            ->menempatiSlot()
            ->get(['id_pesanan_jadwal_studio', 'kode', 'tgl_pinjam', 'waktu_mulai', 'waktu_selesai', 'status'])
            ->groupBy(fn (Peminjaman $p) => $p->tgl_pinjam->toDateString());

        $tutupan = $this->penutupan($ruangan, $awal, $akhir);

        $hasil = [];
        for ($hari = $awal->copy(); $hari->lte($akhir); $hari->addDay()) {
            $tgl = $hari->toDateString();
            $bookingHari = $booking->get($tgl, collect());
            $tutupHari = $tutupan->filter(fn (JadwalTutup $t) => $t->mencakup($hari))->values();
            $terisi = $this->intervalTerisi($hari, $bookingHari, $tutupHari, $aturan);

            $hasil[$tgl] = ['tanggal' => $tgl, 'jumlah_booking' => $bookingHari->count()]
                + $this->statusTanggal($hari->copy(), $terisi, $tutupHari, $aturan);
        }

        return $hasil;
    }

    /**
     * Detail satu hari: jendela operasional dan rentang yang sudah terisi.
     *
     * @return array<string, mixed>
     */
    public function detailHari(Ruangan $ruangan, Carbon $tanggal, ?int $abaikanId = null): array
    {
        $aturan = $this->aturan();
        $tanggal = $tanggal->copy()->startOfDay();

        $booking = Peminjaman::query()
            ->where('id_ruangan', $ruangan->id_ruangan)
            ->whereDate('tgl_pinjam', $tanggal->toDateString())
            ->menempatiSlot()
            ->when($abaikanId, fn ($q) => $q->whereKeyNot($abaikanId))
            ->orderBy('waktu_mulai')
            ->get(['id_pesanan_jadwal_studio', 'kode', 'tgl_pinjam', 'waktu_mulai', 'waktu_selesai', 'status']);

        $tutupHari = $this->penutupan($ruangan, $tanggal, $tanggal);
        $terisi = $this->intervalTerisi($tanggal, $booking, $tutupHari, $aturan);

        return [
            'tanggal' => $tanggal->toDateString(),
            'label' => tanggal_lengkap($tanggal),
            'buka' => $aturan['jam_buka'],
            'tutup' => $aturan['jam_tutup'],
            'paling_awal' => $this->hhmm($this->menitPalingAwal($tanggal, $aturan)),
            'terisi' => array_map(fn ($i) => [
                'mulai' => $this->hhmm($i['mulai']),
                'selesai' => $this->hhmm($i['selesai']),
                'jenis' => $i['jenis'],
                'status' => $i['status'],
                'label' => $i['label'],
            ], $terisi),
        ] + $this->statusTanggal($tanggal->copy(), $terisi, $tutupHari, $aturan);
    }

    /**
     * Event kalender (booking + penutupan) untuk rentang tanggal.
     *
     * @param  bool  $anonim  Sembunyikan identitas peminjam (jadwal publik).
     * @param  array<string>|null  $status
     * @return array<int, array<string, mixed>>
     */
    public function jadwal(Carbon $dari, Carbon $sampai, ?Ruangan $ruangan = null, bool $anonim = true, ?array $status = null): array
    {
        $aturan = $this->aturan();
        $status ??= StatusPeminjaman::blockingValues();

        $events = Peminjaman::query()
            ->with(['user:id_user,username', 'ruangan:id_ruangan,nama_ruangan,slug'])
            ->whereBetween('tgl_pinjam', [$dari->toDateString(), $sampai->toDateString()])
            ->whereIn('status', $status)
            ->when($ruangan, fn ($q) => $q->where('id_ruangan', $ruangan->id_ruangan))
            ->urutJadwal()
            ->get()
            ->map(fn (Peminjaman $p) => [
                'id' => 'b-'.$p->getKey(),
                'jenis' => 'booking',
                'tanggal' => $p->tgl_pinjam->toDateString(),
                'mulai' => substr($p->waktu_mulai, 0, 5),
                'selesai' => substr($p->waktu_selesai, 0, 5),
                'ruangan' => $p->ruangan?->nama_ruangan,
                'status' => $p->status->value,
                'status_label' => $p->status->shortLabel(),
                'tone' => $p->status->tone(),
                'judul' => $anonim
                    ? ($p->status === StatusPeminjaman::Menunggu ? 'Menunggu konfirmasi' : 'Terisi')
                    : $p->user?->username,
                'keterangan' => $anonim ? null : Str::limit((string) $p->ket_keperluan, 90),
                'url' => $anonim ? null : route('admin.peminjaman.show', $p),
            ])
            ->all();

        foreach ($this->penutupan($ruangan, $dari, $sampai) as $tutupan) {
            $mulai = $tutupan->tgl_mulai->copy()->max($dari);
            $akhir = $tutupan->tgl_selesai->copy()->min($sampai);
            for ($hari = $mulai->copy(); $hari->lte($akhir); $hari->addDay()) {
                if (! $this->isHariOperasional($hari)) {
                    continue;
                }
                $events[] = [
                    'id' => 't-'.$tutupan->id.'-'.$hari->format('Ymd'),
                    'jenis' => 'tutup',
                    'tanggal' => $hari->toDateString(),
                    'mulai' => $tutupan->sepanjangHari() ? $aturan['jam_buka'] : substr($tutupan->jam_mulai, 0, 5),
                    'selesai' => $tutupan->sepanjangHari() ? $aturan['jam_tutup'] : substr($tutupan->jam_selesai, 0, 5),
                    'ruangan' => $tutupan->ruangan?->nama_ruangan ?? 'Semua ruangan',
                    'status' => 'tutup',
                    'status_label' => 'Tutup',
                    'tone' => 'zinc',
                    'judul' => $tutupan->keterangan,
                    'keterangan' => null,
                    'url' => null,
                ];
            }
        }

        usort($events, fn ($a, $b) => [$a['tanggal'], $a['mulai']] <=> [$b['tanggal'], $b['mulai']]);

        return $events;
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * @param  Collection<int, Peminjaman>  $booking
     * @param  Collection<int, JadwalTutup>  $tutupan
     * @return array<int, array{mulai:int, selesai:int, jenis:string, status:?string, label:string}>
     */
    private function intervalTerisi(Carbon $tanggal, Collection $booking, Collection $tutupan, array $aturan): array
    {
        $buka = $this->menit($aturan['jam_buka']);
        $tutup = $this->menit($aturan['jam_tutup']);
        $items = [];

        foreach ($tutupan as $t) {
            if (! $t->mencakup($tanggal)) {
                continue;
            }
            $items[] = [
                'mulai' => $t->sepanjangHari() ? $buka : $this->menit($t->jam_mulai),
                'selesai' => $t->sepanjangHari() ? $tutup : $this->menit($t->jam_selesai),
                'jenis' => 'tutup',
                'status' => null,
                'label' => $t->keterangan,
            ];
        }

        foreach ($booking as $b) {
            $items[] = [
                'mulai' => $this->menit($b->waktu_mulai),
                'selesai' => $this->menit($b->waktu_selesai),
                'jenis' => 'booking',
                'status' => $b->status->value,
                'label' => $b->status === StatusPeminjaman::Menunggu ? 'Menunggu konfirmasi' : 'Terisi',
            ];
        }

        usort($items, fn ($a, $b) => $a['mulai'] <=> $b['mulai']);

        return $items;
    }

    /** @return array{status:string, keterangan:?string, sisa_menit:int} */
    private function statusTanggal(Carbon $tanggal, array $terisi, Collection $tutupan, array $aturan): array
    {
        $hasil = fn (string $status, ?string $ket = null, int $sisa = 0) => ['status' => $status, 'keterangan' => $ket, 'sisa_menit' => $sisa];

        if ($tanggal->lt(today())) {
            return $hasil('lewat');
        }
        if (! $this->isHariOperasional($tanggal)) {
            return $hasil('libur', 'Studio tidak beroperasi');
        }
        if ($tanggal->gt($this->tanggalTerakhir())) {
            return $hasil('diluar', 'Belum dibuka untuk pemesanan');
        }

        $penuhHari = $tutupan->first(fn (JadwalTutup $t) => $t->sepanjangHari());
        if ($penuhHari) {
            return $hasil('tutup', $penuhHari->keterangan);
        }

        $dari = $this->menitPalingAwal($tanggal, $aturan);
        $sampai = $this->menit($aturan['jam_tutup']);
        if ($dari + $aturan['durasi_min_menit'] > $sampai) {
            return $hasil('lewat', 'Waktu pengajuan hari ini sudah lewat');
        }

        ['terpanjang' => $terpanjang, 'total' => $total] = $this->celah($dari, $sampai, $terisi);

        if ($terpanjang < $aturan['durasi_min_menit']) {
            return $hasil('penuh', 'Semua slot sudah terisi', $total);
        }

        $ketTutup = $tutupan->isNotEmpty() ? 'Tutup sebagian: '.$tutupan->pluck('keterangan')->implode(', ') : null;

        return $total < ($sampai - $dari)
            ? $hasil('sebagian', $ketTutup ?? 'Sebagian slot sudah terisi', $total)
            : $hasil('tersedia', null, $total);
    }

    /** @return array{terpanjang:int, total:int} */
    private function celah(int $dari, int $sampai, array $terisi): array
    {
        $kursor = $dari;
        $terpanjang = 0;
        $total = 0;

        foreach ($terisi as $i) {
            if ($i['selesai'] <= $kursor) {
                continue;
            }
            if ($i['mulai'] >= $sampai) {
                break;
            }
            if ($i['mulai'] > $kursor) {
                $gap = $i['mulai'] - $kursor;
                $terpanjang = max($terpanjang, $gap);
                $total += $gap;
            }
            $kursor = max($kursor, $i['selesai']);
        }

        if ($kursor < $sampai) {
            $gap = $sampai - $kursor;
            $terpanjang = max($terpanjang, $gap);
            $total += $gap;
        }

        return ['terpanjang' => $terpanjang, 'total' => $total];
    }

    private function menitPalingAwal(Carbon $tanggal, array $aturan): int
    {
        $buka = $this->menit($aturan['jam_buka']);
        if (! $tanggal->isToday()) {
            return $buka;
        }

        $sekarang = now()->hour * 60 + now()->minute + $aturan['jeda_minimal_menit'];
        $interval = $aturan['interval_menit'];
        $selisih = max(0, $sekarang - $buka);

        return $buka + (int) ceil($selisih / $interval) * $interval;
    }

    public function menit(string $waktu): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($waktu, 0, 5)));

        return $h * 60 + $m;
    }

    public function hhmm(int $menit): string
    {
        return sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
    }
}
