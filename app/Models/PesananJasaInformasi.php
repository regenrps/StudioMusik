<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jawaban pemesan untuk setiap field dinamis sebuah jasa musik.
 * Nama field disalin saat pesanan dibuat agar riwayat tetap utuh
 * meskipun admin mengubah formulir layanan di kemudian hari.
 */
class PesananJasaInformasi extends Model
{
    protected $table = 'pesanan_jasa_musik_informasi';

    protected $fillable = ['pesanan_jasa_musik_id', 'id_field', 'tipe_field', 'nama_field', 'value_field', 'nama_asli'];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(PesananJasa::class, 'pesanan_jasa_musik_id', 'id_pesanan_jasa_musik');
    }

    public function isBerkas(): bool
    {
        return $this->tipe_field === 'file';
    }

    public function isTautan(): bool
    {
        return $this->tipe_field === 'url';
    }
}
