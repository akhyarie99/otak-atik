<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Karya;
use App\Models\Kelas;
use App\Models\Keanggotaan;
use App\Models\MisiPercobaan;
use App\Models\Sekolah;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Milestone 4.4 — PRD 6.7: tugas & tenggat, papan progres per siswa &
// per misi, ekspor nilai, LKPD cetak. "Selesai bila" resminya butuh
// guru sungguhan yang tidak bisa coding (lihat rencana-build.md) — di
// sini dibuktikan bagian yang BISA diuji otomatis: datanya benar,
// terisolasi per tenant, dan alurnya tidak error.
class RuangGuruTest extends TestCase
{
    use RefreshDatabase;

    private function guruDenganKelas(): array
    {
        $sekolah = Sekolah::factory()->create();
        $guru = User::factory()->create();
        $keanggotaanGuru = Keanggotaan::create([
            'user_id' => $guru->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Guru, 'aktif' => true,
        ]);
        $kelas = Kelas::withoutGlobalScopes()->create([
            'tenant_id' => $sekolah->id, 'nama' => '5A', 'tahun_ajaran' => '2026/2027',
            'kode_kelas' => strtoupper(fake()->unique()->bothify('??????')),
        ]);

        return [$sekolah, $guru, $keanggotaanGuru, $kelas];
    }

    private function siswaDiKelas(Sekolah $sekolah, Kelas $kelas, string $nama): Keanggotaan
    {
        $user = User::factory()->create(['nama_panggilan' => $nama]);
        $keanggotaan = Keanggotaan::create([
            'user_id' => $user->id, 'sekolah_id' => $sekolah->id, 'peran' => Peran::Siswa, 'aktif' => true,
        ]);
        $kelas->anggota()->attach($keanggotaan->id);

        return $keanggotaan;
    }

    public function test_guru_bisa_memberi_tugas_dan_melihatnya_di_daftar(): void
    {
        [, $guru, $keanggotaan, $kelas] = $this->guruDenganKelas();
        $this->actingAs($guru)->withSession(['keanggotaan_aktif_id' => $keanggotaan->id]);

        $resp = $this->post('/tugas', [
            'kelas_id' => $kelas->id,
            'misi_id' => 'tk2-05-segitiga',
            'tenggat' => now()->addWeek()->toDateTimeString(),
        ]);

        $resp->assertRedirect(route('tugas.index'));
        $this->assertDatabaseHas('tugas', [
            'kelas_id' => $kelas->id,
            'misi_id' => 'tk2-05-segitiga',
            'diberikan_oleh' => $keanggotaan->id,
        ]);

        $index = $this->get('/tugas');
        $index->assertOk();
    }

    public function test_papan_progres_menghitung_percobaan_ke_berapa_siswa_berhasil(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->guruDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Ani');

        // Ani gagal 2 kali, baru lulus di percobaan ke-3 — persis kasus
        // yang menurut PRD 6.4 "lebih berguna daripada nilai akhir".
        MisiPercobaan::create(['tenant_id' => $sekolah->id, 'keanggotaan_id' => $siswa->id, 'misi_id' => 'tk2-05-segitiga', 'lulus' => false]);
        MisiPercobaan::create(['tenant_id' => $sekolah->id, 'keanggotaan_id' => $siswa->id, 'misi_id' => 'tk2-05-segitiga', 'lulus' => false]);
        MisiPercobaan::create(['tenant_id' => $sekolah->id, 'keanggotaan_id' => $siswa->id, 'misi_id' => 'tk2-05-segitiga', 'lulus' => true]);

        $this->actingAs($guru)->withSession(['keanggotaan_aktif_id' => $keanggotaanGuru->id]);
        $resp = $this->get("/kelas/{$kelas->id}/progres");

        $resp->assertOk();
        $props = $resp->viewData('page')['props'];
        $baris = collect($props['siswa'])->firstWhere('nama_panggilan', 'Ani');
        $misi = collect($baris['misi'])->firstWhere('misi_id', 'tk2-05-segitiga');

        $this->assertTrue($misi['lulus']);
        $this->assertSame(3, $misi['percobaan_ke']);
        $this->assertSame(3, $misi['jumlah_percobaan']);
    }

