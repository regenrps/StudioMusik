<?php

namespace App\Notifications;

use App\Models\PesananJasa;

/** Untuk admin: ada pesanan jasa musik baru. */
class PesananJasaBaru extends NotifikasiDasar
{
    public function __construct(public PesananJasa $pesanan)
    {
        parent::__construct();
    }

    protected function judul(): string
    {
        return 'Pesanan jasa musik baru';
    }

    protected function pesan(): string
    {
        $p = $this->pesanan->loadMissing('user', 'jasa');

        return $p->user->username.' memesan '.$p->jasa->nama_jenis_jasa.' "'.$p->judul.'" dengan tenggat '.tanggal($p->tenggat_produksi, 'l, d M Y').'.';
    }

    protected function rincian(): array
    {
        return ['Kode' => $this->pesanan->kode];
    }

    protected function url(object $notifiable): string
    {
        return route('admin.jasa.show', $this->pesanan);
    }

    protected function tombol(): string
    {
        return 'Tinjau pesanan';
    }

    protected function ikon(): string
    {
        return 'heroicon-o-musical-note';
    }

    protected function tone(): string
    {
        return 'amber';
    }
}
