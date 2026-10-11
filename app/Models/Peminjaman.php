<?php

namespace App\Models;

use App\Enums\StatusPeminjaman;
use App\Services\PengaturanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Peminjaman (booking) ruangan studio.
 *
 * @property Carbon $tgl_pinjam
 * @property StatusPeminjaman $status
 */
class Peminjaman extends Model
{
    use HasFactory;

    protected $table = 'pesanan_jadwal_studio';

    protected $primaryKey = 'id_pesanan_jadwal_studio';

    protected $fillable = [
        'kode',
        'id_user',
        'id_ruangan',
        'status',
        'tgl_pinjam',
        'waktu_mulai',
        'waktu_selesai',
        'jumlah_orang',
        'img_jaminan',
        'img_kondisi_awal',
        'img_kondisi_akhir',
        'catatan_checkout',
        'ket_keperluan',
        'ket_admin',
        'alasan_batal',
        'rating',
        'review',
        'diproses_oleh',
        'diproses_at',
        'checkin_at',
        'checkout_at',
        'dibatalkan_at',
        'diulas_at',
        'pengingat_at',
    ];

    protected $casts = [
        'status' => StatusPeminjaman::class,
        'tgl_pinjam' => 'date',
        'jumlah_orang' => 'integer',
        'rating' => 'integer',
        'diproses_at' => 'datetime',
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
        'diulas_at' => 'datetime',
        'pengingat_at' => 'datetime',
    ];

    public static function buatKode(): string
    {
        do {
            $kode = 'STD-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
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

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan', 'id_ruangan');
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

    public function scopeMenempatiSlot(Builder $query): Builder
    {
        return $query->whereIn('status', StatusPeminjaman::blockingValues());
    }

    public function scopeStatus(Builder $query, StatusPeminjaman ...$status): Builder
    {
        return $query->whereIn('status', array_map(fn ($s) => $s->value, $status));
    }

    public function scopeTumpangTindih(Builder $query, string $mulai, string $selesai): Builder
    {
        // Dua rentang [a,b) dan [c,d) bertumpuk jika a < d dan c < b.
        // Jadwal yang bersambung (17.00–18.00 lalu 18.00–19.00) tidak bentrok.
        return $query->where('waktu_mulai', '<', $selesai)->where('waktu_selesai', '>', $mulai);
    }

    public function scopeUrutJadwal(Builder $query, string $arah = 'asc'): Builder
    {
        return $query->orderBy('tgl_pinjam', $arah)->orderBy('waktu_mulai', $arah);
    }

    // -------------------------------------------------------------- Atribut

    public function getMulaiAttribute(): Carbon
    {
        return Carbon::parse($this->tgl_pinjam->toDateString().' '.$this->waktu_mulai);
    }

    public function getSelesaiAttribute(): Carbon
    {
        return Carbon::parse($this->tgl_pinjam->toDateString().' '.$this->waktu_selesai);
    }

    public function getDurasiMenitAttribute(): int
    {
        return (int) $this->mulai->diffInMinutes($this->selesai);
    }

    public function getRentangWaktuAttribute(): string
    {
        return substr((string) $this->waktu_mulai, 0, 5).'–'.substr((string) $this->waktu_selesai, 0, 5);
    }

    public function getDurasiLabelAttribute(): string
    {
        return durasi_label($this->durasi_menit);
    }

    // ------------------------------------------------- Aturan tindakan (UI)

    public function bisaDibatalkanPeminjam(): bool
    {
        if ($this->status === StatusPeminjaman::Menunggu) {
            return true;
        }

        if ($this->status === StatusPeminjaman::Disetujui) {
            $batasJam = (int) app(PengaturanService::class)->get('batas_pembatalan_jam');

            return now()->lt($this->mulai->copy()->subHours($batasJam));
        }

        return false;
    }

    public function bisaCheckIn(): bool
    {
        return $this->status === StatusPeminjaman::Disetujui
            && now()->between($this->mulai->copy()->subMinutes(15), $this->selesai);
    }

    public function bisaCheckOut(): bool
    {
        return $this->status === StatusPeminjaman::Berlangsung;
    }

    public function bisaDiulas(): bool
    {
        return $this->status === StatusPeminjaman::Selesai && $this->rating === null;
    }

    public function sudahLewat(): bool
    {
        return $this->selesai->isPast();
    }
}
