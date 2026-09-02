<?php

namespace Tests\Feature\Tenant;

use App\Models\Kelas;
use App\Models\Scopes\TenantScope;
use App\Models\Sekolah;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Aturan tetap #5: "Setiap kueri yang menyentuh data sekolah wajib
// melewati scope tenant, dengan uji otomatis yang membuktikan akses
// lintas tenant selalu kosong — termasuk lewat ID langsung." Ini uji itu.
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_kueri_daftar_tidak_pernah_menampilkan_data_sekolah_lain(): void
    {
        $sekolahA = Sekolah::factory()->create();
        $sekolahB = Sekolah::factory()->create();

        $context = app(TenantContext::class);

        $context->aktifkan($sekolahB->id);
        Kelas::factory()->count(3)->create();

        $context->aktifkan($sekolahA->id);
        Kelas::factory()->count(2)->create();

        $this->assertCount(2, Kelas::all());
        $this->assertCount(2, Kelas::query()->get());
    }

    public function test_kelas_sekolah_lain_tidak_bisa_diambil_lewat_id_langsung(): void
    {
        $sekolahA = Sekolah::factory()->create();
        $sekolahB = Sekolah::factory()->create();

        $context = app(TenantContext::class);

        $context->aktifkan($sekolahB->id);
        $kelasB = Kelas::factory()->create();

        // Ganti konteks ke sekolah A — ID kelasB diketahui persis, dicoba
        // diambil langsung. Ini kasus paling berbahaya: penyerang yang
        // menebak atau mengetahui ID tetap harus mentok.
        $context->aktifkan($sekolahA->id);

        $this->assertNull(Kelas::find($kelasB->id));
        $this->assertNull(Kelas::where('id', $kelasB->id)->first());
        $this->assertDatabaseCount('kelas', 1); // baris ADA, cuma tidak boleh kelihatan
    }

    public function test_scope_memang_yang_menyaring_bukan_kebetulan_kosong(): void
    {
        $sekolahA = Sekolah::factory()->create();
        $sekolahB = Sekolah::factory()->create();

        $context = app(TenantContext::class);
        $context->aktifkan($sekolahB->id);
        $kelasB = Kelas::factory()->create();

        $context->aktifkan($sekolahA->id);
        $this->assertNull(Kelas::find($kelasB->id));

        // Buktikan datanya memang ada dan scope-nya yang aktif menahan —
        // bukan tabel kosong karena sebab lain.
        $ditemukan = Kelas::withoutGlobalScope(TenantScope::class)->find($kelasB->id);
        $this->assertNotNull($ditemukan);
        $this->assertSame($sekolahB->id, $ditemukan->tenant_id);
    }

    public function test_sekolah_sendiri_tetap_bisa_melihat_datanya_sendiri(): void
    {
        $sekolah = Sekolah::factory()->create();
        $context = app(TenantContext::class);

        $context->aktifkan($sekolah->id);
        $kelas = Kelas::factory()->create();

        $this->assertNotNull(Kelas::find($kelas->id));
        $this->assertSame($sekolah->id, Kelas::find($kelas->id)->tenant_id);
    }

    public function test_tanpa_tenant_aktif_sama_sekali_gagal_tertutup_bukan_membocorkan_semua(): void
    {
        Sekolah::factory()->create();
        $sekolahB = Sekolah::factory()->create();

        $context = app(TenantContext::class);
        $context->aktifkan($sekolahB->id);
        Kelas::factory()->count(3)->create();

        // Tidak ada tenant aktif (mis. middleware belum jalan, atau lupa
        // dipasang) — HARUS kosong, bukan menampilkan ketiga kelas itu.
        $context->bersihkan();
        $this->assertCount(0, Kelas::all());
        $this->assertDatabaseCount('kelas', 3); // datanya tetap ada, cuma tidak kelihatan
    }

    public function test_tenant_id_otomatis_terisi_dari_konteks_aktif_saat_dibuat(): void
    {
        $sekolah = Sekolah::factory()->create();
        app(TenantContext::class)->aktifkan($sekolah->id);

        $kelas = Kelas::factory()->create();

        $this->assertSame($sekolah->id, $kelas->tenant_id);
    }
}
