<?php

namespace App\Notifications;

use App\Models\Laporan;

/** Untuk staf: laporan masalah baru. */
class LaporanBaru extends NotifikasiDasar
{
    public function __construct(public Laporan $laporan)
    {
        parent::__construct();
    }

    protected function judul(): string
    {
        return 'Laporan masalah: '.$this->laporan->jenis_laporan;
    }

    protected function pesan(): string
    {
        $l = $this->laporan->loadMissing('pelapor', 'ruangan');

        return ($l->pelapor?->username ?? 'Staf').' melaporkan masalah'
            .($l->ruangan ? ' di '.$l->ruangan->nama_ruangan : '').': '.str($l->keterangan)->limit(140);
    }

    protected function url(object $notifiable): string
    {
        return route('admin.laporan.show', $this->laporan);
    }

    protected function ikon(): string
    {
        return 'heroicon-o-exclamation-triangle';
    }

    protected function tone(): string
    {
        return 'rose';
    }
}
