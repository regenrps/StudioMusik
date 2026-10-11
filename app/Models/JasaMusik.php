<?php

namespace App\Models;

use App\Enums\StatusPesananJasa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JasaMusik extends Model
{
    use HasFactory;

    protected $table = 'master_jasa_musik';

    protected $primaryKey = 'id_jasa_musik';

    protected $fillable = [
        'nama_jenis_jasa',
        'slug',
        'ringkasan',
        'deskripsi',
        'sk',
        'gambar',
        'estimasi_hari',
        'harga_mulai',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'estimasi_hari' => 'integer',
        'harga_mulai' => 'integer',
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (JasaMusik $jasa) {
            if (blank($jasa->slug) || $jasa->isDirty('nama_jenis_jasa')) {
                $dasar = Str::slug($jasa->nama_jenis_jasa) ?: 'jasa';
                $slug = $dasar;
                $i = 2;
                while (static::where('slug', $slug)->when($jasa->exists, fn ($q) => $q->whereKeyNot($jasa->getKey()))->exists()) {
                    $slug = $dasar.'-'.$i++;
                }
                $jasa->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function field(): HasMany
    {
        return $this->hasMany(FieldJasaMusik::class, 'jasa_musik_id', 'id_jasa_musik')->orderBy('urutan')->orderBy('id');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(PesananJasa::class, 'id_jasa_musik', 'id_jasa_musik');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama_jenis_jasa');
    }

    public function getGambarUrlAttribute(): string
    {
        return $this->gambar
            ? asset('storage/'.ltrim($this->gambar, '/'))
            : asset('assets/img/BuatJingle.jpg');
    }

    public function getHargaLabelAttribute(): string
    {
        return $this->harga_mulai ? 'Mulai '.rupiah($this->harga_mulai) : 'Gratis untuk civitas ITERA';
    }

    /** @return array{rata: float|null, jumlah: int} */
    public function ringkasanUlasan(): array
    {
        $q = $this->pesanan()->whereNotNull('rating');

        return [
            'rata' => ($avg = (clone $q)->avg('rating')) ? round((float) $avg, 1) : null,
            'jumlah' => $q->count(),
        ];
    }

    public function jumlahSelesai(): int
    {
        return $this->pesanan()->where('status', StatusPesananJasa::Selesai)->count();
    }
}
