<?php

namespace App\Notifications;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;

/** Untuk peminjam: status peminjaman berubah. */
class PeminjamanDiperbarui extends NotifikasiDasar
{
    /** Status saat notifikasi dibuat (antrean bisa memproses belakangan). */
    public StatusPeminjaman $status;

    public function __construct(public Peminjaman $peminjaman)
    {
        parent::__construct();
        $this->status = $peminjaman->status;
    }

    protected function judul(): string
    {
        return match ($this->status) {
            StatusPeminjaman::Disetujui => 'Peminjaman disetujui',
            StatusPeminjaman::Ditolak => 'Peminjaman ditolak',
            StatusPeminjaman::Dibatalkan => 'Peminjaman dibatalkan admin',
            StatusPeminjaman::Kedaluwarsa => 'Pengajuan kedaluwarsa',
            StatusPeminjaman::TidakHadir => 'Tercatat tidak hadir',
            StatusPeminjaman::Selesai => 'Peminjaman diselesaikan',
            default => 'Status peminjaman diperbarui',
        };
    }

    protected function pesan(): string
    {
        $p = $this->peminjaman->loadMissing('ruangan');
        $jadwal = $p->ruangan->nama_ruangan.', '.tanggal($p->tgl_pinjam, 'l, d M Y').' pukul '.str_replace(':', '.', $p->rentang_waktu);

        return match ($this->status) {
            StatusPeminjaman::Disetujui => 'Pengajuan Anda untuk '.$jadwal.' telah disetujui. Saat tiba, lakukan check-in dengan memotret kondisi studio.',
            StatusPeminjaman::Ditolak => 'Pengajuan Anda untuk '.$jadwal.' tidak dapat disetujui. Alasan: '.($p->ket_admin ?: '-'),
            StatusPeminjaman::Dibatalkan => 'Peminjaman '.$jadwal.' dibatalkan oleh admin. Alasan: '.($p->alasan_batal ?: '-'),
            StatusPeminjaman::Kedaluwarsa => 'Pengajuan untuk '.$jadwal.' tidak diproses hingga jadwal lewat. Silakan ajukan jadwal lain.',
            StatusPeminjaman::TidakHadir => 'Tidak ada check-in untuk peminjaman '.$jadwal.'. Hubungi admin jika ini keliru.',
            StatusPeminjaman::Selesai => 'Peminjaman '.$jadwal.' telah ditandai selesai oleh admin.',
            default => 'Status peminjaman '.$jadwal.' kini: '.$this->status->label().'.',
        };
    }

    protected function rincian(): array
    {
        return array_filter([
            'Kode' => $this->peminjaman->kode,
            'Catatan admin' => $this->status === StatusPeminjaman::Disetujui ? $this->peminjaman->ket_admin : null,
        ]);
    }

    protected function url(object $notifiable): string
    {
        return route('akun.peminjaman.show', $this->peminjaman);
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
