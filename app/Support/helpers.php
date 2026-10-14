<?php

use App\Services\PengaturanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

if (! function_exists('pengaturan')) {
    /** Ambil nilai pengaturan studio (lihat config/studio.php). */
    function pengaturan(string $kunci, mixed $default = null): mixed
    {
        return app(PengaturanService::class)->get($kunci, $default);
    }
}

if (! function_exists('tanggal')) {
    /** Format tanggal berbahasa Indonesia, mis. "Senin, 12 Okt 2026". */
    function tanggal(Carbon|string|null $tanggal, string $format = 'd M Y'): string
    {
        if (blank($tanggal)) {
            return '—';
        }

        return Carbon::parse($tanggal)->locale('id')->translatedFormat($format);
    }
}

if (! function_exists('tanggal_lengkap')) {
    function tanggal_lengkap(Carbon|string|null $tanggal): string
    {
        return tanggal($tanggal, 'l, d F Y');
    }
}

if (! function_exists('tanggal_waktu')) {
    function tanggal_waktu(Carbon|string|null $waktu): string
    {
        return tanggal($waktu, 'd M Y, H.i');
    }
}

if (! function_exists('jam')) {
    /** "17:00:00" → "17.00" */
    function jam(?string $waktu): string
    {
        return $waktu ? str_replace(':', '.', substr($waktu, 0, 5)) : '—';
    }
}

if (! function_exists('relatif')) {
    function relatif(Carbon|string|null $waktu): string
    {
        return $waktu ? Carbon::parse($waktu)->locale('id')->diffForHumans() : '—';
    }
}

if (! function_exists('durasi_label')) {
    /** 90 → "1 jam 30 menit" */
    function durasi_label(int $menit): string
    {
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return trim(($jam ? $jam.' jam ' : '').($sisa ? $sisa.' menit' : ''));
    }
}

if (! function_exists('rupiah')) {
    function rupiah(int|float|null $angka): string
    {
        return 'Rp '.number_format((float) $angka, 0, ',', '.');
    }
}

if (! function_exists('konten')) {
    /**
     * Render konten panjang (deskripsi, S&K, tutorial) secara aman.
     * Mendukung Markdown; HTML lama dari editor WYSIWYG dibersihkan
     * dengan daftar tag yang diizinkan dan seluruh atribut dibuang.
     */
    function konten(?string $teks): HtmlString
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return new HtmlString('');
        }

        if (preg_match('/<\s*(p|ol|ul|li|strong|em|h[1-6]|br|figure|div)\b/i', $teks)) {
            $bersih = strip_tags($teks, '<p><br><strong><b><em><i><u><ol><ul><li><h2><h3><h4><blockquote>');
            // Buang SEMUA atribut, termasuk bentuk tanpa spasi seperti <p/onclick=...>.
            $bersih = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $bersih);

            return new HtmlString($bersih);
        }

        return new HtmlString(Str::markdown($teks, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]));
    }
}

if (! function_exists('tujuan_aman')) {
    /**
     * Kembalikan $url hanya jika menuju aplikasi ini sendiri (mencegah open
     * redirect, termasuk trik "http://domain-kita.evil.com" dan "//evil.com").
     */
    function tujuan_aman(mixed $url, string $cadangan): string
    {
        if (! is_string($url) || $url === '' || preg_match('/[\x00-\x1F\\\\]/', $url)) {
            return $cadangan;
        }

        // Path relatif aman ("/akun"), tetapi bukan protocol-relative ("//evil.com").
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $bagian = parse_url($url);
        $hostAplikasi = parse_url(url('/'), PHP_URL_HOST);

        $aman = is_array($bagian)
            && in_array(strtolower($bagian['scheme'] ?? ''), ['http', 'https'], true)
            && isset($bagian['host'])
            && strcasecmp($bagian['host'], (string) $hostAplikasi) === 0
            && ! isset($bagian['user']);

        return $aman ? $url : $cadangan;
    }
}

if (! function_exists('wa_link')) {
    function wa_link(?string $nomor, string $pesan = ''): ?string
    {
        $nomor = preg_replace('/\D+/', '', (string) $nomor);
        if ($nomor === '') {
            return null;
        }
        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return 'https://wa.me/'.$nomor.($pesan !== '' ? '?text='.rawurlencode($pesan) : '');
    }
}
