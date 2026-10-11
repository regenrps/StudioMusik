<?php

namespace Database\Factories;

use App\Enums\StatusPesananJasa;
use App\Models\JasaMusik;
use App\Models\PesananJasa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PesananJasa>
 */
class PesananJasaFactory extends Factory
{
    protected $model = PesananJasa::class;

    public function definition(): array
    {
        return [
            'kode' => 'JSM-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'id_user' => User::factory(),
            'id_jasa_musik' => JasaMusik::factory(),
            'status' => StatusPesananJasa::Menunggu,
            'judul' => 'Jingle '.fake()->words(2, true),
            'tenggat_produksi' => today()->addDays(14)->toDateString(),
            'keterangan' => fake()->sentence(),
        ];
    }

    public function status(StatusPesananJasa $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
