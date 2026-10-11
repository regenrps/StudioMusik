<?php

namespace App\Console\Commands;

use App\Services\PeminjamanService;
use Illuminate\Console\Command;

class SinkronStatusPeminjaman extends Command
{
    protected $signature = 'studio:sinkron-status';

    protected $description = 'Kedaluwarsakan pengajuan yang lewat, tandai tidak hadir, dan kirim pengingat jadwal.';

    public function handle(PeminjamanService $service): int
    {
        $hasil = $service->sinkronOtomatis();

        $this->components->info(sprintf(
            'Selesai: %d kedaluwarsa, %d tidak hadir, %d pengingat terkirim.',
            $hasil['kedaluwarsa'], $hasil['tidak_hadir'], $hasil['pengingat'],
        ));

        return self::SUCCESS;
    }
}
