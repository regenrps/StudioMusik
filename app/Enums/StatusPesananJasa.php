<?php

namespace App\Enums;

enum StatusPesananJasa: string
{
    case Menunggu = 'pending';
    case Disetujui = 'approved';
    case Diproduksi = 'in_production';
    case Selesai = 'completed';
    case Ditolak = 'rejected';
    case Dibatalkan = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Persetujuan',
            self::Disetujui => 'Dalam Antrean',
            self::Diproduksi => 'Sedang Diproduksi',
            self::Selesai => 'Selesai',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Diproduksi => 'Diproduksi',
            default => $this->label(),
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Disetujui => 'sky',
            self::Diproduksi => 'violet',
            self::Selesai => 'emerald',
            self::Ditolak => 'rose',
            self::Dibatalkan => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Menunggu => 'heroicon-o-clock',
            self::Disetujui => 'heroicon-o-queue-list',
            self::Diproduksi => 'heroicon-o-musical-note',
            self::Selesai => 'heroicon-o-check-circle',
            self::Ditolak => 'heroicon-o-x-circle',
            self::Dibatalkan => 'heroicon-o-no-symbol',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Menunggu => 'Pesanan sedang ditinjau admin.',
            self::Disetujui => 'Pesanan diterima dan menunggu jadwal produksi.',
            self::Diproduksi => 'Tim komposer sedang mengerjakan pesanan Anda.',
            self::Selesai => 'Hasil produksi sudah tersedia.',
            self::Ditolak => 'Pesanan tidak dapat diproses.',
            self::Dibatalkan => 'Pesanan dibatalkan.',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    public function isFinal(): bool
    {
        return ! $this->isActive();
    }

    /** @return array<self> */
    public static function active(): array
    {
        return [self::Menunggu, self::Disetujui, self::Diproduksi];
    }

    /** @return array<string> */
    public static function activeValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::active());
    }

    /** Urutan tahapan untuk stepper progres (0 = keluar dari alur). */
    public function step(): int
    {
        return match ($this) {
            self::Menunggu => 1,
            self::Disetujui => 2,
            self::Diproduksi => 3,
            self::Selesai => 4,
            default => 0,
        };
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
