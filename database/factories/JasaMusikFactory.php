<?php

namespace Database\Factories;

use App\Models\JasaMusik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JasaMusik>
 */
class JasaMusikFactory extends Factory
{
    protected $model = JasaMusik::class;

    public function definition(): array
    {
        return [
            'nama_jenis_jasa' => fake()->unique()->randomElement(['Jingle', 'Aransemen', 'Minus One', 'Mixing', 'Mastering', 'Scoring']),
            'ringkasan' => fake()->sentence(),
            'deskripsi' => fake()->paragraph(),
            'sk' => "1. Data harus jelas.\n2. Revisi maksimal dua kali.",
            'estimasi_hari' => 7,
            'is_active' => true,
        ];
    }

    /** Tambahkan field formulir standar: Genre (teks wajib) dan Referensi (berkas opsional). */
    public function denganField(): static
    {
        return $this->afterCreating(function (JasaMusik $jasa) {
            $jasa->field()->createMany([
                ['nama_field' => 'Genre', 'jenis_field' => 'text', 'is_required' => true, 'urutan' => 1],
                ['nama_field' => 'Referensi', 'jenis_field' => 'file', 'is_required' => false, 'urutan' => 2],
            ]);
        });
    }
}
