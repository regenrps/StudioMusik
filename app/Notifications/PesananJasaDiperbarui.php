<?php

namespace App\Notifications;

use App\Enums\StatusPesananJasa;
use App\Models\PesananJasa;

/** Untuk pemesan: status pesanan jasa musik berubah. */
class PesananJasaDiperbarui extends NotifikasiDasar
{
    public StatusPesananJasa $status;

    public function __construct(public PesananJasa $pesanan)
    {
        parent::__construct();
        $this->status = $pesanan->status;
    }

    protected function judul(): string
    {
        return match ($this->status) {
            StatusPesananJasa::Disetujui => 'Pesanan jasa diterima',
            StatusPesananJasa::Diproduksi => 'Pesanan mulai diproduksi',
            StatusPesananJasa::Selesai => 'Hasil produksi siap',
            StatusPesananJasa::Ditolak => 'Pesanan jasa ditolak',
            StatusPesananJasa::Dibatalkan => 'Pesanan jasa dibatalkan admin',
            default => 'Status pesanan jasa diperbarui',
        };
    }

    protected function pesan(): string
    {
        $p = $this->pesanan->loadMissing('jasa');
        $nama = $p->jasa->nama_jenis_jasa.' "'.$p->judul.'"';

        return match ($this->status) {
            StatusPesananJasa::Disetujui => 'Pesanan '.$nama.' diterima dan dijadwalkan mulai '.tanggal($p->tgl_produksi, 'd M Y').' dengan target selesai '.tanggal($p->tenggat_produksi, 'd M Y').'.',
            StatusPesananJasa::Diproduksi => 'Tim komposer mulai mengerjakan '.$nama.'. Target selesai '.tanggal($p->tenggat_produksi, 'd M Y').'.',
            StatusPesananJasa::Selesai => 'Hasil produksi '.$nama.' sudah tersedia. Silakan unduh dan beri ulasan.',
            StatusPesananJasa::Ditolak => 'Pesanan '.$nama.' tidak dapat diproses. Alasan: '.($p->keterangan_admin ?: '-'),
            StatusPesananJasa::Dibatalkan => 'Pesanan '.$nama.' dibatalkan oleh admin. Alasan: '.($p->alasan_batal ?: '-'),
            default => 'Status pesanan '.$nama.' kini: '.$this->status->label().'.',
        };
    }

    protected function rincian(): array
    {
        return array_filter([
            'Kode' => $this->pesanan->kode,
            'Catatan admin' => in_array($this->status, [StatusPesananJasa::Disetujui, StatusPesananJasa::Diproduksi], true) ? $this->pesanan->keterangan_admin : null,
            'Catatan hasil' => $this->status === StatusPesananJasa::Selesai ? $this->pesanan->hasil_catatan : null,
        ]);
    }

    protected function url(object $notifiable): string
    {
        return route('akun.jasa.show', $this->pesanan);
    }

    protected function tombol(): string
    {
        return $this->status === StatusPesananJasa::Selesai ? 'Lihat & unduh hasil' : 'Lihat pesanan';
    }

    protected function ikon(): string
    {
        return $this->status->icon();
    }

    protected function tone(): string
    {
        return $this->status->tone();
    }
}
