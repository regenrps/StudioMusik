<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Ruangan>
 */
class RuanganFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_ruangan' => 'Studio '.fake()->unique()->word(),
            'deskripsi' => fake()->paragraph(),
            'kapasitas' => 10,
            'fasilitas' => "Drum set\nAmpli gitar\nAmpli bass\nKeyboard\nAC",
            'lokasi' => 'Gedung D Lantai 3',
            'is_active' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
