<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case K3L = 'k3l';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::K3L => 'K3L',
            self::User => 'Peminjam',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Mengelola seluruh data, persetujuan, dan pengaturan.',
            self::K3L => 'Memantau jadwal, kondisi ruangan, dan laporan masalah.',
            self::User => 'Civitas yang meminjam studio dan memesan jasa musik.',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::User;
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
