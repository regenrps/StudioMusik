<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoRuangan extends Model
{
    protected $table = 'detail_data_ruangan';

    protected $primaryKey = 'id_detail_ruangan';

    protected $fillable = ['id_ruangan', 'img_ruangan', 'urutan'];

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan', 'id_ruangan');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.ltrim($this->img_ruangan, '/'));
    }
}
