<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusPeminjaman;
use App\Exceptions\AturanBisnisException;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Models\User;
use App\Notifications\AktivitasPeminjaman;
use App\Notifications\PeminjamanBaru;
use App\Notifications\PeminjamanDiperbarui;
use App\Notifications\PengingatPeminjaman;
use App\Support\Berkas;
use App\Support\Notifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Seluruh perubahan status peminjaman melewati kelas ini sehingga aturan
 * bisnis, jejak audit, dan notifikasi selalu konsisten.
 *
 * Alur: pending → approved → in_use → completed
 *         ↘ rejected / cancelled / expired / no_show
 */
class PeminjamanService
{
    public function __construct(
        private readonly KetersediaanService $ketersediaan,
        private readonly PengaturanService $pengaturan,
    ) {
    }

    /**
     * @param  array{tgl_pinjam:string, waktu_mulai:string, durasi:int|string, jumlah_orang:int|string, ket_keperluan:string}  $data
     */
    public function ajukan(User $user, Ruangan $ruangan, array $data, UploadedFile $jaminan): Peminjaman
    {
        $tanggal = Carbon::parse($data['tgl_pinjam'])->startOfDay();
        $mulai = substr($data['waktu_mulai'], 0, 5);
        $durasi = (int) $data['durasi'];
        $jumlahOrang = (int) $data['jumlah_orang'];
        $selesai = $tanggal->copy()->setTimeFromTimeString($mulai)->addMinutes($durasi)->format('H:i');

        $batasOrang = min($ruangan->kapasitas ?: PHP_INT_MAX, (int) $this->pengaturan->get('maks_orang'));
        if ($jumlahOrang > $batasOrang) {
            throw new AturanBisnisException('Jumlah orang maksimal '.$batasOrang.' orang untuk ruangan ini.');
        }

        $path = Berkas::simpan($jaminan, 'jaminan');

        try {
            $peminjaman = DB::transaction(function () use ($user, $ruangan, $tanggal, $mulai, $selesai, $durasi, $jumlahOrang, $data, $path) {
                // Kunci baris ruangan & user: pengajuan bersamaan diproses berurutan
                // sehingga dua orang tidak bisa mendapatkan slot yang sama.
                Ruangan::query()->whereKey($ruangan->getKey())->lockForUpdate()->first();
                User::query()->whereKey($user->getKey())->lockForUpdate()->first();

                $maks = (int) $this->pengaturan->get('maks_booking_aktif');
                if ($maks > 0 && $user->jumlahPeminjamanAktif() >= $maks) {
                    throw new AturanBisnisException(
                        'Anda masih memiliki '.$maks.' peminjaman aktif. Selesaikan atau batalkan terlebih dahulu sebelum mengajukan jadwal baru.'
                    );
                }

                if ($pesan = $this->ketersediaan->cekSlot($ruangan, $tanggal, $mulai, $durasi)) {
                    throw new AturanBisnisException($pesan);
                }

                $peminjaman = Peminjaman::create([
                    'kode' => Peminjaman::buatKode(),
                    'id_user' => $user->getKey(),
                    'id_ruangan' => $ruangan->getKey(),
                    'status' => StatusPeminjaman::Menunggu,
                    'tgl_pinjam' => $tanggal->toDateString(),
                    'waktu_mulai' => $mulai.':00',
                    'waktu_selesai' => $selesai.':00',
                    'jumlah_orang' => $jumlahOrang,
                    'ket_keperluan' => trim($data['ket_keperluan']),
                    'img_jaminan' => $path,
                ]);

                LogAktivitas::catat($peminjaman, 'diajukan',
                    'Mengajukan '.$ruangan->nama_ruangan.' pada '.tanggal($tanggal).' '.$mulai.'–'.$selesai, [], $user);

                return $peminjaman;
            });
        } catch (Throwable $e) {
            Berkas::hapus($path);
            throw $e;
        }

        Notifier::staf(new PeminjamanBaru($peminjaman));

        return $peminjaman;
    }

