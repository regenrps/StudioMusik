<?php

namespace App\Notifications;

use App\Models\Peminjaman;

/** Untuk staf (in-app saja): peminjam melakukan check-in, check-out, atau membatalkan. */
class AktivitasPeminjaman extends NotifikasiDasar
{
    protected bool $email = false;

    public function __construct(public Peminjaman $peminjaman, public string $aksi)
    {
        parent::__construct();
    }

    protected function judul(): string
    {
        return match ($this->aksi) {
            'checkin' => 'Peminjam check-in',
            'checkout' => 'Peminjam check-out',
            'dibatalkan' => 'Peminjaman dibatalkan peminjam',
            default => 'Aktivitas peminjaman',
        };
    }

    protected function pesan(): string
    {
        $p = $this->peminjaman->loadMissing('user', 'ruangan');
        $jadwal = $p->ruangan->nama_ruangan.' · '.tanggal($p->tgl_pinjam).' '.str_replace(':', '.', $p->rentang_waktu);

        return match ($this->aksi) {
            'checkin' => $p->user->username.' mulai memakai '.$jadwal.'.',
            'checkout' => $p->user->username.' selesai memakai '.$jadwal.' dan mengunggah foto kondisi akhir.',
            'dibatalkan' => $p->user->username.' membatalkan '.$jadwal.'. Slot kini tersedia kembali.',
            default => $jadwal,
        };
    }

    protected function url(object $notifiable): string
    {
        return route('admin.peminjaman.show', $this->peminjaman);
    }

    protected function ikon(): string
    {
        return match ($this->aksi) {
            'checkin' => 'heroicon-o-arrow-right-end-on-rectangle',
            'checkout' => 'heroicon-o-arrow-left-start-on-rectangle',
            default => 'heroicon-o-no-symbol',
        };
    }

    protected function tone(): string
    {
        return match ($this->aksi) {
            'checkin' => 'violet',
            'checkout' => 'emerald',
            default => 'zinc',
        };
    }
}
