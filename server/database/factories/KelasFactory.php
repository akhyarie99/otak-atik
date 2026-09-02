<?php

namespace Database\Factories;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // tenant_id sengaja TIDAK diisi di sini — biar terisi otomatis
        // dari TenantContext aktif lewat BelongsToTenant (itu justru
        // yang mau dibuktikan uji tenant scope). Timpa manual kalau
        // sebuah test butuh tenant_id tertentu di luar konteks aktif.
        return [
            'nama' => $this->faker->randomElement(['4A', '4B', '5A', '5B', '6A']),
            'tahun_ajaran' => '2026/2027',
        ];
    }
}
