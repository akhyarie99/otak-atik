<?php

namespace Database\Factories;

use App\Models\Reaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaksi>
 */
class ReaksiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis' => fake()->randomElement(Reaksi::JENIS_TERSEDIA),
        ];
    }
}
