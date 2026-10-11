<?php

namespace App\Models;

use App\Enums\TipeField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Field isian dinamis pada formulir pemesanan sebuah jasa musik.
 */
class FieldJasaMusik extends Model
{
    protected $table = 'form_jasa_musik';

    protected $fillable = ['jasa_musik_id', 'nama_field', 'jenis_field', 'is_required', 'bantuan', 'urutan'];

    protected $casts = [
        'jenis_field' => TipeField::class,
        'is_required' => 'boolean',
        'urutan' => 'integer',
    ];

    public function jasa(): BelongsTo
    {
        return $this->belongsTo(JasaMusik::class, 'jasa_musik_id', 'id_jasa_musik');
    }

    /** Nama input HTML yang stabil untuk field ini. */
    public function getInputNameAttribute(): string
    {
        return 'field_'.$this->id;
    }
}
