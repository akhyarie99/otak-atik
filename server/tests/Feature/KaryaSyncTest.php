<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\KaryaVersi;
use App\Models\Keanggotaan;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Milestone 4.3 — PRD 6.5: "Sinkron ke server saat ada koneksi. Konflik
// diselesaikan dengan tulisan terakhir menang, dengan riwayat versi
// yang bisa dikembalikan." "Selesai bila": tab ditutup paksa saat anak
// bekerja, karya utuh saat dibuka lagi di perangkat lain — separuh
// "perangkat lain"-nya dibuktikan di sini (API sungguhan, token
// sungguhan); separuh "autosave lokal" dibuktikan lewat Playwright di
// browser (IndexedDB tidak ada di lingkungan uji PHP).
class KaryaSyncTest extends TestCase
{
    use RefreshDatabase;

    private function siswaDenganToken(): array
    {
        $sekolah = Sekolah::factory()->create();
        $guru = User::factory()->create();
        $keanggotaan = Keanggotaan::create([
            'user_id' => $guru->id,
            'sekolah_id' => $sekolah->id,
            'peran' => Peran::Siswa,
            'aktif' => true,
        ]);
        $token = $guru->createToken("karya-sync:keanggotaan:{$keanggotaan->id}")->plainTextToken;

        return [$sekolah, $keanggotaan, $token];
    }

    private function header(string $token): array
    {
        // Guard Sanctum meng-cache user yang teresolusi PER INSTANCE
        // guard, bukan per permintaan — dalam satu metode tes yang
        // memanggil beberapa token berbeda, tanpa ini permintaan kedua
        // diam-diam memakai user dari permintaan pertama (artefak
        // pengujian Laravel, bukan perilaku permintaan HTTP sungguhan
        // yang masing-masing mulai dari proses PHP baru).
        $this->app['auth']->forgetGuards();

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_simpan_pertama_kali_membuat_karya_dan_satu_versi_riwayat(): void
    {
        [, $keanggotaan, $token] = $this->siswaDenganToken();

        $resp = $this->putJson('/api/karya/mutakhir', [
            'project' => ['blockly' => ['blocks' => []], 'program' => []],
            'client_updated_at' => now()->toIso8601String(),
        ], $this->header($token));

        $resp->assertOk();
        $this->assertDatabaseCount('karya', 1);
        $this->assertDatabaseCount('karya_versi', 1);
        $this->assertSame($keanggotaan->id, Karya::first()->keanggotaan_id);
    }

    public function test_tulisan_lebih_baru_menang_dan_tulisan_lebih_lama_tidak_menimpa(): void
    {
        [, , $token] = $this->siswaDenganToken();
        $h = $this->header($token);

        $lama = now()->subMinutes(10);
        $baru = now();

        // Simpan versi BARU dulu...
        $this->putJson('/api/karya/mutakhir', [
            'project' => ['isi' => 'versi-baru'],
            'client_updated_at' => $baru->toIso8601String(),
        ], $h)->assertOk();

        // ...lalu versi LAMA menyusul (mis. perangkat lain yang baru
        // dapat koneksi setelah lama offline). Tidak boleh menimpa.
        $resp = $this->putJson('/api/karya/mutakhir', [
            'project' => ['isi' => 'versi-lama'],
            'client_updated_at' => $lama->toIso8601String(),
        ], $h);

        $resp->assertOk();
        $this->assertSame('versi-baru', $resp->json('project.isi')); // server membalas keadaan yang MENANG

        $karya = Karya::first();
        $this->assertSame('versi-baru', $karya->project_json['isi']);

        // Tapi versi lama TETAP masuk riwayat, tidak hilang begitu saja.
        $this->assertDatabaseCount('karya_versi', 2);
        $isiRiwayat = KaryaVersi::pluck('project_json')->map(fn ($p) => $p['isi'])->all();
        $this->assertContains('versi-lama', $isiRiwayat);
        $this->assertContains('versi-baru', $isiRiwayat);
    }

    public function test_riwayat_versi_bisa_dilihat_dan_dipulihkan(): void
    {
        [, , $token] = $this->siswaDenganToken();
        $h = $this->header($token);

        $this->putJson('/api/karya/mutakhir', [
            'project' => ['isi' => 'v1'],
            'client_updated_at' => now()->subMinutes(5)->toIso8601String(),
        ], $h);
        $this->putJson('/api/karya/mutakhir', [
            'project' => ['isi' => 'v2'],
            'client_updated_at' => now()->toIso8601String(),
        ], $h);

        $versi = $this->getJson('/api/karya/mutakhir/versi', $h);
        $versi->assertOk();
        $this->assertCount(2, $versi->json());

        $idVersiPertama = KaryaVersi::where('project_json->isi', 'v1')->first()->id;
        $pulih = $this->postJson("/api/karya/mutakhir/versi/{$idVersiPertama}/pulihkan", [], $h);

        $pulih->assertOk();
        $this->assertSame('v1', $pulih->json('project.isi'));
        $this->assertSame('v1', Karya::first()->project_json['isi']);
        // Memulihkan menambah riwayat baru, bukan menghapus yang lama.
        $this->assertDatabaseCount('karya_versi', 3);
    }

    public function test_siswa_sekolah_lain_tidak_bisa_memulihkan_versi_karya_yang_bukan_miliknya(): void
    {
        [, , $tokenA] = $this->siswaDenganToken();
        [, , $tokenB] = $this->siswaDenganToken();

        $this->putJson('/api/karya/mutakhir', [
            'project' => ['isi' => 'milik-a'],
            'client_updated_at' => now()->toIso8601String(),
        ], $this->header($tokenA));

        $versiA = KaryaVersi::first();

        // Siswa B (sekolah lain) mencoba memulihkan versi milik siswa A
        // lewat ID langsung — persis pola uji aturan tetap #5, kali ini
        // lewat rute API/token, bukan sesi.
        $resp = $this->postJson("/api/karya/mutakhir/versi/{$versiA->id}/pulihkan", [], $this->header($tokenB));

        $resp->assertNotFound();
    }

    public function test_tanpa_token_yang_valid_ditolak(): void
    {
        $this->getJson('/api/karya/mutakhir')->assertUnauthorized();
    }

    public function test_membaca_karya_yang_belum_pernah_disimpan_mengembalikan_204_bukan_galat(): void
    {
        [, , $token] = $this->siswaDenganToken();

        $resp = $this->getJson('/api/karya/mutakhir', $this->header($token));

        // 204 (bukan 200 dengan body null) — Laravel/Symfony merender
        // response()->json(null) sebagai "{}", yang truthy di JavaScript;
        // 204 tidak punya body sama sekali, tidak ambigu.
        $resp->assertNoContent();
    }
}
