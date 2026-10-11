<?php

namespace App\Enums;

enum TipeField: string
{
    case Teks = 'text';
    case Paragraf = 'textarea';
    case Angka = 'number';
    case Tautan = 'url';
    case Berkas = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Teks => 'Teks singkat',
            self::Paragraf => 'Paragraf',
            self::Angka => 'Angka',
            self::Tautan => 'Tautan (URL)',
            self::Berkas => 'Unggah berkas',
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
