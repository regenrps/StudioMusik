<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\PesananJasaInformasi;
use App\Support\Berkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        return $this->sajikan($path, $peminjaman->kode.'-'.$jenis, subjek: 'peminjaman:'.$peminjaman->kode.':'.$jenis, pemilik: $peminjaman->id_user);
    }

    public function lampiranJasa(Request $request, PesananJasa $pesanan, PesananJasaInformasi $informasi): StreamedResponse
    {
        Gate::authorize('lihatBerkas', $pesanan);
        abort_unless($informasi->pesanan_jasa_musik_id === $pesanan->getKey() && $informasi->isBerkas(), 404);

        return $this->sajikan($informasi->value_field, $informasi->nama_asli ?: basename($informasi->value_field), unduh: true,
            subjek: 'jasa:'.$pesanan->kode.':lampiran:'.$informasi->id, pemilik: $pesanan->id_user);
    }

    public function hasilJasa(PesananJasa $pesanan): StreamedResponse
    {
        Gate::authorize('lihatBerkas', $pesanan);

        $ext = pathinfo((string) $pesanan->hasil_berkas, PATHINFO_EXTENSION);

        return $this->sajikan($pesanan->hasil_berkas, 'Hasil-'.$pesanan->kode.($ext ? '.'.$ext : ''), unduh: true,
            subjek: 'jasa:'.$pesanan->kode.':hasil', pemilik: $pesanan->id_user);
    }

    public function laporan(Laporan $laporan, int $indeks): StreamedResponse
    {
        Gate::authorize('staf');

        return $this->sajikan($laporan->daftarGambar()[$indeks] ?? null, 'laporan-'.$laporan->getKey().'-'.($indeks + 1),
            subjek: 'laporan:'.$laporan->getKey().':'.$indeks);
    }

    private function sajikan(?string $path, string $nama, bool $unduh = false, string $subjek = '', ?int $pemilik = null): StreamedResponse
    {
        $disk = Storage::disk(Berkas::PRIVAT);
        abort_if(blank($path) || ! $disk->exists($path), 404, 'Berkas tidak ditemukan.');

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        if (! str_contains($nama, '.') && ($ext = pathinfo($path, PATHINFO_EXTENSION))) {
            $nama .= '.'.$ext;
        }

        // Jejak privasi: siapa membuka berkas pribadi milik siapa.
        $user = request()->user();
        Log::channel('keamanan')->info('akses_berkas_privat', array_filter([
            'berkas' => $subjek,
            'user_id' => $user?->getKey(),
            'peran' => $user?->user_role?->value,
            'pemilik_id' => $pemilik,
            'milik_sendiri' => $pemilik !== null ? (int) $pemilik === (int) $user?->getKey() : null,
            'ip' => request()->ip(),
        ], fn ($v) => $v !== null));

        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            // Cegah berkas dirender sebagai dokumen aktif (HTML/SVG berisi skrip).
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox",
        ];

        $gambarAman = in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);

        return ! $unduh && $gambarAman
            ? $disk->response($path, $nama, $headers)
            : $disk->download($path, $nama, $headers);
    }
}