    public function test_misi_yang_belum_pernah_dicoba_tertandai_belum_bukan_error(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->guruDenganKelas();
        $this->siswaDiKelas($sekolah, $kelas, 'Budi');

        $this->actingAs($guru)->withSession(['keanggotaan_aktif_id' => $keanggotaanGuru->id]);
        $resp = $this->get("/kelas/{$kelas->id}/progres");

        $resp->assertOk();
        $props = $resp->viewData('page')['props'];
        $baris = collect($props['siswa'])->firstWhere('nama_panggilan', 'Budi');
        foreach ($baris['misi'] as $m) {
            $this->assertFalse($m['lulus']);
            $this->assertSame(0, $m['jumlah_percobaan']);
            $this->assertNull($m['percobaan_ke']);
        }
    }

    public function test_ekspor_csv_berisi_status_setiap_siswa(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->guruDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Citra');
        MisiPercobaan::create(['tenant_id' => $sekolah->id, 'keanggotaan_id' => $siswa->id, 'misi_id' => 'tk2-01-maju', 'lulus' => true]);

        $this->actingAs($guru)->withSession(['keanggotaan_aktif_id' => $keanggotaanGuru->id]);
        $resp = $this->get("/kelas/{$kelas->id}/progres/csv");

        $resp->assertOk();
        $resp->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Citra', $resp->getContent());
        $this->assertStringContainsString('Lulus (percobaan ke-1)', $resp->getContent());
    }

    public function test_guru_sekolah_lain_tidak_bisa_melihat_progres_kelas_yang_bukan_miliknya(): void
    {
        [$sekolahA, , , $kelasA] = $this->guruDenganKelas();
        [, $guruB, $keanggotaanB] = $this->guruDenganKelas();

        $this->actingAs($guruB)->withSession(['keanggotaan_aktif_id' => $keanggotaanB->id]);
        $resp = $this->get("/kelas/{$kelasA->id}/progres");

        // Kelas A tidak kelihatan dari tenant B (TenantScope + route
        // model binding — pola yang sama seperti aturan tetap #5).
        $resp->assertNotFound();
    }

    public function test_lkpd_bisa_dibuka_tanpa_login_dan_berisi_tujuan_pembelajaran(): void
    {
        $resp = $this->get('/lkpd/tk2-05-segitiga');

        $resp->assertOk();
        $resp->assertSee('Gambar Segitiga');
        $resp->assertSee('Tujuan pembelajaran');
    }

    public function test_editor_mencatat_percobaan_misi_lewat_token_dan_masuk_papan_progres(): void
    {
        [$sekolah, $guru, $keanggotaanGuru, $kelas] = $this->guruDenganKelas();
        $siswa = $this->siswaDiKelas($sekolah, $kelas, 'Dedi');
        $token = $siswa->user->createToken("karya-sync:keanggotaan:{$siswa->id}")->plainTextToken;

        $resp = $this->postJson('/api/misi/percobaan', [
            'misi_id' => 'tk2-01-maju',
            'lulus' => true,
        ], ['Authorization' => "Bearer {$token}"]);
        $resp->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->actingAs($guru)->withSession(['keanggotaan_aktif_id' => $keanggotaanGuru->id]);
        $progres = $this->get("/kelas/{$kelas->id}/progres");
        $baris = collect($progres->viewData('page')['props']['siswa'])->firstWhere('nama_panggilan', 'Dedi');
        $misi = collect($baris['misi'])->firstWhere('misi_id', 'tk2-01-maju');

        $this->assertTrue($misi['lulus']);
    }
}
