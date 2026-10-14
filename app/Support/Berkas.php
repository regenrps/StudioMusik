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

    /**
     * Satu-satunya ekstensi yang boleh dipakai untuk berkas tersimpan.
     * Ekstensi lain (php, phtml, phar, svg, html, ...) tidak pernah dipakai,
     * meskipun isi berkas lolos validasi — mencegah unggahan "polyglot"
     * (mis. JPEG valid bernama shell.php) dieksekusi di folder publik.
     */
    public const EKSTENSI_AMAN = ['jpg', 'jpeg', 'png', 'webp', 'mp3', 'wav', 'mp4', 'm4a', 'flac', 'pdf', 'zip'];

    public static function simpan(UploadedFile $file, string $folder, string $disk = self::PRIVAT): string
    {
        $nama = now()->format('Ymd').'-'.Str::lower((string) Str::ulid()).'.'.static::ekstensiAman($file);

        return Storage::disk($disk)->putFileAs(trim($folder, '/'), $file, $nama);
    }

    public static function ekstensiAman(UploadedFile $file): string
    {
        $klien = Str::lower((string) $file->getClientOriginalExtension());
        if (in_array($klien, self::EKSTENSI_AMAN, true)) {
            return $klien;
        }

        // Nama klien mencurigakan: pakai ekstensi hasil deteksi isi berkas.
        $terdeteksi = Str::lower((string) $file->guessExtension());

        return in_array($terdeteksi, self::EKSTENSI_AMAN, true) ? $terdeteksi : 'bin';
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
