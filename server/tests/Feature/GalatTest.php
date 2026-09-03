<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Keanggotaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Milestone 7.3 (PRD 9.6): pemantauan galat ringan — baca ekor
// storage/logs/laravel.log, hanya untuk admin_platform.
class GalatTest extends TestCase
{
    use RefreshDatabase;

    private string $berkasLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->berkasLog = storage_path('logs/laravel.log');
    }

    protected function tearDown(): void
    {
        if (is_file($this->berkasLog)) {
            unlink($this->berkasLog);
        }
        parent::tearDown();
    }

    public function test_bukan_admin_platform_tidak_bisa_membuka_halaman_galat(): void
    {
        $user = User::factory()->create();
        Keanggotaan::factory()->create([
            'user_id' => $user->id, 'peran' => Peran::Guru, 'aktif' => true,
        ]);

        $this->actingAs($user)->get('/admin/galat')->assertNotFound();
    }

    public function test_admin_platform_melihat_entri_error_terbaru_dari_log(): void
    {
        @mkdir(dirname($this->berkasLog), 0777, true);
        file_put_contents($this->berkasLog, implode("\n", [
            '[2026-09-01 08:00:00] local.INFO: pesan info biasa, tidak relevan',
            '[2026-09-02 09:15:30] local.ERROR: Gagal memproses pembayaran {"order_id":"123"}',
            '#0 baris jejak tumpukan lanjutan',
            '[2026-09-03 10:30:00] local.CRITICAL: Basis data tidak terhubung',
            '',
        ]));

        $user = User::factory()->create();
        Keanggotaan::factory()->create([
            'user_id' => $user->id, 'sekolah_id' => null, 'peran' => Peran::AdminPlatform, 'aktif' => true,
        ]);

        $resp = $this->actingAs($user)->get('/admin/galat');

        $resp->assertOk();
        $resp->assertSee('Gagal memproses pembayaran');
        $resp->assertSee('baris jejak tumpukan lanjutan');
        $resp->assertSee('Basis data tidak terhubung');
        $resp->assertDontSee('pesan info biasa');
    }

    public function test_halaman_galat_tidak_error_kalau_belum_ada_berkas_log(): void
    {
        $user = User::factory()->create();
        Keanggotaan::factory()->create([
            'user_id' => $user->id, 'sekolah_id' => null, 'peran' => Peran::AdminPlatform, 'aktif' => true,
        ]);

        $resp = $this->actingAs($user)->get('/admin/galat');

        $resp->assertOk();
        $resp->assertSee('Tidak ada entri galat tercatat');
    }
}
