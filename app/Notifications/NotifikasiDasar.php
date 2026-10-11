<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Kerangka notifikasi: satu definisi menghasilkan notifikasi in-app
 * (kanal database) dan email dengan isi yang konsisten.
 */
abstract class NotifikasiDasar extends Notification implements ShouldQueue
{
    use Queueable;

    /** Kirim juga lewat email (selain notifikasi in-app). */
    protected bool $email = true;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract protected function judul(): string;

    abstract protected function pesan(): string;

    abstract protected function url(object $notifiable): string;

    protected function ikon(): string
    {
        return 'heroicon-o-bell';
    }

    protected function tone(): string
    {
        return 'indigo';
    }

    /** @return array<string, string> Baris detail pada email. */
    protected function rincian(): array
    {
        return [];
    }

    protected function tombol(): string
    {
        return 'Lihat detail';
    }

    public function pakaiEmail(): bool
    {
        return $this->email;
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return $this->email ? ['mail'] : [];
        }

        return $this->email && filled($notifiable->email ?? null) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nama = $notifiable instanceof User ? $notifiable->username : null;

        $mail = (new MailMessage)
            ->subject($this->judul().' – '.config('studio.nama'))
            ->greeting('Halo'.($nama ? ', '.$nama : '').'!')
            ->line($this->pesan());

        foreach ($this->rincian() as $label => $nilai) {
            $mail->line('**'.$label.':** '.$nilai);
        }

        return $mail
            ->action($this->tombol(), $this->url($notifiable))
            ->salutation("Salam,\n\n".config('studio.nama'));
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => $this->judul(),
            'pesan' => $this->pesan(),
            'url' => $this->url($notifiable),
            'ikon' => $this->ikon(),
            'tone' => $this->tone(),
        ];
    }

    protected function untukStaf(object $notifiable): bool
    {
        return $notifiable instanceof User ? $notifiable->isStaff() : true;
    }
}
