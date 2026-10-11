<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Penutupan studio pada rentang tanggal tertentu (UTS/UAS, kegiatan UKM,
 * perawatan alat). Jika jam tidak diisi, studio tutup sepanjang hari.
 *
 * @property Carbon $tgl_mulai
 * @property Carbon $tgl_selesai
 */
class JadwalTutup extends Model
{
    protected $table = 'jadwal_tutup';

    protected $fillable = ['id_ruangan', 'tgl_mulai', 'tgl_selesai', 'jam_mulai', 'jam_selesai', 'keterangan', 'dibuat_oleh'];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan', 'id_ruangan');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'id_user');
    }

    /**
     * Penutupan yang beririsan dengan rentang tanggal. Jika ruangan diberikan,
     * hanya penutupan untuk ruangan tersebut atau untuk semua ruangan.
     */
    public function scopeBerlaku(Builder $query, ?int $idRuangan, Carbon $dari, Carbon $sampai): Builder
    {
        return $query
            ->whereDate('tgl_mulai', '<=', $sampai->toDateString())
            ->whereDate('tgl_selesai', '>=', $dari->toDateString())
            ->when($idRuangan !== null, fn ($q) => $q->where(
                fn ($q) => $q->whereNull('id_ruangan')->orWhere('id_ruangan', $idRuangan)
            ));
    }

    public function sepanjangHari(): bool
    {
        return blank($this->jam_mulai) || blank($this->jam_selesai);
    }

    public function mencakup(Carbon $tanggal): bool
    {
        return $tanggal->betweenIncluded($this->tgl_mulai->copy()->startOfDay(), $this->tgl_selesai->copy()->endOfDay());
    }

    public function getRentangLabelAttribute(): string
    {
        $tgl = $this->tgl_mulai->isSameDay($this->tgl_selesai)
            ? tanggal($this->tgl_mulai)
            : tanggal($this->tgl_mulai, 'd M').' – '.tanggal($this->tgl_selesai);

        return $this->sepanjangHari() ? $tgl.' · Sepanjang hari' : $tgl.' · '.substr($this->jam_mulai, 0, 5).'–'.substr($this->jam_selesai, 0, 5);
    }
}
