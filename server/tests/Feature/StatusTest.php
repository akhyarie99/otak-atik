<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Milestone 7.3 (PRD 9.6): halaman status terbuka tanpa login.
class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_status_terbuka_tanpa_login_dan_melaporkan_basis_data_baik(): void
    {
        $resp = $this->get('/status');

        $resp->assertOk();
        $resp->assertSee('Semua sistem berjalan normal');
        $resp->assertSee('Terhubung');
    }

    public function test_halaman_status_melaporkan_waktu_cadangan_terakhir_setelah_cadangan_dibuat(): void
    {
        Storage::fake('local');
        Artisan::call('cadangan:jalankan');

        $resp = $this->get('/status');

        $resp->assertOk();
        $resp->assertDontSee('Belum ada cadangan tercatat');
    }
}
