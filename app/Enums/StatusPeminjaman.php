<?php

namespace App\Enums;

enum StatusPeminjaman: string
{
    case Menunggu = 'pending';
    case Disetujui = 'approved';
    case Berlangsung = 'in_use';
    case Selesai = 'completed';
    case Ditolak = 'rejected';
    case Dibatalkan = 'cancelled';
    case Kedaluwarsa = 'expired';
    case TidakHadir = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Persetujuan',
            self::Disetujui => 'Disetujui',
            self::Berlangsung => 'Sedang Berlangsung',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::TidakHadir => 'Tidak Hadir',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Berlangsung => 'Berlangsung',
            default => $this->label(),
        };
    }

    /** Nama warna yang dipetakan komponen <x-badge>. */
    public function tone(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Disetujui => 'sky',
            self::Berlangsung => 'violet',
            self::Selesai => 'emerald',
            self::Ditolak => 'rose',
            self::Dibatalkan, self::Kedaluwarsa => 'zinc',
            self::TidakHadir => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Menunggu => 'heroicon-o-clock',
            self::Disetujui => 'heroicon-o-check-badge',
            self::Berlangsung => 'heroicon-o-musical-note',
            self::Selesai => 'heroicon-o-check-circle',
            self::Ditolak => 'heroicon-o-x-circle',
            self::Dibatalkan => 'heroicon-o-no-symbol',
            self::Kedaluwarsa => 'heroicon-o-archive-box-x-mark',
            self::TidakHadir => 'heroicon-o-user-minus',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Menunggu => 'Pengajuan sedang ditinjau admin. Anda akan menerima notifikasi setelah diputuskan.',
            self::Disetujui => 'Jadwal sudah dikonfirmasi. Lakukan check-in dengan foto kondisi studio saat tiba.',
            self::Berlangsung => 'Studio sedang Anda pakai. Lakukan check-out dengan foto kondisi akhir setelah selesai.',
            self::Selesai => 'Peminjaman selesai. Terima kasih telah menjaga studio.',
            self::Ditolak => 'Pengajuan tidak dapat disetujui.',
            self::Dibatalkan => 'Peminjaman dibatalkan.',
            self::Kedaluwarsa => 'Pengajuan tidak diproses sampai jadwal terlewat.',
            self::TidakHadir => 'Tidak ada check-in pada jadwal yang disetujui.',
        };
    }

    /** Status yang menempati slot jadwal. */
    public function blocksSlot(): bool
    {
        return in_array($this, self::blocking(), true);
    }

    public function isFinal(): bool
    {
        return ! $this->blocksSlot();
    }

    /** @return array<self> */
    public static function blocking(): array
    {
        return [self::Menunggu, self::Disetujui, self::Berlangsung];
    }

    /** @return array<string> */
    public static function blockingValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::blocking());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
