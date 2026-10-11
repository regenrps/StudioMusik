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
                fputcsv($out, array_map(fn ($v) => is_string($v) ? str_replace(["\r\n", "\r"], "\n", $v) : $v, $row), ';');
            }
            fclose($out);
        }, $namaBerkas, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
