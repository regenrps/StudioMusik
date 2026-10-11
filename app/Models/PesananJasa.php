<?php

namespace App\Models;

use App\Enums\StatusPesananJasa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pesanan jasa musik (jingle, aransemen, minus one, dst).
 *
 * @property StatusPesananJasa $status
 * @property Carbon $tenggat_produksi
 * @property Carbon|null $tgl_produksi
 */
class PesananJasa extends Model
{
    use HasFactory;

    protected $table = 'pesanan_jasa_musik';

    protected $primaryKey = 'id_pesanan_jasa_musik';

    protected $fillable = [
        'kode',
        'id_user',
        'id_jasa_musik',
        'status',
        'judul',
        'tgl_produksi',
        'tenggat_produksi',
        'keterangan',
        'keterangan_admin',
        'hasil_berkas',
        'hasil_tautan',
        'hasil_catatan',
        'alasan_batal',
        'rating',
        'review',
        'diproses_oleh',
        'diproses_at',
        'mulai_produksi_at',
        'selesai_at',
        'dibatalkan_at',
        'diulas_at',
    ];

    protected $casts = [
        'status' => StatusPesananJasa::class,
        'tgl_produksi' => 'date',
        'tenggat_produksi' => 'date',
        'rating' => 'integer',
        'diproses_at' => 'datetime',
        'mulai_produksi_at' => 'datetime',
        'selesai_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
        'diulas_at' => 'datetime',
    ];

    public static function buatKode(): string
    {
        do {
            $kode = 'JSM-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (static::where('kode', $kode)->exists());

        return $kode;
    }

    public function getRouteKeyName(): string
    {
        return 'kode';
    }

    // ---------------------------------------------------------------- Relasi

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function jasa(): BelongsTo
    {
        return $this->belongsTo(JasaMusik::class, 'id_jasa_musik', 'id_jasa_musik');
    }

    public function informasi(): HasMany
    {
        return $this->hasMany(PesananJasaInformasi::class, 'pesanan_jasa_musik_id', 'id_pesanan_jasa_musik')->orderBy('id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh', 'id_user');
    }

    public function log(): MorphMany
    {
        return $this->morphMany(LogAktivitas::class, 'subjek')->latest('created_at')->latest('id');
    }

    // ----------------------------------------------------------------- Scope

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', StatusPesananJasa::activeValues());
    }

    public function scopeStatus(Builder $query, StatusPesananJasa ...$status): Builder
    {
        return $query->whereIn('status', array_map(fn ($s) => $s->value, $status));
    }

    // ---------------------------------------------------------------- Helper

    public function sisaHari(): ?int
    {
        if (! $this->status->isActive()) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->tenggat_produksi, false);
    }

    public function terlambat(): bool
    {
        return $this->status->isActive() && $this->tenggat_produksi->lt(today());
    }

    public function punyaHasil(): bool
    {
        return filled($this->hasil_berkas) || filled($this->hasil_tautan);
    }

    public function bisaDibatalkanPemesan(): bool
    {
        return $this->status === StatusPesananJasa::Menunggu;
    }

    public function bisaDiulas(): bool
    {
        return $this->status === StatusPesananJasa::Selesai && $this->rating === null;
    }
}
