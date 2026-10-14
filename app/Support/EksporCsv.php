<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV yang langsung terbaca rapi di Microsoft Excel (UTF-8 BOM,
 * pemisah titik koma sesuai pengaturan regional Indonesia).
 */
class EksporCsv
{
    /**
     * @param  array<int, string>  $header
     * @param  iterable<array<int, mixed>>  $baris
     */
    public static function unduh(string $namaBerkas, array $header, iterable $baris): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header, ';');
            foreach ($baris as $row) {
                fputcsv($out, array_map([static::class, 'sel'], $row), ';');
            }
            fclose($out);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Netralkan "formula injection": teks dari pengguna yang diawali = + - @
     * (atau tab/CR) akan dieksekusi Excel sebagai rumus. Diberi awalan
     * apostrof agar dibaca sebagai teks biasa.
     */
    public static function sel(mixed $nilai): mixed
    {
        if (! is_string($nilai)) {
            return $nilai;
        }

        $nilai = str_replace(["\r\n", "\r"], "\n", $nilai);

        return preg_match('/^[=+\-@\t\r]/', $nilai) ? "'".$nilai : $nilai;
    }
}
