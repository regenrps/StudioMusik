<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Jejak audit: siapa melakukan apa terhadap data apa.
 */
class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    const UPDATED_AT = null;

    protected $fillable = ['id_user', 'subjek_type', 'subjek_id', 'aksi', 'deskripsi', 'properti', 'ip_address'];

    protected $casts = [
        'properti' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function subjek(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat aktivitas. Pelaku diambil dari user yang sedang login
     * kecuali diberikan secara eksplisit (misal dari scheduler: null).
     */
    public static function catat(?Model $subjek, string $aksi, string $deskripsi, array $properti = [], User|false|null $pelaku = false): self
    {
        $pelaku = $pelaku === false ? auth()->user() : $pelaku;

        return static::create([
            'id_user' => $pelaku?->getKey(),
            'subjek_type' => $subjek?->getMorphClass(),
            'subjek_id' => $subjek?->getKey(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'properti' => $properti ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
