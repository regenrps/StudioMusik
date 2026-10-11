<?php

namespace App\Models;

use App\Enums\StatusLaporan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Laporan masalah/insiden (kerusakan alat, kebersihan, keamanan).
 */
class Laporan extends Model
{
    protected $table = 'laporan';

    protected $primaryKey = 'id_laporan';

    protected $fillable = [
        'id_user',
        'id_ruangan',
        'id_pesanan_jadwal_studio',
        'tgl_laporan',
        'jenis_laporan',
        'status',
        'gambar',
        'keterangan',
        'tindak_lanjut',
        'selesai_at',
    ];

    protected $casts = [
        'tgl_laporan' => 'date',
        'gambar' => 'array',
        'status' => StatusLaporan::class,
        'selesai_at' => 'datetime',
    ];

    public const JENIS = [
        'Kerusakan Alat',
        'Kebersihan',
        'Keamanan',
        'Kelistrikan',
        'Pelanggaran SOP',
        'Lainnya',
    ];

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan', 'id_ruangan');
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_pesanan_jadwal_studio', 'id_pesanan_jadwal_studio');
    }

    public function log(): MorphMany
    {
        return $this->morphMany(LogAktivitas::class, 'subjek')->latest('created_at')->latest('id');
    }

    /** @return array<int, string> */
    public function daftarGambar(): array
    {
        return array_values(array_filter((array) $this->gambar));
    }
}
