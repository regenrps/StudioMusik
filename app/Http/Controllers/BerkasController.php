<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\PesananJasaInformasi;
use App\Support\Berkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan berkas privat setelah memeriksa hak akses.
 * Gambar ditampilkan inline; berkas lain diunduh.
 */
class BerkasController extends Controller
{
    public function peminjaman(Peminjaman $peminjaman, string $jenis): StreamedResponse
    {
        Gate::authorize('lihatBerkas', $peminjaman);

        $path = match ($jenis) {
            'jaminan' => $peminjaman->img_jaminan,
            'awal' => $peminjaman->img_kondisi_awal,
            'akhir' => $peminjaman->img_kondisi_akhir,
        };

        return $this->sajikan($path, $peminjaman->kode.'-'.$jenis);
    }

    public function lampiranJasa(Request $request, PesananJasa $pesanan, PesananJasaInformasi $informasi): StreamedResponse
    {
        Gate::authorize('lihatBerkas', $pesanan);
        abort_unless($informasi->pesanan_jasa_musik_id === $pesanan->getKey() && $informasi->isBerkas(), 404);

        return $this->sajikan($informasi->value_field, $informasi->nama_asli ?: basename($informasi->value_field), unduh: true);
    }

    public function hasilJasa(PesananJasa $pesanan): StreamedResponse
    {
        Gate::authorize('lihatBerkas', $pesanan);

        $ext = pathinfo((string) $pesanan->hasil_berkas, PATHINFO_EXTENSION);

        return $this->sajikan($pesanan->hasil_berkas, 'Hasil-'.$pesanan->kode.($ext ? '.'.$ext : ''), unduh: true);
    }

    public function laporan(Laporan $laporan, int $indeks): StreamedResponse
    {
        Gate::authorize('staf');

        return $this->sajikan($laporan->daftarGambar()[$indeks] ?? null, 'laporan-'.$laporan->getKey().'-'.($indeks + 1));
    }

    private function sajikan(?string $path, string $nama, bool $unduh = false): StreamedResponse
    {
        $disk = Storage::disk(Berkas::PRIVAT);
        abort_if(blank($path) || ! $disk->exists($path), 404, 'Berkas tidak ditemukan.');

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        if (! str_contains($nama, '.') && ($ext = pathinfo($path, PATHINFO_EXTENSION))) {
            $nama .= '.'.$ext;
        }

        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return ! $unduh && str_starts_with($mime, 'image/')
            ? $disk->response($path, $nama, $headers)
            : $disk->download($path, $nama, $headers);
    }
}
