<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ruangan extends Model
{
    use HasFactory;

    protected $table = 'data_ruangan';

    protected $primaryKey = 'id_ruangan';

    protected $fillable = [
        'nama_ruangan',
        'slug',
        'deskripsi',
        'thumbnail',
        'kapasitas',
        'fasilitas',
        'lokasi',
        'is_active',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Ruangan $ruangan) {
            if (blank($ruangan->slug) || $ruangan->isDirty('nama_ruangan')) {
                $ruangan->slug = static::slugUnik($ruangan->nama_ruangan, $ruangan->getKey());
            }
        });
    }

    public static function slugUnik(string $nama, $abaikanId = null): string
    {
        $dasar = Str::slug($nama) ?: 'ruangan';
        $slug = $dasar;
        $i = 2;
        while (static::where('slug', $slug)->when($abaikanId, fn ($q) => $q->whereKeyNot($abaikanId))->exists()) {
            $slug = $dasar.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------------------------------------------------------------- Relasi

    public function foto(): HasMany
    {
        return $this->hasMany(FotoRuangan::class, 'id_ruangan', 'id_ruangan')->orderBy('urutan');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_ruangan', 'id_ruangan');
    }

    // ----------------------------------------------------------------- Scope

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ---------------------------------------------------------------- Helper

    public function getThumbnailUrlAttribute(): string
    {
        return $this->thumbnail
            ? asset('storage/'.ltrim($this->thumbnail, '/'))
            : asset('assets/img/ruang-studio.jpg');
    }

    /** @return array<int, string> */
    public function getDaftarFasilitasAttribute(): array
    {
        return collect(preg_split('/[\r\n,;]+/', (string) $this->fasilitas))
            ->map(fn ($f) => trim($f, " \t-•."))
            ->filter()
            ->values()
            ->all();
    }

    /** @return array{rata: float|null, jumlah: int} */
    public function ringkasanUlasan(): array
    {
        $q = $this->peminjaman()->whereNotNull('rating');

        return [
            'rata' => ($avg = (clone $q)->avg('rating')) ? round((float) $avg, 1) : null,
            'jumlah' => $q->count(),
        ];
    }

    public function jumlahPeminjamanSelesai(): int
    {
        return $this->peminjaman()->where('status', StatusPeminjaman::Selesai)->count();
    }
}
