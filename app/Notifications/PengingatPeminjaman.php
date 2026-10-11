<?php

namespace App\Notifications;

use App\Models\Peminjaman;

/** Untuk peminjam: pengingat menjelang jadwal. */
class PengingatPeminjaman extends NotifikasiDasar
{
    public function __construct(public Peminjaman $peminjaman)
    {
        parent::__construct();
    }

    protected function judul(): string
    {
        return 'Pengingat jadwal studio';
    }

    protected function pesan(): string
    {
        $p = $this->peminjaman->loadMissing('ruangan');

        return 'Jadwal Anda di '.$p->ruangan->nama_ruangan.' dimulai '.relatif($p->mulai)
            .' (pukul '.jam($p->waktu_mulai).'). Jangan lupa check-in dengan foto kondisi studio saat tiba.';
    }

    protected function rincian(): array
    {
        return [
            'Kode' => $this->peminjaman->kode,
            'Lokasi' => (string) ($this->peminjaman->ruangan->lokasi ?? config('studio.lokasi')),
        ];
    }

    protected function url(object $notifiable): string
    {
        return route('akun.peminjaman.show', $this->peminjaman);
    }

    protected function ikon(): string
    {
        return 'heroicon-o-bell-alert';
    }

    protected function tone(): string
    {
        return 'sky';
    }
}
