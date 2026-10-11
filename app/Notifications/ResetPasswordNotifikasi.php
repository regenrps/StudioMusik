<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotifikasi extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $menit = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Atur ulang kata sandi – '.config('studio.nama'))
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.')
            ->action('Atur ulang kata sandi', $url)
            ->line('Tautan ini berlaku selama '.$menit.' menit.')
            ->line('Jika Anda tidak meminta pengaturan ulang, abaikan email ini. Kata sandi Anda tidak akan berubah.')
            ->salutation("Salam,\n\n".config('studio.nama'));
    }
}
