<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\NotifikasiDasar;
use App\Services\PengaturanService;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Pengiriman notifikasi yang aman: kegagalan SMTP/antrean dicatat
 * ke log tetapi tidak membatalkan aksi pengguna yang sudah tersimpan.
 */
class Notifier
{
    public static function kirim(mixed $penerima, NotifikasiDasar $notifikasi): void
    {
        try {
            Notification::send($penerima, $notifikasi);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Kirim ke staf aktif dengan peran tertentu (bawaan: admin) dan,
     * jika notifikasi memakai email, ke alamat tambahan di Pengaturan.
     */
    public static function staf(NotifikasiDasar $notifikasi, array $peran = [Role::Admin]): void
    {
        $staf = User::query()->aktif()->role(...$peran)->get();
        if ($staf->isNotEmpty()) {
            static::kirim($staf, $notifikasi);
        }

        if (! $notifikasi->pakaiEmail() || ! in_array(Role::Admin, $peran, true)) {
            return;
        }

        $tambahan = array_values(array_diff(
            array_map('strtolower', app(PengaturanService::class)->emailNotifikasi()),
            $staf->pluck('email')->map(fn ($e) => strtolower((string) $e))->all(),
        ));

        if ($tambahan !== []) {
            try {
                Notification::route('mail', $tambahan)->notify($notifikasi);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
