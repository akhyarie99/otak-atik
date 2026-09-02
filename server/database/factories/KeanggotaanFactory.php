<?php

namespace Database\Factories;

use App\Enums\Peran;
use App\Models\Keanggotaan;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keanggotaan>
 */
class KeanggotaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sekolah_id' => Sekolah::factory(),
            'peran' => Peran::Guru,
            'aktif' => true,
        ];
    }
}