    public function setujui(Peminjaman $peminjaman, User $admin, ?string $catatan = null): Peminjaman
    {
        DB::transaction(function () use ($peminjaman, $admin, $catatan) {
            Ruangan::query()->whereKey($peminjaman->id_ruangan)->lockForUpdate()->first();
            $peminjaman->refresh();

            $this->pastikanStatus($peminjaman, [StatusPeminjaman::Menunggu], 'Pengajuan ini sudah diproses sebelumnya.');

            if ($peminjaman->selesai->isPast()) {
                throw new AturanBisnisException('Jadwal pengajuan ini sudah lewat dan tidak dapat disetujui.');
            }

            $pesan = $this->ketersediaan->cekSlot(
                $peminjaman->ruangan, $peminjaman->tgl_pinjam, $peminjaman->waktu_mulai,
                $peminjaman->durasi_menit, $peminjaman->getKey(), modeAdmin: true,
            );
            if ($pesan) {
                throw new AturanBisnisException('Tidak dapat disetujui: '.$pesan);
            }

            $peminjaman->update([
                'status' => StatusPeminjaman::Disetujui,
                'ket_admin' => $catatan ?: null,
                'diproses_oleh' => $admin->getKey(),
                'diproses_at' => now(),
            ]);

            LogAktivitas::catat($peminjaman, 'disetujui', 'Menyetujui peminjaman', array_filter(['catatan' => $catatan]), $admin);
        });

        Notifier::kirim($peminjaman->user, new PeminjamanDiperbarui($peminjaman));

        return $peminjaman;
    }

    public function tolak(Peminjaman $peminjaman, User $admin, string $alasan): Peminjaman
    {
        DB::transaction(function () use ($peminjaman, $admin, $alasan) {
            $peminjaman->refresh();
            $this->pastikanStatus($peminjaman, [StatusPeminjaman::Menunggu], 'Pengajuan ini sudah diproses sebelumnya.');

            $peminjaman->update([
                'status' => StatusPeminjaman::Ditolak,
                'ket_admin' => $alasan,
                'diproses_oleh' => $admin->getKey(),
                'diproses_at' => now(),
            ]);

            LogAktivitas::catat($peminjaman, 'ditolak', 'Menolak peminjaman', ['alasan' => $alasan], $admin);
        });

        Notifier::kirim($peminjaman->user, new PeminjamanDiperbarui($peminjaman));

        return $peminjaman;
    }

    public function batalkan(Peminjaman $peminjaman, User $oleh, string $alasan): Peminjaman
    {
        $olehPeminjam = $oleh->getKey() === $peminjaman->id_user && ! $oleh->isAdmin();

        DB::transaction(function () use ($peminjaman, $oleh, $alasan, $olehPeminjam) {
            $peminjaman->refresh();

            if ($olehPeminjam && ! $peminjaman->bisaDibatalkanPeminjam()) {
                throw new AturanBisnisException($peminjaman->status === StatusPeminjaman::Disetujui
                    ? 'Pembatalan paling lambat '.$this->pengaturan->get('batas_pembatalan_jam').' jam sebelum jadwal. Silakan hubungi admin.'
                    : 'Peminjaman dengan status "'.$peminjaman->status->label().'" tidak dapat dibatalkan.');
            }

            $this->pastikanStatus($peminjaman, [StatusPeminjaman::Menunggu, StatusPeminjaman::Disetujui],
                'Peminjaman dengan status "'.$peminjaman->status->label().'" tidak dapat dibatalkan.');

            $peminjaman->update([
                'status' => StatusPeminjaman::Dibatalkan,
                'alasan_batal' => $alasan,
                'dibatalkan_at' => now(),
            ]);

            LogAktivitas::catat($peminjaman, 'dibatalkan',
                $olehPeminjam ? 'Peminjam membatalkan peminjaman' : 'Admin membatalkan peminjaman', ['alasan' => $alasan], $oleh);
        });

        if ($olehPeminjam) {
            Notifier::staf(new AktivitasPeminjaman($peminjaman, 'dibatalkan'));
        } else {
            Notifier::kirim($peminjaman->user, new PeminjamanDiperbarui($peminjaman));
        }

        return $peminjaman;
    }

