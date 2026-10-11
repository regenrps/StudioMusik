<?php

namespace App\Notifications;

use App\Models\PesananJasa;

/** Untuk admin (in-app saja): pemesan membatalkan pesanan. */
class AktivitasPesananJasa extends NotifikasiDasar
{
    protected bool $email = false;

    public function __construct(public PesananJasa $pesanan, public string $aksi)
    {
        parent::__construct();
    }

    protected function judul(): string
    {
        return $this->aksi === 'dibatalkan' ? 'Pesanan jasa dibatalkan pemesan' : 'Aktivitas pesanan jasa';
    }

    protected function pesan(): string
    {
        $p = $this->pesanan->loadMissing('user', 'jasa');

        return $p->user->username.' membatalkan pesanan '.$p->jasa->nama_jenis_jasa.' "'.$p->judul.'".';
    }

    protected function url(object $notifiable): string
    {
        return route('admin.jasa.show', $this->pesanan);
    }

    protected function ikon(): string
    {
        return 'heroicon-o-no-symbol';
    }

    protected function tone(): string
    {
        return 'zinc';
    }
}
