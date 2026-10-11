<?php

namespace App\Notifications;

use App\Models\Peminjaman;

/** Untuk admin: ada pengajuan peminjaman baru yang perlu ditinjau. */
class PeminjamanBaru extends NotifikasiDasar
{
    public function __construct(public Peminjaman $peminjaman)
    {
        parent::__construct();
    }

    protected function judul(): string
    {
        return 'Pengajuan peminjaman baru';
    }

    protected function pesan(): string
    {
        $p = $this->peminjaman->loadMissing('user', 'ruangan');

        return $p->user->username.' mengajukan '.$p->ruangan->nama_ruangan.' pada '
            .tanggal($p->tgl_pinjam, 'l, d M Y').' pukul '.str_replace(':', '.', $p->rentang_waktu).'.';
    }

    protected function rincian(): array
    {
        $p = $this->peminjaman;

        return [
            'Kode' => $p->kode,
            'Jumlah orang' => $p->jumlah_orang.' orang',
            'Keperluan' => (string) $p->ket_keperluan,
        ];
    }

    protected function url(object $notifiable): string
    {
        return route('admin.peminjaman.show', $this->peminjaman);
    }

    protected function tombol(): string
    {
        return 'Tinjau pengajuan';
    }

    protected function ikon(): string
    {
        return 'heroicon-o-calendar-days';
    }

    protected function tone(): string
    {
        return 'amber';
    }
}
