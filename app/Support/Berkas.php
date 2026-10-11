<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Utilitas penyimpanan berkas unggahan dengan nama acak (tidak dapat ditebak).
 */
class Berkas
{
    public const PUBLIK = 'public';

    public const PRIVAT = 'private';

    public static function simpan(UploadedFile $file, string $folder, string $disk = self::PRIVAT): string
    {
        $ext = Str::lower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $nama = now()->format('Ymd').'-'.Str::lower((string) Str::ulid()).'.'.$ext;

        return Storage::disk($disk)->putFileAs(trim($folder, '/'), $file, $nama);
    }

    public static function hapus(?string $path, string $disk = self::PRIVAT): void
    {
        if (filled($path) && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public static function namaAsli(UploadedFile $file): string
    {
        $nama = Str::of($file->getClientOriginalName())->replaceMatches('/[^\w.\- ()]+/u', '')->trim()->limit(120, '');

        return (string) ($nama->isEmpty() ? 'berkas.'.$file->getClientOriginalExtension() : $nama);
    }

    public static function ukuranLabel(int $bytes): string
    {
        $unit = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($unit) - 1) {
            $n /= 1024;
            $i++;
        }

        return number_format($n, $i ? 1 : 0, ',', '.').' '.$unit[$i];
    }
}
