<?php

namespace Database\Factories;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Peminjaman>
 */
class PeminjamanFactory extends Factory
{
    protected $model = Peminjaman::class;

    public function definition(): array
    {
        return [
            'kode' => 'STD-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'id_user' => User::factory(),
            'id_ruangan' => Ruangan::factory(),
            'status' => StatusPeminjaman::Menunggu,
            'tgl_pinjam' => today()->addDay()->toDateString(),
            'waktu_mulai' => '17:00:00',
            'waktu_selesai' => '19:00:00',
            'jumlah_orang' => 5,
            'ket_keperluan' => 'Latihan band untuk acara kampus',
            'img_jaminan' => 'jaminan/contoh.jpg',
        ];
    }

    public function pada(string $tanggal, string $mulai, string $selesai): static
    {
        return $this->state(fn () => ['tgl_pinjam' => $tanggal, 'waktu_mulai' => $mulai.':00', 'waktu_selesai' => $selesai.':00']);
    }

    public function status(StatusPeminjaman $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