    public function checkIn(Peminjaman $peminjaman, UploadedFile $foto): Peminjaman
    {
        if (! $peminjaman->bisaCheckIn()) {
            throw new AturanBisnisException($peminjaman->status === StatusPeminjaman::Disetujui
                ? 'Check-in dibuka 15 menit sebelum jadwal mulai ('.jam($peminjaman->waktu_mulai).') hingga jadwal berakhir.'
                : 'Check-in hanya untuk peminjaman yang sudah disetujui.');
        }

        $path = Berkas::simpan($foto, 'kondisi');

        try {
            DB::transaction(function () use ($peminjaman, $path) {
                $peminjaman->update([
                    'status' => StatusPeminjaman::Berlangsung,
                    'img_kondisi_awal' => $path,
                    'checkin_at' => now(),
                ]);
                LogAktivitas::catat($peminjaman, 'checkin', 'Check-in dan mengunggah foto kondisi awal');
            });
        } catch (Throwable $e) {
            Berkas::hapus($path);
            throw $e;
        }

        Notifier::staf(new AktivitasPeminjaman($peminjaman, 'checkin'), [Role::Admin, Role::K3L]);

        return $peminjaman;
    }

    public function checkOut(Peminjaman $peminjaman, UploadedFile $foto, ?string $catatan = null): Peminjaman
    {
        if (! $peminjaman->bisaCheckOut()) {
            throw new AturanBisnisException('Check-out hanya untuk peminjaman yang sedang berlangsung.');
        }

        $path = Berkas::simpan($foto, 'kondisi');

        try {
            DB::transaction(function () use ($peminjaman, $path, $catatan) {
                $peminjaman->update([
                    'status' => StatusPeminjaman::Selesai,
                    'img_kondisi_akhir' => $path,
                    'catatan_checkout' => $catatan ?: null,
                    'checkout_at' => now(),
                ]);
                LogAktivitas::catat($peminjaman, 'checkout', 'Check-out dan mengunggah foto kondisi akhir',
                    array_filter(['catatan' => $catatan]));
            });
        } catch (Throwable $e) {
            Berkas::hapus($path);
            throw $e;
        }

        Notifier::staf(new AktivitasPeminjaman($peminjaman, 'checkout'), [Role::Admin, Role::K3L]);

        return $peminjaman;
    }

    /** Admin menandai selesai (mis. peminjam lupa check-out). */
    public function selesaikan(Peminjaman $peminjaman, User $admin, ?string $catatan = null): Peminjaman
    {
        $this->pastikanStatus($peminjaman, [StatusPeminjaman::Berlangsung, StatusPeminjaman::Disetujui, StatusPeminjaman::TidakHadir],
            'Peminjaman ini tidak dapat ditandai selesai.');

        $peminjaman->update([
            'status' => StatusPeminjaman::Selesai,
            'checkout_at' => $peminjaman->checkout_at ?? now(),
            'catatan_checkout' => $catatan ?: $peminjaman->catatan_checkout,
        ]);
        LogAktivitas::catat($peminjaman, 'diselesaikan', 'Admin menandai peminjaman selesai', array_filter(['catatan' => $catatan]), $admin);

        Notifier::kirim($peminjaman->user, new PeminjamanDiperbarui($peminjaman));

        return $peminjaman;
    }

    public function tandaiTidakHadir(Peminjaman $peminjaman, ?User $oleh = null): Peminjaman
    {
        $this->pastikanStatus($peminjaman, [StatusPeminjaman::Disetujui], 'Hanya peminjaman yang disetujui yang dapat ditandai tidak hadir.');

        $peminjaman->update(['status' => StatusPeminjaman::TidakHadir]);
        LogAktivitas::catat($peminjaman, 'tidak_hadir',
            $oleh ? 'Admin menandai peminjam tidak hadir' : 'Otomatis: tidak ada check-in hingga jadwal berakhir', [], $oleh);

        Notifier::kirim($peminjaman->user, new PeminjamanDiperbarui($peminjaman));

        return $peminjaman;
    }

