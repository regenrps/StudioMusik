<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TutorialAlat extends Model
{
    use HasFactory;

    protected $table = 'tutorial_penggunaan_alat';

    protected $primaryKey = 'id_tutorial';

    protected $fillable = ['nama_alat', 'kategori', 'gambar_alat', 'deskripsi', 'video_url'];

    public function getGambarUrlAttribute(): string
    {
        return $this->gambar_alat
            ? asset('storage/'.ltrim($this->gambar_alat, '/'))
            : asset('assets/img/lobby-studio.jpg');
    }

    public function getRingkasanAttribute(): string
    {
        return Str::limit(trim(html_entity_decode(strip_tags((string) $this->deskripsi))), 120);
    }

    /** URL embed YouTube jika video_url berasal dari YouTube. */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $this->video_url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        return null;
    }
}
