<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pengaturan operasional studio yang dapat diubah admin.
 * Nilai bawaan berasal dari config('studio.default') dan ditimpa
 * oleh baris di tabel `pengaturan` (disimpan sebagai JSON).
 */
class PengaturanService
{
    private const CACHE_KEY = 'studio.pengaturan.v1';

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $tersimpan = [];
        try {
            $tersimpan = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('pengaturan')) {
                    return [];
                }

                return Pengaturan::query()->pluck('nilai', 'kunci')
                    ->map(fn ($v) => json_decode((string) $v, true))
                    ->all();
            });
        } catch (Throwable) {
            // Basis data belum siap (mis. saat instalasi) – gunakan nilai bawaan.
        }

        return $this->memo = array_merge(config('studio.default', []), $tersimpan);
    }

    public function get(string $kunci, mixed $default = null): mixed
    {
        return $this->all()[$kunci] ?? $default;
    }

    /** @param array<string, mixed> $nilai */
    public function set(array $nilai): void
    {
        foreach ($nilai as $kunci => $value) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => json_encode($value)]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->memo = null;
    }

    /** @return array<int, int> */
    public function hariOperasional(): array
    {
        return array_values(array_map('intval', (array) $this->get('hari_operasional', [1, 2, 3, 4, 5])));
    }

    /** @return array<string> Alamat email tambahan penerima notifikasi admin. */
    public function emailNotifikasi(): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $this->get('email_notifikasi', '')))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
