<?php

namespace App\Console\Commands;

use App\Enums\StatusPeminjaman;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Support\Berkas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Minimisasi data pribadi: foto KTM/KTP jaminan hanya diperlukan selama
 * peminjaman berjalan. Setelah peminjaman berakhir lebih dari N hari,
 * berkasnya dihapus permanen. Foto kondisi ruangan tetap disimpan.
 */
class BersihkanJaminan extends Command
{
    protected $signature = 'studio:bersihkan-jaminan {--hari= : Timpa STUDIO_RETENSI_JAMINAN_HARI}';

    protected $description = 'Hapus foto KTM/KTP jaminan dari peminjaman yang sudah berakhir lebih dari N hari.';

    public function handle(): int
    {
        $hari = (int) ($this->option('hari') ?? config('studio.retensi_jaminan_hari'));
        if ($hari <= 0) {
            $this->components->info('Retensi jaminan dinonaktifkan (0 hari) — tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        $batas = today()->subDays($hari)->toDateString();
        $jumlah = 0;

        Peminjaman::query()
            ->whereNotNull('img_jaminan')
            ->whereNotIn('status', StatusPeminjaman::blockingValues())
            ->whereDate('tgl_pinjam', '<', $batas)
            ->lazyById(200, 'id_pesanan_jadwal_studio')
            ->each(function (Peminjaman $p) use (&$jumlah) {
                Berkas::hapus($p->img_jaminan);
                $p->forceFill(['img_jaminan' => null])->saveQuietly();
                $jumlah++;
            });

        if ($jumlah > 0) {
            LogAktivitas::catat(null, 'retensi_jaminan', "Otomatis: menghapus {$jumlah} foto KTM/KTP jaminan (lebih dari {$hari} hari)", [], null);
            Log::channel('keamanan')->info('retensi_jaminan', ['jumlah' => $jumlah, 'hari' => $hari]);
        }

        $this->components->info("Selesai: {$jumlah} foto jaminan dihapus.");

        return self::SUCCESS;
    }
}
