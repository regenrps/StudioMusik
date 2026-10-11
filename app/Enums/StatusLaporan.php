<?php

namespace App\Enums;

enum StatusLaporan: string
{
    case Baru = 'open';
    case Ditangani = 'in_progress';
    case Selesai = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Ditangani => 'Sedang Ditangani',
            self::Selesai => 'Selesai',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Baru => 'rose',
            self::Ditangani => 'amber',
            self::Selesai => 'emerald',
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