    public function beriUlasan(Peminjaman $peminjaman, int $rating, ?string $review): Peminjaman
    {
        if (! $peminjaman->bisaDiulas()) {
            throw new AturanBisnisException('Ulasan hanya dapat diberikan sekali untuk peminjaman yang telah selesai.');
        }

        $peminjaman->update([
            'rating' => max(1, min(5, $rating)),
            'review' => $review ?: null,
            'diulas_at' => now(),
        ]);
        LogAktivitas::catat($peminjaman, 'diulas', 'Memberi ulasan '.$rating.'/5');

        return $peminjaman;
    }

    /**
     * Dijalankan scheduler (dan middleware cadangan): kedaluwarsakan pengajuan
     * yang tak diproses, tandai tidak hadir, dan kirim pengingat.
     *
     * @return array{kedaluwarsa:int, tidak_hadir:int, pengingat:int}
     */
    public function sinkronOtomatis(): array
    {
        $hasil = ['kedaluwarsa' => 0, 'tidak_hadir' => 0, 'pengingat' => 0];
        $sekarang = now();
        $hari = $sekarang->toDateString();
        $jam = $sekarang->format('H:i:s');

        $lewat = fn ($q, string $kolom) => $q->where(fn ($q) => $q
            ->whereDate('tgl_pinjam', '<', $hari)
            ->orWhere(fn ($q) => $q->whereDate('tgl_pinjam', $hari)->where($kolom, '<=', $jam)));

        Peminjaman::query()->status(StatusPeminjaman::Menunggu)->where(fn ($q) => $lewat($q, 'waktu_mulai'))
            ->with('user', 'ruangan')->lazyById(100, 'id_pesanan_jadwal_studio')
            ->each(function (Peminjaman $p) use (&$hasil) {
                $p->update(['status' => StatusPeminjaman::Kedaluwarsa]);
                LogAktivitas::catat($p, 'kedaluwarsa', 'Otomatis: pengajuan tidak diproses hingga jadwal dimulai', [], null);
                Notifier::kirim($p->user, new PeminjamanDiperbarui($p));
                $hasil['kedaluwarsa']++;
            });

        Peminjaman::query()->status(StatusPeminjaman::Disetujui)->where(fn ($q) => $lewat($q, 'waktu_selesai'))
            ->with('user', 'ruangan')->lazyById(100, 'id_pesanan_jadwal_studio')
            ->each(function (Peminjaman $p) use (&$hasil) {
                $this->tandaiTidakHadir($p);
                $hasil['tidak_hadir']++;
            });

        $menit = (int) $this->pengaturan->get('pengingat_menit');
        if ($menit > 0) {
            $batas = $sekarang->copy()->addMinutes($menit);
            Peminjaman::query()->status(StatusPeminjaman::Disetujui)->whereNull('pengingat_at')
                ->whereBetween('tgl_pinjam', [$hari, $batas->toDateString()])
                ->with('user', 'ruangan')->get()
                ->filter(fn (Peminjaman $p) => $p->mulai->between($sekarang, $batas))
                ->each(function (Peminjaman $p) use (&$hasil) {
                    $p->update(['pengingat_at' => now()]);
                    Notifier::kirim($p->user, new PengingatPeminjaman($p));
                    $hasil['pengingat']++;
                });
        }

        return $hasil;
    }

    /** @param  array<StatusPeminjaman>  $diizinkan */
    private function pastikanStatus(Peminjaman $peminjaman, array $diizinkan, string $pesan): void
    {
        if (! in_array($peminjaman->status, $diizinkan, true)) {
            throw new AturanBisnisException($pesan);
        }
    }
}
