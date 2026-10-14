<?php

namespace App\Listeners;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Satu tempat untuk mencatat kejadian autentikasi ke:
 *  - tabel log_aktivitas (terlihat di menu Log Aktivitas admin), dan
 *  - berkas storage/logs/keamanan-*.log (JSON, untuk audit/forensik).
 */
class CatatKejadianKeamanan
{
    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'masuk',
            Logout::class => 'keluar',
            Failed::class => 'gagalMasuk',
            PasswordReset::class => 'resetSandi',
        ];
    }

    public function masuk(Login $event): void
    {
        $this->catat('masuk', 'Masuk ke sistem', $event->user, ['ingat_saya' => $event->remember]);
    }

    public function keluar(Logout $event): void
    {
        if ($event->user) {
            $this->catat('keluar', 'Keluar dari sistem', $event->user);
        }
    }

    public function gagalMasuk(Failed $event): void
    {
        $email = Str::limit((string) ($event->credentials['email'] ?? ''), 120, '');
        $alasan = $event->user ? ($event->user->is_active ? 'kata sandi salah' : 'akun nonaktif') : 'email tidak terdaftar';

        $this->catat('gagal_masuk', 'Percobaan masuk gagal ('.$alasan.')', $event->user,
            ['email' => $email, 'alasan' => $alasan], pelaku: null, level: 'warning');
    }

    public function resetSandi(PasswordReset $event): void
    {
        $this->catat('reset_password', 'Mengatur ulang kata sandi lewat email', $event->user);
    }

    private function catat(string $aksi, string $deskripsi, mixed $user, array $properti = [], User|false|null $pelaku = false, string $level = 'info'): void
    {
        $user = $user instanceof User ? $user : null;

        try {
            LogAktivitas::catat($user, $aksi, $deskripsi, $properti, $pelaku === false ? $user : $pelaku);
        } catch (Throwable $e) {
            report($e);
        }

        Log::channel('keamanan')->log($level, $aksi, array_filter([
            'user_id' => $user?->getKey(),
            'ip' => request()->ip(),
            'ua' => Str::limit((string) request()->userAgent(), 160, ''),
            ...$properti,
        ], fn ($v) => $v !== null && $v !== ''));
    }
}
